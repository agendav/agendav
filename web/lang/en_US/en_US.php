<?php

$labels = array();
$messages = array();
$js_messages = array();

// Labels

$labels['username'] = 'User name';
$labels['password'] = 'Password';
$labels['months_long'] = array('January', 'February', 'March', 'April',
		'May', 'June', 'July', 'August', 'September', 'October', 'November',
		'December');
$labels['months_short'] = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
		'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$labels['daynames_long'] = array('Monday', 'Tuesday', 'Wednesday',
		'Thursday', 'Friday', 'Saturday', 'Sunday');
$labels['daynames_short'] = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
		'Sun');
$labels['today'] = 'Today';
$labels['month'] = 'month';
$labels['week'] = 'week';
$labels['day'] = 'day';
$labels['allday'] = 'all day';
$labels['synchronizing'] = 'Sinchronyzing events...';
$labels['loading_dialog'] = 'Loading dialog...';
$labels['sending_form'] = 'Sending form...';
$labels['error_loading_dialog'] = 'Error loading dialog';
$labels['oops'] = 'Oops. Unexpected error';
$labels['choose_date'] = 'Choose date';
$labels['interface_error'] = 'Interface error';
$labels['current_event_not_loaded'] = 'Current event is not available';
$labels['event_deleted'] = 'Event deleted';
$labels['event_not_deleted'] = 'Error deleting event';
$labels['error_loading_calendar_list'] = 'Error reading calendar list';
$labels['no_calendars'] = 'No calendars available';

$labels['calendar_label'] = 'Calendar:';
$labels['location_label'] = 'Location:';
$labels['description_label'] = 'Description:';
$labels['rrule_label'] = 'Recurrence:';
$labels['rrule_template'] = 'This event repeats %explanation';
$labels['rrule_unparseable'] = 'This event has recurrence rules associated that this program cannot understand. Raw definition:';

$labels['displayname_label'] = 'Display name:';
$labels['internal_name_label'] = 'Internal name:';
$labels['optional'] = '(optional)';
$labels['color_label'] = 'Color:';

$labels['summary_label'] = 'Summary:';
$labels['start_date_label'] = 'Start date:';
$labels['end_date_label'] = 'End date:';
$labels['start_time_label'] = 'Start time:';
$labels['end_time_label'] = 'End time:';
$labels['all_day_label'] = 'All day:';

$labels['exceptions_to_recurrent_events'] = 'Exceptions to recurrent events';

$labels['repeat_label'] = 'Repeat:';
$labels['recurrence_no'] = 'No repetitions';
$labels['recurrence_daily'] = 'Daily';
$labels['recurrence_weekly'] = 'Weekly';
$labels['recurrence_monthly'] = 'Monthly';
$labels['recurrence_yearly'] = 'Yearly';

$labels['repeat_count_label'] = 'Count:';
$labels['repeat_until_label'] = 'Until:';

$labels['privacy_label'] = 'Privacy';
$labels['privacy_public'] = 'Public';
$labels['privacy_private'] = 'Private';
$labels['privacy_confidential'] = 'Confidential';

$labels['transp_label'] = 'Show this time as:';
$labels['transp_opaque'] = 'Busy';
$labels['transp_transparent'] = 'Free';

$labels['general_options'] = 'General options';
$labels['repeat_options'] = 'Repeat';
$labels['workgroup_options'] = 'Workgroup';
$labels['share_options'] = 'Share';

$labels['new_calendar'] = 'New calendar';

$labels['create_event'] = 'Create event';
$labels['calendars'] = 'Calendars';
$labels['refresh'] = 'Refresh';
$labels['delete'] = 'Delete';
$labels['close'] = 'Close';
$labels['save'] = 'Save';
$labels['create'] = 'Create';
$labels['login'] = 'Login';
$labels['modify'] = 'Modify';
$labels['cancel'] = 'Cancel';
$labels['next'] = 'next';
$labels['previous'] = 'previous';
$labels['yes'] = 'Yes';

$labels['shared_by'] = 'shared by %user';
$labels['share_with_label'] = 'Share with:';
$labels['invalid_calendar'] = 'Invalid calendar %calendar';
$labels['public_caldav_url'] = 'URL for calendaring desktop applications:';

$labels['create_new_calendar'] = 'Create new calendar';

// Messages
$messages['bad_login'] = 'Invalid username or password';
$messages['invalid_date'] = 'Invalid date';
$messages['invalid_time'] = 'Invalid time';
$messages['element_not_found'] = 'Element not found';
$messages['element_modified'] = 'Element was modified while you were editing it. Please, refresh.';
$messages['unknown_http_code'] = 'Unknown error, HTTP code=%res';
$messages['no_internal_calendar_name'] = 'Empty internal calendar name';
$messages['no_calendar_name'] = 'Empty calendar name';
$messages['no_calendar_color'] = 'Color must be supplied';
$messages['mkcalendar_invalid_params'] = 'Server refused to create calendar. Please, check your creation parameters';
$messages['server_forbidden'] = 'Server refused your request (permission forbidden)';
$messages['setacl_invalid_xml'] = 'Internal error when generating permissions';
$messages['setacl_invalid_users'] = 'Some of the users you specified do notexist';
$messages['invalid_dialog_call'] = 'Interface error: invalid call to %func';
$messages['not_implemented'] = '%feature: still not implemented';
$messages['rrule_missing'] = 'Internal error accesing recurrence rule';
$messages['start_greater_than_end'] = 'End date must be greater than or equal to start date';
$messages['invalid_rrule'] = 'Check your recurrence parameters';
$messages['invalid_calendar'] = 'You have no access to calendar %calendar or it does not exist';
$messages['internal_generation_error'] = 'Internal calendar generation error';
$messages['internal_name_in_use'] = 'Internal calendar name already being used';
$messages['calendar_created'] = 'Successful calendar creation';
$messages['calendar_deleted'] = 'Successful calendar delete';
$messages['calendar_modified'] = 'Successful calendar modification';

$messages['confirm_calendar_delete'] = 'Are you sure you want to delete the following calendar?';
$messages['confirm_event_delete_from_calendar'] = 'Are you sure you want to delete the following event?';
$messages['permanent_removal_warning'] = 'Your information will be permanently lost';
$messages['recurrent_delete_all'] = 'Every repetition of this event will be deleted';
$messages['shared_with_you'] = 'You have access to this calendar because %user shared it with you';
$messages['share_with_explanation'] = 'You can share this calendar with
other users and let them modify it. Place their usernames below, separated
by commas or spaces';

// JS messages
$js_messages['modification_failed'] = 'Modification failed';
$js_messages['calendar_created'] = $messages['calendar_created'];
$js_messages['error_loading_events'] = 'Error loading events from calendar %cal';
$js_messages['error_refreshing_session'] = 'Error refreshing your session';
