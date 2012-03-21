<div id="popup" class="freeow freeow-top-right"></div>
<?php
$base = base_url();
$relative = preg_replace('/^http[s]:\/\/[^\/]+/', '', $base);
?>
 
<script language="JavaScript" type="text/javascript">
//<![CDATA[
var base_url = '<?php echo $base; ?>';
var base_app_url = '<?php echo site_url(); ?>/';
var relative_url = '<?php echo $relative; ?>';
var agendav_version = '<?php echo AGENDAV_VERSION; ?>';
//]]>
</script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js_generator/prefs')?>"></script>

<?php
$js = (ENVIRONMENT == 'development' ? 
		Defs::$jsfiles : 
		array('jquery-base-' . AGENDAV_VERSION . '.js', 
			'agendav-' .  AGENDAV_VERSION . '.js'));

// Additional JS files
$additional_js = $this->config->item('additional_js');
if ($additional_js !== FALSE && is_array($additional_js)) {
	foreach ($additional_js as $js) {
		$js[] = $js;
	}
}

foreach ($js as $jsfile) {
	echo script_tag('js/' . $jsfile);
}
?>

<?php
// Load session refresh code
if (isset($load_session_refresh) && $load_session_refresh === TRUE):
?>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js_generator/session_refresh')?>"></script>
<?php
endif;

if (isset($login_page) && $login_page === TRUE):
	?>
<script language="JavaScript" type="text/javascript">
//<![CDATA[
$(document).ready(function() {
	$("input:submit").button();
	$('input[name="user"]').focus();

});
//]]>
</script>
	<?php
endif;

if (isset($load_calendar_colors) && $load_calendar_colors === TRUE) {
	// Calendar colors
	$calendar_colors = $this->config->item('calendar_colors');
	$this->load->view('js_code/calendar_colors',
			array('colors' => $calendar_colors));
}


$img = array(
		'src' => 'img/agendav_small.png',
		'alt' => 'AgenDAV',
		);
?>
</body>
</html>
