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

class Caldav {
    private $final_url;
    private $CI;
    private static $client = null;

    private $http_auth_method;

    function __construct($params) {

        $this->CI =& get_instance();

        $this->http_auth_method =
            $this->CI->config->item('caldav_http_auth_method');

        // Empty string or FALSE
        if ($this->http_auth_method != CURLAUTH_BASIC &&
                 $this->http_auth_method != CURLAUTH_DIGEST) {
            $this->http_auth_method = CURLAUTH_BASIC | CURLAUTH_DIGEST;
        }

        // Load ICS helper library
        $this->CI->load->library('icshelper');

        require_once('caldav-client.php');

    }

    /**
     *
     * Returns FALSE on error
     */
    function fetch_events( $user, $passwd, $start, $end,
                        $calendar = 'calendario') {
        $this->prepare_client($user, $passwd, $calendar);

        $events = $this->client->GetEvents($start, $end);

        // Bogus CalDAV server
        if ($events === FALSE) {
            $this->CI->extended_logs->message('ERROR', 
                    'Possible invalid CalDAV server');
        } else {
            $this->CI->extended_logs->message('INTERNALS', 'Received ' 
                    .  count($events) . ' event(s)');
        }

        return $events;
    }

    /**
     *
     * Returns FALSE on error, null if no event was found
     */
    function fetch_resource_by_uid( $user, $passwd, $uid,
                        $calendar = 'calendario') {
        $this->prepare_client($user, $passwd, $calendar);

        $resource = array_values($this->client->GetEntryByUid($uid));

        if (!is_array($resource) || count($resource) == 0) {
            $this->CI->extended_logs->message('INTERNALS', 
                    'Search for uid=' . $uid 
                    . ' on calendar ' . $calendar 
                    . ' failed or returned more than one element '
                    .'['.count($resource).']');
            return null;
        } else {
            return $resource[0];
        }
    }

    /**
     * Deletes a resource
     *
     * Returns TRUE on success, or an i18n array (msg, [params]) 
     * on failure
     */

    function delete_resource( $user, $passwd, $href,
                        $calendar = 'calendario',
                        $etag = null) {

        $this->prepare_client($user, $passwd, $calendar);

        $resource = $this->build_calendar_url($user, $calendar, $href);

        $res = $this->client->DoDELETERequest($resource, $etag);

        // Did this operation succeed?
        $success = FALSE;
        $logmsg = '';
        $usermsg = '';
        $params = array();
        switch ($res) {
            case '404':
                $logmsg = 'Element not found';
                $usermsg = 'error_eventnotfound';
                break;
            case '412':
                $logmsg = 'Element was modified while deleting';
                $usermsg = 'error_eventchanged';
                break;
            case '204':
            case '200':
                // Success
                $success = TRUE;
                break;
            default:
                $logmsg = "HTTP code: " . $res;
                $usermsg = 'error_unknownhttpcode'; 
                $params = array('%res' => $res);
                break;
        }

        if ($success === FALSE) {
            $this->CI->extended_logs->message('INTERNALS', 
                    'Delete failed for resource ' . $href .
                    'on calendar ' . $calendar .'. Reason: ' .
                    $logmsg);
            return array($usermsg, $params);
        } else {
            $this->CI->extended_logs->message('INTERNALS', 
                    'Deleted resource ' . $href 
                    .' from calendar ' .  $calendar);
            return TRUE;
        }

    }

    /**
     * Puts a iCalendar resource (iCalComponent object)
     * 
     * Returns etag of new resource, or FALSE if failed
     *
     * Use etag = '*' when adding new resources
     */

