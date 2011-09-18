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

class Recurrency extends CI_Model {

	function __construct() {
		parent::__construct();
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
							$explanation = 'diariamente';
							break;
						case 'WEEKLY':
							$explanation = 'semanalmente';
							break;
						case 'MONTHLY':
							$explanation = 'mensualmente';
							break;
						case 'YEARLY':
							$explanation = 'anualmente';
							break;
						default:
							// Oops!
							return FALSE;
							break;
					}
					break;
				case 'COUNT':
					$explanation .= ', un total de ' . $v . ' veces';
					break;
				case 'UNTIL':
					$date = $this->dates->idt2datetime($v, 
								'UTC');
					// TODO configurable timezone and format
					$date->setTimeZone(new DateTimeZone('Europe/Madrid'));
					$explanation .= ', hasta que llegue el día ' .
						$date->format('d/m/Y');
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
			$rrule_err = 'Tipo de recurrencia no definido';
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
				$rrule_err = 'Tipo de recurrencia ' .
					$opts['recurrence_type'] . ' desconocido';
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
				$rrule_err = 'La fecha pasada no es válida ('
						.  $opts['recurrence_until'] .')';
				return FALSE;
			} else {
				$res['UNTIL'] = $this->dates->datetime2idt($date);
			}
		}

		return $res;
	}
}

