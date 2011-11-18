<?php
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

class Defs {
	// Leave jQuery and jQuery UI the two first scripts
	static $jsfiles= array(
			'libs/jquery-1.6.4.min.js',
			'libs/jquery-ui-1.8.16.min.js',
			'libs/jquery.freeow.min.js',
			'libs/fullcalendar.min.js',
			'libs/jquery.loadmask.min.js',
			'libs/jquery.colorPicker.20091014.js',
			'libs/tag-it.20110621.js',
			'libs/jquery.qtip.min.js',
			'libs/jquery.colorhelpers.js',
			'libs/jquery.timePicker.20110318.js',
			'app.js',
			);

	static $cssfiles = array(
			'Aristo_20110919.css',
			'fullcalendar.1.5.2.css',
			'jquery.qtip.min.css',
			'timePicker.css',
			'app.css',
			);
	static $printcssfiles = array(
			'fullcalendar.print.1.5.2.css',
			'app.print.css',
			);

	function definitions() {
		define('AGENDAV_VERSION', '1.2.3');
	}
}