    function put_resource( $user, $passwd, $href,
                        $calendar = 'calendario',
                        $icalendar,
                        $etag = null) {

        $this->prepare_client($user, $passwd, $calendar);

        // Avoid strange problems with empty href and empty calendars
        if (!isset($href) || empty($href)) {
            $this->CI->extended_logs->message('ERROR', 'Discarding PUT'
                    .' attempt without href specified');
            return FALSE;
        }
        $url = $this->build_calendar_url($user, $calendar, $href);
        $ical_text = $icalendar->createCalendar();
        $new_etag = $this->client->DoPUTRequest($url, $ical_text, $etag);

        switch ($this->client->GetHTTPResultCode()) {
            case '412':
                // ETag match failed
                $this->CI->extended_logs->message('INTERNALS',
                        'PUT with ETag=' . $etag . ' failed on '
                        .$url . '. Precondition failed.');
                return FALSE;
            case '201':
            case '204':
                // All right
                $this->CI->extended_logs->message('INTERNALS',
                        'Successful PUT with ETag=' . $etag . ' on '
                        . $url);
                return $new_etag;
            default:
                $this->CI->extended_logs->message('INTERNALS',
                        'PUT with ETag=' . $etag . ' on '
                        . $url . ' returned ' .
                        $this->client->GetHttpResultCode());
                return FALSE;
        }

    }


    /**
     * Constructs the full CalDAV URL and client
     */
    function prepare_client($user, $passwd, $calendar = 'home') {
        $this->final_url = $this->build_calendar_url($user, $calendar);
        if (self::$client === null) {
            $this->client = new CalDAVClient($this->final_url, $user, $passwd,
                    array('auth' => $this->http_auth_method));
            $this->client->SetUserAgent('AgenDAV v' . AGENDAV_VERSION);
        } else {
            $this->client->setCredentials($user, $passwd);
        }
        $this->client->SetCalendar($this->final_url);
        $this->client->PrincipalURL($this->final_url);
        $this->client->CalendarHomeSet($this->final_url);
    }

