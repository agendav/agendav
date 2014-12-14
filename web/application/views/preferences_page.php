<h1><?php echo $this->i18n->_('labels', 'preferences')?></h1>

<div class="preferences-container">
<?php

echo form_open('prefs/save', array('id' => 'prefs_form'));

?>
<div class="form-group">
<label for="timezone"><?php echo $this->i18n->_('labels', 'timezone') ?></label>
<?php
echo form_dropdown('timezone', $available_timezones, $timezone, 'class="form-control"');
?>

    <span class="help-block"><?php echo $this->i18n->_('messages', 'help_timezone')?></span>
</div>
<?php


$this->load->view('preferences_calendars', array(
    'calendar_list' => $calendar_list,
    'calendar_ids_and_dn' => $calendar_ids_and_dn,
    'default_calendar' => $default_calendar,
    'hidden_calendars' => $hidden_calendars));

echo form_close();
?>

<div id="prefs_buttons">
<button id="save_button" class="btn btn-success"><?php echo $this->i18n->_('labels', 'save')?></button>
<a href="<?php echo base_url()?>" id="return_button" class="btn btn-default"><i class="fa fa-calendar"></i> <?php echo $this->i18n->_('labels', 'return')?></a>
</div>

</div>
