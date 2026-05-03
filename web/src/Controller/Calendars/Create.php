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

use AgenDAV\Uuid;
use AgenDAV\Controller\JSONController;
use AgenDAV\CalDAV\Resource\Calendar;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Create extends JSONController
{
    protected function validateInput(ParameterBag $input)
    {
        foreach (['displayname', 'calendar_color'] as $name) {
            if (empty($input->get($name))) {
                return false;
            }
        }
        return true;
    }

    protected function execute(
        ParameterBag $input,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $calendar_home_set = $this->container->get('session')->get('calendar_home_set');
        $url = $calendar_home_set . Uuid::generate();

        $calendar = new Calendar($url, [
            Calendar::DISPLAYNAME => $input->get('displayname'),
            Calendar::COLOR => $input->get('calendar_color'),
        ]);

        $this->client->createCalendar($calendar);

        return $this->generateSuccess($response);
    }
}
