<?php

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Uuid;
use AgenDAV\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Data\Transformer\CalendarTransformer;
use League\Fractal\Resource\Collection;

class Createcalendar extends JSONController
{
    /**
     * Validates user input
     *
     * @param array $input
     * @return bool
     */
    protected function validateInput(array $input)
    {
        $fields = [
            'displayname',
            'calendar_color',
        ];

        foreach ($fields as $name) {
            if (empty($input[$name])) {
                return false;
            }
        }

        return true;
    }

    public function execute(array $input)
    {
        $calendar_home_set = $this->container['session']->get('calendar_home_set');
        $url = $calendar_home_set . Uuid::generate();

        $calendar = new Calendar($url, [
            Calendar::DISPLAYNAME => $input['displayname'],
            Calendar::COLOR => $input['calendar_color'],
        ]);

        $this->client->createCalendar($calendar);

        return $this->generateSuccess();
    }

}
