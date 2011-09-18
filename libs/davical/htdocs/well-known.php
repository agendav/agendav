<?php

require_once('./always.php');

dbg_error_log( 'well-known', 'iSchedule requested' );

require_once('HTTPAuthSession.php');
$c->allow_unauthenticated = true;
$session = new HTTPAuthSession();

if ( ! isset ( $request ) ) {
 require_once('CalDAVRequest.php');
 $request = new CalDAVRequest();
}


switch ( $request->path ) {
  case '/.well-known/caldav':
  case '/.well-known/carddav':
    header('Location: ' . ConstructURL('/',true) );
    $request->DoResponse(301); // Moved permanently
    // does not return.
}



if ( $c->enable_scheduling != true )
{
 $request->DoResponse( 404, translate('The application program does not understand that request.') );
 exit ();
}

header ( 'iSchedule-Version: 1.0' );

switch ( $request->method ) {
  case 'GET':        ischedule_get();                      break;
  case 'POST':       include('iSchedule.php');             break;

  default:
    dbg_error_log( 'well-known', 'Unhandled request method >>%s<<', $request->method );
    dbg_log_array( 'well-known', '_SERVER', $_SERVER, true );
    dbg_error_log( 'well-known', 'RAW: %s', str_replace("\n", '',str_replace("\r", '', $request->raw_post)) );
}

$request->DoResponse( 500, translate('The application program does not understand that request.') );





function ischedule_get ( )
{
 global $request,$c;
 if ( $request->path != '/.well-known/ischedule' || $_GET['query'] != 'capabilities' )
 {
  $request->DoResponse( 404, translate('The application program does not understand that request.' . $request->path ) );
  return false;
 }
 header ( 'Content-Type: application/xml; charset=utf-8' );
 echo '<?xml version="1.0" encoding="utf-8" ?>';
 echo <<<RESPONSE
  <query-result xmlns="urn:ietf:params:xml:ns:ischedule">
    <capability-set>
      <supported-version-set>
        <version>1.0</version>
      </supported-version-set>
      <supported-scheduling-message-set>
        <comp name="VEVENT">
          <method name="REQUEST"/>
          <method name="ADD"/>
          <method name="REPLY"/>
          <method name="CANCEL"/>
        </comp>
        <comp name="VTODO"/>
        <comp name="VFREEBUSY"/>
      </supported-scheduling-message-set>
      <supported-calendar-data-type>
        <calendar-data-type content-type="text/calendar" version="2.0"/>
      </supported-calendar-data-type>
      <supported-attachment-values>
        <inline-attachment/>
        <external-attachment/>
      </supported-attachment-values>
      <supported-recipient-uri-scheme-set>
        <scheme>mailto</scheme>
      </supported-recipient-uri-scheme-set>
      <max-content-length>102400</max-content-length>
      <min-date-time>19910101T000000Z</min-date-time>
      <max-date-time>20381231T000000Z</max-date-time>
      <max-instances>150</max-instances>
      <max-recipients>250</max-recipients>

RESPONSE;
 echo '      <administrator>mailto:' . $c->admin_email . '</administrator>' . "\n";
 echo <<<RESPONSE
    </capability-set>
 </query-result>
RESPONSE;
 exit ( 0 );
}
