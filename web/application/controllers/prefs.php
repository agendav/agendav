<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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

class Prefs extends CI_Controller {

	function __construct() {
		parent::__construct();

		// Force authentication
		$this->auth->force_auth();
	}

	function index() {
		// Layout components
		$components = array();
		$title = $this->config->item('site_title');

		$data_header = array(
				'title' => $title,
				'logged_in' => TRUE,
				'body_class' => array('prefspage'),
				);

		$data_calendar = array();
		$logo = $this->config->item('logo');
		$data_calendar['logo'] = custom_logo($logo, $title);
		$data_calendar['title'] = $title;

		$components['header'] = 
			$this->load->view('common_header', $data_header, TRUE);

		$components['navbar'] = 
			$this->load->view('navbar', $data_header, TRUE);

		// Empty sidebar
		$components['sidebar'] = '';



		// Calendar list
		$calendar_list = $this->session->userdata('available_calendars');
		if (FALSE === $calendar_list) {
			$this->load->library('caldav');
			$calendar_list = $this->caldav->all_user_calendars(
					$this->auth->get_user(),
					$this->auth->get_passwd());
		}

		$data_prefs = array(
				'calendar_list' => $calendar_list,
				);

		$components['content'] = $this->load->view('preferences_page',
				$data_prefs, TRUE);
		$components['footer'] = $this->load->view('footer',
				array(
					'load_session_refresh' => TRUE,
					'load_calendar_colors' => TRUE,
					), TRUE);

		$this->load->view('layouts/app.php', $components);
	}

}
