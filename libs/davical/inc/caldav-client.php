<?php
/**
* A Class for connecting to a caldav server
*
* @package   awl
* removed curl - now using fsockopen
* changed 2009 by Andres Obrero - Switzerland andres@obrero.ch
*
* @subpackage   caldav
* @author Andrew McMillan <debian@mcmillan.net.nz>
* @copyright Andrew McMillan
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/


/**
* A class for accessing DAViCal via CalDAV, as a client
*
* @package   awl
*/
class CalDAVClient {
  /**
  * Server, username, password, calendar
  *
  * @var string
  */
  var $base_url, $user, $pass, $calendar, $entry, $protocol, $server, $port;

  /**
  * The useragent which is send to the caldav server
  *
  * @var string
  */
  var $user_agent = 'DAViCalClient';

  var $headers = array();
  var $body = "";
  var $requestMethod = "GET";
  var $httpRequest = ""; // for debugging http headers sent
  var $xmlRequest = ""; // for debugging xml sent
  var $httpResponse = ""; // for debugging http headers received
  var $xmlResponse = ""; // for debugging xml received

  /**
  * Constructor, initialises the class
  *
  * @param string $base_url  The URL for the calendar server
  * @param string $user      The name of the user logging in
  * @param string $pass      The password for that user
  * @param string $calendar  The name of the calendar (not currently used)
  */
  function CalDAVClient( $base_url, $user, $pass, $calendar = '' ) {
    $this->user = $user;
    $this->pass = $pass;
    $this->calendar = $calendar;
    $this->headers = array();

    if ( preg_match( '#^(https?)://([a-z0-9.-]+)(:([0-9]+))?(/.*)$#', $base_url, $matches ) ) {
      $this->server = $matches[2];
      $this->base_url = $matches[5];
      if ( $matches[1] == 'https' ) {
        $this->protocol = 'ssl';
        $this->port = 443;
      }
      else {
        $this->protocol = 'tcp';
        $this->port = 80;
      }
      if ( $matches[4] != '' ) {
        $this->port = intval($matches[4]);
      }
    }
    else {
      trigger_error("Invalid URL: '".$base_url."'", E_USER_ERROR);
    }
  }

  /**
  * Adds an If-Match or If-None-Match header
  *
  * @param bool $match to Match or Not to Match, that is the question!
  * @param string $etag The etag to match / not match against.
  */
  function SetMatch( $match, $etag = '*' ) {
    $this->headers[] = sprintf( "%s-Match: %s", ($match ? "If" : "If-None"), $etag);
  }

 /*
  * Add a Depth: header.  Valid values are 0, 1 or infinity
  *
  * @param int $depth  The depth, default to infinity
  */
  function SetDepth( $depth = '0' ) {
    $this->headers[] = 'Depth: '. ($depth == '1' ? "1" : ($depth == 'infinity' ? $depth : "0") );
  }

  /**
  * Add a Depth: header.  Valid values are 1 or infinity
  *
  * @param int $depth  The depth, default to infinity
  */
  function SetUserAgent( $user_agent = null ) {
    if ( !isset($user_agent) ) $user_agent = $this->user_agent;
    $this->user_agent = $user_agent;
  }

  /**
  * Add a Content-type: header.
  *
  * @param int $type  The content type
  */
  function SetContentType( $type ) {
    $this->headers[] = "Content-type: $type";
  }

  /**
  * Split response into httpResponse and xmlResponse
  *
  * @param string Response from server
   */
  function ParseResponse( $response ) {
      $pos = strpos($response, '<?xml');
      if ($pos === false) {
        $this->httpResponse = trim($response);
      }
      else {
        $this->httpResponse = trim(substr($response, 0, $pos));
        $this->xmlResponse = trim(substr($response, $pos));
      }
  }

  /**
   * Output http request headers
   *
   * @return HTTP headers
   */
  function GetHttpRequest() {
      return $this->httpRequest;
  }
  /**
   * Output http response headers
   *
   * @return HTTP headers
   */
  function GetHttpResponse() {
      return $this->httpResponse;
  }
  /**
   * Output xml request
   *
   * @return raw xml
   */
  function GetXmlRequest() {
      return $this->xmlRequest;
  }
  /**
   * Output xml response
   *
   * @return raw xml
   */
  function GetXmlResponse() {
      return $this->xmlResponse;
  }

