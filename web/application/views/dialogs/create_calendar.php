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
   <td><label for="displayname">Nombre descriptivo del
   calendario:</label></td>
	<td><?php echo form_input($form_displayname);?></td>
   </tr>
   <tr>
   <td><label for="displayname">Nombre interno (opcional):</label></td>
	<td><?php echo form_input($form_internal);?></td>
   </tr>
   <td><label for="calendar_color">Color del calendario:</label></td>
	<td><?php echo form_input($form_color);?></td>
   </tr>
</table>
<?php
echo form_close();
?>
</div>
