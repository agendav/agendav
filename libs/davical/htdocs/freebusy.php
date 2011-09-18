<?php
require_once("./always.php");
dbg_error_log( "freebusy", " User agent: %s", ((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Unfortunately Mulberry and Chandler don't send a 'User-agent' header with their requests :-(")) );
dbg_log_array( "headers", '_SERVER', $_SERVER, true );
if ( isset($c->public_freebusy_url) && $c->public_freebusy_url ) {
  require_once("PublicSession.php");
  $session = new PublicSession();
}
else {
  require_once("HTTPAuthSession.php");
  $session = new HTTPAuthSession();
}


/**
* Submission parameters recommended by calconnect, plus some generous alternatives
*/
param_to_global('fb_start', '#^[a-z0-9/:.,+-]+$#i', 'start', 'from');
param_to_global('fb_end', '#^[a-z0-9/:.,+-]+$#i', 'end', 'until', 'finish', 'to');
param_to_global('fb_period', '#^[+-]?P?(\d+[WD]?)(T(\d+H)?(\d+M)?(\d+S)?)?+$#', 'period');
param_to_global('fb_format', '#^\S+/\S+$#', 'format');
param_to_global('fb_user', '#^.*$#', 'user', 'userid', 'user_no', 'email');
param_to_global('fb_token', '#^[a-z0-9+/-]+$#i', 'token');

if ( isset($fb_period) ) $fb_period = strtoupper($fb_period);

if ( !isset($fb_start) || $fb_start == '' )  $fb_start  = date('Y-m-d\TH:i:s', time() - 86400 ); // no recommended default.  -1 day
if ( (!isset($fb_period) && !isset($fb_end)) || ($fb_period == '' && $fb_end == '') )
  $fb_period = 'P44D'; // 44 days - 2 days more than recommended default


/**
* If fb_user (user, userid, user_no or email parameter) then we adjust
* the path of the request to suit.
*/
if ( isset($fb_user) ) $_SERVER['PATH_INFO'] = '/'.$fb_user.'/';

/**
* We also allow URLs like .../freebusy.php/user@example.com to work, so long as
* the e-mail matches a single user whose calendar we have rights to.
* @NOTE: It is OK for there to *be* duplicate e-mail addresses, just so long as we
* only have read permission (or more) for only one of them.
*/
require_once("CalDAVRequest.php");
$request = new CalDAVRequest(array("allow_by_email" => 1));
$path_match = '^'.$request->path;
if ( preg_match( '{^/(\S+@[a-z0-9][a-z0-9-]*[.][a-z0-9.-]+)/?$}i', $request->path, $matches ) ) {
  $u = getUserByEMail($matches[1]);
  $path_match = '^/'.$u->username.'/';
}

if ( isset($fb_format) && $fb_format != 'text/calendar' ) {
  $request->DoResponse( 406, 'This server only supports the text/calendar format for freebusy URLs' );
}

if ( ! $request->HavePrivilegeTo('read-free-busy') ) $request->DoResponse( 404 );

require_once("freebusy-functions.php");

switch ( $_SERVER['REQUEST_METHOD'] ) {
  case 'GET':
    $range_start = new RepeatRuleDateTime($fb_start);
    if ( !isset($fb_end) ) {
      $range_end = clone($range_start);
      $range_end->modify($fb_period);
    }
    else {
      $range_end = new RepeatRuleDateTime($fb_end);
    }
    $freebusy = get_freebusy( $path_match, $range_start, $range_end );

    $result = new iCalComponent();
    $result->VCalendar();
    $result->AddComponent($freebusy);

    $request->DoResponse( 200, $result->Render(), 'text/calendar' );
    break;

  default:
    dbg_error_log( "freebusy", "Unhandled request method >>%s<<", $_SERVER['REQUEST_METHOD'] );
    dbg_log_array( "freebusy", 'HEADERS', $raw_headers );
    dbg_log_array( "freebusy", '_SERVER', $_SERVER, true );
    @dbg_error_log( "freebusy", "RAW: %s", str_replace("\n", "",str_replace("\r", "", $request->raw_post)) );
}

