<?php
/**
* CalDAV Server - handle GET method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("get", "GET method handler");

require_once("iCalendar.php");
require_once("DAVResource.php");

$dav_resource = new DAVResource($request->path);
$dav_resource->NeedPrivilege( array('urn:ietf:params:xml:ns:caldav:read-free-busy','DAV::read') );

if ( ! $dav_resource->Exists() ) {
  $request->DoResponse( 404, translate("Resource Not Found.") );
}

function obfuscated_event( $icalendar ) {
  // The user is not admin / owner of this calendar looking at his calendar and can not admin the other cal,
  // or maybe they don't have *read* access but they got here, so they must at least have free/busy access
  // so we will present an obfuscated version of the event that just says "Busy" (translated :-)
  $confidential = new iCalComponent();
  $confidential->SetType($icalendar->GetType());
  $confidential->AddProperty( 'SUMMARY', translate('Busy') );
  $confidential->AddProperty( 'CLASS', 'CONFIDENTIAL' );
  $confidential->SetProperties( $icalendar->GetProperties('DTSTART'), 'DTSTART' );
  $confidential->SetProperties( $icalendar->GetProperties('RRULE'), 'RRULE' );
  $confidential->SetProperties( $icalendar->GetProperties('DURATION'), 'DURATION' );
  $confidential->SetProperties( $icalendar->GetProperties('DTEND'), 'DTEND' );
  $confidential->SetProperties( $icalendar->GetProperties('UID'), 'UID' );
  $confidential->SetProperties( $icalendar->GetProperties('CREATED'), 'CREATED' );

  return $confidential;
}


if ( $dav_resource->IsCollection() ) {
  if ( ! $dav_resource->IsCalendar() && !(isset($c->get_includes_subcollections) && $c->get_includes_subcollections) ) {
    /** RFC2616 says we must send an Allow header if we send a 405 */
    header("Allow: PROPFIND,PROPPATCH,OPTIONS,MKCOL,REPORT,DELETE");
    $request->DoResponse( 405, translate("GET requests on collections are only supported for calendars.") );
  }

  /**
  * The CalDAV specification does not define GET on a collection, but typically this is
  * used as a .ics download for the whole collection, which is what we do also.
  */
  $sql = 'SELECT caldav_data, class, caldav_type, calendar_item.user_no, logged_user ';
  $sql .= 'FROM collection INNER JOIN caldav_data USING(collection_id) INNER JOIN calendar_item USING ( dav_id ) WHERE ';
  if ( isset($c->get_includes_subcollections) && $c->get_includes_subcollections ) {
    $sql .= '(collection.dav_name ~ :path_match ';
    $sql .= 'OR collection.collection_id IN (SELECT bound_source_id FROM dav_binding WHERE dav_binding.dav_name ~ :path_match)) ';
    $params = array( ':path_match' => '^'.$request->path );
  }
  else {
    $sql .= 'caldav_data.collection_id = :collection_id ';
    $params = array( ':collection_id' => $dav_resource->resource_id() );
  }
  if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= ' ORDER BY dav_id';

  $qry = new AwlQuery( $sql, $params );
  if ( !$qry->Exec("GET",__LINE__,__FILE__) ) {
    $request->DoResponse( 500, translate("Database Error") );
  }

  /**
  * Here we are constructing a whole calendar response for this collection, including
  * the timezones that are referred to by the events we have selected.
  */
  $vcal = new iCalComponent();
  $vcal->VCalendar();
  $displayname = $dav_resource->GetProperty('displayname');
  if ( isset($displayname) ) {
    $vcal->AddProperty("X-WR-CALNAME", $displayname);
  }

  $need_zones = array();
  $timezones = array();
  while( $event = $qry->Fetch() ) {
    $ical = new iCalComponent( $event->caldav_data );

    /** Save the timezone component(s) into a minimal set for inclusion later */
    $event_zones = $ical->GetComponents('VTIMEZONE',true);
    foreach( $event_zones AS $k => $tz ) {
      $tzid = $tz->GetPValue('TZID');
      if ( !isset($tzid) ) continue ;
      if ( $tzid != '' && !isset($timezones[$tzid]) ) {
        $timezones[$tzid] = $tz;
      }
    }

    /** Work out which ones are actually used here */
    $comps = $ical->GetComponents('VTIMEZONE',false);
    foreach( $comps AS $k => $comp ) {
      $tzid = $comp->GetPParamValue('DTSTART', 'TZID');      if ( isset($tzid) && !isset($need_zones[$tzid]) ) $need_zones[$tzid] = 1;
      $tzid = $comp->GetPParamValue('DUE',     'TZID');      if ( isset($tzid) && !isset($need_zones[$tzid]) ) $need_zones[$tzid] = 1;
      $tzid = $comp->GetPParamValue('DTEND',   'TZID');      if ( isset($tzid) && !isset($need_zones[$tzid]) ) $need_zones[$tzid] = 1;

      if ( $dav_resource->HavePrivilegeTo('all',false) || $session->user_no == $event->user_no || $session->user_no == $event->logged_user
            || ( $c->allow_get_email_visibility && $comp->IsAttendee($session->email) ) ) {
        /**
        * These people get to see all of the event, and they should always
        * get any alarms as well.
        */
        $vcal->AddComponent($comp);
        continue;
      }
      /** No visibility even of the existence of these events if they aren't admin/owner/attendee */
      if ( $event->class == 'PRIVATE' ) continue;

      if ( ! $dav_resource->HavePrivilegeTo('DAV::read') || $event->class == 'CONFIDENTIAL' ) {
       $vcal->AddComponent(obfuscated_event($comp));
      }
      elseif ( isset($c->hide_alarm) && $c->hide_alarm ) {
        // Otherwise we hide the alarms (if configured to)
        $comp->ClearComponents('VALARM');
        $vcal->AddComponent($comp);
      }
      else {
        $vcal->AddComponent($comp);
      }
    }
  }

  /** Put the timezones on there that we need */
  foreach( $need_zones AS $tzid => $v ) {
    if ( isset($timezones[$tzid]) ) $vcal->AddComponent($timezones[$tzid]);
  }

  $response = $vcal->Render();
  header( 'Content-Length: '.strlen($response) );
  header( 'Etag: '.$dav_resource->unique_tag() );
  $request->DoResponse( 200, ($request->method == 'HEAD' ? '' : $response), 'text/calendar; charset="utf-8"' );
}


