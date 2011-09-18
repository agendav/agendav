<?php
/**
* CalDAV Server - main program
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@catalyst.net.nz>
* @copyright Catalyst .Net Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
require("./always.php");
dbg_error_log( "caldav", " User agent: %s", ((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Unfortunately Mulberry does not send a 'User-agent' header with its requests :-(")) );
dbg_log_array( "headers", '_SERVER', $_SERVER, true );
require("PublicSession.php");
$session = new PublicSession();

/** A simplified DAV header in this case */
$dav = "1, 2, calendar-access";
header( "DAV: $dav");

require_once("CalDAVRequest.php");
$request = new CalDAVRequest();
if ( !isset($request->ticket) && !$request->IsPublic()
       || (isset($request->ticket) && $request->ticket->expired ) ) {
  $request->DoResponse( 403, translate('Anonymous users may only access public calendars') );
}

switch ( $request->method ) {
  case 'OPTIONS':    include_once("caldav-OPTIONS.php");    break;
  case 'REPORT':     include_once("caldav-REPORT.php");     break;
  case 'PROPFIND':   include_once("caldav-PROPFIND.php");   break;
  case 'GET':        include_once("caldav-GET.php");        break;
  case 'HEAD':       include_once("caldav-GET.php");        break;

  case 'PROPPATCH':
  case 'MKCALENDAR':
  case 'MKCOL':
  case 'PUT':
  case 'DELETE':
  case 'LOCK':
  case 'UNLOCK':
    $request->DoResponse( 403, translate('Anonymous users are not allowed to modify calendars') );
    break;

  case 'TESTRRULE':  include_once("test-RRULE.php");        break;

  default:
    dbg_error_log( "caldav", "Unhandled request method >>%s<<", $request->method );
    dbg_log_array( "caldav", '_SERVER', $_SERVER, true );
    dbg_error_log( "caldav", "RAW: %s", str_replace("\n", "",str_replace("\r", "", $request->raw_post)) );
}

$request->DoResponse( 500, translate("The application program does not understand that request.") );

