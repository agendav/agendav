<h2><?php echo $this->i18n->_('labels', 'calendars')?></h2>
<table id="preferences_calendar_manager" class="table table-striped">
<thead>
 <th><?php echo $this->i18n->_('labels', 'calendar')?></th>
 <th style="text-align: center"><?php echo $this->i18n->_('labels', 'hidelist')?></th>
 <th></th>
</thead>
<tbody>
<?php
foreach ($calendar_list as $cal):
?>
 <tr>
  <td>
  <?php echo $cal->getProperty(AgenDAV\CalDAV\Resource\Calendar::DISPLAYNAME) ?>
  </td>
  <td style="text-align: center">
    <input type="hidden" name="calendar[][name]" value="<?php echo
	$cal->getUrl() ?>" />
    <input type="checkbox" name="calendar[][hide]"
	value="1"<?php echo (isset($hidden_calendars[$cal->getUrl()])) ? ' checked="checked"' : ''?>>
  </td>
 </tr>
<?php
endforeach;
?>
</tbody>
</table>

<div class="form-group">
<label for="default_calendar"><?php echo $this->i18n->_('labels', 'defaultcalendar') ?></label>
<?php 
echo form_dropdown('default_calendar', $calendar_ids_and_dn, $default_calendar, 'class="form-control"');
?>

    <span class="help-block"><?php echo $this->i18n->_('messages', 'help_defaultcalendar')?></span>
</div>


