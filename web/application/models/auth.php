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

class Auth extends CI_Model {
	var $user, $passwd, $name, $mail;

	function __construct() {
		parent::__construct();

		$this->user = $this->session->userdata('user');
		$this->passwd = $this->session->userdata('passwd');
		if ($this->passwd !== FALSE) {
			$this->passwd = $this->encrypt->decode($this->passwd);
		}
		$this->name = $this->session->userdata('name');
		$this->mail = $this->session->userdata('mail');
	}

	/**
	  */
	function new_session($data) {
		$data['passwd'] = isset($data['passwd']) ? 
			$this->encrypt->encode($data['passwd']) : '';
		$this->session->set_userdata($data);
	}

	/**
	 */
	function delete_session() {
		$this->session->unset_userdata(
				array(
					'user' => '',
					'passwd' => '',
					'name' => '',
					'mail' => '',
					));
		$this->session->sess_destroy();
	}

	/**
	  */
	function is_authenticated() {
		return ($this->user === FALSE) ? FALSE : TRUE;
	}

	/**
	  */
	function force_auth() {
		if (!$this->is_authenticated() && !defined('SPECIAL_REQUEST')) {
			redirect('/login');
		}
	}


	/**
	  */
	function get_user() {
		return $this->user;
	}

	/**
	  */
	function get_passwd() {
		return $this->passwd;
	}

	/**
	  */
	function get_name() {
		return $this->name;
	}

	/**
	  */
	function get_mail() {
		return $this->mail;
	}
}

