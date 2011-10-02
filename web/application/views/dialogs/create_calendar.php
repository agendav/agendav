<div id="create_calendar_dialog">
<?php
$data_form = array(
		'id' => 'calendar_create_form',
		'class' => 'uniForm',
		);
echo form_open('caldav2json/create_calendar', $data_form);
$form_internal = array(
		'name' => 'calendar',
		'value' => '',
		'class' => 'calendar medium',
		'maxlength' => '255',
		'size' => '25',
		);

$form_displayname = array(
		'name' => 'displayname',
		'value' => '',
		'class' => 'displayname medium',
		'maxlength' => '255',
		'size' => '25',
		);

$form_color = array(
		'name' => 'calendar_color',
		'value' => $default_calendar_color,
		'class' => 'calendar_color pick_color auto',
		'maxlength' => '7',
		'size' => '7',
		);
?>
 <fieldset class="inlineLabels">
  <div class="ctrlHolder">
  <label for="displayname"><?php echo $this->i18n->_('labels',
		   'displayname') ?></label>
	<?php echo form_input($form_displayname);?>
  </div>
  <div class="ctrlHolder">
   <label for="internal"><?php echo $this->i18n->_('labels',
		   'internalname')?> 
   <?php echo $this->i18n->_('labels', 'optional')?></label>
	<?php echo form_input($form_internal);?>
  </div>

   
  <div class="ctrlHolder">
   <label for="calendar_color"><?php echo
   $this->i18n->_('labels', 'color')?></label>
	<?php echo form_input($form_color);?>
  </div>
   
<?php
echo form_close();
?>
</fieldset>
</div>
