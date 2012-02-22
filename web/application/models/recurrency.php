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

class Recurrency extends CI_Model {

	private $date_format;
	private $tz;

	function __construct() {
		parent::__construct();
		$this->date_format = $this->dates->date_format_string('date');
		$this->tz = $this->config->item('default_timezone');
	}

	/**
	 * Returns a human readable expression about what does this rule do
	 *
	 * If no explanation is possible, FALSE is returned
	 */

	function rrule_explain($rrule = null, &$extract_params) {
		$explanation = '';
		$extract_params = array();

		if (is_null($rrule)) {
			return FALSE;
		}
		
		foreach ($rrule as $k => $v) {
			switch($k) {
				case 'FREQ':
					switch ($v) {
						case 'DAILY':
							$explanation = $this->i18n->_('labels',
									'repeatdaily');
							break;
						case 'WEEKLY':
							$explanation = $this->i18n->_('labels',
									'repeatweekly');
							break;
						case 'MONTHLY':
							$explanation = $this->i18n->_('labels',
									'repeatmonthly');
							break;
						case 'YEARLY':
							$explanation = $this->i18n->_('labels',
									'repeatyearly');
							break;
						default:
							// Oops!
							return FALSE;
							break;
					}
					break;
				case 'COUNT':
					$explanation .= ', ' . $this->i18n->_('labels',
							'explntimes',
							array('%n' => $v));
					break;
				case 'UNTIL':
					$date = $this->dates->idt2datetime($v, 
								'UTC');
					$date->setTimeZone(new DateTimeZone($this->tz));
					$explanation .= ', ' . $this->i18n->_('labels',
							'expluntil',
							array('%d' => $date->format($this->date_format)));
					break;
				case 'INTERVAL':
					if ($v != "1") {
						return FALSE;
					}
					break;
				default:
					// We don't know (at this moment) how to parse this rule
					return FALSE;
					break;
			}
		}

		return $explanation;
	}

	/**
	 * Builds a recurrence rule based on recurrence type, count and until
	 */

	function build($opts, &$rrule_err) {
		if (!isset($opts['recurrence_type'])) {
			$rrule_err = $this->i18n->_('messages',
					'error_bogusrepeatrule');
			return FALSE;
		}

		$res = array();

		switch ($opts['recurrence_type']) {
			case 'DAILY':
			case 'WEEKLY':
			case 'MONTHLY':
			case 'YEARLY':
				$res['FREQ'] = $opts['recurrence_type'];
				break;
			default:
				// Oops
				$rrule_err = $this->i18n->_('messages',
						'error_bogusrepeatrule');
				return FALSE;
				break;
		}

		if (isset($opts['recurrence_count']) &&
				!empty($opts['recurrence_count'])) {
			$res['COUNT'] = $opts['recurrence_count'];
		} else if (isset($opts['recurrence_until']) &&
				!empty($opts['recurrence_until'])) {
			$date =
				$this->dates->frontend2datetime($opts['recurrence_until'],
						'UTC');
			if ($date === FALSE) {
				$rrule_err = $this->i18n->_('messages',
						'error_bogusrepeatrule');
				return FALSE;
			} else {
				$res['UNTIL'] = $this->dates->datetime2idt($date);
			}
		}

		return $res;
	}
}

