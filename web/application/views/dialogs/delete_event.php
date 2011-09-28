<div id="delete_event_dialog">
 <p><?php echo $this->i18n->_('messages', 'confirm_event_delete')?></p>

 <p class="title">
 </p>

 <div class="rrule">
 <p><?php echo $this->i18n->_('messages', 'recurrent_delete_all')?></p>
 </div>

 
<?php
$data_form = array(
	'id' => 'delete_form'
);

// CSRF implies using form_open()
echo form_open('caldav2json/delete_event', $data_form);
?>
<input type="hidden" name="uid" class="uid" value="" />
<input type="hidden" name="calendar" class="calendar" value="" />
<input type="hidden" name="href" class="href" value="" />
<input type="hidden" name="etag" class="etag" value="" />
<?php
echo form_close();
?>
</div>
