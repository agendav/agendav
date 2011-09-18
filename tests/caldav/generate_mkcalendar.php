<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/davical/inc:'
		. '../../libs/icalcreator');

require_once('XMLDocument.php');
require_once('iCalcreator.class.php');
require_once('iCalUtilityFunctions.class.php');


$ns = array(
			'DAV:' => '', 
			'urn:ietf:params:xml:ns:caldav' => 'C',
			'http://apple.com/ns/ical/' => 'ical');

$xml = new XMLDocument($ns);

$set = $xml->NewXMLElement('set');
$prop = $set->NewElement('prop');
$xml->NSElement($prop, 'displayname', 'Calendario de prueba');
$xml->NSElement($prop, 'http://apple.com/ns/ical/:calendar-color', '#000000ff');

$cal = new vcalendar();
iCalUtilityFunctions::createTimezone($cal,'Europe/Madrid',
		array('X-LIC-LOCATION' => 'Europe/Madrid'));

//$xml->NSElement($prop, 'urn:ietf:params:xml:ns:caldav:calendar-timezone',
//		'<![CDATA[' . $cal->createCalendar() . ']]');

echo $xml->Render('C:mkcalendar', $set, null,
		'urn:ietf:params:xml:ns:caldav');
?>
