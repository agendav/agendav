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

/**
 * This file contains date and time formats for several parts of AgenDAV
 * Please, read each comment carefully
 */

$formats = array();

/*
 * Full date used on informational messages
 *
 * Uses strftime syntax
 * http://php.net/strftime
 */
$formats['full_date_strftime'] = '%a, %e %B de %Y';

/*
 * Fullcalendar labels
 *
 * Use special syntax from Fullcalendar:
 * http://arshaw.com/fullcalendar/docs/utilities/formatDate/
 */
$formats['column_month_fullcalendar'] = 'ddd';
$formats['column_week_fullcalendar'] = 'ddd d';
$formats['column_day_fullcalendar'] = 'ddd d MMMM';
$formats['column_table_fullcalendar'] = 'd MMM yyyy';
$formats['title_month_fullcalendar'] = 'MMMM yyyy';
$formats['title_week_fullcalendar'] = "d[ MMMM][ 'de' yyyy]{ '&#8212;' d MMMM 'de' yyyy}";
$formats['title_day_fullcalendar'] = 'dddd, d MMM yyyy';
$formats['title_table_fullcalendar'] = 'dddd, d MMMM \'de\' yyyy';

