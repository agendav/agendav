<?php
// Add required paths
$current_include_path = get_include_path();
set_include_path($current_include_path .
        ':/home/jorge/pry/agendav/libs/icalcreator'); 


// . '../../libs/icalcreator:' 
require_once('iCalcreator.class.php');

$config = array(
        'unique_id' => 'us.es',
        );

$file = explode("\n",
        @file_get_contents('33EAB23E-CB57-45EA-BF71-DA3A141F63A8.ics'));

$ical = new vcalendar($config);

$res = $ical->parse($file);
$ical->sort();

$expand = $ical->selectComponents(2011, 10, 1, 2011, 11, 1,
        'vevent', false, true, false);

           if ($expand !== FALSE) {
               foreach( $expand as $year => $year_arr ) {
                   foreach( $year_arr as $month => $month_arr ) {
                       foreach( $month_arr as $day => $day_arr ) {
                           foreach( $day_arr as $event ) {
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