  /**
  * Send a request to the server
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  *
  * @return string The content of the response from the server
  */
  function DoRequest( $relative_url = "" ) {
    if(!defined("_FSOCK_TIMEOUT")){ define("_FSOCK_TIMEOUT", 10); }
    $headers = array();

    $headers[] = $this->requestMethod." ". $this->base_url . $relative_url . " HTTP/1.1";
    $headers[] = "Authorization: Basic ".base64_encode($this->user .":". $this->pass );
    $headers[] = "Host: ".$this->server .":".$this->port;

    foreach( $this->headers as $ii => $head ) {
      $headers[] = $head;
    }
    $headers[] = "Content-Length: " . strlen($this->body);
    $headers[] = "User-Agent: " . $this->user_agent;
    $headers[] = 'Connection: close';
    $this->httpRequest = join("\r\n",$headers);
    $this->xmlRequest = $this->body;

    $fip = fsockopen( $this->protocol . '://' . $this->server, $this->port, $errno, $errstr, _FSOCK_TIMEOUT); //error handling?
    if ( !(get_resource_type($fip) == 'stream') ) return false;
    if ( !fwrite($fip, $this->httpRequest."\r\n\r\n".$this->body) ) { fclose($fip); return false; }
    $rsp = "";
    while( !feof($fip) ) { $rsp .= fgets($fip,8192); }
    fclose($fip);

    $this->headers = array();  // reset the headers array for our next request
    $this->ParseResponse($rsp);
    return $rsp;
  }


  /**
  * Send an OPTIONS request to the server
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  *
  * @return array The allowed options
  */
  function DoOptionsRequest( $relative_url = "" ) {
    $this->requestMethod = "OPTIONS";
    $this->body = "";
    $headers = $this->DoRequest($relative_url);
    $options_header = preg_replace( '/^.*Allow: ([a-z, ]+)\r?\n.*/is', '$1', $headers );
    $options = array_flip( preg_split( '/[, ]+/', $options_header ));
    return $options;
  }



  /**
  * Send an XML request to the server (e.g. PROPFIND, REPORT, MKCALENDAR)
  *
  * @param string $method The method (PROPFIND, REPORT, etc) to use with the request
  * @param string $xml The XML to send along with the request
  * @param string $relative_url The URL to make the request to, relative to $base_url
  *
  * @return array An array of the allowed methods
  */
  function DoXMLRequest( $request_method, $xml, $relative_url = '' ) {
    $this->body = $xml;
    $this->requestMethod = $request_method;
    $this->SetContentType("text/xml");
    return $this->DoRequest($relative_url);
  }



  /**
  * Get a single item from the server.
  *
  * @param string $relative_url The part of the URL after the calendar
  */
  function DoGETRequest( $relative_url ) {
    $this->body = "";
    $this->requestMethod = "GET";
    return $this->DoRequest( $relative_url );
  }


  /**
  * PUT a text/icalendar resource, returning the etag
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  * @param string $icalendar The iCalendar resource to send to the server
  * @param string $etag The etag of an existing resource to be overwritten, or '*' for a new resource.
  *
  * @return string The content of the response from the server
  */
  function DoPUTRequest( $relative_url, $icalendar, $etag = null ) {
    $this->body = $icalendar;

    $this->requestMethod = "PUT";
    if ( $etag != null ) {
      $this->SetMatch( ($etag != '*'), $etag );
    }
    $this->SetContentType("text/icalendar");
    $headers = $this->DoRequest($relative_url);

    /**
    * RSCDS will always return the real etag on PUT.  Other CalDAV servers may need
    * more work, but we are assuming we are running against RSCDS in this case.
    */
    $etag = preg_replace( '/^.*Etag: "?([^"\r\n]+)"?\r?\n.*/is', '$1', $headers );
    return $etag;
  }


  /**
  * DELETE a text/icalendar resource
  *
  * @param string $relative_url The URL to make the request to, relative to $base_url
  * @param string $etag The etag of an existing resource to be deleted, or '*' for any resource at that URL.
  *
  * @return int The HTTP Result Code for the DELETE
  */
  function DoDELETERequest( $relative_url, $etag = null ) {
    $this->body = "";

    $this->requestMethod = "DELETE";
    if ( $etag != null ) {
      $this->SetMatch( true, $etag );
    }
    $this->DoRequest($relative_url);
    return $this->resultcode;
  }


