<div id="modify_calendar_dialog">
<div id="modify_calendar_dialog_tabs">
<?php
$data_form = array(
	'id' => 'modify_calendar_form',
	'class' => 'uniForm',
);
echo form_open('caldav2json/modify_calendar', $data_form);

// Shared calendar?
if (isset($shared) && $shared === TRUE) {
	$show_shared = TRUE;
} else {
	$show_shared = FALSE;
}
?>
<ul>
 <li><a href="#tabs-general"><?php echo
 $this->i18n->_('labels', 'generaloptions')?></a></li>
<?php if (!$show_shared): ?>
 <li><a href="#tabs-share"><?php echo 
  $this->i18n->_('labels','shareoptions')?></a></li>
<?php endif; ?>
</ul>


<div id="tabs-general">
<?php


echo form_hidden('calendar', $calendar);


echo form_hidden('shared', ($show_shared) ? 'true' : 'false');
if ($show_shared) {
	echo form_hidden('sid', isset($sid) ? $sid : '?');
	echo form_hidden('user_from', isset($user_from) ? $user_from : '?');
} else {
	// Users who can access this calendar
	$form_share_with = array(
			'name' => 'share_with',
			'value' => $share_with,
			'class' => 'share_with',
			'maxlength' => '255',
			'size' => '25',
			);
}

$form_displayname = array(
		'name' => 'displayname',
		'value' => $displayname,
		'class' => 'displayname',
		'maxlength' => '255',
		'size' => '25',
		);

$form_color = array(
		'name' => 'calendar_color',
		'value' => $color,
		'class' => 'calendar_color pick_color',
		'maxlength' => '7',
		'size' => '7',
		);

// Shared calendars
if ($show_shared):
?>
<div class="share_info ui-corner-all">
<?php
echo $this->i18n->_('messages', 'info_sharedby',
		array('%user' => '<span
			class="show_user_name">'.$user_from.'</span>'))?>
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
if (!$show_shared):
?>
<div id="tabs-share">
	<div class="share_info ui-corner-all">
	<?php echo $this->i18n->_('messages', 'info_shareexplanation');?>
	</div>
<?php
echo formelement(
		$this->i18n->_('labels', 'sharewith'),
		form_input($form_share_with));
?>
</div>
<?php
endif;
echo form_close();
?>
</div>
</div>
