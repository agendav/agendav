<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/davical/inc');


require_once('iCalendar.php');


$ical = new iCalComponent();
$ical->VCalendar();

// TODO Timezone

$vevent = new iCalComponent();
$vevent->SetType('VEVENT');

$now = gmdate('Ymd\TH:is\Z');
$uid = generate_guid();

$vevent->AddProperty('CREATED', $now);
$vevent->AddProperty('LAST-MODIFIED', $now);
$vevent->AddProperty('DTSTAMP', $now);
$vevent->AddProperty('SEQUENCE', 1);
$vevent->AddProperty('SUMMARY', 'A ver que tal');
$vevent->AddProperty('UID', $uid);
$vevent->AddProperty('DTSTART', '20110502T120000Z');
$vevent->AddProperty('DTEND', '20110502T140000Z');

$ical->AddComponent($vevent);

echo $ical->Render();


function generate_guid() {
	if (function_exists('com_create_guid') === true)
	{
		return trim(com_create_guid(), '{}');
	}

	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
			mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535),
			mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0,
				65535), mt_rand(0, 65535), mt_rand(0, 65535));
}


?>
