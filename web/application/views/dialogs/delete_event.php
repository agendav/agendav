<div id="delete_event_dialog">
 <p>¿Confirma que desea borrar el siguiente evento del calendario <span
 class="calendar"></span>?</p>

 <p class="title">
 </p>

 <div class="rrule">
 <p>Tenga en cuenta que al ser un evento recurrente se borrarán todas las repeticiones del
 mismo</p>
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
