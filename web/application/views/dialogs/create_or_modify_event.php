<div id="com_event_dialog">
<div id="com_event_dialog_tabs">
<?php
/**
  allday can be false (on editing). Disable some fields
 */

if (isset($allday) && $allday === FALSE) {
	unset($allday);
}

/*
 * Recurrence rules
 */
if (isset($recurrence)) {
	$recurrence_type = $recurrence['FREQ'];
	if (isset($recurrence['COUNT'])) {
		$recurrence_count = $recurrence['COUNT'];
	} elseif (isset($recurrence['UNTIL'])) {
		$recurrence_until = $recurrence['UNTIL'];
	}
}

$data_form = array(
	'id' => 'com_form'
);
echo form_open('caldav2json/com_event', $data_form);

// Define all form fields
$form_summary = array(
		'name' => 'summary',
		'value' => (isset($summary) && $summary !== FALSE) ? $summary : '',
		'class' => 'summary required',
		'maxlength' => '255',
		'size' => '25',
		);

$form_location = array(
		'name' => 'location',
		'value' => (isset($location) && $location !== FALSE) ? $location : '',
		'class' => 'location',
		'maxlength' => '255',
		'size' => '25',
		);

$form_calendar = $calendars;

$form_startdate = array(
		'name' => 'start_date',
		'value' => $start_date,
		'class' => 'start_date required',
		'maxlength' => '10',
		'size' => '15',
		);

$form_enddate = array(
		'name' => 'end_date',
		'value' => $end_date,
		'class' => 'end_date',
		'maxlength' => '10',
		'size' => '15',
		);

$form_starttime = array(
		'name' => 'start_time',
		'class' => 'time start_time',
		'maxlength' => '10',
		'value' => $start_time,
		'size' => '15',
		);

$form_endtime = array(
		'name' => 'end_time',
		'class' => 'time end_time',
		'maxlength' => '10',
		'value' => $end_time,
		'size' => '15',
		);

$form_allday = array(
		'name' => 'allday',
		'value' => 'true', // Value used if checkbox is marked
		'class' => 'allday',
		'checked' => isset($allday),
		);

$form_description = array(
		'name' => 'description',
		'class' => 'description',
		'rows' => '4',
		'cols' => '25',
		'value' => (isset($description) && $description !== FALSE) ? $description : '',
		);

$form_recurrence_type = array(
		'none' => 'No',
		'DAILY' => 'Diariamente',
		'WEEKLY' => 'Semanalmente',
		'MONTHLY' => 'Mensualmente',
		'YEARLY' => 'Anualmente',
		);


$form_recurrence_count = array(
		'name' => 'recurrence_count',
		'value' => (isset($recurrence_count) && $recurrence_count !== FALSE)
			? $recurrence_count : '',
		'class' => 'recurrence_count',
		'maxlength' => '20',
		'size' => '3',
		);
$form_recurrence_until = array(
		'name' => 'recurrence_until',
		'value' => (isset($recurrence_until) && $recurrence_until !== FALSE)
			? $recurrence_until : '',
		'class' => 'recurrence_until',
		'maxlength' => '10',
		'size' => '15',
		);

if (isset($recurrence_type) && $recurrence_type == 'none') {
	$recurrence_count['disabled'] = 'disabled';
	$recurrence_until['disabled'] = 'disabled';
}

$form_class = array(
		'PUBLIC' => 'Público',
		'PRIVATE' => 'Privado',
		'CONFIDENTIAL' => 'Confidencial',
		);

$form_transp = array(
		'OPAQUE' => 'Ocupado',
		'TRANSPARENT' => 'Libre',
		);

?>
<ul>
 <li><a href="#tabs-general">General</a></li>
 <li><a href="#tabs-recurrence">Repetición</a></li>
 <li><a href="#tabs-workgroup">Trabajo en grupo</a></li>
</ul>
<div id="tabs-general">
<?php if (isset($modification) && $modification === TRUE): ?>
 <input type="hidden" name="modification" class="modification" value="true" />
<?php endif; ?>
 <input type="hidden" name="uid" class="uid"
 	value="<?php echo isset($uid) ? $uid : '' ?>" />
 <input type="hidden" name="href" class="href"
 	value="<?php echo isset($href) ? $href : ''; ?>" />
 <input type="hidden" name="etag" class="etag"
 	value="<?php echo isset($etag) ? $etag : ''; ?>" />
  <table>
   <tr>
    <td><label for="summary">Título:</label></td>
	<td><?php echo form_input($form_summary);?></td>
   </tr>
   <tr>
    <td><label for="location">Lugar:</label></td>
	<td><?php echo form_input($form_location);?></td>
   </tr>
   <tr>
    <td><label for="calendar">Calendario:</label></td>
	<?php echo form_hidden('original_calendar', $calendar)?>
	<td><?php echo form_dropdown('calendar', $form_calendar,
			$calendar)?></td>
   </tr>

   <tr>
    <td><label for="start_date">Fecha de inicio:</label></td>
	<td><?php echo form_input($form_startdate);?> <?php echo
	form_input($form_starttime)?></td>
   </tr>

   <tr>
    <td><label for="end_date">Fecha de finalización:</label></td>
	<td><?php echo form_input($form_enddate);?> <?php echo
	form_input($form_endtime)?></td>
   </tr>

   <tr>
    <td><label for="allday">Día completo:</label></td>
	<td><?php echo form_checkbox($form_allday);?></td>
   </tr>

   <tr>
    <td><label for="description">Descripción:</label></td>
	<td><?php echo form_textarea($form_description);?></td>
   </tr>
  </table>
 </div>
 <div id="tabs-recurrence">
  <table>
   <tr>
   <?php if (!isset($unparseable_rrule)): ?>
    <td><label for="recurrence_type">Repetir evento:</label></td>
	<td><?php echo form_dropdown('recurrence_type',
			$form_recurrence_type, (isset($recurrence_type) ?
				$recurrence_type : 'none'),
			'class="recurrence_type"');?></td>
   <tr>
    <td><label class="suboption" for="recurrence_count">Repeticiones:</label></td>
	<td><?php echo form_input($form_recurrence_count)?></td>
   </tr>
   <tr>
    <td><label class="suboption" for="recurrence_until">Repetir hasta:</label></td>
	<td><?php echo form_input($form_recurrence_until)?></td>
   </tr>
   <?php else: ?>
    <td><label for="recurrence_type">Repetir evento:</label></td>
	<td>
	 <input type="hidden" name="unparseable_rrule" value="true" />
	 Definió la siguiente regla de repetición fuera de AgenDAV: 
	 <pre><?php echo $rrule_raw?></pre>
	 Para modificarla utilice un cliente de escritorio.
	</td>
   <?php endif; ?>
   </tr>
  </table>
 </div>
 <div id="tabs-workgroup">
  <table>
   <tr>
    <td><label for="class">Privacidad:</label></td>
	<td><?php echo form_dropdown('class', 
			$form_class, (isset($class) ? $class : 'PUBLIC'))?></td>
   <tr>
    <td><label for="transp">Consideración del tiempo:</label></td>
	<td><?php echo form_dropdown('transp', 
			$form_transp, (isset($transp) ? $transp : 'OPAQUE'))?></td>
   </tr>
  </table>
 </div>
<?php echo form_close() ?>
</div>