    /**
     * Is this a valid calendar resource?
     */
    function is_valid_calendar($user, $passwd, $calendar) {
        $this->prepare_client($user, $passwd, $calendar);
        $url = $this->build_calendar_url($user, $calendar);
        $info = $this->client->GetCalendarDetailsByURL($url);

        if ($this->client->GetHttpResultCode() != '207') {
            // Resource not found (404) or no enough permissions (403)
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Check for correct authentication
     */

    function check_server_authentication($user, $passwd) {
        $this->prepare_client($user, $passwd, '');
        return $this->client->CheckValidCalDAV();
    }

    /**
     * Gets last HTTP response in array form:
     * [http result code, http response headers, http response body]
     */

    function get_last_response() {
        if (is_null($this->client)) {
            return array ('', '', '');
        } else {
            return array(
                    $this->client->GetHttpResultCode(),
                    $this->client->GetResponseHeaders(),
                    $this->client->GetResponseBody(),
                    );
        }
    }

    /**
     * Get a list of calendars owned by current user
     */
    function get_own_calendars($user, $passwd) {
        $this->prepare_client($user, $passwd, '');

        return $this->client->FindCalendars();
    }

    /**
     * Get the properties of a calendar list
     *
     * @return Prepared data for browser, FALSE on error
     */
    function get_shared_calendars_info($user, $passwd, $calendar_list) {
        $this->prepare_client($user, $passwd, '');
        
        $tmpcals = array();
        foreach ($calendar_list as $calid => $properties_on_db) {
            $url = $this->build_calendar_url($user, $calid);
            $info = $this->client->GetCalendarDetailsByURL($url);

            if (!is_array($info) || count($info) == 0) {
                // Something went really wrong in this calendar
                $this->CI->extended_logs->message('ERROR', 
                        'Ignoring shared calendar '
                        . $url . '. PROPFIND yielded '
                        . $this->client->GetHttpResultCode());
                continue;
            }

            $properties = $info[$calid];


            // Give priority to previous data (user customizations?)
            $preserve = array('sid', 'user_from', 'color', 'displayname');
            foreach ($preserve as $p) {
                if (isset($properties_on_db[$p])) {
                    $properties->$p = $properties_on_db[$p];
                }
            }

            $properties->shared = TRUE;
            $properties->write_access = $properties_on_db['write_access'];
            $tmpcals[$calid] = $properties;
        }

        return $tmpcals;
    }

    /**
     * Creates a new calendar inside a principal collection
     *
     * @return boolean  TRUE on successful creation, i18n array (msg,
     * [params])
     */

    function mkcalendar( $user, $passwd, $calendar = '',
                        $props = array()) {

        $this->prepare_client($user, $passwd, '');

        // Preconditions
        $logmsg = '';
        $usermsg = '';
        $params = array();

        // Empty calendar?
        if (empty($calendar)) {
            $logmsg = 'no internal name specified';
            $usermsg = 'error_internalcalnamemissing';
        }
        
        if (!isset($props['displayname'])) {
            $logmsg = 'no display name specified';
            $usermsg = 'error_calnamemissing';
        }

        if (!isset($props['color'])) {
            $logmsg = 'no color specified';
            $usermsg = 'error_calcolormissing';
        }

        if (!empty($logmsg)) {
            $this->CI->extended_logs->message('ERROR', 
                    'Invalid call to mkcalendar(): ' . $logmsg);
            return array($usermsg, $params);
        }

        $url = $this->build_calendar_url($user, $calendar);

        // Create XML body
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                'http://apple.com/ns/ical/' => 'ical');
        $xml = new XMLDocument($ns);
        $set = $xml->NewXMLElement('set');
        $prop = $set->NewElement('prop');
        $xml->NSElement($prop, 'displayname', $props['displayname']);
        $xml->NSElement($prop, 
                'http://apple.com/ns/ical/:calendar-color',
                $props['color']);

        // TODO: associate timezone? AWL doesn't like <CDATA, 
        // gets replaced by html entity
        
        $xml_text = $xml->Render('C:mkcalendar',
                $set, null, 'http://apple.com/ns/ical/:calendar-color');

        $res = $this->client->DoXMLRequest('MKCALENDAR', 
                $xml_text, $url);

        $success = FALSE;
        $logmsg = '';
        $usermsg = '';
        $params = array();

        switch ($this->client->GetHTTPResultCode()) {
            case '201':
                // OK
                $success = TRUE;
                break;
            case '207':
                // Error on parameters
                $logmsg = 'Invalid parameters (207)';
                $usermsg = 'error_mkcalendar';
                break;
            case '403':
                // Permission denied
                $logmsg = 'Access forbidden';
                $usermsg =  'error_denied';
                break;
            default:
                $code = $this->client->GetHttpResultCode();
                $logmsg = "HTTP code: " . $code;
                $usermsg = 'error_unknownhttpcode'; 
                $params = array('%res' => $code);
        }

        if ($success === FALSE) {
            $this->CI->extended_logs->message('INTERNALS',
                    'Calendar '.$calendar.' not created.'
                    .' Reason: ' . $logmsg);
            return array($usermsg, $params);
        } else {
            $this->CI->extended_logs->message('INTERNALS',
                    'Calendar ' . $calendar . ' successfully created');
            return TRUE;
        }
    }

    /**
     * Applies a properties change to a DAV resource
     *
     * @return boolean  TRUE on successful creation, i18n array (msg,
     * [params]) otherwise
     */
    function proppatch( $user, $passwd, $calendar = '',
                        $props = array()) {
        $this->prepare_client($user, $passwd, '');

        // Preconditions
        $logmsg = '';
        $usermsg = '';
        $params = array();

        // Empty calendar?
        if (empty($calendar)) {
            $logmsg = 'no internal name specified';
            $usermsg = 'error_internalcalnamemissing';
        }
        
        if (!isset($props['displayname'])) {
            $logmsg = 'no display name specified';
            $usermsg = 'error_calnamemissing';
        }

        if (!isset($props['color'])) {
            $logmsg = 'no color specified';
            $usermsg = 'error_calcolormissing';
        }

        if (!empty($logmsg)) {
            $this->CI->extended_logs->message('ERROR', 
                    'Invalid call to proppatch(): ' . $logmsg);
            return array($usermsg, $params);
        }

        $url = $this->build_calendar_url($user, $calendar);

        // Create XML body
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                'http://apple.com/ns/ical/' => 'ical');
        $xml = new XMLDocument($ns);
        $set = $xml->NewXMLElement('set');
        $prop = $set->NewElement('prop');
        $xml->NSElement($prop, 'displayname', $props['displayname']);
        $xml->NSElement($prop, 
                'http://apple.com/ns/ical/:calendar-color',
                $props['color']);

        // TODO: associate timezone? AWL doesn't like <CDATA, 
        // gets replaced by html entity
        
        $xml_text = $xml->Render('propertyupdate',
                $set, null, 'http://apple.com/ns/ical/:calendar-color');

        $result = $this->client->DoPROPPATCH($xml_text, $url);

        $success = FALSE;
        $logmsg = '';
        $usermsg = '';

        if ($result === TRUE) {
            $success = TRUE;
        } else {
            $logmsg = $result;
            $usermsg = 'error_modfailed';
        }

        if ($success === FALSE) {
            $this->CI->extended_logs->message('INTERNALS',
                    'Calendar '.$calendar.' not modified.'
                    .' Found unexpected status on some properties: ' . $logmsg);
            return array($usermsg, $params);
        } else {
            $this->CI->extended_logs->message('INTERNALS',
                    'Calendar ' . $calendar . ' successfully modified');
            return TRUE;
        }
    }

