<?php
class CalendarExtendedInfo extends CalendarInfo {
	public $calendar, $order, $color, $shared;

	function __construct($url, $displayname = null, $getctag = null ) {
		// Be consistent with iCalcreator
		$this->order = FALSE;
		$this->color = FALSE;

		parent::__construct($url, $displayname, $getctag);
	}
}


class CURLCalDAVClient extends CalDAVClient {

	// Requests timeout
	private $timeout;

	// cURL handle
	private $ch;

	// Full URL
	private $full_url;


	/**
	 * Constructor
	 *
	 * Valid options are:
	 *
	 *  $options['auth'] : Auth type. Can be any of values for
	 *   CURLOPT_HTTPAUTH (from
	 *   http://www.php.net/manual/es/function.curl-setopt.php). Default:
	 *   basic or digest
     *
	 *  $options['timeout'] : Timeout in seconds
	 */

	// TODO: proxy options, interface used,
	function __construct( $base_url, $user, $pass, $options = array()) {
		parent::__construct($base_url, $user, $pass);
		$this->timeout = isset($options['timeout']) ? 
			$options['timeout'] : 10;
		$this->ch = curl_init();
		curl_setopt_array($this->ch, array(
					CURLOPT_CONNECTTIMEOUT => $this->timeout,
					CURLOPT_FAILONERROR => FALSE,
					CURLOPT_FOLLOWLOCATION => TRUE,
					CURLOPT_MAXREDIRS => 2,
					CURLOPT_FORBID_REUSE => FALSE,
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_HTTPAUTH =>
						isset($options['auth']) ?
							$options['auth'] :
							(CURLAUTH_BASIC | CURLAUTH_DIGEST),
					CURLOPT_USERAGENT => 'cURL based CalDAV client',
					CURLINFO_HEADER_OUT => TRUE,
					CURLOPT_HEADER => TRUE,
					CURLOPT_SSL_VERIFYPEER => FALSE,
					));

		$this->full_url = $base_url;
	}

	/**
	 * Sets User-Agent
	 */
	function SetUserAgent($user_agent) {
		$this->user_agent = $user_agent;
		curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);
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
	 * Send a request to the server
	 *
	 * @param string $url The URL to make the request to
	 *
	 * @return string The content of the response from the server
	 */
	function DoRequest( $url = null ) {
		if (is_null($url)) {
			$url = $this->full_url;
		}

		$url = str_replace(" ","%20",$url);
		$this->request_url = $url;

		curl_setopt($this->ch, CURLOPT_URL, $url);

		// Request method
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->requestMethod);

		// Empty body. If not used, cURL will spend ~5s on this request
		if ($this->requestMethod == 'HEAD' || empty($this->body)) {
			curl_setopt($this->ch, CURLOPT_NOBODY, TRUE);
		} else {
			curl_setopt($this->ch, CURLOPT_NOBODY, FALSE);
		}

		// Headers
		if (!isset($this->headers['content-type'])) $this->headers['content-type'] = "Content-type: text/plain";

		// Remove cURL generated 'Expect: 100-continue'
		$this->headers['disable_expect'] = 'Expect:';
		curl_setopt($this->ch, CURLOPT_HTTPHEADER,
				array_values($this->headers));

		curl_setopt($this->ch, CURLOPT_USERPWD, $this->user . ':' .
				$this->pass);

