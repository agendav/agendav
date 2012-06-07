<div id="create_calendar_dialog">
<?php
$data_form = array(
		'id' => 'calendar_create_form',
		'class' => 'form-horizontal',
		);
echo form_open('caldav2json/create_calendar', $data_form);

$form_displayname = array(
		'name' => 'displayname',
		'value' => '',
		'class' => 'displayname input-medium',
		'maxlength' => '255',
		'size' => '25',
		);

$form_color = array(
		'name' => 'calendar_color',
		'value' => $default_calendar_color,
		'class' => 'calendar_color pick_color input-mini',
		'maxlength' => '7',
		'size' => '7',
		);
?>
<?php
echo formelement(
	  $this->i18n->_('labels', 'displayname'),
	  form_input($form_displayname));

echo formelement(
	  $this->i18n->_('labels', 'color'),
	  form_input($form_color));

echo form_close();
?>
</div>
