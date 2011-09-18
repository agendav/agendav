<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/davical/inc');


require_once('iCalendar.php');
$a = file_get_contents('simple.ics');

// New increment: dayDelta, minuteDelta
$daydelta = 1; // Can be negative
$minutedelta = 0; // Can be negative

// Nuevo
$new = new iCalComponent();
$ical = new iCalComponent($a);


$comps = $ical->GetComponents();

$new->VCalendar();

// Timezones
$tzs = array();
foreach ($comps as $k => $v) {
	if ($v->GetType() == 'VTIMEZONE') {
		$tzres = $comps[$k];
		$tzid = $tzres->GetPValue('TZID');
		$tzval = $tzres->GetPValue('X-LIC-LOCATION');
		if ($tzval == null || empty($tzval)) {
			// Try to extract it from TZID
			if (preg_match('#([^/]+/[^/]+)$#', $tzid, $matches)) {
				$tzval = $matches[1];
			}
		}

		if ($tzval != null && !empty($tzval)) {
			$tzs[$tzid] = $tzval;
		}
	}
}

foreach ($comps as $k => $c) {
	// We look for a VEVENT
	if ($c->GetType() != 'VEVENT') {
		echo " Nos saltamos éste\n";
		continue;
	} else {
		// Timezone
		$dtstart = $c->GetPValue('DTSTART');
		$has_z = preg_match('/Z$/', $dtstart);
		$final_tz = null;
		if ($has_z) {
			// UTC
			$final_tz = 'UTC';
		} else {
			$tzid = $c->GetPParamValue('DTSTART', 'TZID');

			if ($tzid != null) {
				if (!isset($tzs[$tzid])) {
					// Bogus Icalendar file!
					// We suppose current timezone
					$final_tz = date_default_timezone_get();
				} else {
					$final_tz = $tzs[$tzid];
				}
			}
		}

		// DTEND, suponemos que existe
		$dtend = $c->GetPValue('DTEND');
		$type = $c->GetPParamValue('DTEND', 'VALUE');
		$is_utc = ($final_tz == 'UTC');
		$obj = null;

		if ($type != null && $type == 'DATE') {
			$format = 'Ymd';
		} else {
			$format = 'Ymd\THis';
			if ($is_utc) {
				$format .= '\Z';
			}
		}

		$dtz = new DateTimeZone($final_tz);

		$objs = DateTime::createFromFormat($format, $dtstart, $dtz);
		$obje = DateTime::createFromFormat($format, $dtend, $dtz);
		$obje->add(new DateInterval('PT1H'));

		$dtendorig = $c->GetProperties('DTEND');

		print_r(array_keys($dtendorig));
		// GetProperties returns as key the current position of the property
		// in the internal resource array
		foreach ($dtendorig as $prop) {
			$prop->Value($obje->format($format));
			$c->ClearProperties('DTEND');
			$c->SetProperties(array($prop), 'DTEND');
		}
		
		$ahora = gmdate('Ymd\TH:is\Z');
		$c->ClearProperties('LAST-MODIFIED');
		$c->ClearProperties('DTSTAMP');
		$c->AddProperty('LAST-MODIFIED', $ahora);
		$c->AddProperty('DTSTAMP', $ahora);

		$comps[$k] = $c;

	}
}

$new->SetComponents($comps);


print $new->Render();


?>
