var prefs_timeformat_option = '<?php 
echo addslashes($this->config->item('default_time_format'))?>';
var prefs_timeformat = '<?php 
echo addslashes($this->dates->time_format_string('fullcalendar'))?>';
var prefs_dateformat = '<?php 
echo addslashes($this->dates->date_format_string('datepicker'))?>';
var prefs_firstday = <?php 
echo $this->config->item('default_first_day')?>;
<?php
// Locale dependent format
$prefs = array(
		'format_column_month',
		'format_column_week',
		'format_column_day',
		'format_title_month',
		'format_title_week',
		'format_title_day',
		);

foreach($prefs as $pref) {
	echo 'var prefs_' . $pref . " = '"
		.addslashes($this->config->item($pref)) ."';";
}
// vim: set ft=javascript