// Just a single event then

$resource = $dav_resource->resource();
$ic = new iCalComponent( $resource->caldav_data );

/** Default deny... */
$allowed = false;
if ( $dav_resource->HavePrivilegeTo('all', false) || $session->user_no == $resource->user_no || $session->user_no == $resource->logged_user
      || ( $c->allow_get_email_visibility && $ic->IsAttendee($session->email) ) ) {
  /**
  * These people get to see all of the event, and they should always
  * get any alarms as well.
  */
  $allowed = true;
}
else if ( $resource->class != 'PRIVATE' ) {
  $allowed = true; // but we may well obfuscate it below
  if ( ! $dav_resource->HavePrivilegeTo('DAV::read') || ( $resource->class == 'CONFIDENTIAL' && ! $request->HavePrivilegeTo('DAV::write-content') ) ) {
    $ical = new iCalComponent( $resource->caldav_data );
    $comps = $ical->GetComponents('VTIMEZONE',false);
    $confidential = obfuscated_event($comps[0]);
    $ical->SetComponents( array($confidential), $resource->caldav_type );
    $resource->caldav_data = $ical->Render();
  }
}
// else $resource->class == 'PRIVATE' and this person may not see it.

if ( ! $allowed ) {
  $request->DoResponse( 403, translate("Forbidden") );
}

header( 'Etag: "'.$resource->dav_etag.'"' );
header( 'Content-Length: '.strlen($resource->caldav_data) );

$contenttype = 'text/plain';
switch( $resource->caldav_type ) {
  case 'VJOURNAL':
  case 'VEVENT':
  case 'VTODO':
    $contenttype = 'text/calendar';
    break;

  case 'VCARD':
    $contenttype = 'text/vcard';
    break;
}

$request->DoResponse( 200, ($request->method == 'HEAD' ? '' : $resource->caldav_data), $contenttype.'; charset="utf-8"' );
