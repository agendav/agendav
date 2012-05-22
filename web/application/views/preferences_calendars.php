<?php
echo formelement(
		$this->i18n->_('labels', 'defaultcalendar'),
		form_dropdown('default_calendar', $calendar_ids_and_dn,
			$default_calendar,
			'class="medium"'));

?>
<table id="preferences_calendar_manager" class="table table-striped">
<thead>
 <th><?php echo $this->i18n->_('labels', 'calendar')?></th>
 <th style="text-align: center"><?php echo $this->i18n->_('labels', 'hidelist')?></th>
 <th></th>
</thead>
<tbody>
<?php
$i=1;
foreach ($calendar_list as $c => $data):
?>
 <tr>
  <td>
  <?php echo $data['displayname'] ?>
  </td>
  <td style="text-align: center">
    <input type="hidden" name="calendar[<?php echo $i ?>][name]" value="<?php echo
	$c?>" />
    <input type="checkbox" name="calendar[<?php echo $i ?>][hide]"
	value="1"<?php echo (isset($hidden_calendars[$c])) ? ' checked="checked"' : ''?>>
  </td>
 </tr>
<?php
$i++;
endforeach;
?>
</tbody>
</table>