  /**
  * Given XML for a calendar query, return an array of the events (/todos) in the
  * response.  Each event in the array will have a 'href', 'etag' and '$response_type'
  * part, where the 'href' is relative to the calendar and the '$response_type' contains the
  * definition of the calendar data in iCalendar format.
  *
  * @param string $filter XML fragment which is the <filter> element of a calendar-query
  * @param string $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  * @param string $report_type Used as a name for the array element containing the calendar data. @deprecated
  *
  * @return array An array of the relative URLs, etags, and events from the server.  Each element of the array will
  *               be an array with 'href', 'etag' and 'data' elements, corresponding to the URL, the server-supplied
  *               etag (which only varies when the data changes) and the calendar data in iCalendar format.
  */
  function DoCalendarQuery( $filter, $relative_url = '' ) {

    $xml = <<<EOXML
<?xml version="1.0" encoding="utf-8" ?>
<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <C:calendar-data/>
    <D:getetag/>
  </D:prop>$filter
</C:calendar-query>
EOXML;

    $this->DoXMLRequest( 'REPORT', $xml, $relative_url );
    $xml_parser = xml_parser_create_ns('UTF-8');
    $this->xml_tags = array();
    xml_parser_set_option ( $xml_parser, XML_OPTION_SKIP_WHITE, 1 );
    xml_parse_into_struct( $xml_parser, $this->xmlResponse, $this->xml_tags );
    xml_parser_free($xml_parser);

    $report = array();
    foreach( $this->xml_tags as $k => $v ) {
      switch( $v['tag'] ) {
        case 'DAV::RESPONSE':
          if ( $v['type'] == 'open' ) {
            $response = array();
          }
          elseif ( $v['type'] == 'close' ) {
            $report[] = $response;
          }
          break;
        case 'DAV::HREF':
          $response['href'] = basename( $v['value'] );
          break;
        case 'DAV::GETETAG':
          $response['etag'] = preg_replace('/^"?([^"]+)"?/', '$1', $v['value']);
          break;
        case 'URN:IETF:PARAMS:XML:NS:CALDAV:CALENDAR-DATA':
          $response['data'] = $v['value'];
          break;
      }
    }
    return $report;
  }


  /**
  * Get the events in a range from $start to $finish.  The dates should be in the
  * format yyyymmddThhmmssZ and should be in GMT.  The events are returned as an
  * array of event arrays.  Each event array will have a 'href', 'etag' and 'event'
  * part, where the 'href' is relative to the calendar and the event contains the
  * definition of the event in iCalendar format.
  *
  * @param timestamp $start The start time for the period
  * @param timestamp $finish The finish time for the period
  * @param string    $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  *
  * @return array An array of the relative URLs, etags, and events, returned from DoCalendarQuery() @see DoCalendarQuery()
  */
  function GetEvents( $start = null, $finish = null, $relative_url = '' ) {
    $filter = "";
    if ( isset($start) && isset($finish) )
        $range = "<C:time-range start=\"$start\" end=\"$finish\"/>";
    else
        $range = '';

    $filter = <<<EOFILTER
  <C:filter>
    <C:comp-filter name="VCALENDAR">
      <C:comp-filter name="VEVENT">
        $range
      </C:comp-filter>
    </C:comp-filter>
  </C:filter>
EOFILTER;

    return $this->DoCalendarQuery($filter, $relative_url);
  }


  /**
  * Get the todo's in a range from $start to $finish.  The dates should be in the
  * format yyyymmddThhmmssZ and should be in GMT.  The events are returned as an
  * array of event arrays.  Each event array will have a 'href', 'etag' and 'event'
  * part, where the 'href' is relative to the calendar and the event contains the
  * definition of the event in iCalendar format.
  *
  * @param timestamp $start The start time for the period
  * @param timestamp $finish The finish time for the period
  * @param boolean   $completed Whether to include completed tasks
  * @param boolean   $cancelled Whether to include cancelled tasks
  * @param string    $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  *
  * @return array An array of the relative URLs, etags, and events, returned from DoCalendarQuery() @see DoCalendarQuery()
  */
  function GetTodos( $start, $finish, $completed = false, $cancelled = false, $relative_url = "" ) {

    if ( $start && $finish ) {
$time_range = <<<EOTIME
                <C:time-range start="$start" end="$finish"/>
EOTIME;
    }

    // Warning!  May contain traces of double negatives...
    $neg_cancelled = ( $cancelled === true ? "no" : "yes" );
    $neg_completed = ( $cancelled === true ? "no" : "yes" );

    $filter = <<<EOFILTER
  <C:filter>
    <C:comp-filter name="VCALENDAR">
          <C:comp-filter name="VTODO">
                <C:prop-filter name="STATUS">
                        <C:text-match negate-condition="$neg_completed">COMPLETED</C:text-match>
                </C:prop-filter>
                <C:prop-filter name="STATUS">
                        <C:text-match negate-condition="$neg_cancelled">CANCELLED</C:text-match>
                </C:prop-filter>$time_range
          </C:comp-filter>
    </C:comp-filter>
  </C:filter>
EOFILTER;

    return $this->DoCalendarQuery($filter, $relative_url);
  }