    /**
     * Sets ACL on a resource
     *
     * @return boolean  TRUE on successful creation, i18n array (message,
     * [params]) otherwise
     */
    function setacl( $user, $passwd, $calendar = '',
                        $share_with = array()) {
        $this->prepare_client($user, $passwd, '');

        // Preconditions
        $logmsg = '';
        $usermsg = '';
        $params = array();

        // Empty calendar?
        if (empty($calendar)) {
            $logmsg = 'no internal name specified';
            $usermsg = 'error_internalcalnamemissing';
        }

        if (!empty($logmsg)) {
            $this->CI->extended_logs->message('ERROR', 'Call to setacl()'
                    .' with no calendar!');
            return array($usermsg, $params);
        }
        
        $url = $this->build_calendar_url($user, $calendar);

        // Create XML body
        $xmlbody = $this->generate_acl_xml($share_with);
        if ($xmlbody === FALSE) {
            $this->CI->extended_logs->message('ERROR', 'Call to setacl()'
                    .' generated invalid XML code. Giving up.');
            return array('error_internal', array());
        }

        $res = $this->client->DoXMLRequest('ACL', 
                $xmlbody, $url);

        $success = FALSE;
        $logmsg = '';
        $usermsg = '';
        $params = array();

        switch ($this->client->GetHTTPResultCode()) {
            case '200':
                // OK
                $success = TRUE;
                break;
            default:
                $code = $this->client->GetHTTPResultCode();
                $logmsg = "HTTP code: " . $code;
                $usermsg = 'error_unknownhttpcode'; 
                $params = array('%res' => $code);
        }

        if ($success === FALSE) {
            $this->CI->extended_logs->message('INTERNALS',
                    'ACL for calendar '.$calendar.' not modified.'
                    .' Reason: ' . $logmsg);
            return array($usermsg, $params);
        } else {
            $this->CI->extended_logs->message('INTERNALS',
                    'Successful modification of ACL for calendar ' 
                    . $calendar);
            return TRUE;
        }
    }

