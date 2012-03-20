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

class Calendar extends CI_Controller {

	function __construct() {
		parent::__construct();

		// Force authentication
		$this->auth->force_auth();
	}

	function index() {
		// Layout components
		$components = array();
		$data_header = array(
				'title' => $this->config->item('site_title'),
				'logged_in' => TRUE,
				'body_class' => array('calendarpage'),
				);

		$data_calendar = array();
		$logo = $this->config->item('logo');
		if ($logo !== FALSE) {
			$data_calendar['logo'] = $logo;
			$data_calendar['title'] = $data_header['title'];
		}

		$components['header'] = 
			$this->load->view('common_header', $data_header, TRUE);
		$components['sidebar'] = 
			$this->load->view('sidebar', $data_calendar, TRUE);
		$components['content'] = 
			$this->load->view('center', array(), TRUE) .
			$this->load->view('event_details_template', array(), TRUE) .
			$this->load->view('share_calendar_manager_row_template', array(), TRUE);
		$components['footer'] = $this->load->view('footer',
				array(
					'load_session_refresh' => TRUE,
					'load_calendar_colors' => TRUE,
					), TRUE);

		$this->load->view('layouts/app.php', $components);
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
