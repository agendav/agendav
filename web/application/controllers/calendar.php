<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

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

class Calendar extends CI_Controller {

	function __construct() {
		parent::__construct();

		// Force authentication
		$this->auth->force_auth();
	}

	function index() {
		$data_header = array(
				'title' => $this->config->item('site_title'),
				'logged_in' => TRUE,
				'css' => array(
					array(
						'href' => 'css/style-1.2.3.print.css',
						'rel' => 'stylesheet',
						'type' => 'text/css',
						'media' => 'print',
						),
					'css/fullcalendar.1.5.2.css',
					array(
						'href' => 'css/fullcalendar.print.1.5.2.css',
						'rel' => 'stylesheet',
						'type' => 'text/css',
						'media' => 'print',
						),
					'css/timePicker.css',
					'css/jquery.qtip.min.a318e5f2ce543ec10234407cd4006e9d1310582909.css',
					'css/Aristo_20110919.css',
					'css/style-1.2.3.css',
					));

		// Calendar view
		$data_calendar = array();
		$logo = $this->config->item('logo');
		if ($logo !== FALSE) {
			$data_calendar['logo'] = $logo;
			$data_calendar['title'] = $data_header['title'];
		}

		$this->load->view('common_header', $data_header);
		$this->load->view('calendar_page', $data_calendar);
		$this->load->view('event_details_template');

		$this->load->view('footer',
				array(
					'full_js_set' => TRUE,
					'load_session_refresh' => TRUE,
					'load_calendar_colors' => TRUE,
					));
	}

	/**
	 * Closes user session
	 */
	function logout() {
		$this->auth->delete_session();

		// Configured redirection
		$logout_url = $this->config->item('logout_redirect_to');
		if ($logout_url === FALSE || empty($logout_url)) {
			$logout_url = 'login';
		}

		redirect($logout_url);
	}

}
