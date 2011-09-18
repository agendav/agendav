<div id="delete_calendar_dialog">
<?php
$data_form = array(
		'id' => 'delete_calendar_form',
		);
echo form_open('caldav2json/delete_calendar', $data_form);
?>
 <input type="hidden" name="calendar" value="<?php echo $calendar?>" />
 <p>¿Confirma que desea borrar el siguiente calendario?</p>

 <p class="title">
  <?php echo $displayname;?>
 </p>

 <p>Tenga en cuenta que <strong>toda</strong> la información asociada al mismo quedará
 eliminada.</p>

<?php
echo form_close();
?>
</div>