    /**
     * Searchs a principal based on passed conditions.
     */
    function principal_property_search($user, $passwd,
            $dn = null, $user_address = null,
            $use_or = TRUE) {

        $this->prepare_client($user, $passwd, '');

        if (is_null($dn) && is_null($user_address)) {
            $this->CI->extended_logs->message('ERROR',
                    'Call to principal_property_search '
                    .'with null dn and user_address');
            return array('err_invalidinput', array());
        }

        // Build XML
        $xml = '<principal-property-search xmlns="DAV:"' .
            ($use_or ? ' test="anyof"' : '') . '>';
        if (!is_null($dn)) {
            $xml .= '<property-search>';
            $xml .= '<prop><displayname/></prop>';
            $xml .= '<match>' . $dn . '</match></property-search>';
        }

        if (!is_null($user_address)) {
            $xml .= '<property-search><prop>';
            $xml .= '<C:calendar-user-address-set '
                .'xmlns:C="urn:ietf:params:xml:ns:caldav"/></prop>';
            $xml .= '<match>'.$user_address.'</match></property-search>';
        }

        $xml .=
            '<prop><displayname/><email/></prop></principal-property-search>';

        // Do request
        $url = $this->build_principal_url($user);

        $res = $this->client->principal_property_search($xml, $url);

        // Extract usernames from $res
        $return_results = array();
        foreach ($res as $elem) {
            $username = $this->extract_username_from_href($elem['href']);
            $elem['username'] = $username;
            $return_results[$username] = $elem;
        }

        // Remove current user, if present
        unset($return_results[$user]);

        // Sort by username
        ksort($return_results);

        return array(
                $this->client->GetHTTPResultCode(),
                $return_results);
    }

    /**
     * Returns the public CalDAV URL for a calendar
     *
     * @param   $calendar   String in the form 'user:calendar', or just
     *                      'user'
     */
    function construct_public_url($calendar = '') {
        $calendar = preg_replace('/:/', '/', $calendar);
        $url = preg_replace('/%s/', $calendar,
                $this->CI->config->item('public_caldav_url'));

        return $url;
    }


    /**
     * Converts a RGB hexadecimal string (#rrggbb or short #rgb) to full
     * RGBA
     */
    function _rgb2rgba($s) {
        if (strlen($s) == '7') {
            return $s . 'ff';
        } elseif (strlen($s) == '4') {
            $res = preg_match('/#(.)(.)(.)/', $s, $matches);
            return '#' . $matches[1] . $matches[1] . $matches[2] .
                $matches[2] . $matches[3] . $matches[3] . 'ff';
        } else {
            // Unknown string
            return $s;
        }
    }


    /**
     * Generates a complete ACL to be set on a calendar
     *
     * @param $share_with   Array of shares in the form:
     *                      [ [sid?, username, write_access],
     *                        [sid2?, username2, write_access2] ..]
     *
     * @return  boolean     TRUE if everything went ok, FALSE otherwise
     */
    function generate_acl_xml($share_with = array()) {
        $ns = array(
                'DAV:' => '', 
                'urn:ietf:params:xml:ns:caldav' => 'C',
                );
        $xml = new XMLDocument($ns);
        $aces = array();

        // Permissions
        $owner_perm = $this->CI->config->item('owner_permissions');
        $r_perm = $this->CI->config->item('read_profile_permissions');
        $rw_perm = $this->CI->config->item('read_write_profile_permissions');
        $other_perm = $this->CI->config->item('default_permissions');

        // Owner permissions
        $aces[] = $this->_ace_for($xml, null, $owner_perm, TRUE);

        // User which can access this calendar
        foreach ($share_with as $share) {
            $user_url = $this->build_principal_url($share['username']);
            $aces[] = $this->_ace_for($xml, $user_url,
                    ($share['write_access'] == '1' ?  $rw_perm : $r_perm));
        }

        // Other users
        $aces[] = $this->_ace_for($xml, null, $other_perm, FALSE);

        return $xml->Render('acl', $aces);
    }

