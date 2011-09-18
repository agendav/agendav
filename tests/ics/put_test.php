<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/davical/inc');

require_once('caldav-client-v2.php');
require_once('mycaldav.php');

$ics = file_get_contents('generated.ics');
$url = 'http://cal.jlp/caldav.php/jorge/calendario/';
// UID from generated.ics
$href = '3B88157F-770E-4E14-9358-CFD659D398A9.ics'; 
$user = 'jorge';
$passwd = 'jorge';

$final_url = $url . $href;

$cal = new MyCalDAV($final_url, $user, $passwd);
$cal->SetCalendar($final_url);

$resp = $cal->DoPUTRequest($final_url, $ics,
		'c647f2664b8f8a91c4638af45de05f08'); // New resource

echo $cal->GetHTTPResultCode() . "\n";

print_r($cal->GetResponseHeaders());

var_dump($resp);


?>
