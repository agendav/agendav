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

class Login extends CI_Controller {

	public function index() {
		// Already authenticated?
		if ($this->auth->is_authenticated()) {
			redirect('/calendar');
		}

		$this->load->helper('form');
		$this->load->library('form_validation');

		$this->form_validation->set_rules(
				array(
					array(
						'field' => 'user',
						'label' => $this->i18n->_('labels', 'username'),
						'rules' => 'required',
						),
					array(
						'field' => 'passwd',
						'label' => $this->i18n->_('labels', 'password'),
						'rules' => 'required',
						),
					));

		// Required fields missing?
		$valid_auth = FALSE;
		$validation = $this->form_validation->run();
		$err = '';

		if ($validation !== FALSE) {
			// Check authentication against server
			$this->load->library('caldav');
			
			$user = $this->input->post('user');
			$passwd = $this->input->post('passwd');

			$valid_auth = $this->caldav->check_server_authentication($user, $passwd);
			if ($valid_auth !== FALSE) {
				// TODO load user prefs
				$data = array(
						'user' => $user,
						'passwd' => $passwd,
						);
				$this->auth->new_session($data);
			} else {
				$err = $this->i18n->_('messages', 'error_auth');
			}
		}

		if ($valid_auth === FALSE) {
            $data_header = array(
					'title' => $this->config->item('site_title'),
                    'js' => array(
                        'jquery-1.6.4.min.js',
                        'jquery-ui-1.8.16.min.js',
                        ),
                    'css' => array(
                        'css/style-1.1.1.css',
                        'css/Aristo_20110919.css',
                        ));
			$this->load->view('common_header', $data_header);

			$data = array();
			if (!empty($err)) {
				$data['custom_errors'] = $err;
			}

			$logo = $this->config->item('logo');
			if ($logo !== FALSE) {
				$data['logo'] = $logo;
				$data['title'] = $data_header['title'];
			}

			$this->load->view('login', $data);
			$this->load->view('footer');
		} else {
			redirect("/calendar");
		}

	}
}

