<?php
/*
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

$translations = array(
    'labels.username' => 'User name',
    'labels.password' => 'Password',

    'labels.january' => 'January',
    'labels.february' => 'February',
    'labels.march' => 'March',
    'labels.april' => 'April',
    'labels.may' => 'May',
    'labels.june' => 'June',
    'labels.july' => 'July',
    'labels.august' => 'August',
    'labels.september' => 'September',
    'labels.october' => 'October',
    'labels.november' => 'November',
    'labels.december' => 'December',

    'labels.sunday' => 'Sunday',
    'labels.monday' => 'Monday',
    'labels.tuesday' => 'Tuesday',
    'labels.wednesday' => 'Wednesday',
    'labels.thursday' => 'Thursday',
    'labels.friday' => 'Friday',
    'labels.saturday' => 'Saturday',
    'labels.sunday' => 'Sunday',

    'labels.sunday_short' => 'Sun',
    'labels.monday_short' => 'Mon',
    'labels.tuesday_short' => 'Tue',
    'labels.wednesday_short' => 'Wed',
    'labels.thursday_short' => 'Thu',
    'labels.friday_short' => 'Fri',
    'labels.saturday_short' => 'Sat',


    'labels.calendar' => 'Calendar',
    'labels.location' => 'Location',
    'labels.description' => 'Description',

    'labels.displayname' => 'Display name',
    'labels.color' => 'Color',

    'labels.summary' => 'Summary',
    'labels.startdate' => 'Start date',
    'labels.enddate' => 'End date',
    'labels.starttime' => 'Start time',
    'labels.endtime' => 'End time',
    'labels.alldayform' => 'All day',
    'labels.choose_date' => 'Choose a date',

    'labels.repetitionexceptions' => 'Exceptions to recurrent events',

    'labels.repeat' => 'Repeat',
    'labels.repeatno' => 'No repetitions',
    'labels.repeatdaily' => 'Daily',
    'labels.repeatweekly' => 'Weekly',
    'labels.repeatmonthly' => 'Monthly',
    'labels.repeatyearly' => 'Yearly',

    'labels.privacy' => 'Privacy',
    'labels.public' => 'Public',
    'labels.private' => 'Private',
    'labels.confidential' => 'Confidential',

    'labels.transp' => 'Show this time as',
    'labels.opaque' => 'Busy',
    'labels.transparent' => 'Free',

    'labels.generaloptions' => 'General options',
    'labels.repeatoptions' => 'Repeat',
    'labels.workgroupoptions' => 'Workgroup',
    'labels.shareoptions' => 'Share',

    'labels.newcalendar' => 'New calendar',
    'labels.modifycalendar' => 'Modify calendar',
    'labels.deletecalendar' => 'Delete calendar',

    'labels.createevent' => 'Create event',
    'labels.editevent' => 'Edit event',
    'labels.deleteevent' => 'Delete event',

    'labels.calendars' => 'Calendars',
    'labels.shared_calendars' => 'Shared calendars',
    'labels.refresh' => 'Refresh',
    'labels.delete' => 'Delete',
    'labels.add' => 'Add',
    'labels.close' => 'Close',
    'labels.save' => 'Save',
    'labels.create' => 'Create',
    'labels.login' => 'Log in',
    'labels.logout' => 'Log out',
    'labels.modify' => 'Modify',
    'labels.cancel' => 'Cancel',
    'labels.yes' => 'Yes',

    'labels.delete_only_this_repetition' => 'Delete only this one',
    'labels.delete_all_repetitions' => 'Delete all repetitions',

    'labels.edit_only_this_repetition' => 'Edit only this one',
    'labels.edit_all_repetitions' => 'Edit all repetitions',

    'labels.untitled' => 'Untitled',

    'labels.sharewith' => 'Share with',
    'labels.currentlysharing' => 'Currently sharing this calendar',
    'labels.publicurl' => 'Calendar URL for CalDAV clients',

    'labels.access' => 'Access',
    'labels.readonly' => 'Read only',
    'labels.readandwrite' => 'Read and write',

    'labels.preferences' => 'Preferences',
    'labels.return' => 'Return',

    'labels.hidelist' => 'Hide from list',
    'labels.defaultcalendar' => 'Default calendar',

    'labels.toggleallcalendars' => 'Show/hide all',

    'labels.minutes' => 'minutes',
    'labels.hours' => 'hours',
    'labels.days' => 'days',
    'labels.weeks' => 'weeks',
    'labels.months' => 'months',

    'labels.remindersoptions' => 'Reminders',
    'labels.reminder' => 'Reminder',

    'labels.add_reminder' => 'Add reminder',

    'labels.before_start' => 'before start',
    'labels.after' => 'After',

    'labels.start' => 'start',
    'labels.end' => 'end',

    'labels.ends' => 'Ends:',
    'labels.never' => 'Never',
    'labels.occurrences' => 'occurrences',

    'labels.timezone' => 'Timezone',
    'labels.every' => 'Every',
    'labels.repeat_by_day' => 'Repeat on',
    'labels.repeat_by_month_day' => 'Day of month',
    'labels.repeat_explanation' => 'This event repeats',
    'labels.keep_rrule' => 'Keep original repeat rule',

    'labels.language' => 'Language',
    'labels.date_format' => 'Date format',
    'labels.time_format' => 'Time format',
    'labels.weekstart' => 'Week starts on',


// Messages
    'messages.error_auth' => 'Invalid username or password',
    'messages.error_denied' => 'Server refused your request (permission forbidden)',
    'messages.error_notimplemented' => '%feature: still not implemented',
    'messages.error_startgreaterend' => 'End date must be greater than or equal to start date',
    'messages.error_internalgen' => 'Internal calendar generation error',

    'messages.info_confirmcaldelete' => 'Are you sure you want to delete the following calendar?',
    'messages.info_edit_recurrent_event' => 'This event repeats. Do you want to edit just this repetition or all repetitions?',
    'messages.info_base_event_with_exceptions_modification' => 'This recurrent event has one or more exceptions set. If you use the "Edit all repetitions" button, exceptions will be removed',
    'messages.info_delete_recurrent_event' => 'This event repeats. Do you want to remove just this repetition or the whole event?',
    'messages.info_delete_recurrent_event_first_instance' => 'This event repeats, and this is the first repetition. Deleting this event will cause all repetitions to be deleted. Are you sure?',
    'messages.info_permanentremoval' => 'Your information will be permanently lost',
    'messages.info_sharedby' => 'You have access to this calendar because %user shared it with you',
    'messages.info_shareexplanation' => 'You can share this calendar with other users and let them modify it. Place their usernames below, separated by commas or spaces',
    'messages.info_notshared' => 'This calendar is not being shared with anyone',
    'messages.info_noreminders' => 'This event has no configured reminders',
    'messages.error_sessexpired' => 'Your session has expired',
    'messages.error_loginagain' => 'Please, log in again',

    'messages.error_modfailed' => 'Modification failed',
    'messages.error_loadevents' => 'Error loading events from calendar %cal',
    'messages.error_sessrefresh' => 'Error refreshing your session',
    'messages.error_internal' => 'Internal error',
    'messages.error_genform' => 'Error generating form',
    'messages.error_invalidinput' => 'Invalid value',
    'messages.error_caldelete' => 'Error deleting calendar',

    'messages.overlay_synchronizing' => 'Synchronizing events...',
    'messages.overlay_loading_dialog' => 'Loading dialog...',
    'messages.overlay_sending_form' => 'Sending form...',
    'messages.overlay_loading_calendar_list' => 'Loading calendar list...',
    'messages.error_loading_dialog' => 'Error loading dialog',

    'messages.error_oops' => 'Oops. Unexpected error',
    'messages.error_interfacefailure' => 'Interface error',
    'messages.error_current_event_not_loaded' => 'Current event is not available',

    'messages.error_event_not_deleted' => 'Error deleting event',
    'messages.error_loading_calendar_list' => 'Error reading calendar list',
    'messages.notice_no_calendars' => 'No calendars available',
    'messages.info_repetition_human' => 'This event repeats %explanation',
    'messages.info_rrule_not_reproducible' => 'This event has recurrence rules associated that cannot be reproduced using this interface. You can keep it as is.',
    'messages.info_rrule_protected' => 'This event has its recurrence rule protected',
    'messages.error_calendarnotfound' => 'Invalid calendar %calendar',
    'messages.error_element_not_found' => 'Element not found',
    'messages.error_element_changed' => 'Element was modified while you were editing it. Please, refresh.',
    'messages.error_unexpectedhttpcode' => 'Received unexpected HTTP code %code% from server',
    'messages.error_calname_missing' => 'Empty calendar name',
    'messages.error_calcolor_missing' => 'Color must be supplied',
    'messages.error_shareunknownusers' => 'Some of the users you specified do not exist',
    'messages.error_empty_fields' => 'Some required fields are empty',

    'messages.help_defaultcalendar' => 'New events will be placed in this calendar by default. Set here your most used calendar',
    'messages.help_timezone' => 'Choose your current timezone. This will affect how you see existing events, and new events will be created using this timezone',

    'messages.info_prefssaved' => 'Preferences saved',

    'messages.more_events' => '+ %count events',

    'messages.info_reminders_no_effect_on_agendav' => 'Note: reminders will only have effect on CalDAV clients that load this calendar',

    'rrule.every' => 'every',
    'rrule.until' => 'until',
    'rrule.day' => 'day',
    'rrule.days' => 'days',
    'rrule.week' => 'week',
    'rrule.weeks' => 'weeks',
    'rrule.month' => 'month',
    'rrule.months' => 'months',
    'rrule.year' => 'year',
    'rrule.years' => 'years',
    'rrule.for' => 'for',
    'rrule.on' => 'on',
    'rrule.time' => 'time',
    'rrule.times' => 'times',
    'rrule.weekday' => 'weekday',
    'rrule.weekdays' => 'weekdays',
    'rrule.in' => 'in',
    'rrule.on the' => 'on the',
    'rrule.and' => 'and',
    'rrule.or' => 'or',
    'rrule.the' => 'the',
    'rrule.last' => 'last',

    'rrule.st' => 'st',
    'rrule.nd' => 'nd',
    'rrule.rd' => 'rd',
    'rrule.th' => 'th',
);

return $translations;