		// Request body
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->body);

		$response = curl_exec($this->ch);

		if (FALSE === $response) {
			// TODO better error handling
			log_message('ERROR', 'Error requesting ' . $url . ': ' 
					. curl_error($this->ch));
			return false;
		}

		$info = curl_getinfo($this->ch);

		// Get headers (idea from SabreDAV WebDAV client)
		$this->httpResponseHeaders = substr($response, 0, $info['header_size']);
		$this->httpResponseBody = substr($response, $info['header_size']);

		// Get only last headers (needed when using unspecific HTTP auth
		// method or request got redirected)
		$this->httpResponseHeaders = preg_replace('/^.+\r\n\r\n(.+)/sU', '$1',
				$this->httpResponseHeaders);

        // Parse response
		$this->ParseResponseHeaders($this->httpResponseHeaders);
		$this->ParseResponse($this->httpResponseBody);

		/*
		   //TODO debug

		log_message('INTERNALS', 'REQh: ' . var_export($info['request_header'], TRUE));
		log_message('INTERNALS', 'REQb: ' . var_export($this->body, TRUE));
		log_message('INTERNALS', 'RPLh: ' . var_export($this->httpResponseHeaders, TRUE));
		log_message('INTERNALS', 'RPLb: ' . var_export($this->httpResponseBody, TRUE));
		*/

		return $response;
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
	function FindCalendars( $recursed=false,$url=null ) {
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
    					'http://calendarserver.org/ns/:calendar-proxy-read',
    					'http://calendarserver.org/ns/:calendar-proxy-write',
    					'http://calendarserver.org/ns/:calendar-proxy-read-for',
    					'http://calendarserver.org/ns/:calendar-proxy-write-for',
				 );
		if ($url === null)
			$url = $this->calendar_home_set[0];
		$this->DoPROPFINDRequest( $url, $properties, 1);
		return $this->parse_calendar_info( ! $recursed );
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

		return $this->parse_calendar_info( false );
	}

	/**
	 * Get calendar info after a PROPFIND
	 */
	function parse_calendar_info( $recurse ) {

		$calendars = array();
		if ( isset($this->xmltags['urn:ietf:params:xml:ns:caldav:calendar']) ) {
			$calendar_urls = array();
			foreach( $this->xmltags['urn:ietf:params:xml:ns:caldav:calendar'] AS $k => $v ) {
				$calendar_urls[$this->HrefForProp('urn:ietf:params:xml:ns:caldav:calendar', $k)] = 1;
			}

			foreach( $this->xmltags['DAV::href'] AS $i => $hnode ) {
				$href = rawurldecode($this->xmlnodes[$hnode]['value']);

				if ( !isset($calendar_urls[$href]) ) continue;
				unset ( $calendar_urls[$href]);

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
		if ( $recurse and isset($this->xmltags['http://calendarserver.org/ns/:calendar-proxy-write-for']) ) {
			$calendar_urls = array();
			for($i = 0; $i < count($this->xmltags['http://calendarserver.org/ns/:calendar-proxy-write-for']); $i+=2) {
			        $range_begin = $this->xmltags['http://calendarserver.org/ns/:calendar-proxy-write-for'][$i];
			        $range_end = $this->xmltags['http://calendarserver.org/ns/:calendar-proxy-write-for'][$i+1];
				for ($j = $range_begin + 1; $j < $range_end; $j++) {
					if ($this->xmlnodes[$j]['tag'] == 'DAV::href' ) {
						$href = $this->xmlnodes[$j]['value'];
						$calendar_urls[$href] = 1;
					}
				}
			}
			$query_urls = array();
			foreach( $this->xmltags['DAV::href'] AS $i => $hnode ) {
				$href = $this->xmlnodes[$hnode]['value'];

				if ( !isset($calendar_urls[$href]) ) continue;
				if ( $href == $url ) continue;
				$query_urls[] = $href;
			}
			foreach (array_unique ($query_urls) as $i => $href) {
			        $href = parse_url($this->request_url, PHP_URL_SCHEME).'://'.parse_url($this->request_url, PHP_URL_HOST).$href;
				$new_calendars = $this->FindCalendars(true, $href);
				$calendars = array_merge($calendars, $new_calendars);
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

	/**
	 * Queries server using a principal-property search
	 *
	 * @param string	XML request
	 * @param string	URL
	 * @return			FALSE on error, array with results otherwise
	 */
	function principal_property_search($xml_text, $url) {
		$result = array();
		$this->DoXMLRequest('REPORT', $xml_text, $url);

		if ($this->httpResultCode == '207') {
			$errmsg = $this->httpResultCode;
			// Find response tag(s)
			if (isset($this->xmltags['DAV::response'])) {
				foreach ($this->xmltags['DAV::response'] as $i => $node) {
					if ($this->xmlnodes[$node]['type'] == 'close') {
						continue;
					}

					$result[$i]['href'] =
						$this->HrefForProp('DAV::response', $i+1);

					$level = $this->xmlnodes[$node]['level'];
					$level++;

					$ok_props = $this->GetOKProps($node);

					foreach ($ok_props as $v) {
						switch($v['tag']) {
							case 'DAV::displayname':
								$result[$i]['displayname'] =
									isset($v['value']) ? $v['value'] : '';
								break;
							case 'DAV::email':
								$result[$i]['email'] = 
									isset($v['value']) ? $v['value'] : '';
								break;
						}
					}

				}
			}
		} else if ($this->httpResultCode != 200) {
			return 'Unknown HTTP code';
		}

		return $result;
	}

}
