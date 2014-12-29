<?php

/*
 * Copyright 2011-2012 Jorge López Pérez <jorge@adobo.org>
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

class Caldav2json extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        if (!$this->container['session']->isAuthenticated()) {
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }
        $this->output->set_content_type('application/json');
    }

    /**
     * Searchs a principal using provided data
     */
    public function principal_search()
    {
        $result = array();
        $term = $this->input->get('term', true);
        $client = $this->container['client'];

        if (!empty($term)) {
            $result = $client->principalSearch($term, $term, true);
        }

        $this->output->set_output(json_encode($result));
    }

}
