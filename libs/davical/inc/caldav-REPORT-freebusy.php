<?php
/**
 * Handle the FREE-BUSY-QUERY variant of REPORT
 */
include_once("freebusy-functions.php");

$fbq_content = $xmltree->GetContent('urn:ietf:params:xml:ns:caldav:free-busy-query');
$fbq_start = $fbq_content[0]->GetAttribute('start');
$fbq_end   = $fbq_content[0]->GetAttribute('end');
if ( ! ( isset($fbq_start) || isset($fbq_end) ) ) {
  $request->DoResponse( 400, 'All valid freebusy requests MUST contain a time-range filter' );
}
$range_start = new RepeatRuleDateTime($fbq_start);
$range_end   = new RepeatRuleDateTime($fbq_end);


/** We use the same code for the REPORT, the POST and the freebusy GET... */
$freebusy = get_freebusy( '^'.$request->path.$request->DepthRegexTail(), $range_start, $range_end );

$result = new iCalComponent();
$result->VCalendar();
$result->AddComponent($freebusy);

$request->DoResponse( 200, $result->Render(), 'text/calendar' );
// Won't return from that

