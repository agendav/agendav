<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path .
        ':/home/jorge/tmp/icalcreator_ultima:'); 


// . '../../libs/icalcreator:' 
require_once('iCalcreator.class.php');

$config = array(
        'unique_id' => 'us.es',
        );

$file = explode("\n",
        @file_get_contents('9DAF859E-0DF8-45D2-AFEE-42E73135DCEB.ics'));

$ical = new vcalendar($config);

$res = $ical->parse($file);
$ical->sort();

$expand = $ical->selectComponents(2011, 5, 10, 2011, 6, 15,
        'vevent', false, true, false);

           if ($expand !== FALSE) {
               foreach( $expand as $year => $year_arr ) {
                   foreach( $year_arr as $month => $month_arr ) {
                       foreach( $month_arr as $day => $day_arr ) {
                           foreach( $day_arr as $event ) {
                               echo
                                   $current_dtstart =
                                   $event->getProperty('X-CURRENT-DTSTART');
                               $dtstart =
                                   $event->getProperty('DTSTART');
                               $current_dtend =
                                   $event->getProperty('X-CURRENT-DTEND');
                               $dtend =
                                   $event->getProperty('DTEND');

                               if ($current_dtstart === FALSE) {
                                   var_dump($dtstart);
                               } else {
                                   var_dump($current_dtstart);
                               }

                               if ($current_dtend === FALSE) {
                                   var_dump($dtend);
                               } else {
                                   var_dump($current_dtend);
                               }
                               echo "\n";
                           }
                       }
                   }
               }
           }

