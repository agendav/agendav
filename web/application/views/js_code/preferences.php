var AgenDAVConf = AgenDAVConf || {};

AgenDAVConf = {
    prefs_timeformat_option: '<?php 
echo addslashes($this->config->item('default_time_format'))?>',
    prefs_timeformat:  '<?php 
echo addslashes($this->dates->time_format_string('fullcalendar'))?>',
    prefs_dateformat:  '<?php 
echo addslashes($this->dates->date_format_string('datepicker'))?>',
    prefs_firstday:  <?php 
echo $this->config->item('default_first_day')?>,
<?php
// Locale dependent format
$prefs = array(
		'format_column_month',
		'format_column_week',
		'format_column_day',
		'format_column_table',
		'format_title_month',
		'format_title_week',
		'format_title_day',
		'format_title_table',
		);

foreach($prefs as $pref) {
	echo ' prefs_' . $pref . ": '"
		.addslashes($this->config->item($pref)) ."',";
}
?>
    timepicker_base: {
        show24Hours: <?php echo ($this->config->item('default_time_format') == '24'
                     ? 'true' : 'false')?>,

        separator: ':',
        step: 30
    },
    prefs_csrf_cookie_name:  '<?php echo
$this->config->item('cookie_prefix') .
$this->config->item('csrf_cookie_name') ?>',
    prefs_csrf_token_name:  '<?php echo
$this->config->item('csrf_token_name') ?>'
};
<?php
// vim: set ft=javascript
