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
	private $langname;
	
	private $lang_path;

	private $langcontents;

	// Language definitions
	static $lang_rels = array(
			'en_US' => array(
				'codeigniter' => 'english',
				),
			'es_ES' => array(
				'codeigniter' => 'spanish',
				),
			'de_DE' => array(
				'codeigniter' => 'german',
				),
			'de_AT' => array(
				'codeigniter' => 'german',
				),
			);

	function __construct() {
		parent::__construct();

		$this->lang_path = APPPATH . '../lang';
		$this->langname = $this->config->item('lang');

		if (!is_dir($this->lang_path)) {
			log_message('ERROR', 'Language path is not a directory');
			die();
		}

		// Defined language?
		if (!isset(I18n::$lang_rels[$this->langname])) {
			log_message('ERROR', 'Language ' .
					$this->langname . ' not registered');
			$this->langname = 'en_US';
		}

		if (FALSE === ($this->langcontents =
					$this->parse_language($this->langname))) {
			$this->extended_logs->message('ERROR', 'Language '
					. $this->langname . ' not found');
			$this->langname = 'en_US';
			$this->langcontents = $this->parse_language($this->langname);
		}

		// Locale
		$this->setlocale();

		// Set CodeIgniter language
		$this->set_ci_language();
	}

	/**
	 * Loads a language file and returns it
	 *
	 * @param	string	Language to load
	 * @return	array	Array with labels and messages, FALSE on error
	 */
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

	/**
	 * Translates a string from current language
	 *
	 * @param	string	Type (label or message)
	 * @param	string	Label/message id
	 * @param	array	Associative array of parameters
	 * @return	string	Translated string
	 */
	public function _($type, $id, $params = array()) {
		$contents = $this->langcontents;
		$raw = (isset($contents[$type][$id])) ? 
			$contents[$type][$id] : 
			'[' . $type . ':' . $id . ']';

		foreach ($params as $key => $replacement) {
			$raw = mb_ereg_replace($key, $replacement, $raw);
		}

		return $raw;
	}

	/**
	 * Sets current locale
	 */
	private function setlocale() {
		setlocale(LC_ALL, $this->langname . '.utf8');
	}

	/**
	 * Sets CodeIgniter language
	 */
	private function set_ci_language() {
		$this->config->set_item('language',
				I18n::$lang_rels[$this->langname]['codeigniter']);
	}

	/**
	 * Dumps language contents 
	 */

	public function dump() {
		return $this->langcontents;
	}
}
