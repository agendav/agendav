<?php
/**
* CalDAV Server - handle PROPFIND method
*
* @package   davical
* @subpackage   propfind
* @author    Andrew McMillan <andrew@catalyst.net.nz>
* @copyright Catalyst .Net Ltd, Andrew McMillan
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log('PROPFIND', 'method handler');

$request->NeedPrivilege( array('DAV::read', 'urn:ietf:params:xml:ns:caldav:read-free-busy','DAV::read-current-user-privilege-set') );

require_once('iCalendar.php');
require_once('XMLDocument.php');
require_once('DAVResource.php');

$reply = new XMLDocument( array( 'DAV:' => '' ) );

if ( !isset($request->xml_tags) ) {
  // Empty body indicates DAV::allprop request according to RFC4918
  $property_list = array('DAV::allprop');
}
else {
  $position = 0;
  $xmltree = BuildXMLTree( $request->xml_tags, $position);
  if ( !is_object($xmltree) ) {
    $request->DoResponse( 403, translate("Request body is not valid XML data!") );
  }
  $allprop    = $xmltree->GetPath('/DAV::propfind/*');
  $property_list = array();
  foreach( $allprop AS $k1 => $propwrap ) {
    switch ( $propwrap->GetTag() ) {
      case 'DAV::allprop':
        $property_list[] = 'DAV::allprop';
        break;
      case 'DAV::propname':
        $property_list[] = 'DAV::propname';
        break;
      default:  // prop, include
        $subprop = $propwrap->GetElements();
        foreach( $subprop AS $k => $v ) {
          if ( is_object($v) && method_exists($v,'GetTag') ) $property_list[] = $v->GetTag();
        }
    }
  }
}

/**
 * Add the calendar-proxy-read/write pseudocollections
 * @param responses array of responses to which to add the collections
 */
function add_proxy_response( $which, $parent_path ) {
  global $request, $reply, $c, $session, $property_list;

  if ($parent_path != $request->principal->dav_name()) {
    dbg_error_log( 'PROPFIND', 'Not returning proxy response since "%s" != "%s"', $parent_path, $request->principal->dav_name() );
    return null; // Nothing to proxy for
  }

  $collection = (object) '';
  if ( $which == 'read' ) {
    $proxy_group = $request->principal->ReadProxyGroup();
  } else if ( $which == 'write' ) {
    $proxy_group = $request->principal->WriteProxyGroup();
  }

  dbg_error_log( 'PROPFIND', 'Returning proxy response to "%s" for "%s"', $which, $parent_path );

  $collection->parent_container = $parent_path;
  $collection->dav_name = $parent_path.'calendar-proxy-'.$which.'/';
  $collection->is_calendar    = 'f';
  $collection->is_addressbook = 'f';
  $collection->is_principal   = 't';
  $collection->is_proxy       = 't';
  $collection->proxy_type     = $which;
  $collection->type           = 'proxy';
  $collection->dav_displayname = $collection->dav_name;
  $collection->collection_id = 0;
  $collection->user_no = $session->user_no;
  $collection->username = $session->username;
  $collection->email = $session->email;
  $collection->created = date('Ymd\THis');
  $collection->dav_etag = md5($c->system_name . $collection->dav_name . implode($proxy_group) );
  $collection->proxy_for = $proxy_group;
  $collection->resourcetypes  = sprintf('<DAV::collection/><http://calendarserver.org/ns/:calendar-proxy-%s/>', $which);
  $collection->in_freebusy_set = 'f';
  $collection->schedule_transp = 'transp';
  $collection->timezone        = null;
  $collection->description     = '';

  $resource = new DAVResource($collection);
  $resource->FetchPrincipal();
  return $resource->RenderAsXML($property_list, $reply);

}


