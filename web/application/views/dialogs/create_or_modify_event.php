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
		'none' => $this->i18n->_('labels', 'recurrence_no'),
		'DAILY' => $this->i18n->_('labels', 'recurrence_daily'),
		'WEEKLY' => $this->i18n->_('labels', 'recurrence_weekly'),
		'MONTHLY' => $this->i18n->_('labels', 'recurrence_monthly'),
		'YEARLY' => $this->i18n->_('labels', 'recurrence_yearly'),
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
		'PUBLIC' => $this->i18n->_('labels', 'privacy_public'),
		'PRIVATE' => $this->i18n->_('labels', 'privacy_private'),
		'CONFIDENTIAL' => $this->i18n->_('labels', 'privacy_confidential'),
		);

$form_transp = array(
		'OPAQUE' => $this->i18n->_('labels', 'transp_opaque'),
		'TRANSPARENT' => $this->i18n->_('labels', 'transp_transparent'),
		);

?>
<ul>
 <li><a href="#tabs-general"><?php echo $this->i18n->_('labels',
		 'general_options')?></a></li>
 <li><a href="#tabs-recurrence"><?php echo $this->i18n->_('labels',
		 'repeat_options')?></a></li>
 <li><a href="#tabs-workgroup"><?php echo $this->i18n->_('labels',
		 'workgroup_options')?></a></li>
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
    <td><label for="summary"><?php echo $this->i18n->_('labels',
			'summary_label')?></label></td>
	<td><?php echo form_input($form_summary);?></td>
   </tr>
   <tr>
    <td><label for="location"><?php echo $this->i18n->_('labels',
			'location_label')?></label></td>
	<td><?php echo form_input($form_location);?></td>
   </tr>
   <tr>
    <td><label for="calendar"><?php echo $this->i18n->_('labels',
			'calendar_label')?></label></td>
	<?php echo form_hidden('original_calendar', $calendar)?>
	<td><?php echo form_dropdown('calendar', $form_calendar,
			$calendar)?></td>
   </tr>

   <tr>
    <td><label for="start_date"><?php echo $this->i18n->_('labels',
			'start_date_label')?></label></td>
	<td><?php echo form_input($form_startdate);?> <?php echo
	form_input($form_starttime)?></td>
   </tr>

   <tr>
    <td><label for="end_date"><?php echo $this->i18n->_('labels',
			'end_date_label')?></label></td>
	<td><?php echo form_input($form_enddate);?> <?php echo
	form_input($form_endtime)?></td>
   </tr>

   <tr>
    <td><label for="allday"><?php echo $this->i18n->_('labels',
			'all_day_label')?></label></td>
	<td><?php echo form_checkbox($form_allday);?></td>
   </tr>

   <tr>
    <td><label for="description"><?php echo $this->i18n->_('labels',
			'description_label')?></label></td>
	<td><?php echo form_textarea($form_description);?></td>
   </tr>
  </table>
 </div>
 <div id="tabs-recurrence">
  <table>
   <tr>
    <td><label for="recurrence_type"><?php echo $this->i18n->_('labels',
			'repeat_label')?></label></td>
   <?php if (!isset($unparseable_rrule)): ?>
	<td><?php echo form_dropdown('recurrence_type',
			$form_recurrence_type, (isset($recurrence_type) ?
				$recurrence_type : 'none'),
			'class="recurrence_type"');?></td>
   <tr>
    <td><label class="suboption" for="recurrence_count"><?php echo
	$this->i18n->_('labels', 'repeat_count_label')?></label></td>
	<td><?php echo form_input($form_recurrence_count)?></td>
   </tr>
   <tr>
    <td><label class="suboption" for="recurrence_until"><?php echo
	$this->i18n->_('labels', 'repeat_until_label')?></label></td>
	<td><?php echo form_input($form_recurrence_until)?></td>
   </tr>
   <?php else: ?>
	<td>
	 <input type="hidden" name="unparseable_rrule" value="true" />
	 <?php echo $this->i18n->_('labels', 'rrule_unparseable')?>
	 <pre><?php echo $rrule_raw?></pre>
	</td>
   <?php endif; ?>
   </tr>
  </table>
 </div>
 <div id="tabs-workgroup">
  <table>
   <tr>
    <td><label for="class"><?php echo $this->i18n->_('labels',
			'privacy_label')?></label></td>
	<td><?php echo form_dropdown('class', 
			$form_class, (isset($class) ? $class : 'PUBLIC'))?></td>
   <tr>
    <td><label for="transp"><?php echo $this->i18n->_('labels',
			'transp_label')?></label></td>
	<td><?php echo form_dropdown('transp', 
			$form_transp, (isset($transp) ? $transp : 'OPAQUE'))?></td>
   </tr>
  </table>
 </div>
<?php echo form_close() ?>
</div>