    /**
     * Generates an ACE element
     */
    function _ace_for(&$xmldoc, $user = null, $perms = array(),
            $is_owner = FALSE) {
        $ace = $xmldoc->NewXMLElement('ace');
        $principal = $ace->NewElement('principal');

        if ($is_owner === TRUE) {
            $principal->NewElement('property')->NewElement('owner');
        } elseif (is_null($user)) {
            $principal->NewElement('authenticated');
        } else {
            $principal->NewElement('href', $user);
        }

        $grant = $ace->Newelement('grant');
        foreach ($perms as $p) {
            $grant->NewElement('privilege')->NewElement($p);
        }

        return $ace;
    }


    /**
     * Builds a principal URL for a given username
     *
     * @param   $user   Username
     */
    function build_principal_url($user) {
        $principal_url = $this->CI->config->item('caldav_principal_url');

        $built = preg_replace('/%u/', $user, $principal_url);
        return $built;
    }

    /**
     * Builds an URL for a calendar or a resource included in a calendar
     * collection
     *
     * @param   $user   Username
     * @param   $calendar   Calendar name. It can be just a calendar name,
     *                      or a identified like 'user:calendar'. In that
     *                      case, URL will be built using these values
     * @param   $href       Optional href, which will be appended to the URL
     */
    function build_calendar_url($user, $calendar, $href = '') {
        $calendar_url = $this->CI->config->item('caldav_calendar_url');

        $pieces = preg_split('/:/', $calendar);
        if (count($pieces) == '1') {
            $use_principal = $user;
        } else {
            $use_principal = $pieces[0];
            $calendar = $pieces[1];
        }

        $replacement = $use_principal 
            . (empty($calendar) ? '' : '/' .  rawurlencode($calendar));
        $built = preg_replace('/%s/', $replacement, $calendar_url) 
            . $href;

        log_message('DEBUG', 'Built calendar URL: ' . $built);
        return $built;
    }

    /**
     * Extracts username from a principal URL
     */
    function extract_username_from_href($href) {
        $tmp_href = parse_url($href);
        $href = $tmp_href['path'];

        $tmp_pattern_parsed =
            parse_url($this->CI->config->item('caldav_principal_url'));
        $pattern_path = $tmp_pattern_parsed['path'];

        // Build a pattern that matches href to extract just the %u part
        $extract_pattern = preg_replace(
                array(
                    '/\/%u\//', '/\//'),
                array('/([^/]+)/',
                    '\/'),
                $pattern_path);

        $matches = preg_match('/' . $extract_pattern . '/',
                $href, $fragments);
        if ($matches == 0) {
            $this->CI->extended_logs->message('ERROR',
                    'Trying to extract username from invalid '
                    .'href: ['.$href.']');
            return '';
        } else {
            return $fragments[1];
        }
    }


    /**
     * Loads full list of calendars for current user
     */
    function all_user_calendars($user, $passwd) {
        $ret = array();

        // TODO order
        $own_calendars = $this->get_own_calendars($user, $passwd);
        $ret = $own_calendars;

        if ($this->CI->config->item('enable_calendar_sharing')) {
            // Add sharing information for this calendar
            foreach ($ret as $calendar) {
                $calendar->share_with =
                    $this->CI->shared_calendars->users_with_access_to($calendar->calendar);
            }

            // Look for shared calendars
            $tmp_shared_calendars =
                $this->CI->shared_calendars->get_shared_with($user);

            if (is_array($tmp_shared_calendars) && count($tmp_shared_calendars) > 0) {
                $shared_calendars = $this->get_shared_calendars_info($user,
                        $passwd, $tmp_shared_calendars);
                if ($shared_calendars === FALSE) {
                    $this->CI->extended_logs->message('ERROR', 
                            'Error reading shared calendars');
                } else {
                    $ret = array_merge($ret, $shared_calendars);
                }
            }
        }

        // Set public URLs
        foreach ($ret as $calendar) {
            $calendar->public_url =
                $this->construct_public_url($calendar->calendar);
        }

        return $ret;
    }



}

