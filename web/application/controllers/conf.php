<?php
/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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

/**
 * This controller loads basic configuration from AgenDAV
 */
class Conf extends MY_Controller
{

    /**
     * Current user
     *
     * @var \AgenDAV\User
     * @access private
     */
    private $user;

    private $options = array(
        'format_column_month',
        'format_column_week',
        'format_column_day',
        'format_column_table',
        'format_title_month',
        'format_title_week',
        'format_title_day',
        'format_title_table',
        'cookie_prefix',
        'csrf_cookie_name',
        'csrf_token_name',
    );

    public function __construct() {
        parent::__construct();

        $session = $this->container['session'];

        if (!$session->isAuthenticated()) {
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }

        $this->output->set_content_type('application/json');
    }

    /**
     * Returns all options
     *
     * @access public
     * @return void
     */
    public function index()
    {
        $result = array();
        foreach ($this->options as $item) {
            $result[$item] = $this->config->item($item);
        }

        $this->output->set_output(json_encode($result));
    }

}
