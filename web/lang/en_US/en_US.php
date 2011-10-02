<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/*
 * Copyright 2011 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * English master language file
 */

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
$labels['daynames_long'] = array('Sunday', 'Monday', 'Tuesday', 'Wednesday',
		'Thursday', 'Friday', 'Saturday');
$labels['daynames_short'] = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri',
		'Sat');

$labels['today'] = 'Today';
$labels['month'] = 'month';
$labels['week'] = 'week';
$labels['day'] = 'day';
$labels['allday'] = 'all day';
$labels['choose_date'] = 'Choose date';

$labels['calendar'] = 'Calendar';
$labels['location'] = 'Location';
$labels['description'] = 'Description';

$labels['displayname'] = 'Display name';
$labels['internalname'] = 'Internal name';
$labels['optional'] = '(optional)';
$labels['color'] = 'Color';

$labels['summary'] = 'Summary';
$labels['startdate'] = 'Start date';
$labels['enddate'] = 'End date';
$labels['starttime'] = 'Start time';
$labels['endtime'] = 'End time';
$labels['alldayform'] = 'All day';

$labels['repetitionexceptions'] = 'Exceptions to recurrent events';

$labels['repeat'] = 'Repeat';
$labels['repeatno'] = 'No repetitions';
$labels['repeatdaily'] = 'Daily';
$labels['repeatweekly'] = 'Weekly';
$labels['repeatmonthly'] = 'Monthly';
$labels['repeatyearly'] = 'Yearly';

$labels['repeatcount'] = 'Count';
$labels['repeatuntil'] = 'Until';

$labels['explntimes'] = '%n times';
$labels['expluntil'] = 'until %d';

$labels['privacylabel'] = 'Privacy';
$labels['public'] = 'Public';
$labels['private'] = 'Private';
$labels['confidential'] = 'Confidential';

$labels['transp'] = 'Show this time as';
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

$labels['sharewith'] = 'Share with';
$labels['publicurl'] = 'URL for calendaring desktop applications';

// Messages
$messages['error_auth'] = 'Invalid username or password';
$messages['error_invaliddate'] = 'Invalid date on field %s';
$messages['error_invalidtime'] = 'Invalid time on field %s';
$messages['error_denied'] = 'Server refused your request (permission forbidden)';
$messages['error_notimplemented'] = '%feature: still not implemented';
$messages['error_startgreaterend'] = 'End date must be greater than or equal to start date';
$messages['error_bogusrepeatrule'] = 'Error, check your recurrence parameters';
$messages['error_internalgen'] = 'Internal calendar generation error';
$messages['error_internalcalnameinuse'] = 'Internal calendar name already being used';

$messages['info_confirmcaldelete'] = 'Are you sure you want to delete the following calendar?';
$messages['info_confirmeventdelete'] = 'Are you sure you want to delete the following event?';
$messages['info_permanentremoval'] = 'Your information will be permanently lost';
$messages['info_repetitivedeleteall'] = 'All repetitive instances of this event will be deleted';
$messages['info_sharedby'] = 'You have access to this calendar because %user shared it with you';
$messages['info_shareexplanation'] = 'You can share this calendar with
other users and let them modify it. Place their usernames below, separated
by commas or spaces';
$messages['error_sessexpired'] = 'Your session has expired';
$messages['error_loginagain'] = 'Please, log in again';

$messages['error_modfailed'] = 'Modification failed';
$messages['error_loadevents'] = 'Error loading events from calendar %cal';
$messages['error_sessrefresh'] = 'Error refreshing your session';
$messages['error_internal'] = 'Internal error';
$messages['error_genform'] = 'Error generating form';
$messages['error_invalidinput'] = 'Invalid value';
$messages['error_caldelete'] = 'Error deleting calendar';

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
$messages['error_eventnotfound'] = 'Element not found';
$messages['error_eventchanged'] = 'Element was modified while you were editing it. Please, refresh.';
$messages['error_unknownhttpcode'] = 'Unknown error, HTTP code=%res';
$messages['error_internalcalnamemissing'] = 'Empty internal calendar name';
$messages['error_calname_missing'] = 'Empty calendar name';
$messages['error_calcolor_missing'] = 'Color must be supplied';
$messages['error_mkcalendar'] = 'Server refused to create calendar. Please, check your creation parameters';
$messages['error_shareunknownusers'] = 'Some of the users you specified do not exist';
