<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '/home/jorge/pry/agendav/libs/icalcreator:'
		. '../../libs/davical/inc');

require_once('iCalcreator.class.php');

$config = array(
		'unique_id' => 'us.es',
		);

$file = 'Europe_Madrid.ics';
$contents = explode("\n", @file_get_contents($file));
$vtimezone = new vtimezone($config);
$res = $vtimezone->parse($contents);

if ($res === FALSE) {
	echo "Falla!";
} else {
	echo "Resultado:\n\n";
	echo $vtimezone->createComponent($x);
}

