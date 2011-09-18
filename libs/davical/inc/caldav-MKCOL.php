<?php
/**
* CalDAV Server - handle MKCOL and MKCALENDAR method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
dbg_error_log('MKCOL', 'method handler');
require_once('AwlQuery.php');

$request->NeedPrivilege('DAV::bind');
$displayname = $request->path;

// Enforce trailling '/' on collection name
if ( ! preg_match( '#/$#', $request->path ) ) {
  dbg_error_log( 'MKCOL', 'Add trailling "/" to "%s"', $request->path);
  $request->path .= '/';
}

$parent_container = '/';
if ( preg_match( '#^(.*/)([^/]+)(/)?$#', $request->path, $matches ) ) {
  $parent_container = $matches[1];
  $displayname = $matches[2];
}

require_once('DAVResource.php');
$parent = new DAVResource( $parent_container );
if ( $parent->IsSchedulingCollection( 'inbox' ) ) {
  $request->PreconditionFailed(403, 'urn:ietf:params:xml:ns:caldav:no-mkcol-in-inbox' );
}


$request_type = $request->method;
$is_calendar = ($request_type == 'MKCALENDAR');
$is_addressbook = false;

$resourcetypes = '<DAV::collection/>';
if ($is_calendar) $resourcetypes .= '<urn:ietf:params:xml:ns:caldav:calendar/>';

require_once('XMLDocument.php');
$reply = new XMLDocument(array( 'DAV:' => '', 'urn:ietf:params:xml:ns:caldav' => 'C' ));

$failure_code = null;

$failure = array();
$dav_properties = array();

if ( isset($request->xml_tags) ) {
  /**
  * The MKCOL request may contain XML to set some DAV properties
  */
  $position = 0;
  $xmltree = BuildXMLTree( $request->xml_tags, $position);
  if ( $xmltree->GetTag() == 'DAV::mkcol' ) $request_type = 'extended-mkcol';

  if ( $xmltree->GetTag() != 'urn:ietf:params:xml:ns:caldav:mkcalendar' && $request_type != 'extended-mkcol' ) {
    $request->DoResponse( 406, sprintf('The XML is not a "DAV::mkcol" or "urn:ietf:params:xml:ns:caldav:mkcalendar" document (%s)', $xmltree->GetTag()) );
  }
  $setprops = $xmltree->GetContent();   // <set>
  $setprops = $setprops[0]->GetContent();  // <prop>
  $setprops = $setprops[0]->GetContent();  // the array of properties.

  foreach( $setprops AS $k => $setting ) {
    $tag = $setting->GetTag();
    $content = $setting->RenderContent();

    dbg_error_log( 'MKCOL', 'Processing tag "%s"', $tag);

    switch( $tag ) {

      case 'DAV::resourcetype':
        /** Any value for resourcetype other than 'calendar' is ignored */
        dbg_error_log( 'MKCOL', 'Extended MKCOL with resourcetype specified. "%s"', $content);
        $is_addressbook = count($setting->GetPath('DAV::resourcetype/urn:ietf:params:xml:ns:carddav:addressbook'));
        $is_calendar    = count($setting->GetPath('DAV::resourcetype/urn:ietf:params:xml:ns:caldav:calendar'));
        if ( $is_addressbook && $is_calendar ) {
          $failure['set-'.$tag] = new XMLElement( 'propstat', array(
              new XMLElement( 'prop', new XMLElement($reply->Tag($tag))),
              new XMLElement( 'status', 'HTTP/1.1 409 Conflict' ),
              new XMLElement('responsedescription', translate('Collections may not be both CalDAV calendars and CardDAV addressbooks at the same time') )
          ));
        }
        else {
          $resourcetypes = $setting->GetPath('DAV::resourcetype/*');
          $resourcetypes = str_replace( "\n", "", implode('',$resourcetypes));
          $success[$tag] = 1;
        }
        break;

      case 'DAV::displayname':
        $displayname = $content;
        /**
        * @todo This is definitely a bug in SOHO Organizer and we probably should respond
        * with an error, rather than silently doing what they *seem* to want us to do.
        */
        if ( preg_match( '/^SOHO.Organizer.6\./', $_SERVER['HTTP_USER_AGENT'] ) ) {
          dbg_error_log( 'MKCOL', 'Displayname is "/" to "%s"', $request->path);
          $parent_container = $request->path;
          $request->path .= $content . '/';
        }
        $success[$tag] = 1;
        break;

      case 'urn:ietf:params:xml:ns:caldav:supported-calendar-component-set':  /** Ignored, since we will support all component types */
      case 'urn:ietf:params:xml:ns:caldav:supported-calendar-data':  /** Ignored, since we will support iCalendar 2.0 */
      case 'urn:ietf:params:xml:ns:caldav:calendar-data':  /** Ignored, since we will support iCalendar 2.0 */
      case 'urn:ietf:params:xml:ns:caldav:max-resource-size':  /** Ignored, since we will support arbitrary size */
      case 'urn:ietf:params:xml:ns:caldav:min-date-time':  /** Ignored, since we will support arbitrary time */
      case 'urn:ietf:params:xml:ns:caldav:max-date-time':  /** Ignored, since we will support arbitrary time */
      case 'urn:ietf:params:xml:ns:caldav:max-instances':  /** Ignored, since we will support arbitrary instances */
        $success[$tag] = 1;
        break;

      /**
      * The following properties are read-only, so they will cause the request to fail
      */
      case 'DAV::getetag':
      case 'DAV::getcontentlength':
      case 'DAV::getcontenttype':
      case 'DAV::getlastmodified':
      case 'DAV::creationdate':
      case 'DAV::lockdiscovery':
      case 'DAV::supportedlock':
        $failure['set-'.$tag] = new XMLElement( 'propstat', array(
            new XMLElement( 'prop', new XMLElement($reply->Tag($tag))),
            new XMLElement( 'status', 'HTTP/1.1 409 Conflict' ),
            new XMLElement('responsedescription', translate('Property is read-only') )
        ));
        if ( isset($failure_code) && $failure_code != 409 ) $failure_code = 207;
        else if ( !isset($failure_code) ) $failure_code = 409;
        break;

      /**
      * If we don't have any special processing for the property, we just store it verbatim (which will be an XML fragment).
      */
      default:
        $dav_properties[$tag] = $content;
        $success[$tag] = 1;
        break;
    }
  }

  /**
  * If we have encountered any instances of failure, the whole damn thing fails.
  */
  if ( count($failure) > 0 ) {
    $props = array();
    $status = array();
    foreach( $success AS $tag => $v ) {
      // Unfortunately although these succeeded, we failed overall, so they didn't happen...
      $props[] = new XMLElement($reply->Tag($tag));
    }

    $status[] = new XMLElement( 'propstat', array(
      new XMLElement('prop', $props),
      new XMLElement('status', 'HTTP/1.1 424 Failed Dependency' )
    ));

    if ( $request_type == 'extended-mkcol' ) {
      $request->DoResponse( $failure_code, $reply->Render('mkcol-response', array_merge( $status, $failure ), 'text/xml; charset="utf-8"' ) );
    }
    else {
      array_unshift( $failure, $reply->href( ConstructURL($request->path) ) );
      $failure[] = new XMLElement('responsedescription', translate('Some properties were not able to be set.') );

      $request->DoResponse( 207, $reply->Render('multistatus', new XMLElement( 'response', $failure )), 'text/xml; charset="utf-8"' );
    }

  }
}

