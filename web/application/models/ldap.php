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

class Ldap extends CI_Model {
	var $host;
	var $port;
	var $admin_dn;
	var $admin_passwd;
	var $base_dn;
	var $id_attr;

	var $search_filter;

	var $conn;

	// Posibles errores de autenticación
	const ERR_TRYAGAINLATER = 'Estamos teniendo dificultades. Por favor, pruebe más tarde';
	const ERR_INVALIDUSERORPASSWD= 'Nombre de usuario o contraseña inválidos';

	/**
	  */
	function __construct() {
		parent::__construct();

		$this->config->load('ldap');

		$this->host = $this->config->item('ldap_host');
		$this->port = $this->config->item('ldap_port');
		$this->admin_dn = $this->config->item('ldap_admin_dn');
		$this->admin_passwd = $this->config->item('ldap_admin_passwd');
		$this->base_dn = $this->config->item('ldap_base_dn');
		$this->id_attr = $this->config->item('ldap_id_attr');
		$this->search_filter = $this->config->item('ldap_search_filter');


		// Avoid blank pages
		if (!function_exists('ldap_connect')) {
			$this->extended_logs->message('ERROR', 
					'LDAP extensions not available!');
			echo "Error! ldap_connect() no existe!";
			die();
		}
		$this->conn = @ldap_connect($this->host, $this->port);
		if ($this->conn === FALSE) {
			$this->extended_logs->message('ERROR', 
					'Error creating LDAP object! Memory issues?');
			throw new Exception('Problema en la creación de objetos');
		}
	}

	/**
	  */
	function login($user, $passwd, &$err) {
		$res = @ldap_bind($this->conn, $this->admin_dn, $this->admin_passwd);
		if ($res === FALSE) {
			// Type of problem
			$err_type = ldap_errno($this->conn);
			$log_msg = '';
			switch ($err_type) {
				case 0x5b:
				case -1:
					$log_msg = "Can't connect to LDAP";
					$err = $this::ERR_TRYAGAINLATER;
					break;
				case 0x20:
				case 0x31:
					$log_msg = "Couldn't bind as admin DN";
					$err = $this::ERR_TRYAGAINLATER;
					break;
				default:
					$log_msg = 'Unknown LDAP error';
					$err = $this::ERR_TRYAGAINLATER;
					break;
			}

			$log_msg .= ' (' . $err_type . ': ' 
					. ldap_err2str($err_type) . ')';
			$this->extended_logs->message('ERROR', $log_msg);

			return FALSE;
		}


		// Continue with login
		// TODO: make this configurable
		$attr = array('dn', 'uid', 'sn', 'givenName', 'cn', 'mail');
		$filter = preg_replace('/%u/', $user, $this->search_filter);

		$res = @ldap_search($this->conn, $this->base_dn,
			$filter, $attr);
		if ($res === FALSE) {
			$this->extended_logs->message('ERROR', 'Error searching on LDAP: '
				. ldap_error($this->conn));
			$err = ERR_TRYAGAINLATER;
			return FALSE;
		}

		// Found any users?
		$info = @ldap_get_entries($this->conn, $res);

		if ($info['count'] != 1) {
			$this->extended_logs->message('AUTHERR', 'Non-existent user: ' 
					. $user);
			$err = $this::ERR_INVALIDUSERORPASSWD;
			return FALSE;
		}

		// Authenticate using user dn
		$user_dn = $info[0]['dn'];
		$res = @ldap_bind($this->conn, $user_dn, $passwd);
		if ($res === FALSE) {
			$this->extended_logs->message('AUTHERR', 'Invalid password from ' 
					. $user);
			$err = $this::ERR_INVALIDUSERORPASSWD;
			return FALSE;
		}

		// Everything is OK
		@ldap_unbind($this->conn);

		$this->extended_logs->message('AUTHOK', 
				'Successful authentication for ' 
				. $user);

		return
				array(
					'user' => $info[0][$this->id_attr][0],
					'passwd' => $passwd,
					'name' => $info[0]['cn'][0],
					'mail' => $info[0]['mail'][0],
					);
	}
}

