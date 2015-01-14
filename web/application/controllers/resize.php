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

use AgenDAV\JSONController;

class Resize extends JSONController
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
            'uid',
            'calendar',
            'timezone',
            'etag',
            'delta',
            'allday',
            'was_allday',
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
        $operation = new AgenDAV\Controller\Event\Resize($this->client);
        $result = $this->generateSuccess(
            $operation->execute($input)
        );

        return $result;
    }
}
