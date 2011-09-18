<?php
/**
* CalDAV Server - main program
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
if ( isset($_SERVER['PATH_INFO']) && preg_match( '{^(/favicon.ico|davical.css|(images|js|css)/.+)$}', $_SERVER['PATH_INFO'], $matches ) ) {
  $filename = $_SERVER['DOCUMENT_ROOT'] . preg_replace('{(\.\.|\\\\)}', '', $matches[1]);
  $fh = @fopen($matches[1],'r');
  if ( ! $fh ) {
    @header( sprintf("HTTP/1.1 %d %s", 404, 'Not found') );
  }
  else {
    fpassthru($fh);
  }
  exit(0);
}
elseif ( isset($_SERVER['PATH_INFO']) && preg_match( '{^/\.well-known/(.+)$}', $_SERVER['PATH_INFO'], $matches ) ) {
	require ('well-known.php');
  exit(0);
}
require_once('./always.php');
// dbg_error_log( 'caldav', ' User agent: %s', ((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unfortunately Mulberry does not send a "User-agent" header with its requests :-(')) );
// dbg_log_array( 'headers', '_SERVER', $_SERVER, true );
require_once('HTTPAuthSession.php');
$session = new HTTPAuthSession();

function send_dav_header() {
  global $c;

  /**
  * access-control is rfc3744, we do most of it, but no way to say that.
  * calendar-schedule is another one we do most of, but the spec is not final yet either.
  */
  if ( isset($c->override_dav_header) ) {
    $dav = $c->override_dav_header;
  }
  else {
    /** hack to get around bugzilla #463392 - remove sometime after 2011-02-28 */
    if ( isset($_SERVER['HTTP_USER_AGENT']) && preg_match( '{ Gecko/(20[01]\d[01]\d[0123]\d)(\d+)? }', $_SERVER['HTTP_USER_AGENT'], $matches ) && $matches[1] < 20100520 ) {
      $dav = '1, 2, 3, access-control, calendar-access, calendar-schedule, extended-mkcol, calendar-proxy, bind, addressbook';
    }
    else {
       // We don't actually do calendar-auto-schedule yet - when we do we should add it on here.
      $dav = '1, 2, 3, access-control, calendar-access, calendar-schedule, extended-mkcol, calendar-proxy, bind, addressbook';
      if ( $c->enable_auto_schedule ) $dav .= ', calendar-auto-schedule';
    }
  }
  $dav = explode( "\n", wordwrap( $dav ) );
  foreach( $dav AS $v ) {
    header( 'DAV: '.trim($v, ', '), false);
  }
}
send_dav_header();  // Avoid polluting global namespace

require_once('CalDAVRequest.php');
$request = new CalDAVRequest();

$allowed = implode( ', ', array_keys($request->supported_methods) );
// header( 'Allow: '.$allowed);

if ( ! ($request->IsPrincipal() || isset($request->collection) || $request->method == 'PUT' || $request->method == 'MKCALENDAR' || $request->method == 'MKCOL' ) ) {
  if ( preg_match( '#^/principals/users(/.*/)$#', $request->path, $matches ) ) {
    // Although this doesn't work with the iPhone, perhaps it will with iCal...
    /** @TODO: integrate handling this URL into CalDAVRequest.php */
    $redirect_url = ConstructURL('/caldav.php'.$matches[1]);
    dbg_error_log( 'LOG WARNING', 'Redirecting %s for "%s" to "%s"', $request->method, $request->path, $redirect_url );
    header('Location: '.$redirect_url );
    exit(0);
  }
}

switch ( $request->method ) {
  case 'OPTIONS':    include_once('caldav-OPTIONS.php');   break;
  case 'REPORT':     include_once('caldav-REPORT.php');    break;
  case 'PROPFIND':   include('caldav-PROPFIND.php');       break;
  case 'GET':        include('caldav-GET.php');            break;
  case 'POST':       include('caldav-POST.php');           break;
  case 'HEAD':       include('caldav-GET.php');            break;
  case 'PROPPATCH':  include('caldav-PROPPATCH.php');      break;
  case 'PUT':
    $request->CoerceContentType();
    switch( $request->content_type ) {
      case 'text/calendar':
        /** use original DAViCal 'PUT' code which will be rewritten */
        include('caldav-PUT.php');
        break;
      case 'text/vcard':
      case 'text/x-vcard':
        include('caldav-PUT-vcard.php');
        break;
      default:
        include('caldav-PUT-default.php');
        break;
    }
    break;
  case 'MKCALENDAR': include('caldav-MKCOL.php');          break;
  case 'MKCOL':      include('caldav-MKCOL.php');          break;
  case 'DELETE':     include('caldav-DELETE.php');         break;
  case 'MOVE':       include('caldav-MOVE.php');           break;
  case 'ACL':        include('caldav-ACL.php');            break;
  case 'LOCK':       include('caldav-LOCK.php');           break;
  case 'UNLOCK':     include('caldav-LOCK.php');           break;
  case 'MKTICKET':   include('caldav-MKTICKET.php');       break;
  case 'DELTICKET':  include('caldav-DELTICKET.php');      break;
  case 'BIND':       include('caldav-BIND.php');           break;

  case 'TESTRRULE':  include('test-RRULE-v2.php');         break;

  default:
    dbg_error_log( 'caldav', 'Unhandled request method >>%s<<', $request->method );
    dbg_log_array( 'caldav', '_SERVER', $_SERVER, true );
    dbg_error_log( 'caldav', 'RAW: %s', str_replace("\n", '',str_replace("\r", '', $request->raw_post)) );
}

$request->DoResponse( 500, translate('The application program does not understand that request.') );

