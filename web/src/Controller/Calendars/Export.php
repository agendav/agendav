<?php

namespace AgenDAV\Controller\Calendars;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\CalDAV\Client;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Export
{
    /**
     * Executes the action assigned to this controller
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function doAction(Request $request, Application $app)
    {
        $this->client = $app['caldav.client'];

        $calendar = $this->client->getCalendarByUrl($request->query->get('calendar'));
        $timezone = new \DateTimeZone('UTC');
        $start = new \DateTime('0000-01-01', $timezone);
        $end = new \DateTime('now', $timezone);
        //Do we have a way to get all the objects without setting a range?
        $events = $this->client->fetchObjectsOnCalendar(
            $calendar,
            $start->format('Ymd\THis\Z'),
            $end->format('Ymd\THis\Z')
        );
        $return = '';
        foreach ($events as $event) {
            $return .= $event->getRenderedEvent();
        }
        $response = new Response($return);
        $response->headers->set('Content-Type', 'text/calendar');
        $response->headers->set('Content-Disposition', 'attachment; filename="agendav-export.ics"');
        return $response;
    }
}
