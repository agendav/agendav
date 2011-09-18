<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/davical/inc');

require_once('caldav-client-v2.php');
require_once('mycaldav.php');

$url = 'http://cal.jlp/caldav.php/jorge/';
$user = 'jorge';
$passwd = 'jorge';

$cal = new MyCalDAV($url, $user, $passwd);
$cal->PrincipalURL($url);
$cal->CalendarHomeSet($url);

$resp = $cal->FindCalendars();

var_dump($resp);


?>
