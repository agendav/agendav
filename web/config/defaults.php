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
| than en_US.
|
*/
$config['default_language']	= 'en_US';

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
| Available color list.
| It's an associative array on which each key => value should be interpreted
| as 'background color' => 'foreground color'
|
*/

$config['additional_calendar_colors'] = array(
	'FAC5C0' => '000000',
	'B7E3C0' => '000000',
	'CAB2FC' => '000000',
	'F8F087' => '000000',
	'E6D5C1' => '000000',
	'FFC48C' => '000000',
	'DAF5FF' => '000000',
	'C4C4BC' => '000000',
);

/*
|--------------------------------------------------------------------------
| Color list
|--------------------------------------------------------------------------
|
| Default background => foreground color for just created calendars
|
*/

$config['default_calendar_color'] = array('B5C7EB' => '000000');

