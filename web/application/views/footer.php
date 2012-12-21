<div id="popup" class="freeow freeow-top-right"></div>
<?php
$enable_calendar_sharing = $this->config->item('enable_calendar_sharing');
$base = base_url();
$relative = preg_replace('/^http[s]:\/\/[^\/]+/', '', $base);
?>
 
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js_generator/siteconf')?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js_generator/userprefs')?>"></script>

<?php
$js = (ENVIRONMENT == 'development' ? 
		Defs::$jsfiles : 
		array('jquery-base-' . AgenDAV\Version::V . '.js', 
			'agendav-' .  AgenDAV\Version::V . '.js'));

// Additional JS files
$additional_js = $this->config->item('additional_js');
if ($additional_js !== FALSE && is_array($additional_js)) {
	foreach ($additional_js as $j) {
		$js[] = $j;
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

$img = array(
		'src' => 'img/agendav_small.png',
		'alt' => 'AgenDAV',
		);
?>

<div id="usermenu_content">
 <ul>
  <li><?php echo anchor('prefs', 
		  $this->i18n->_('labels', 'preferences'),
		  array('class' => 'prefs'))?></li>
  <li><?php echo anchor('main/logout',
		  $this->i18n->_('labels', 'logout'),
		  array('class' => 'logout'))?></li>
 </ul>
</div>
</body>
</html>
