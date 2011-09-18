<?php
/**
* CalDAV Server - handle DELETE method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("delete", "DELETE method handler");

require_once('DAVResource.php');
$dav_resource = new DAVResource($request->path);
$container = $dav_resource->FetchParentContainer();
$container->NeedPrivilege('DAV::unbind');

$lock_opener = $request->FailIfLocked();


function delete_collection( $id ) {
  $params = array( ':collection_id' => $id );
  $qry = new AwlQuery('SELECT child.collection_id AS child_id FROM collection child JOIN collection parent ON (parent.dav_name = child.parent_container) WHERE parent.collection_id = :collection_id', $params );
  if ( $qry->Exec('DELETE',__LINE__,__FILE__) && $qry->rows() > 0 ) {
    while( $row = $qry->Fetch() ) {
      delete_collection($row->child_id);
    }
  }

  if ( $qry->QDo("SELECT write_sync_change(collection_id, 404, caldav_data.dav_name) FROM caldav_data WHERE collection_id = :collection_id", $params )
    && $qry->QDo("DELETE FROM property WHERE dav_name LIKE (SELECT dav_name FROM collection WHERE collection_id = :collection_id) || '%'", $params )
    && $qry->QDo("DELETE FROM locks WHERE dav_name LIKE (SELECT dav_name FROM collection WHERE collection_id = :collection_id) || '%'", $params )
    && $qry->QDo("DELETE FROM caldav_data WHERE collection_id = :collection_id", $params )
    && $qry->QDo("DELETE FROM collection WHERE collection_id = :collection_id", $params ) ) {
    @dbg_error_log( "DELETE", "DELETE (collection): User: %d, ETag: %s, Path: %s", $session->user_no, $request->etag_if_match, $request->path);
    return true;
  }
  return false;
}


if ( !$dav_resource->Exists() )$request->DoResponse( 404 );

if ( ! ( $dav_resource->resource_id() > 0 ) ) {
  $request->DoResponse( 403 );
}

$qry = new AwlQuery();
$qry->Begin();

if ( $dav_resource->IsBinding() ) {
  $params = array( ':dav_name' => $dav_resource->dav_name() );

  if ( $qry->QDo("DELETE FROM dav_binding WHERE dav_name = :dav_name", $params )
    && $qry->Commit() ) {
    @dbg_error_log( "DELETE", "DELETE: Binding: %d, ETag: %s, Path: %s", $session->user_no, $request->etag_if_match, $request->path);
    $request->DoResponse( 204 );
  }
}
else if ( $dav_resource->IsCollection() ) {
  if ( delete_collection( $dav_resource->resource_id() ) && $qry->Commit() ) {
    $request->DoResponse( 204 );
  }
}
else {
  if ( (isset($request->etag_if_match) && $request->etag_if_match != $dav_resource->unique_tag() ) ) {
    $request->DoResponse( 412, translate("Resource has changed on server - not deleted") );
  }

  $params = array( ':dav_id' => $dav_resource->resource_id() );

  if ( $qry->QDo("SELECT write_sync_change(collection_id, 404, caldav_data.dav_name) FROM caldav_data WHERE dav_id = :dav_id", $params )
    && $qry->QDo("DELETE FROM property WHERE dav_name = (SELECT dav_name FROM caldav_data WHERE dav_id = :dav_id)", $params )
    && $qry->QDo("DELETE FROM locks WHERE dav_name = (SELECT dav_name FROM caldav_data WHERE dav_id = :dav_id)", $params )
    && $qry->QDo("DELETE FROM caldav_data WHERE dav_id = :dav_id", $params )
    && $qry->Commit() ) {
    @dbg_error_log( "DELETE", "DELETE: User: %d, ETag: %s, Path: %s", $session->user_no, $request->etag_if_match, $request->path);
    if ( function_exists('log_caldav_action') ) {
      log_caldav_action( 'DELETE', $dav_resource->GetProperty('uid'), $dav_resource->GetProperty('user_no'), $dav_resource->GetProperty('collection_id'), $request->path );
    }
    $request->DoResponse( 204 );
  }
}

$request->DoResponse( 500 );
