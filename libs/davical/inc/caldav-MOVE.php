<?php
/**
* CalDAV Server - handle MOVE method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
dbg_error_log("MOVE", "method handler");

require_once('DAVResource.php');

$request->NeedPrivilege('DAV::unbind');

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || (isset($c->dbg['move']) && $c->dbg['move'])) ) {
  $fh = fopen('/tmp/MOVE.txt','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}

$lock_opener = $request->FailIfLocked();

$dest = new DAVResource($request->destination);

if ( $dest->dav_name() == '/' || $dest->IsPrincipal() ) {
  $dest->NeedPrivilege('DAV::bind');
}

if ( ! $dest->ContainerExists() ) {
  $request->DoResponse( 409, translate('Destination collection does not exist') );
}

if ( ! $request->overwrite && $dest->Exists() ) {
  $request->DoResponse( 412, translate('Not overwriting existing destination resource') );
}

if ( isset($request->etag_none_match) && $request->etag_none_match != '*' ) {
  $request->DoResponse( 412 );  /** request to move, but only if there is no source? WTF! */
}

$src  = new DAVResource($request->path);
if ( ! $src->Exists() ) {
  $request->DoResponse( 412, translate('Source resource does not exist.') );
}

if ( $src->IsCollection() ) {
  switch( $dest->ContainerType() ) {
    case 'calendar':
    case 'addressbook':
    case 'schedule-inbox':
    case 'schedule-outbox':
      $request->DoResponse( 412, translate('Special collections may not contain a calendar or other special collection.') );
  };
}
else {
  if ( (isset($request->etag_if_match) && $request->etag_if_match != '' )
        || ( isset($request->etag_none_match) && $request->etag_none_match != '') ) {

    /**
    * RFC2068, 14.25:
    * If none of the entity tags match, or if "*" is given and no current
    * entity exists, the server MUST NOT perform the requested method, and
    * MUST return a 412 (Precondition Failed) response.
    *
    * RFC2068, 14.26:
    * If any of the entity tags match the entity tag of the entity that
    * would have been returned in the response to a similar GET request
    * (without the If-None-Match header) on that resource, or if "*" is
    * given and any current entity exists for that resource, then the
    * server MUST NOT perform the requested method.
    */
    $error = '';
    if ( isset($request->etag_if_match) && $request->etag_if_match != $src->unique_tag() ) {
      $error = translate( 'Existing resource does not match "If-Match" header - not accepted.');
    }
    else if ( isset($request->etag_none_match) && $request->etag_none_match != '' && $request->etag_none_match == $src->unique_tag() ) {
      $error = translate( 'Existing resource matches "If-None-Match" header - not accepted.');
    }
    if ( $error != '' ) $request->DoResponse( 412, $error );
  }
}

$src->NeedPrivilege('DAV::unbind');
$dest->NeedPrivilege('DAV::write-content');
if ( ! $dest->Exists() ) $dest->NeedPrivilege('DAV::bind');


function rollback( $response_code = 412 ) {
  global $request;
  $qry = new AwlQuery('ROLLBACK');
  $qry->Exec('move'); // Just in case
  $request->DoResponse( $response_code );
  // And we don't return from that.
}


$qry = new AwlQuery('BEGIN');
if ( !$qry->Exec('move') ) rollback(500);

$src_name = $src->dav_name();
$dst_name = $dest->dav_name();
$src_collection = $src->GetProperty('collection_id');
$dst_collection = $dest->GetProperty('collection_id');
$src_user_no = $src->GetProperty('user_no');
$dst_user_no = $dest->GetProperty('user_no');


if ( $src->IsCollection()  ) {
  if ( $dest->Exists() ) {
    $qry = new AwlQuery( 'DELETE FROM collection WHERE dav_name = :dst_name', array( ':dst_name' => $dst_name ) );
    if ( !$qry->Exec('move') ) rollback(500);
  }
  /** @TODO: Need to confirm this will work correctly if we move this into another user's hierarchy. */
  $sql = 'UPDATE collection SET dav_name = :dst_name ';
  $params = array(':dst_name' => $dst_name);
  if ( $src_user_no != $dst_user_no ) {
    $sql .= ', user_no = :dst_user_no ';
    $params[':dst_user_no'] = $dst_user_no;
  }
  $sql .= 'WHERE collection_id = :src_collection';
  $params[':src_collection'] = $src_collection;
  $qry = new AwlQuery( $sql, $params );
  if ( !$qry->Exec('move') ) rollback(500);
}
else {
  if ( $dest->Exists() ) {
    $qry = new AwlQuery( 'DELETE FROM caldav_data WHERE dav_name = :dst_name', array( ':dst_name' => $dst_name) );
    if ( !$qry->Exec('move') ) rollback(500);
  }
  $sql = 'UPDATE caldav_data SET dav_name = :dst_name';
  $params = array( ':dst_name' => $dst_name );
  if ( $src_user_no != $dst_user_no ) {
    $sql .= ', user_no = :dst_user_no';
    $params[':dst_user_no'] = $dst_user_no;
  }
  if ( $src_collection != $dst_collection ) {
    $sql .= ', collection_id = :dst_collection';
    $params[':dst_collection'] = $dst_collection;
  }
  $sql .=' WHERE dav_name = :src_name';
  $params[':src_name'] = $src_name;
  $qry = new AwlQuery( $sql, $params );
  if ( !$qry->Exec('move') ) rollback(500);

  $qry = new AwlQuery( 'SELECT write_sync_change( :src_collection, 404, :src_name );', array(
    ':src_name' => $src_name,
    ':src_collection' => $src_collection
  ) );
  if ( !$qry->Exec('move') ) rollback(500);
  if ( function_exists('log_caldav_action') ) {
    log_caldav_action( 'DELETE', $src->GetProperty('uid'), $src_user_no, $src_collection, $src_name );
  }

  $qry = new AwlQuery( 'SELECT write_sync_change( :dst_collection, :sync_type, :dst_name );', array(
    ':dst_name' => $dst_name,
    ':dst_collection' => $dst_collection,
    ':sync_type' => ( $dest->Exists() ? 200 : 201 )
  ) );
  if ( !$qry->Exec('move') ) rollback(500);
  if ( function_exists('log_caldav_action') ) {
    log_caldav_action( ( $dest->Exists() ? 'UPDATE' : 'INSERT' ), $src->GetProperty('uid'), $dst_user_no, $dst_collection, $dst_name );
  }

}

$qry = new AwlQuery('COMMIT');
if ( !$qry->Exec('move') ) rollback(500);

$request->DoResponse( 200 );