  /**
  * Get the calendar entry by UID
  *
  * @param uid
  * @param string    $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  *
  * @return array An array of the relative URL, etag, and calendar data returned from DoCalendarQuery() @see DoCalendarQuery()
  */
  function GetEntryByUid( $uid, $relative_url = '' ) {
    $filter = "";
    if ( $uid ) {
      $filter = <<<EOFILTER
  <C:filter>
    <C:comp-filter name="VCALENDAR">
          <C:comp-filter name="VEVENT">
                <C:prop-filter name="UID">
                        <C:text-match icollation="i;octet">$uid</C:text-match>
                </C:prop-filter>
          </C:comp-filter>
    </C:comp-filter>
  </C:filter>
EOFILTER;
    }

    return $this->DoCalendarQuery($filter, $relative_url);
  }


  /**
  * Get the calendar entry by HREF
  *
  * @param string    $href         The href from a call to GetEvents or GetTodos etc.
  * @param string    $relative_url The URL relative to the base_url specified when the calendar was opened.  Default ''.
  *
  * @return string The iCalendar of the calendar entry
  */
  function GetEntryByHref( $href, $relative_url = '' ) {
    return $this->DoGETRequest( $relative_url . $href );
  }

}

/**
* Usage example
*
* $cal = new CalDAVClient( "http://calendar.example.com/caldav.php/username/calendar/", "username", "password", "calendar" );
* $options = $cal->DoOptionsRequest();
* if ( isset($options["PROPFIND"]) ) {
*   // Fetch some information about the events in that calendar
*   $cal->SetDepth(1);
*   $folder_xml = $cal->DoXMLRequest("PROPFIND", '<?xml version="1.0" encoding="utf-8" ?><propfind xmlns="DAV:"><prop><getcontentlength/><getcontenttype/><resourcetype/><getetag/></prop></propfind>' );
* }
* // Fetch all events for February
* $events = $cal->GetEvents("20070101T000000Z","20070201T000000Z");
* foreach ( $events AS $k => $event ) {
*   do_something_with_event_data( $event['data'] );
* }
* $acc = array();
* $acc["google"] = array(
* "user"=>"kunsttherapie@gmail.com",
* "pass"=>"xxxxx",
* "server"=>"ssl://www.google.com",
* "port"=>"443",
* "uri"=>"https://www.google.com/calendar/dav/kunsttherapie@gmail.com/events/",
* );
*
* $acc["davical"] = array(
* "user"=>"some_user",
* "pass"=>"big secret",
* "server"=>"calendar.foo.bar",
* "port"=>"80",
* "uri"=>"http://calendar.foo.bar/caldav.php/some_user/home/",
* );
* //*******************************
*
* $account = $acc["davical"];
*
* //*******************************
* $cal = new CalDAVClient( $account["uri"], $account["user"], $account["pass"], "", $account["server"], $account["port"] );
* $options = $cal->DoOptionsRequest();
* print_r($options);
*
* //*******************************
* //*******************************
*
* $xmlC = <<<PROPP
* <?xml version="1.0" encoding="utf-8" ?>
* <D:propfind xmlns:D="DAV:" xmlns:C="http://calendarserver.org/ns/">
*     <D:prop>
*             <D:displayname />
*             <C:getctag />
*             <D:resourcetype />
*
*     </D:prop>
* </D:propfind>
* PROPP;
* //if ( isset($options["PROPFIND"]) ) {
*   // Fetch some information about the events in that calendar
* //  $cal->SetDepth(1);
* //  $folder_xml = $cal->DoXMLRequest("PROPFIND", $xmlC);
* //  print_r( $folder_xml);
* //}
*
* // Fetch all events for February
* $events = $cal->GetEvents("20090201T000000Z","20090301T000000Z");
* foreach ( $events as $k => $event ) {
*     print_r($event['data']);
*     print "\n---------------------------------------------\n";
* }
*
* //*******************************
* //*******************************
*/
