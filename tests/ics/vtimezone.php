<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/icalcreator:' 
		. '../../libs/davical/inc');

require_once('iCalcreator.class.php');

$config = array(
		'unique_id' => 'us.es',
		);

$ical = new vcalendar($config);

$res = iCalUtilityFunctions::createTimezone($ical, 'Europe/Madrid');
var_dump($res);

echo $ical->createCalendar();
