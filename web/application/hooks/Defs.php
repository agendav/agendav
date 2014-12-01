<?php
/*
 * Copyright 2011-2014 Jorge López Pérez <jorge@adobo.org>
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
            'libs/jquery-1.11.1.js',
            'libs/moment.js',
            'libs/jquery-ui.js',
            'libs/jquery.timepicker.js',
            'libs/jquery.freeow.min.js',
            'libs/jquery.colorPicker.js',
            'libs/imagesloaded.pkg.min.js',
            'libs/jquery.qtip.js',
            'libs/jquery.colorhelpers.js',
            'libs/jquery.cookie.js',
            'libs/jquery.serializeobject.js',
            'libs/fullcalendar.js',
            'translation.js',
            'libs/dust-core-1.0.0.js',
            'templates/templates.js',
            'datetime.js',
            'app.js',
            );

    static $cssfiles = array(
            'agendav.css',
            'jquery-ui.css',
            'jquery-ui.structure.css',
            'jquery-ui.theme.css',
            'fullcalendar.css',
            'jquery.qtip.css',
            'freeow.css',
            'jquery.timepicker.css',
            'colorpicker.css',
            );
    static $printcssfiles = array(
            'app.print.css',
            'fullcalendar.print.css',
            );

    function definitions() {
        set_include_path(implode(PATH_SEPARATOR, array(
                        BASEPATH . '../lib/iCalcreator',
                        get_include_path()
                        )));

    }

    /**
     * Set PHP default timezone. date.timezone has to be set on php.ini, PHP
     * throws some warnings when it is not. Use configuration parameter
     * default_timezone
     */
    function default_tz() {
        $CI_config =& load_class('Config');
        date_default_timezone_set($CI_config->item('default_timezone'));
    }
}