/**
* Get XML response for items in the collection
* If '/' is requested, a list of visible users is given, otherwise
* a list of calendars for the user which are parented by this path.
*/
function get_collection_contents( $depth, $collection, $parent_path = null ) {
  global $c, $session, $request, $reply, $property_list;

  $bound_from = $collection->bound_from();
  $bound_to   = $collection->dav_name();
  if ( !isset($parent_path) ) $parent_path = $collection->dav_name();
  dbg_error_log('PROPFIND','Getting collection contents: Depth %d, Path: %s, Bound from: %s, Bound to: %s',
                                                              $depth, $collection->dav_name(), $bound_from, $bound_to );

  $date_format = iCalendar::HttpDateFormat();
  $responses = array();
  if ( ! $collection->IsCalendar() &&  ! $collection->IsAddressbook() ) {
    /**
    * Calendar/Addressbook collections may not contain collections, so we won't look
    */
    $params = array( ':session_principal' => $session->principal_id, ':scan_depth' => $c->permission_scan_depth );
    if ( $bound_from == '/' ) {
      $sql = "SELECT usr.*, '/' || username || '/' AS dav_name, md5(username || updated::text) AS dav_etag, ";
      $sql .= "to_char(joined at time zone 'GMT',$date_format) AS created, ";
      $sql .= "to_char(updated at time zone 'GMT',$date_format) AS modified, ";
      $sql .= 'FALSE AS is_calendar, TRUE AS is_principal, FALSE AS is_addressbook, \'principal\' AS type, ';
      $sql .= 'principal_id AS collection_id, ';
      $sql .= 'principal.* ';
      $sql .= 'FROM usr JOIN principal USING (user_no) ';
      $sql .= "WHERE (pprivs(:session_principal::int8,principal.principal_id,:scan_depth::int) & 1::BIT(24))::INT4::BOOLEAN ";
      $sql .= 'ORDER BY usr.user_no';
    }
    else {
      $qry = new AwlQuery('SELECT * FROM dav_binding WHERE dav_binding.parent_container = :this_dav_name ORDER BY bind_id',
                           array(':this_dav_name' => $bound_from));
      if( $qry->Exec('PROPFIND',__LINE__,__FILE__) && $qry->rows() > 0 ) {
        while( $binding = $qry->Fetch() ) {
          $resource = new DAVResource($binding->dav_name);
          if ( $resource->HavePrivilegeTo('DAV::read', false) ) {
            $resource->set_bind_location( str_replace($bound_from,$bound_to,$binding->dav_name));
            $responses[] = $resource->RenderAsXML($property_list, $reply);
            if ( $depth > 0 ) {
              $responses = array_merge($responses, get_collection_contents( $depth - 1, $resource, $binding->dav_name ) );
            }
          }
        }
      }

      $sql = 'SELECT principal.*, collection.*, \'collection\' AS type ';
      $sql .= 'FROM collection LEFT JOIN principal USING (user_no) ';
      $sql .= 'WHERE parent_container = :this_dav_name ';
      $sql .= "AND (path_privs(:session_principal::int8,collection.dav_name,:scan_depth::int) & 1::BIT(24))::INT4::BOOLEAN ";
      $sql .= ' ORDER BY collection_id';
      $params[':this_dav_name'] = $bound_from;
    }
    $qry = new AwlQuery($sql, $params);

    if( $qry->Exec('PROPFIND',__LINE__,__FILE__) && $qry->rows() > 0 ) {
      while( $subcollection = $qry->Fetch() ) {
        $resource = new DAVResource($subcollection);
        $resource->set_bind_location( str_replace($bound_from,$bound_to,$subcollection->dav_name));
        $responses[] = $resource->RenderAsXML($property_list, $reply);
        if ( $depth > 0 ) {
          $responses = array_merge($responses, get_collection_contents( $depth - 1, $resource,
                                                   str_replace($resource->parent_path(), $parent_path, $resource->dav_name() ) ) );
        }
      }
    }

    if ( $collection->IsPrincipal() ) {
      // Caldav Proxy: 5.1 par. 2: Add child resources calendar-proxy-(read|write)
      dbg_error_log('PROPFIND','Adding calendar-proxy-read and write. Path: %s', $bound_from );
      $response = add_proxy_response('read', $bound_from );
      if ( isset($response) ) $responses[] = $response;
      $response = add_proxy_response('write', $bound_from );
      if ( isset($response) ) $responses[] = $response;
    }
  }

  /**
  * freebusy permission is not allowed to see the items in a collection.  Must have at least read permission.
  */
  if ( $collection->HavePrivilegeTo('DAV::read', false) ) {
    dbg_error_log('PROPFIND','Getting collection items: Depth %d, Path: %s', $depth, $bound_from );
    $privacy_clause = ' ';
    $time_limit_clause = ' ';
    if ( $collection->IsCalendar() ) {
      if ( ! $collection->HavePrivilegeTo('all', false) ) {
        $privacy_clause = " AND (calendar_item.class != 'PRIVATE' OR calendar_item.class IS NULL) ";
      }

      if ( isset($c->hide_older_than) && intval($c->hide_older_than > 0) ) {
        $time_limit_clause = " AND calendar_item.dtstart > (now() - interval '".intval($c->hide_older_than)." days') ";
      }
    }

    $sql = 'SELECT collection.*, principal.*, calendar_item.*, caldav_data.*, ';
    $sql .= "to_char(coalesce(calendar_item.created, caldav_data.created) at time zone 'GMT',$date_format) AS created, ";
    $sql .= "to_char(last_modified at time zone 'GMT',$date_format) AS modified, ";
    $sql .= 'summary AS dav_displayname ';
    $sql .= 'FROM caldav_data LEFT JOIN calendar_item USING( dav_id, user_no, dav_name, collection_id) ';
    $sql .= 'LEFT JOIN collection USING(collection_id,user_no) LEFT JOIN principal USING(user_no) ';
    $sql .= 'WHERE collection.dav_name = :collection_dav_name '.$time_limit_clause.' '.$privacy_clause;
    if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= " ORDER BY caldav_data.dav_id";
    $qry = new AwlQuery( $sql, array( ':collection_dav_name' => $bound_from) );
    if( $qry->Exec('PROPFIND',__LINE__,__FILE__) && $qry->rows() > 0 ) {
      while( $item = $qry->Fetch() ) {
        $resource = new DAVResource($item);
        $resource->set_bind_location( str_replace($bound_from,$bound_to,$item->dav_name));
        $responses[] = $resource->RenderAsXML($property_list, $reply, $parent_path );
      }
    }
  }

  return $responses;
}



