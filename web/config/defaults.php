<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Application title
|--------------------------------------------------------------------------
|
| To be shown in page titles and some other places
|
*/
$config['site_title'] = 'Calendar web access for My Company LTD';

/*
|--------------------------------------------------------------------------
| Logo
|--------------------------------------------------------------------------
|
| Filename from public/img
|
*/
$config['logo'] = 'agendav_100transp.png';

/*
|--------------------------------------------------------------------------
| Footer message
|--------------------------------------------------------------------------
|
| Text to be shown in footer
|
*/
$config['footer'] = 'My Company LTD';

/*
|--------------------------------------------------------------------------
| URL to redirect after logging out
|--------------------------------------------------------------------------
|
| Leave empty if you want to redirect to login page
|
*/
$config['logout_redirect_to'] = '';

/*
|--------------------------------------------------------------------------
| Additional static JavaScript files
|--------------------------------------------------------------------------
|
| Files have to be placed inside public/js
|
| Useful for programmatically adding events via Fullcalendar
|
*/
$config['additional_js'] = array();

/*
|--------------------------------------------------------------------------
| Show public CalDAV in calendar edit form
|--------------------------------------------------------------------------
|
| Set this to true if you want to show public CalDAV URLs in calendar edit
| form
|
*/
$config['show_public_caldav_url'] = TRUE;

/*
|--------------------------------------------------------------------------
| Default Language
|--------------------------------------------------------------------------
|
| This determines which language should be used by default. Make sure
| there is an available translation if you intend to use something other
| than en.
|
*/
$config['default_language']	= 'en';

/*
|--------------------------------------------------------------------------
| Default time format
|--------------------------------------------------------------------------
|
| This determines which time format should be used by default. You can choose
| between 12 and 24 hours time format
*/
$config['default_time_format'] = '24';

/*
|--------------------------------------------------------------------------
| Default date format
|--------------------------------------------------------------------------
|
| This determines which date format should be used by default in forms.
| Dates will be formatted anywhere else as stated in locale file.
| You can choose any of the following values:
|
| * ymd : YYYY-month-day will be used
| * dmy: day-month-YYYY will be used
| * mdy: month-day-YYYY will be used
*/
$config['default_date_format'] = 'ymd';

/*
|--------------------------------------------------------------------------
| Readable date format (strftime)
|--------------------------------------------------------------------------
|
| This determines which date format should be used by default when showing a
| date. It's not used in forms.
|
| Requires strftime syntax (http://php.net/strftime)
|
| Some examples:
|
|  * %a %e %B %Y : Mon 5 March 2012
|  * %a %e de %B de %Y : Lun 5 de marzo de 2012 (great for es_ES)
*/

$config['format_full_date'] = '%a %e %B %Y';

/*
|--------------------------------------------------------------------------
| Calendar titles and columns date formats
|--------------------------------------------------------------------------
|
| Following options control how the web calendar should format dates on
| titles and columns for each type of view
|
| Requires own Fullcalendar syntax
| (http://arshaw.com/fullcalendar/docs/utilities/formatDate/)
|
*/

$config['format_column_month'] = 'ddd'; 
$config['format_column_week'] = 'ddd d'; 
$config['format_column_day'] = 'ddd d MMMM'; 
$config['format_column_table'] = 'MMM d, yyyy';

$config['format_title_month'] = 'MMMM yyyy';
$config['format_title_week'] = "MMM d[ yyyy]{ '&#8212;'[ MMM] d yyyy}";
$config['format_title_day'] = 'dddd, MMM d yyyy';
$config['format_title_table'] = 'dddd, MMM d yyyy';

/*
|--------------------------------------------------------------------------
| Default first day of week
|--------------------------------------------------------------------------
|
| This determines which day should be first of week.
|
| 0 means Sunday, 1 means Monday and so on
*/

$config['default_first_day'] = 0;

/*
|--------------------------------------------------------------------------
| Default timezone
|--------------------------------------------------------------------------
|
| This determines which timezone should be used by default
|
| Please, use a valid timezone from http://php.net/timezones
*/

$config['default_timezone'] = 'Europe/Madrid';



/*
|--------------------------------------------------------------------------
| Color list
|--------------------------------------------------------------------------
|
| Background colors. Foreground color are calculated by clients
|
*/

$config['calendar_colors'] = array(
		'D4EAEF',
		'3A89C9',
		'107FC9',
		'FAC5C0',
		'FF4E50',
		'BD3737',
		'C9DF8A',
		'77AB59',
		'36802D',
		'F8F087',
		'E6D5C1',
		'3E4147',
);
// advanced.php

/*
|--------------------------------------------------------------------------
| Log permissions
|--------------------------------------------------------------------------
|
| Use a full octal permission.
| Example: 0644, 0666
*/
$config['log_create_permissions'] = 0640;

// ==============================
// Additional caldav.php options


/*
 * Allow calendar sharing
 * ======================
 *
 * You can enable or disable calendar sharing. If your CalDAV server does not
 * support WebDAV ACLs disable sharing
 */

$config['enable_calendar_sharing'] = TRUE;


// Default permissions for calendar owner
$config['owner_permissions'] = array('all', 'read', 'unlock', 'read-acl',
		'read-current-user-privilege-set', 'write-acl', 'C:read-free-busy',
		'write', 'write-properties', 'write-content', 'bind', 'unbind');

// Permissions for sharing calendars using the 'read' profile
$config['read_profile_permissions'] = array('C:read-free-busy', 'read');

// Permissions for sharing calendars using the 'read+write' profile
$config['read_write_profile_permissions'] = array('C:read-free-busy',
		'read', 'write');

// Authenticated users default permissions
$config['default_permissions'] = array('C:read-free-busy');
