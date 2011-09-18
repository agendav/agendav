<?php
/**
* CalDAV Server - handle PUT method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("POST", "method handler");

require_once("XMLDocument.php");
require_once("iCalendar.php");
include_once('caldav-PUT-functions.php');
include_once('freebusy-functions.php');

if ( ! $request->AllowedTo("CALDAV:schedule-send-freebusy")
  && ! $request->AllowedTo("CALDAV:schedule-send-invite")
  && ! $request->AllowedTo("CALDAV:schedule-send-reply") ) {
  // $request->DoResponse(403);
  dbg_error_log( "WARN", ": POST: permissions not yet checked" );
}

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || isset($c->dbg['post'])) ) {
  $fh = fopen('/tmp/POST.txt','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}


function handle_freebusy_request( $ic ) {
  global $c, $session, $request;

  $reply = new XMLDocument( array("DAV:" => "", "urn:ietf:params:xml:ns:caldav" => "C" ) );
  $responses = array();

  $fbq_start = $ic->GetPValue('DTSTART');
  $fbq_end   = $ic->GetPValue('DTEND');
  if ( ! ( isset($fbq_start) || isset($fbq_end) ) ) {
    $request->DoResponse( 400, 'All valid freebusy requests MUST contain a DTSTART and a DTEND' );
  }

  $range_start = new RepeatRuleDateTime($fbq_start);
  $range_end   = new RepeatRuleDateTime($fbq_end);

  $attendees = $ic->GetProperties('ATTENDEE');
  if ( preg_match( '# iCal/\d#', $_SERVER['HTTP_USER_AGENT']) ) {
    dbg_error_log( "POST", "Non-compliant iCal request.  Using X-WR-ATTENDEE property" );
    $wr_attendees = $ic->GetProperties('X-WR-ATTENDEE');
    foreach( $wr_attendees AS $k => $v ) {
      $attendees[] = $v;
    }
  }
  dbg_error_log( "POST", "Responding with free/busy for %d attendees", count($attendees) );

  foreach( $attendees AS $k => $attendee ) {
    $attendee_email = preg_replace( '/^mailto:/', '', $attendee->Value() );
    dbg_error_log( "POST", "Calculating free/busy for %s", $attendee_email );

    /** @TODO: Refactor this so we only do one query here and loop through the results */
    $params = array( ':session_principal' => $session->principal_id, ':scan_depth' => $c->permission_scan_depth, ':email' => $attendee_email );
    $qry = new AwlQuery('SELECT pprivs(:session_principal::int8,principal_id,:scan_depth::int) AS p, username FROM usr JOIN principal USING(user_no) WHERE lower(usr.email) = lower(:email)', $params );
    if ( !$qry->Exec('POST',__LINE__,__FILE__) ) $request->DoResponse( 501, 'Database error');
    if ( $qry->rows() > 1 ) {
      // Unlikely, but if we get more than one result we'll do an exact match instead.
      if ( !$qry->QDo('SELECT pprivs(:session_principal::int8,principal_id,:scan_depth::int) AS p, username FROM usr JOIN principal USING(user_no) WHERE usr.email = :email', $params ) )
        $request->DoResponse( 501, 'Database error');
      if ( $qry->rows() == 0 ) {
        /** Sigh... Go back to the original case-insensitive match */
        $qry->QDo('SELECT pprivs(:session_principal::int8,principal_id,:scan_depth::int) AS p, username FROM usr JOIN principal USING(user_no) WHERE lower(usr.email) = lower(:email)', $params );
      }
    }

    $response = $reply->NewXMLElement("response", false, false, 'urn:ietf:params:xml:ns:caldav');
    $reply->CalDAVElement($response, "recipient", $reply->href($attendee->Value()) );

    if ( $qry->rows() == 0 ) {
      $reply->CalDAVElement($response, "request-status", "3.7;Invalid Calendar User" );
      $reply->CalDAVElement($response, "calendar-data" );
      $responses[] = $response;
      continue;
    }
    if ( ! $attendee_usr = $qry->Fetch() ) $request->DoResponse( 501, 'Database error');
    if ( (privilege_to_bits('schedule-query-freebusy') & bindec($attendee_usr->p)) == 0 ) {
      $reply->CalDAVElement($response, "request-status", "3.8;No authority" );
      $reply->CalDAVElement($response, "calendar-data" );
      $responses[] = $response;
      continue;
    }
    $attendee_path_match = '^/'.$attendee_usr->username.'/';
    $fb = get_freebusy( $attendee_path_match, $range_start, $range_end, bindec($attendee_usr->p) );

    $fb->AddProperty( 'UID',       $ic->GetPValue('UID') );
    $fb->SetProperties( $ic->GetProperties('ORGANIZER'), 'ORGANIZER');
    $fb->AddProperty( $attendee );

    $vcal = new iCalComponent();
    $vcal->VCalendar( array('METHOD' => 'REPLY') );
    $vcal->AddComponent( $fb );

    $response = $reply->NewXMLElement( "response", false, false, 'urn:ietf:params:xml:ns:caldav' );
    $reply->CalDAVElement($response, "recipient", $reply->href($attendee->Value()) );
    $reply->CalDAVElement($response, "request-status", "2.0;Success" );  // Cargo-cult setting
    $reply->CalDAVElement($response, "calendar-data", $vcal->Render() );
    $responses[] = $response;
  }

  $response = $reply->NewXMLElement( "schedule-response", $responses, $reply->GetXmlNsArray(), 'urn:ietf:params:xml:ns:caldav' );
  $request->XMLResponse( 200, $response );
}


function handle_cancel_request( $ic ) {
  global $c, $session, $request;

  $reply = new XMLDocument( array("DAV:" => "", "urn:ietf:params:xml:ns:caldav" => "C" ) );

  $response = $reply->NewXMLElement( "response", false, false, 'urn:ietf:params:xml:ns:caldav' );
  $reply->CalDAVElement($response, "request-status", "2.0;Success" );  // Cargo-cult setting
  $response = $reply->NewXMLElement( "schedule-response", $response, $reply->GetXmlNsArray() );
  $request->XMLResponse( 200, $response );
}

$ical = new iCalComponent( $request->raw_post );
$method =  $ical->GetPValue('METHOD');

$resources = $ical->GetComponents('VTIMEZONE',false);
$first = $resources[0];
switch ( $method ) {
  case 'REQUEST':
    dbg_error_log('POST', 'Handling iTIP "REQUEST" method with "%s" component.', $method, $first->GetType() );
    if ( $first->GetType() == 'VFREEBUSY' )
      handle_freebusy_request( $first );
    elseif ( $first->GetType() == 'VEVENT' ) {
      handle_schedule_request( $ical );
    }
    else {
      dbg_error_log('POST', 'Ignoring iTIP "REQUEST" with "%s" component.', $first->GetType() );
    }
    break;
  case 'REPLY':
    dbg_error_log('POST', 'Handling iTIP "REPLY" with "%s" component.', $first->GetType() );
    handle_schedule_reply ( $ical );
    break;

  case 'CANCEL':
    dbg_error_log("POST", "Handling iTIP 'CANCEL'  method.", $method );
    handle_cancel_request( $first );
    break;

  default:
    dbg_error_log("POST", "Unhandled '%s' method in request.", $method );
}
