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

class Main extends MY_Controller
{

    private $user;

    function __construct() {
        parent::__construct();

        $this->user = $this->container['user'];

        // Force authentication
        if (!$this->user->isAuthenticated()) {
            redirect('/login');
        }
    }

    function index() {
        // Layout components
        $components = array();
        $title = $this->config->item('site_title');

        $data_header = array(
                'title' => $title,
                'logged_in' => true,
                'username' => $this->user->getUsername(),
                'body_class' => array('calendarpage'),
                );

        $data_calendar = array();
        $logo = $this->config->item('logo');
        $data_calendar['logo'] = custom_logo($logo, $title);
        $data_calendar['title'] = $title;

        $components['header'] = 
            $this->load->view('common_header', $data_header, true);

        $components['navbar'] = 
            $this->load->view('navbar', $data_header, true);

        $components['sidebar'] = 
            $this->load->view('sidebar', $data_calendar, true);
        $components['content'] = 
            $this->load->view('center', array(), true); 
        $components['footer'] = $this->load->view('footer',
                array(
                    'load_session_refresh' => true,
                    ), true);

        $this->load->view('layouts/app.php', $components);
    }

    /**
     * Closes user session
     */
    function logout() {
        $this->user->removeSession();

        // Configured redirection
        $logout_url = $this->config->item('logout_redirect_to');
        if ($logout_url === false || empty($logout_url)) {
            $logout_url = 'login';
        }

        redirect($logout_url);
    }

}
