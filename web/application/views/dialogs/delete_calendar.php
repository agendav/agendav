<div id="delete_calendar_dialog">
<?php
$data_form = array(
		'id' => 'delete_calendar_form',
		);
echo form_open('caldav2json/delete_calendar', $data_form);
?>
 <input type="hidden" name="calendar" value="<?php echo $calendar?>" />
<p><?php echo $this->i18n->_('messages', 'info_confirmcaldelete')?></p>

 <p class="title">
  <?php echo $displayname;?>
 </p>

 <p><?php echo $this->i18n->_('messages', 'info_permanentremoval')?></p>

<?php
echo form_close();
?>
</div>
