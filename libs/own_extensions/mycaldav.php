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

	function __construct( $base_url, $user, $pass ) {
		parent::__construct($base_url, $user, $pass);
	}


	/**
	 * Check with OPTIONS if calendar-access is enabled
	 * 
	 * Can be used to check authentication against server
	 *
	 */
	function CheckValidCalDAV() {
		// Clean headers
		$this->headers = array();
		$dav_options = $this->DoOptionsRequestAndGetDAVHeader();
		$valid_caldav_server = isset($dav_options['calendar-access']);

		return $valid_caldav_server;
	}

	/**
	 * Issues an OPTIONS request
	 *
	 * @param string $url The URL to make the request to
	 *
	 * @return array DAV options
	 */
	function DoOptionsRequestAndGetDAVHeader( $url = null ) {
		$this->requestMethod = "OPTIONS";
		$this->body = "";
		$headers = $this->DoRequest($url);

		$result = array();

		$headers = preg_split('/\r?\n/', $headers);

		// DAV header(s)
		$dav_header = preg_grep('/^DAV:/', $headers);
		if (is_array($dav_header)) {
			$dav_header = array_values($dav_header);
			$dav_header = preg_replace('/^DAV: /', '', $dav_header);

			$dav_options = array();

			foreach ($dav_header as $d) {
				$dav_options = array_merge($dav_options,
						array_flip(preg_split('/[, ]+/', $d)));
			}

			$result = $dav_options;

		}

		return $result;
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

	/**
	 * Issues a PROPPATCH on a resource
	 *
	 * @param string	XML request
	 * @param string	URL
	 * @return			TRUE on success, FALSE otherwise
	 */
	function DoPROPPATCH($xml_text, $url) {
		$this->DoXMLRequest('PROPPATCH', $xml_text, $url);

		$errmsg = '';

		if ($this->httpResultCode == '207') {
			$errmsg = $this->httpResultCode;
			// Find propstat tag(s)
			if (isset($this->xmltags['DAV::propstat'])) {
				foreach ($this->xmltags['DAV::propstat'] as $i => $node) {
					if ($this->xmlnodes[$node]['type'] == 'close') {
						continue;
					}
					// propstat @ $i: open
					// propstat @ $i + 1: close
					// Search for prop and status
					$level = $this->xmlnodes[$node]['level'];
					$level++;

					while ($this->xmlnodes[++$node]['level'] >= $level) {
						if ($this->xmlnodes[$node]['tag'] == 'DAV::status'
								&& $this->xmlnodes[$node]['value'] !=
								'HTTP/1.1 200 OK') {
							return $this->xmlnodes[$node]['value'];
						}
					}
				}
			}
		} else if ($this->httpResultCode != 200) {
			return 'Unknown HTTP code';
		}

		return TRUE;
	}

}
