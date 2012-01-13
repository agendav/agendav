<?php
class CalendarExtendedInfo extends CalendarInfo {
	public $calendar, $order, $color, $shared, $shared_with;

	function __construct($url, $displayname = null, $getctag = null ) {
		// Be consistent with iCalcreator
		$this->order = FALSE;
		$this->color = FALSE;

		parent::__construct($url, $displayname, $getctag);
	}
}


class MyCalDAV extends CalDAVClient {

	protected $valid_caldav_server = null;


	function __construct( $base_url, $user, $pass ) {
		parent::__construct($base_url, $user, $pass);
	}


	/**
	 * Check with OPTIONS if PROPFIND and REPORT are supported
	 * 
	 * Can be used to check authentication against server
	 *
	 */
	function CheckValidCalDAV() {
		// Clean headers
		$this->headers = array();
		$options = $this->DoOptionsRequest();
		if (isset($options['REPORT']) && isset($options['PROPFIND'])) {
			$this->valid_caldav_server = TRUE;
		} else {
			$this->valid_caldav_server = FALSE;
		}

		return $this->valid_caldav_server;
	}

	function DoCalendarQuery( $filter, $url = null ) {
		if (is_null($this->valid_caldav_server)) {
			// Headers will be wiped by CheckValidCalDAV. Restore 
			// ithem after this call
			$current_headers = $this->headers;
			$this->CheckValidCalDAV();
			$this->headers = $current_headers;
		}

		if ($this->valid_caldav_server) {
			return parent::DoCalendarQuery($filter, $url);
		} else {
			log_message('ERROR', 'Invalid CalDAV server');
			return FALSE;
		}
	}

	/*
	 * Find own calendars
	 */
	function FindCalendars( $recursed=false ) {
		if ( !isset($this->calendar_home_set[0]) ) {
			$this->FindCalendarHome();
		}
		$properties = 
			array(
					'resourcetype',
					'displayname',
					'http://calendarserver.org/ns/:getctag',
					'http://apple.com/ns/ical/:calendar-color',
					'http://apple.com/ns/ical/:calendar-order',
				 );
		$this->DoPROPFINDRequest( $this->calendar_home_set[0], $properties, 1);

		return $this->parse_calendar_info();
	}

	/**
	 * Do a PROPFIND on a calendar and retrieve its information
	 */
	function GetCalendarDetailsByURL($url) {
		$properties = 
			array(
					'resourcetype',
					'displayname',
					'http://calendarserver.org/ns/:getctag',
					'http://apple.com/ns/ical/:calendar-color',
					'http://apple.com/ns/ical/:calendar-order',
				 );
		$this->DoPROPFINDRequest($url, $properties, 0);

		return $this->parse_calendar_info();
	}

	/**
	 * Get calendar info after a PROPFIND
	 */
	function parse_calendar_info() {

		$calendars = array();
		if ( isset($this->xmltags['urn:ietf:params:xml:ns:caldav:calendar']) ) {
			$calendar_urls = array();
			foreach( $this->xmltags['urn:ietf:params:xml:ns:caldav:calendar'] AS $k => $v ) {
				$calendar_urls[$this->HrefForProp('urn:ietf:params:xml:ns:caldav:calendar', $k)] = 1;
			}

			foreach( $this->xmltags['DAV::href'] AS $i => $hnode ) {
				$href = rawurldecode($this->xmlnodes[$hnode]['value']);

				if ( !isset($calendar_urls[$href]) ) continue;

				//        printf("Seems '%s' is a calendar.\n", $href );


				$calendar = new CalendarExtendedInfo($href);

				/*
				 *  Transform href into calendar
				 * /xxxxx/yyyyy/caldav.php/principal/resource/
				 *                          t-3       t-2
				 */
				$pieces = preg_split('/\//', $href);
				$total = count($pieces);
				$calendar_id = $pieces[$total-3] . ':' . $pieces[$total-2];
				$calendar->calendar = $calendar_id;

				$ok_props = $this->GetOKProps($hnode);
				foreach( $ok_props AS $v ) {
					switch( $v['tag'] ) {
						case 'http://calendarserver.org/ns/:getctag':
							$calendar->getctag = isset($v['value']) ?
								$v['value'] : '';
							break;
						case 'DAV::displayname':
							$calendar->displayname = isset($v['value']) ?
								$v['value'] : 'calendar';
							break;
						case 'http://apple.com/ns/ical/:calendar-color':
							$calendar->color = isset($v['value']) ?
								$v['value'] : '#ffffffff';
							break;
						case 'http://apple.com/ns/ical/:calendar-order':
							$calendar->order = isset($v['value']) ?
								$v['value'] : '1';
							break;
					}
				}
				$calendars[] = $calendar;
			}
		}

		return $this->CalendarUrls($calendars);
	}

	/**
	 * Fetch events in a time range. Uses DAViCal GetEvents function and
	 * just adds "Depth: 1" header
	 */

	function GetEvents( $start = null, $finish = null, $relative_url = null ) {
		$this->SetDepth('1');
		return parent::GetEvents($start, $finish, $relative_url);
	}

	/**
	 * Fetch a single event using Depth: 1
	 */
	function GetEntryByUid( $uid, $relative_url = null ) {
		$this->SetDepth('1');
		return parent::GetEntryByUid($uid, $relative_url);
	}


}