$sql = 'SELECT * FROM collection WHERE dav_name = :dav_name';
$qry = new AwlQuery( $sql, array( ':dav_name' => $request->path) );
if ( ! $qry->Exec('MKCOL',__LINE__,__FILE__) ) {
  $request->DoResponse( 500, translate('Error querying database.') );
}
if ( $qry->rows() != 0 ) {
  $request->DoResponse( 405, translate('A collection already exists at that location.') );
}

$qry = new AwlQuery();
$qry->Begin();

if ( ! $qry->QDo( 'INSERT INTO collection ( user_no, parent_container, dav_name, dav_etag, dav_displayname,
                                 is_calendar, is_addressbook, resourcetypes, created, modified )
              VALUES( :user_no, :parent_container, :dav_name, :dav_etag, :dav_displayname,
                      :is_calendar, :is_addressbook, :resourcetypes, current_timestamp, current_timestamp )',
           array(
              ':user_no'          => $request->user_no,
              ':parent_container' => $parent_container,
              ':dav_name'         => $request->path,
              ':dav_etag'         => md5($request->user_no. $request->path),
              ':dav_displayname'  => $displayname,
              ':is_calendar'      => ($is_calendar ? 't' : 'f'),
              ':is_addressbook'   => ($is_addressbook ? 't' : 'f'),
              ':resourcetypes'    => $resourcetypes
           ) ) ) {
  $request->DoResponse( 500, translate('Error writing calendar details to database.') );
}
foreach( $dav_properties AS $k => $v ) {
  if ( ! $qry->QDo('SELECT set_dav_property( :dav_name, :user_no, :tag::text, :value::text )',
             array( ':dav_name' => $request->path, ':user_no' => $request->user_no, ':tag' => $k, ':value' => $v) ) ) {
    $request->DoResponse( 500, translate('Error writing calendar properties to database.') );
  }
}
if ( !$qry->Commit() ) {
  $request->DoResponse( 500, translate('Error writing calendar details to database.') );
}
dbg_error_log( 'MKCOL', 'New calendar "%s" created named "%s" for user "%d" in parent "%s"', $request->path, $displayname, $session->user_no, $parent_container);
header('Cache-Control: no-cache'); /** RFC4791 mandates this at 5.3.1 */
$request->DoResponse( 201, '' );

/**
* @todo We could also respond to the request...
*
*   <?xml version="1.0" encoding="utf-8" ?>
*   <C:mkcalendar xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
*     <D:set>
*       <D:prop>
*         <D:displayname>Lisa's Events</D:displayname>
*         <C:calendar-description xml:lang="en">Calendar restricted to events.</C:calendar-description>
*         <C:supported-calendar-component-set>
*           <C:comp name="VEVENT"/>
*         </C:supported-calendar-component-set>
*         <C:calendar-timezone><![CDATA[BEGIN:VCALENDAR
*   PRODID:-//Example Corp.//CalDAV Client//EN
*   VERSION:2.0
*   BEGIN:VTIMEZONE
*   TZID:US-Eastern
*   LAST-MODIFIED:19870101T000000Z
*   BEGIN:STANDARD
*   DTSTART:19671029T020000
*   RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
*   TZOFFSETFROM:-0400
*   TZOFFSETTO:-0500
*   TZNAME:Eastern Standard Time (US & Canada)
*   END:STANDARD
*   BEGIN:DAYLIGHT
*   DTSTART:19870405T020000
*   RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4
*   TZOFFSETFROM:-0500
*   TZOFFSETTO:-0400
*   TZNAME:Eastern Daylight Time (US & Canada)
*   END:DAYLIGHT
*   END:VTIMEZONE
*   END:VCALENDAR
*   ]]></C:calendar-timezone>
*       </D:prop>
*     </D:set>
*   </C:mkcalendar>
*
*/

