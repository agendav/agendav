<table id="preferences_calendar_manager" class="table table-striped">
<thead>
 <th><?php echo $this->i18n->_('labels', 'calendar')?></th>
 <th><?php echo $this->i18n->_('labels', 'hidelist')?></th>
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
  <td>
    <input type="hidden" name="calendar[<?php echo $i ?>][name]" value="<?php echo
	$c?>" />
    <input type="checkbox" name="calendar[<?php echo $i ?>][hide]" value="">
  </td>
 </tr>
<?php
$i++;
endforeach;
?>
</tbody>
</table>
