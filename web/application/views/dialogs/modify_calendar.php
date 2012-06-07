<div id="modify_calendar_dialog">
<div id="modify_calendar_dialog_tabs">
<?php
$data_form = array(
	'id' => 'modify_calendar_form',
	'class' => 'form-horizontal',
);
echo form_open('caldav2json/modify_calendar', $data_form);

$show_share_options = (isset($show_share_options) ? $show_share_options :
		TRUE);

?>
<ul>
 <li><a href="#tabs-general"><?php echo
 $this->i18n->_('labels', 'generaloptions')?></a></li>
<?php if ($show_share_options && !$is_shared_calendar): ?>
 <li><a href="#tabs-share"><?php echo 
  $this->i18n->_('labels','shareoptions')?></a></li>
<?php endif; ?>
</ul>


<div id="tabs-general">
<?php


echo form_hidden('calendar', $calendar);


echo form_hidden('is_shared_calendar', ($show_share_options && $is_shared_calendar) ? 'true' : 'false');
if ($show_share_options && $is_shared_calendar) {
	echo form_hidden('sid', isset($sid) ? $sid : '?');
	echo form_hidden('user_from', isset($user_from) ? $user_from : '?');
}

$form_displayname = array(
		'name' => 'displayname',
		'value' => $displayname,
		'class' => 'displayname input-medium',
		'maxlength' => '255',
		'size' => '25',
		);

$form_color = array(
		'name' => 'calendar_color',
		'value' => $color,
		'class' => 'calendar_color pick_color input-mini',
		'maxlength' => '7',
		'size' => '7',
		);

// Shared calendars
if ($show_share_options && $is_shared_calendar):
?>
<div class="share_info ui-corner-all">
<?php
echo $this->i18n->_('messages', 'info_sharedby',
		array('%user' => '<span
			class="username">'.$user_from.'</span>'));

if (!isset($write_access) || $write_access === FALSE) {
	echo ' (' . $this->i18n->_('labels', 'readonly') . ')';
}
?>

</div>

<?php
endif;

echo formelement(
		$this->i18n->_('labels', 'displayname'),
		form_input($form_displayname));

echo formelement(
		$this->i18n->_('labels', 'color'),
		form_input($form_color));

if (isset($public_url)):
	$img = array(
			'src' => 'img/calendar_link.png',
			'alt' => $this->i18n->_('labels', 'publicurl'),
			'title' => $this->i18n->_('labels', 'publicurl'),
			);
?>
<div class="public_url"><?php echo $this->i18n->_('labels',
		'publicurl') . ' ' . anchor($public_url, img($img))?></div>
<?php
endif;
?>
</div>

<?php
if ($show_share_options && !$is_shared_calendar):

?>
<div id="tabs-share">
<?php
	$this->load->view('share_calendar_manager_table',
			array('shares' => $share_with));
?>

</div>
<?php
endif;
echo form_close();
?>
</div>
</div>
