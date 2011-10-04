var prefs_timeformat = '<?php 
echo Dates::$timeformats[$this->config->item('format_time')]['fullcalendar']?>';
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
		.addslashes($this->i18n->_('labels', $pref)) ."';";
}
// vim: set ft=javascript
