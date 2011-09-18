<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path . ':' 
		. '../../libs/awl/inc:' 
		. '../../libs/own_extensions:' 
		. '../../libs/davical/inc');


require_once('iCalendar.php');
$a = file_get_contents('simple.ics');

// Nuevo
$new = new iCalComponent();

$ical = new iCalComponent($a);

$new->SetType($ical->GetType());

$comps = $ical->GetComponents();

$i = 0;
foreach ($comps as $c) {
	$i++;
	echo "[" . $i . "] Un " . $c->GetType() . "\n";

	//print_r($c->GetProperties('DTSTART'));

	// Buscamos un VEVENT
	if ($c->GetType() != 'VEVENT') {
		echo " Nos saltamos éste\n";
		$new->AddComponent($c);
		continue;
	} else {
		// Buscamos DTSTART
		$dtstarts = $c->GetProperties('DTSTART');

		foreach ($dtstarts as $k => $v) {
			$tzid = $v->GetParameterValue('TZID');
			if ($tzid == NULL) {
				// Directamente
				$v->Value('20110502T1600Z');
			} else {
				// Hacer lo que sea con la zona horaria!
				$v->Value('20110502T1800');
			}
		}
		echo "vamos\n";
		$c->SetProperties($dtstarts, 'DTSTART');

		$ahora = gmdate('Ymd\TH:is\Z');
		$c->ClearProperties('LAST-MODIFIED');
		$c->ClearProperties('DTSTAMP');
		$c->AddProperty('LAST-MODIFIED', $ahora);
		$c->AddProperty('DTSTAMP', $ahora);

		$new->AddComponent($c);
	}
}

print $new->Render();


?>
