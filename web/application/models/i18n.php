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

class I18n extends CI_Model {
	private $default_name = 'en_US';  // TODO
	private $current_name;
	
	private $lang_path;

	private $default_contents;

	function __construct() {
		parent::__construct();

		$this->lang_path = APPPATH . '../lang';

		if (!is_dir($this->lang_path)) {
			log_message('ERROR', 'Language path is not a directory');
			die();
		}

		if (FALSE === ($this->default_contents =
					$this->parse_language($this->default_name))) {
			$this->extended_logs->message('ERROR', 'Default language '
					. $this->default_name . ' not found');
			die();
		}

		setlocale(LC_ALL, 'en_US.utf8');
	}

	private function parse_language($lang) {
		$file = $this->lang_path . '/' . $lang . '/' . $lang . '.php';

		if (!is_file($file)) {
			return FALSE;
		} else {
			$messages = array();
			$labels = array();
			$js_messages = array();

			@include($file);

			return array(
				'messages' => $messages,
				'labels' => $labels,
				'js_messages' => $js_messages,
				);
		}
	}

	public function _($type, $id, $params = array()) {
		$contents = $this->default_contents;
		$raw = (isset($contents[$type][$id])) ? 
			$contents[$type][$id] : 
			'[' . $type . ':' . $id . ']';

		foreach ($params as $key => $replacement) {
			$raw = mb_ereg_replace($key, $replacement, $raw);
		}

		return $raw;
	}

	public function dump($type, $use_default = FALSE) {
		$contents = $this->default_contents;
		return $contents[$type];
	}
}
