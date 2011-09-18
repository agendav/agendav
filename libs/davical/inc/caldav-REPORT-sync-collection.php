<?php
/**
* CalDAV Server - handle sync-collection report (draft-daboo-webdav-sync-01)
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

$responses = array();

/**
 * Build the array of properties to include in the report output
 */
$sync_tokens = $xmltree->GetPath('/DAV::sync-collection/DAV::sync-token');
$sync_token = $sync_tokens[0]->GetContent();
if ( !isset($sync_token) ) $sync_token = 0;
$sync_token = intval($sync_token);
dbg_error_log( 'sync', " sync-token: %s", $sync_token );


$props = $xmltree->GetElements('DAV::prop');
$v = $props[0];
$props = $v->GetContent();
$proplist = array();
foreach( $props AS $k => $v ) {
  $proplist[] = $v->GetTag();
}

function display_status( $status_code ) {
  return sprintf( 'HTTP/1.1 %03d %s', intval($status_code), getStatusMessage($status_code) );
}

$collection = new DAVResource( $request->path );
if ( !$collection->Exists() ) {
  $request->DoResponse( 404 );
}
  
$params = array( ':collection_id' => $collection->GetProperty('collection_id'), ':sync_token' => $sync_token );
$sql = "SELECT new_sync_token( :sync_token, :collection_id)";
$qry = new AwlQuery($sql, $params);
if ( !$qry->Exec("REPORT",__LINE__,__FILE__) || $qry->rows() <= 0 ) {
  $request->DoResponse( 500, translate("Database error") );
}
$row = $qry->Fetch();

if ( !isset($row->new_sync_token) ) {
  /** If we got a null back then they gave us a sync token we know not of, so provide a full sync */
  $sync_token = 0;
  $params[':sync_token'] = $sync_token;
  if ( !$qry->QDo($sql, $params) || $qry->rows() <= 0 ) {
    $request->DoResponse( 500, translate("Database error") );
  }
  $row = $qry->Fetch();
}
$new_token = $row->new_sync_token;

if ( $sync_token == $new_token ) {
  // No change, so we just re-send the old token.
  $responses[] = new XMLElement( 'sync-token', $new_token );
}
else {
  if ( $sync_token == 0 ) {
    $sql = <<<EOSQL
  SELECT collection.*, calendar_item.*, caldav_data.*, addressbook_resource.*, 201 AS sync_status FROM collection
              LEFT JOIN caldav_data USING (collection_id)
              LEFT JOIN calendar_item USING (dav_id)
                           LEFT JOIN addressbook_resource USING (dav_id)
              WHERE collection.collection_id = :collection_id
     ORDER BY collection.collection_id, caldav_data.dav_id
EOSQL;
    unset($params[':sync_token']);
  }
  else {
    $sql = <<<EOSQL
  SELECT collection.*, calendar_item.*, caldav_data.*, addressbook_resource.*, sync_changes.*
    FROM collection LEFT JOIN sync_changes USING(collection_id)
                           LEFT JOIN caldav_data USING (collection_id,dav_id)
                           LEFT JOIN calendar_item USING (collection_id,dav_id)
                           LEFT JOIN addressbook_resource USING (dav_id)
                           WHERE collection.collection_id = :collection_id
         AND sync_time > (SELECT modification_time FROM sync_tokens WHERE sync_token = :sync_token)
     ORDER BY collection.collection_id, sync_changes.dav_name, sync_changes.sync_time
EOSQL;
  }
  $qry = new AwlQuery($sql, $params );
 
  $last_dav_name = '';
  $first_status = 0;
  
  if ( $qry->Exec("REPORT",__LINE__,__FILE__) ) {
    while( $object = $qry->Fetch() ) {
      if ( $object->dav_name == $last_dav_name ) {
        /** The complex case: this is the second or subsequent for this dav_id */
        if ( $object->sync_status == 404 ) {
          array_pop($responses);
          $resultset = array(
            new XMLElement( 'href', ConstructURL($object->dav_name) ),
            new XMLElement( 'status', display_status($object->sync_status) )
          );
          $responses[] = new XMLElement( 'sync-response', $resultset );
          $first_status = 404;
        }
        else if ( $object->sync_status == 201 && $first_status == 404 ) {
          // ... Delete ... Create ... is indicated as a create, but don't forget we started with a delete
          array_pop($responses);
          $dav_resource = new DAVResource($object);
          $resultset = $dav_resource->GetPropStat($proplist,$reply);
          array_unshift($resultset, new XMLElement( 'href', ConstructURL($object->dav_name)));
          $responses[] = new XMLElement( 'response', $resultset );
        }
        /** Else:
         *    the object existed at start and we have multiple modifications,
         *  or,
         *    the object didn't exist at start and we have subsequent modifications,
         *  but:
         *    in either case we simply stick with our existing report.
         */
      }
      else {
        /** The simple case: this is the first one for this dav_id */
        if ( $object->sync_status == 404 ) {
          $resultset = array(
            new XMLElement( 'href', ConstructURL($object->dav_name) ),
            new XMLElement( 'status', display_status($object->sync_status) )
          );
          $first_status = 404;
        }
        else {
          $dav_resource = new DAVResource($object);
          $resultset = $dav_resource->GetPropStat($proplist,$reply);
          array_unshift($resultset, new XMLElement( 'href', ConstructURL($object->dav_name)));
          $first_status = $object->sync_status;
        }
        $responses[] = new XMLElement( 'response', $resultset );
        $last_dav_name  = $object->dav_name;
      }
    }
    $responses[] = new XMLElement( 'sync-token', $new_token );
  }
  else {
    $request->DoResponse( 500, translate("Database error") );
  }
}

$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
