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

class Js_generator extends CI_Controller {

	// Special methods that do should not enforce authentication
	private $not_enforced = array(
			'i18n',
			'prefs',
			);

	function __construct() {
		parent::__construct();

		define('SPECIAL_REQUEST', TRUE);

		if (!in_array($this->uri->segment(2), $this->not_enforced) &&
				!$this->auth->is_authenticated()) {
			$this->extended_logs->message('INTERNALS', 
					'Anonymous access attempt to '
					. uri_string());
			die();
		}

		$this->output->set_content_type('text/javascript');
	}

	function index() {
	}

	/**
	 * Session refresh code
	 */
	function session_refresh() {
		$seconds = $this->config->item('sess_time_to_update');
		$seconds++; // Give a margin of 1s to update
		$this->load->view('js_code/session_refresh',
				array('every' => $seconds));
	}

	/**
	 * Dumb function to allow session refresh
	 */
	function dumb() {
		$this->output->set_output(json_encode(''));
	}

	/**
	 * Loads i18n strings
	 */
	function i18n() {
		$this->load->view('js_code/localized_strings');
	}

	/**
	 * Loads app preferences
	 */
	function prefs() {
		$this->output->set_header(
				'Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		$this->output->set_header(
				'Cache-Control: no-store, no-cache, must-revalidate');
		$this->output->set_header(
				'Cache-Control: post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache'); 
		$this->load->view('js_code/preferences');
	}

}
