<div id="create_calendar_dialog">
<?php
$data_form = array(
		'id' => 'calendar_create_form',
		);
echo form_open('caldav2json/create_calendar', $data_form);
$form_internal = array(
		'name' => 'calendar',
		'value' => '',
		'class' => 'calendar',
		'maxlength' => '255',
		'size' => '25',
		);

$form_displayname = array(
		'name' => 'displayname',
		'value' => '',
		'class' => 'displayname',
		'maxlength' => '255',
		'size' => '25',
		);

$form_color = array(
		'name' => 'calendar_color',
		'value' => $default_calendar_color,
		'class' => 'calendar_color pick_color',
		'maxlength' => '7',
		'size' => '7',
		);
?>
 <table>
  <tr>
   <td><label for="displayname"><?php echo $this->i18n->_('labels',
		   'displayname') ?></label></td>
	<td><?php echo form_input($form_displayname);?></td>
   </tr>
   <tr>
   <td><label for="internal"><?php echo $this->i18n->_('labels',
		   'internalname')?> 
   <?php echo $this->i18n->_('labels', 'optional')?></label></td>
	<td><?php echo form_input($form_internal);?></td>
   </tr>
   <td><label for="calendar_color"><?php echo
   $this->i18n->_('labels', 'color')?></label></td>
	<td><?php echo form_input($form_color);?></td>
   </tr>
</table>
<?php
echo form_close();
?>
</div>