/**
* Something that we can handle, at least roughly correctly.
*/
$responses = array();
if ( $request->IsProxyRequest() ) {
  $response = add_proxy_response($request->proxy_type, $request->principal->dav_name() );
  if ( isset($response) ) $responses[] = $response;
}
else {
  $resource = new DAVResource($request->path);
  if ( ! $resource->Exists() ) {
    $request->PreconditionFailed( 404, 'must-exist', translate('That resource is not present on this server.') );
  }
  $resource->NeedPrivilege('DAV::read');
  if ( $resource->IsCollection() ) {
    dbg_error_log('PROPFIND','Getting collection contents: Depth %d, Path: %s', $request->depth, $resource->dav_name() );
    $responses[] = $resource->RenderAsXML($property_list, $reply);
    if ( $request->depth > 0 ) {
      $responses = array_merge($responses, get_collection_contents( $request->depth - 1, $resource ) );
    }
  }
  elseif ( $request->HavePrivilegeTo('DAV::read',false) ) {
    $responses[] = $resource->RenderAsXML($property_list, $reply);
  }
}

$xmldoc = $reply->Render('multistatus', $responses);
$etag = md5($xmldoc);
header('ETag: "'.$etag.'"');
$request->DoResponse( 207, $xmldoc, 'text/xml; charset="utf-8"' );

