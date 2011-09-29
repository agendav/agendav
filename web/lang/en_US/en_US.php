<?php

$labels = array();
$messages = array();

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
$labels['choose_date'] = 'Choose date';

$labels['calendar'] = 'Calendar:';
$labels['location'] = 'Location:';
$labels['description'] = 'Description:';

$labels['displayname'] = 'Display name:';
$labels['internalname'] = 'Internal name:';
$labels['optional'] = '(optional)';
$labels['color'] = 'Color:';

$labels['summary'] = 'Summary:';
$labels['startdate'] = 'Start date:';
$labels['enddate'] = 'End date:';
$labels['alldayform'] = 'All day:';

$labels['repetitionexceptions'] = 'Exceptions to recurrent events';

$labels['repeat'] = 'Repeat:';
$labels['repeatno'] = 'No repetitions';
$labels['repeatdaily'] = 'Daily';
$labels['repeatweekly'] = 'Weekly';
$labels['repeatmonthly'] = 'Monthly';
$labels['repeatyearly'] = 'Yearly';

$labels['repeatcount'] = 'Count:';
$labels['repeatuntil'] = 'Until:';

$labels['privacylabel'] = 'Privacy:';
$labels['public'] = 'Public';
$labels['private'] = 'Private';
$labels['confidential'] = 'Confidential';

$labels['transp'] = 'Show this time as:';
$labels['opaque'] = 'Busy';
$labels['transparent'] = 'Free';

$labels['generaloptions'] = 'General options';
$labels['repeatoptions'] = 'Repeat';
$labels['workgroupoptions'] = 'Workgroup';
$labels['shareoptions'] = 'Share';

$labels['newcalendar'] = 'New calendar';
$labels['modifycalendar'] = 'Modify calendar';

$labels['createevent'] = 'Create event';
$labels['editevent'] = 'Edit event';
$labels['deleteevent'] = 'Delete event';
$labels['deletecalendar'] = 'Delete calendar';
$labels['calendars'] = 'Calendars';
$labels['refresh'] = 'Refresh';
$labels['delete'] = 'Delete';
$labels['close'] = 'Close';
$labels['save'] = 'Save';
$labels['create'] = 'Create';
$labels['login'] = 'Log in';
$labels['modify'] = 'Modify';
$labels['cancel'] = 'Cancel';
$labels['next'] = 'next';
$labels['previous'] = 'previous';
$labels['yes'] = 'Yes';

$labels['sharewith'] = 'Share with:';
$labels['publicurl'] = 'URL for calendaring desktop applications:';

// Messages
$messages['error_auth'] = 'Invalid username or password';
$messages['error_invaliddate'] = 'Invalid date on field %s';
$messages['error_invalidtime'] = 'Invalid time on field %s';
$messages['error_denied'] = 'Server refused your request (permission forbidden)';
$messages['error_notimplemented'] = '%feature: still not implemented';
$messages['error_startgreaterend'] = 'End date must be greater than or equal to start date';
$messages['error_bogusrepeatrule'] = 'Error, check your recurrence parameters';
$messages['error_internalgen'] = 'Internal calendar generation error';
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
$messages['session_expired'] = 'Your session has expired';
$messages['login_again'] = 'Please, log in again';

$messages['modification_failed'] = 'Modification failed';
$messages['error_loading_events'] = 'Error loading events from calendar %cal';
$messages['error_refreshing_session'] = 'Error refreshing your session';
$messages['internal_error'] = 'Internal error';
$messages['error_generating_form'] = 'Error generating form';
$messages['invalid_data'] = 'Invalid data';
$messages['error_delete_calendar'] = 'Error deleting calendar';

$messages['overlay_synchronizing'] = 'Sinchronyzing events...';
$messages['overlay_loading_dialog'] = 'Loading dialog...';
$messages['overlay_sending_form'] = 'Sending form...';
$messages['overlay_loading_calendar_list'] = 'Loading calendar list...';
$messages['error_loading_dialog'] = 'Error loading dialog';

$messages['error_oops'] = 'Oops. Unexpected error';
$messages['error_interfacefailure'] = 'Interface error';
$messages['error_current_event_not_loaded'] = 'Current event is not available';

$messages['error_event_not_deleted'] = 'Error deleting event';
$messages['error_loading_calendar_list'] = 'Error reading calendar list';
$messages['notice_no_calendars'] = 'No calendars available';
$messages['info_repetition_human'] = 'This event repeats %explanation';
$messages['info_repetition_unparseable'] = 'This event has recurrence rules associated that this program cannot understand. Raw definition:';
$messages['error_calendarnotfound'] = 'Invalid calendar %calendar';
$messages['error_eventnotfound'] = 'Event not found';
$messages['error_eventchanged'] = 'Element was modified while you were editing it. Please, refresh.';
$messages['error_unknownhttpcode'] = 'Unknown error, HTTP code=%res';
$messages['error_internalcalnamemissing'] = 'Empty internal calendar name';
$messages['error_calname_missing'] = 'Empty calendar name';
$messages['error_calcolor_missing'] = 'Color must be supplied';
$messages['error_mkcalendar'] = 'Server refused to create calendar. Please, check your creation parameters';
$messages['error_shareunknownusers'] = 'Some of the users you specified do notexist';
