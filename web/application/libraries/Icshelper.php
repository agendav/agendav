<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/*
 * Copyright 2011-2014 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\CalDAV\Resource\CalendarObject;
use AgenDAV\Data\Reminder;
use AgenDAV\DateHelper;
use AgenDAV\Uuid;

class Icshelper {
    private $config; // for iCalCreator

    private $tz;

    private $date_format; // Date format given by lang file

    private $date_frontend_format_pref;
    private $date_frontend_format;
    private $time_frontend_format_pref;
    private $time_frontend_format;


    function __construct() {

        $this->CI =& get_instance();

        // Timezone
        $this->tz = $this->CI->timezonemanager->getTz(
                $this->CI->config->item('default_timezone'));

        $this->date_frontend_format_pref = $this->CI->config->item('default_date_format');
        $this->time_frontend_format_pref = $this->CI->config->item('default_time_format');
        $this->date_frontend_format = DateHelper::getDateFormatFor('date', $this->date_frontend_format_pref);
        $this->time_frontend_format = DateHelper::gettimeFormatFor('date', $this->time_frontend_format_pref);

        $this->config = array(
                'unique_id' =>
                $this->CI->config->item('icalendar_unique_id'),
                );

        require_once('iCalcreator.class.php');
    }

    /**
     * Creates a new iCalendar resource
     * 
     * Property keys can be lowercase
     *
     * Returns generated guid, FALSE on error. $generated will be filled with
     * new generated resource (iCalComponent object)
     */
    function new_resource($properties, &$generated, $tz, $reminders =
            array()) {
        $properties = array_change_key_case($properties, CASE_UPPER);

        $contents = '';
        $ical = new vcalendar($this->config);
        // Default CALSCALE in standard
        $ical->setProperty('calscale', 'GREGORIAN');

        $allday = (isset($properties['ALLDAY']) && $properties['ALLDAY'] ==
                'true');

        if ($allday) {
            // Discard timezone
            $tz = $this->CI->timezonemanager->getTz('UTC');
        }

        // Add VTIMEZONE
        $this->add_vtimezone($ical, $tz->getName());

        $vevent =& $ical->newComponent('vevent');

        $now = DateHelper::dateTimeToiCalendar(
            new \DateTime('now', $this->CI->timezonemanager->getTz('UTC')),
            'DATE-TIME'
        );
        $uid = Uuid::generate();

        $vevent->setProperty('CREATED', $now);
        $vevent->setProperty('LAST-MODIFIED', $now);
        $vevent->setProperty('DTSTAMP', $now);
        $vevent->setProperty('UID', $uid);
        $vevent->setProperty('SEQUENCE', '0'); // RFC5545, 3.8.7.4
        $vevent->setProperty('SUMMARY', $properties['SUMMARY']);

        // Rest of properties
        $add_prop = array('DTSTART', 'DTEND', 'DESCRIPTION', 'LOCATION',
                'DURATION', 'RRULE', 'TRANSP', 'CLASS');

        foreach ($add_prop as $p) {
            if (isset($properties[$p]) && !empty($properties[$p])) {
                $params = FALSE;

                // Generate DTSTART/DTEND
                if ($p == 'DTSTART' || $p == 'DTEND') {
                    if ($tz->getName() != 'UTC') {
                        $params = array('TZID' => $tz->getName());
                    }
                    $properties[$p] = $this->CI->dates->datetime2idt(
                            $properties[$p], $tz);
                    // All day: use parameter VALUE=DATE
                    if ($allday) {
                        $params['VALUE'] = 'DATE';
                    }
                }
                $vevent->setProperty($p, $properties[$p], $params);
            }
        }

        // VALARM components (reminders)
        $vevent = $this->set_valarms($vevent, $reminders);


        $generated = $ical;
        return $uid;
    }


    /**
     * Expands a list of resources to repeated events, depending on
     * recurrence rules and recurrence exceptions/modifications
     *
     * @param array   $resources  array of CalendarObject
     * @param int       $start      Start timestamp
     * @param int       $end        End timestamp
     * @param string        $calendar       Current calendar
     */
    function expand_and_parse_events(array $resources, $start, $end, $calendar) {
        $result = array();

        // Dates
        $utc = $this->CI->timezonemanager->getTz('UTC');
        $date_start = new DateTime($start, $utc);
        $date_end = new DateTime($end, $utc);

        foreach ($resources as $resource) {
            $event_href = $resource->getUrl();
            $event_etag = $resource->getEtag();

            $ical = new vcalendar($this->config);
            $res = $ical->parse($resource->getContents());
            if ($res === FALSE) {
                log_message('ERROR', "Couldn't parse event with href=" . $event_href);
            }
            $ical->sort();

            $timezones = $this->get_timezones($ical);

            $sy = intval($date_start->format('Y'));
            $sm = intval($date_start->format('m'));
            $sd = intval($date_start->format('d'));
            $ey = intval($date_end->format('Y'));
            $em = intval($date_end->format('m'));
            $ed = intval($date_end->format('d'));

            $expand = $ical->selectComponents($sy, $sm, $sd, $ey, $em, $ed,
                    'vevent', false, true, false);

            if ($expand !== FALSE) {
                foreach( $expand as $year => $year_arr ) {
                    foreach( $year_arr as $month => $month_arr ) {
                        foreach( $month_arr as $day => $day_arr ) {
                            foreach( $day_arr as $event ) {
                                $tz = $this->detect_tz($event, $timezones);
                                $result[] =
                                    $this->parse_vevent_fullcalendar($event,
                                        $event_href, $event_etag, $calendar,
                                        $tz, $timezones);
                            }
                        }
                    }
                }
            } else {
                $expand = $ical->selectComponents($sy, $sm, $sd, $ey, $em, $ed,
                        'vevent', true, true, false);
                if ($expand === FALSE) {
                    log_message('WARNING',
                            'CalendarObject ' . $calendar . $event_href . ' cannot be expanded.');
                } else {
                    foreach($expand as $event) {
                        $tz = $this->detect_tz($event, $timezones);
                        $result[] =
                            $this->parse_vevent_fullcalendar($event,
                                    $event_href, $event_etag, $calendar,
                                    $tz, $timezones);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Parses an VEVENT for Fullcalendar
     */
    function parse_vevent_fullcalendar($vevent, 
            $href, $etag, $calendar = 'calendario', $tz, $timezones) {

        $this_event = array(
                'href' => $href,
                'calendar' => $calendar,
                'etag' => $etag,
                'disableDragging' => FALSE,
                'disableResizing' => FALSE,
                'ignoreTimezone' => TRUE,
                'timezone' => $tz->getName(),
                );

        // Start and end date
        $dtstart = $this->extract_date($vevent, 'DTSTART', $tz);
        $dtend = $this->extract_date($vevent, 'DTEND', $tz);

        // We have for sure DTSTART
        $start = $dtstart['result'];

        // Do we have DTEND?
        if (!is_null($dtend)) {
            $end = $dtend['result'];
        } else {
            $duration = $vevent->getProperty('duration',
                    false, false, true);

            // Calculate dtend if not present
            if ($duration !== FALSE) {
                $end = $this->CI->dates->idt2datetime($duration,
                        $tz);
            } else {
                // RFC 2445, p52
                if ($dtstart['value'] == 'DATE-TIME') {
                    $end = clone $start;
                } else {
                    $end = clone $start;
                    $end->add(new DateInterval('P1D'));
                }
            }
        }

        // Is this a recurrent event?
        if (FALSE !== ($current_dtstart =
                    $vevent->getProperty('x-current-dtstart'))) {
            // Is this a multiday event? In that case, ignore this event

            // Hack to avoid getProperty() ignore next getProperty() on 
            // RRULE.
            if (FALSE === $vevent->rrule) {
                return FALSE;
            }

            $this_event['expanded'] = TRUE;

            // Format depends on DTSTART
            if (!isset($dtstart['property']['value']['hour'])) {
                $current_dtstart[1] .= ' 00:00:00';
            }

            // Keep a copy
            $orig_start = clone $start;

            $start = $this->CI->dates->x_current2datetime($current_dtstart[1], $tz);
            unset($this_event['end']);

            $current_dtend = $vevent->getProperty('x-current-dtend');
            if ($current_dtend !== FALSE) {
                if (!isset($dtstart['property']['value']['hour'])) {
                    $current_dtend[1] .= ' 00:00:00';
                }

                $orig_end = clone $end;
                $end =
                    $this->CI->dates->x_current2datetime($current_dtend[1],
                            $tz);

            }
        }


        $interesting_props = array(
                'summary', 'uid', 'description', 'rrule',
                'duration', 'location', 'class', 'recurrence-id',
                'transp',
                );

        foreach ($interesting_props as $p) {
            // TODO: more properties
            // TODO multiple ocurrences of the same property?
            // TODO current-dtstart
            $prop = $vevent->getProperty($p, FALSE, TRUE);

            if ($prop === FALSE) {
                continue;
            }

            $val = $prop['value'];
            $params = $prop['params'];
            switch ($p) {
                case 'summary':
                    $this_event['title'] = $val;
                    break;
                case 'uid':
                    $this_event['uid'] = $val;
                    break;
                case 'description':
                    $description = $val;
                    $this_event['description'] = 
                        preg_replace('/\\\n|\\\r/', "\n", $description);
                    break;
                case 'rrule':
                    $this_event['recurrence_components'] = $val;
                    $new_val = trim($vevent->_format_recur('RRULE',
                                array($prop)));
                    $this_event['rrule'] = $new_val;

                    $explanation =
                        $this->CI->recurrence->rrule_explain($val);
                    if ($explanation !== FALSE) {
                        $this_event['rrule_explained'] = $explanation;
                    } else {
                        $this_event['unparseable_rrule'] = TRUE;
                    }
                    // TODO make it editable when able to parse it
                    $this_event['editable'] = FALSE;
                    break;
                case 'duration':
                    $this_event['duration'] =
                        iCalUtilityFunctions::_format_duration($val);
                    break;
                case 'location':
                    $this_event['location'] = $val;
                    break;
                case 'class':
                    $this_event['icalendar_class'] = $val;
                    break;
                case 'transp':
                    $this_event['transp'] = $val;
                    break;
                case 'recurrence-id':
                    // TODO parse a little bit
                    $this_event['recurrence_id'] = $val;
                    break;
                default:
                    log_message('ERROR', 
                            'Attempt to parse iCalendar property ' . $p 
                            . ' on VEVENT which is not developed '
                            .'yet');
                    break;
            }
        }


        // Internal fullCalendar id
        $this_event['id'] = $calendar . $this_event['uid'] . '@' . $start->format(\DateTime::ISO8601);




        // Is this an all day event?
        $this_event['allDay'] = FALSE;

        if (isset($dtstart['value']) &&
                $dtstart['value'] == 'DATE') {
            $this_event['allDay'] = TRUE;
        } else if (($end->getTimestamp() - $start->getTimestamp())%86400 == 0) {
            if ($start->format('Hi') == '0000') {
                $this_event['allDay'] = TRUE;
            }

            // Check using UTC and local time
            if ($start->getTimeZone()->getName() == 'UTC') {
                $test_start = clone $start;
                $test_start->setTimeZone($this->tz);
                if ($test_start->format('Hi') == '0000') {
                    $this_event['allDay'] = TRUE;
                }
            }
        }

        if ($this_event['allDay'] === TRUE) {
            // Fool fullcalendar (dates are inclusive). 
            // For expanded events have special care, 
            // iCalcreator expands them using start_day=end_day, which
            // confuses fullCalendar

            $start->setTime(0, 0, 0);
            $end->setTime(0, 0, 0);


            if (isset($this_event['expanded'])) {
                $orig_start->setTime(0, 0, 0);
                $orig_end->setTime(0, 0, 0);

                $orig_end->sub(new DateInterval('P1D'))->add(new
                        DateInterval('PT1H'));
            }


            $this_event['orig_allday'] = TRUE;

        } else {
            $this_event['orig_allday'] = FALSE;
        }


        // To be used with strftime()
        $ts_start = $start->getTimestamp();
        $ts_end = $end->getTimestamp();

        // Needed for some conversions (Fullcalendar timestamp and am/pm
        // indicator)
        if (!isset($this_event['allDay']) 
                || $this_event['allDay']  !== TRUE) {
            $start->setTimeZone($this->tz);
            $end->setTimeZone($this->tz);
        }

        // Expanded events
        if (isset($orig_start)) {
            $orig_start->setTimeZone($this->tz);
            $orig_end->setTimeZone($this->tz);
            $this_event['orig_start'] = $orig_start->format(DateTime::ISO8601);
            $this_event['orig_end'] = $orig_end->format(DateTime::ISO8601);
        }

        // Empty title?
        if (!isset($this_event['title'])) {
            $this_event['title'] = $this->CI->i18n->_('labels', 'untitled');
        }

        $this_event['start'] = $start->format(DateTime::ISO8601);
        $this_event['end'] = $end->format(DateTime::ISO8601);

        // Reminders for this event
        $this_event['visible_reminders'] = array();
        $this_event['reminders'] = array();

        $reminders = $this->parse_valarms($vevent, $timezones);

        foreach ($reminders as $reminder) {
            list($count, $unit) = $reminder->getParsedWhen();
            $this_event['visible_reminders'][] = $reminder->getPosition();
            $this_event['reminders'][] = [
                'position' => $reminder->getPosition(),
                'count' => $count,
                'unit' => $unit,
            ];
        }

        return $this_event;
    }

    /**
     * Parses an iCalendar resource
     */
    function parse_icalendar($data) {
        $vcalendar = new vcalendar($this->config);
        $vcalendar->parse($data);

        return $vcalendar;
    }


    /**
     * Collects all timezones (VTIMEZONE) present in a resource
     *
     * Returns an associative array with 'tzid' => DateTimeZone('real tz
     * name')
     */
    function get_timezones($icalendar) {
        $result = array();
        while ($vt = $icalendar->getComponent('vtimezone')) {
            $tzid = $vt->getProperty('TZID');
            // Contains (usually) the time zone name
            $tzval = $vt->getProperty('X-LIC-LOCATION');
            
            if ($tzval === FALSE || empty($tzval)) {
                // Try to extract it from TZID name
                $tzval = $tzid;
            } else {
                $tzval = $tzval[1];
            }

            // Do we have tzval?
            if ($tzval !== FALSE && !empty($tzval)) {
                $result[$tzid] = $this->CI->timezonemanager->getTz($tzval);
            }
        }

        return $result;
    }

    /**
     * Finds a component within a resource, and returns its index in the
     * components array.
     *
     * Useful for replacing existing components by using GetComponents() to
     * save resources directly
     *
     * @param   iCalComponent   $reosurce   Full iCalComponent VCALENDAR
     * @param   string  $type   VEVENT, VTIMEZONE, etc
     * @param   conditions  Associative array. Possible keys:
     *                       - RECURRENCE-ID
     *                       - ?
     * @param   iCalComponent   The found object
     */
    function find_component_position($resource, $type, 
            $conditions = array(), &$comp) {

        // Position
        $i = 1;
        $found = FALSE;
        $comp = null;

        while ($found === FALSE && ($c = $resource->getComponent($type))) {
            // Check conditions
            if (isset($conditions['recurrence-id'])) {
                $recurr_id = $c->getProperty('recurrence-id');
                if ($recurr_id !== FALSE && $recurr_id ==
                        $conditions['recurrence-id']) {
                    $found = $i;
                }
            } else if (!isset($conditions['recurrence-id'])) {
                $found = $i;
            }

            if ($found !== FALSE) {
                $comp = $c;
            }
        }

        return $found;
    }

    /**
     * Replaces a component in the n-th position
     */
    function replace_component($resource, $type, $n, $new) {
        $resource->setComponent($new, $type, $n);
        return $resource;
    }


    /**
     * Applies a LAST-MODIFIED change on the iCalendar component
     * (VEVENT, etc)
     */
    function set_last_modified($component) {
        $now = $this->CI->dates->datetime2idt();

        $component->setProperty('last-modified', $now);

        // SEQUENCE
        $seq = $component->getProperty('sequence');
        if ($seq !== FALSE) {
            $seq = intval($seq);
            $seq++;
            $component->setProperty('sequence', $seq);
        }

        return $component;
    }

    /**
     * Gets DTSTART/other property timezone from a component
     *
     */
    function detect_tz($component, $tzs, $prop = 'dtstart') {
        $dtstart = $component->getProperty($prop, FALSE, TRUE);
        $val = $dtstart['value'];
        $params = $dtstart['params'];
        $has_z = isset($val['tz']) ? ($val['tz']=='Z') : FALSE;
        $value = $this->paramvalue($params, 'value');;
        $used_tz = null;
        if ($has_z || $value == 'DATE') {
            $used_tz = $this->CI->timezonemanager->getTz('UTC');
        } else {
            $tzid = $this->paramvalue($params, 'tzid');;

            if ($tzid !== FALSE && isset($tzs[$tzid])) {
                $used_tz = $tzs[$tzid];
            } else {
                // Not UTC but no TZID/invalid TZID?!
                $used_tz = $this->CI->timezonemanager->getTz(
                        $this->CI->config->item('default_timezone'));
            }
        }

        return $used_tz;
    }


    /**
     * Sets a component DTSTART value
     * 
     * @param iCalComponent $component
     * @param DateTimeZone $tz      Used TZ
     * @param DateTime $new_start
     * @param string $increment
     * @param string $force_new_value_type
     * @param string $force_new_tzid
     */
    function make_start($component, $tz,
            $new_start = null,
            $increment = null,
            $force_new_value_type = null,
            $force_new_tzid = null) {

        $value = null;
        $format = null;
        $params = array();

        $info = $this->extract_date($component, 'DTSTART', $tz);
        // No current DTSTART?
        if (is_null($info)) {
            $params = array('VALUE' => (is_null($force_new_value_type) ?
                        'DATE-TIME' : $force_new_value_type));
            $value = new DateTime('now', $tz);
        } else {
            $params = $info['property']['params'];
            if (!is_null($force_new_value_type)) {
                $params['VALUE'] = $force_new_value_type;
            } elseif (!isset($params['VALUE'])) {
                $params['VALUE'] = 'DATE-TIME';
            }

            $value = $this->CI->dates->idt2datetime($info['property']['value'],
                    $tz);
        }

        // DATE values can't have TZID
        if ($params['VALUE'] == 'DATE') {
            unset($params['TZID']);
        } else if (!is_null($force_new_tzid)) {
            $params['TZID'] = $force_new_tzid;
        }

        $format = $this->CI->dates->format_for($params['VALUE'], $tz);

        // Use current DTSTART
        if (!is_null($new_start)) {
            $value = $new_start;
        }

        // Increment
        if (!is_null($increment)) {
            $value->add($this->CI->dates->duration2di($increment));
        }

        $component->setProperty('dtstart', $this->CI->dates->datetime2idt(
                    $value, $tz, $format), $params);

        return $component;
    }

    /**
     * Sets a component end value
     * 
     * @param iCalComponent $component
     * @param DateTimeZone $tz      Used TZ
     * @param DateTime $new_start
     * @param string $increment
     * @param string $force_new_value_type
     */
    function make_end($component, $tz,
            $new_end = null,
            $increment = null,
            $force_new_value_type = null,
            $force_new_tzid = null) {

        $value = null;
        $format = null;
        $params = array();

        $dtend_info = $this->extract_date($component, 'DTEND', $tz);

        if (is_null($dtend_info)) {
            // No DTEND in event
            if (is_null($new_end)) {
                // Event has DURATION defined. Generate DTEND and remove
                // DURATION property
                $dtend_info = $this->getProperty('duration', FALSE, FALSE, TRUE);

                if ($dtend_info === FALSE) {
                    // Something is wrong . No DTEND nor DURATION
                    // Return the component as is
                    log_message('ERROR',
                            'Event with uid=' . $component->getProperty('uid')
                            .' has neither DTEND nor DURATION properties');
                    return $component;
                }

                $value = $this->CI->dates->idt2datetime($dtend, $tz);

            }

            // Get current DTSTART params
            $dtstart_info = $this->extract_date($component, 'DTSTART', $tz);
            if (is_null($dtend_info)) {
                // Neither DTSTART nor DTEND!?
                $params = array('VALUE' => 'DATE-TIME');
            } else {
                $params = $dtstart_info['property']['params'];
            }

            // We prefer DTEND to DURATION
            $component->deleteProperty('duration');
        } else {
            $params = $dtend_info['property']['params'];
            $value = $this->CI->dates->idt2datetime($dtend_info['property']['value'],
                    $tz);
        }

        // VALUE parameter
        if (!is_null($force_new_value_type)) {
            $params['VALUE'] = $force_new_value_type;
        } elseif (!isset($params['VALUE'])) {
            $params['VALUE'] = 'DATE-TIME';
        }


        // Use retrieved DTEND (or calculated)
        if (!is_null($new_end)) {
            $value = $new_end;
        }

        // Increment
        if (!is_null($increment)) {
            $value->add($this->CI->dates->duration2di($increment));
        }

        // DATE values can't have TZID
        if ($params['VALUE'] == 'DATE') {
            unset($params['TZID']);
        } else if (!is_null($force_new_tzid)) {
            $params['TZID'] = $force_new_tzid;
        } 

        $format = $this->CI->dates->format_for($params['VALUE'], $tz);

        // Save new value
        $component->setProperty('dtend',
                $this->CI->dates->datetime2idt($value, $tz, $format),
                $params);

        return $component;
    }

    /**
     * Make easy to parse a DTSTART/DTEND
     */
    function extract_date($component, $name = 'DTSTART', $tz) {
        $p = $component->getProperty($name, FALSE, TRUE);
        if ($p === FALSE) {
            return null;
        } else {
            $val = $p['value'];
            $params = $p['params'];
        }

        $obj = $this->CI->dates->idt2datetime(
                $val,
                $tz);

        $value_parameter = $this->paramvalue($params, 'value', 'DATE-TIME');

        return array(
                'property' => $p,
                'value' => $value_parameter,
                'result' => $obj,
                );
    }

    /**
     * Changes every property passed as an associative array (key will be
     * uppercased) on given component. DTSTART, DTEND and
     * DURATION are ignored, use make_start and make_end instead
     *
     * @param iCalComponent $component
     * @param array $properties
     */

    function change_properties($component, $properties) {
        $properties = array_change_key_case($properties, CASE_UPPER);

        foreach ($properties as $p => $v) {
            if ($p == 'DTSTART' || $p == 'DTEND' || $p == 'DURATION') {
                continue;
            }
            // TODO: multivalued properties?

            // TRANSP
            if ($p == 'TRANSP') {
                if ($v != 'OPAQUE' && $v != 'TRANSPARENT') {
                    log_message('ERROR', 'Invalid TRANSP value ('.$v.').  Ignoring.');
                    continue;
                }
            }

            $component->deleteProperty($p);
            if (!empty($v)) {
                $component->setProperty($p, $v);
            }
        }

        return $component;
    }


    /**
     * Changes an event to have all its components with a new timezone
     *
     * Affected properties: DTSTART, DTEND, DUE, EXDATE, RDATE
     *
     * Only changed if VALUE is DATE-TIME or TIME
     * Information extracted from RFC 2445, 4.2.19
     */
    function change_tz($component, $old_tz, $new_tzid, $new_tz) {
        $change = array('DTSTART', 'DTEND', 'DUE', 'EXDATE', 'RDATE');
        foreach ($change as $c) {
            $new_prop = array();
            $prop = $component->GetProperties($c);
            foreach ($prop as $p) {
                $valuep = $p->GetParameterValue('VALUE');
                if (!is_null($valuep) && $valuep == 'DATE') {
                    $new_prop[] = $p;
                    continue;
                }
                $tzid = $p->GetParameterValue('TZID');
                if (!is_null($tzid) && $tzid == $new_tzid) {
                    // Keep untouched
                    $new_prop[] = $p;
                    continue;
                }

                // No TZ or different TZ
                $val = $p->Value();
                $multiple = preg_split('/,/', $val);
                foreach ($multiple as $v) {
                    $new_p = clone $p;
                    $current = $this->CI->dates->idt2datetime($v,
                            $this->CI->dates->format_for($valuep, $old_tz),
                            $old_tz);
                    $new_p->SetParameterValue('TZID', $new_tzid);
                    $new_p->Value($this->CI->dates->datetime2idt($current,
                                $new_tz));
                    $new_prop[] = $new_p;
                }
            } // end foreach $prop

            // Set new properties
            $component->SetProperties($new_prop, $c);
        }

        return $component;
    }


    /**
     * Make it easy to access parameters
     */
    function paramvalue($params, $name, $default_val = FALSE) {
        $name = strtoupper($name);
        return (isset($params[$name]) ? $params[$name] : $default_val);
    }

    /**
     * Add a VTIMEZONE using the specified TZID
     * If VTIMEZONE was already added, do nothing
     *
     * @param   iCalcomponent
     * @param   string  Timezone id to add
     * @param   array   (Optional) result from get_timezones()
     * @return  Used TZID, even when it was not added
     */

    function add_vtimezone(&$resource, $tzid, $timezones = array()) {
        if ($tzid != 'UTC' && !isset($timezones[$tzid])) {
            $res = iCalUtilityFunctions::createTimezone($resource,
                    $tzid, array( 'X-LIC-LOCATION' => $tzid));
            if ($res === FALSE) {
                log_message('ERROR', 
                        "Couldn't create vtimezone with tzid=" . $tzid
                        .' Defaulting to UTC');
                $tzid = 'UTC';
            }
        }

        return $tzid;
    }

    /**
     * Parses a VEVENT resource VALARM definitions
     * 
     * Returns an associative array ('n1#' => new Reminder, 'n2#' => new
     * Reminder...), where 'n#' is the order where this VALARM was found
     */
    function parse_valarms($vevent, $timezones = array()) {
        $parsed_reminders = array();

        $position = 0;
        while ($valarm = $vevent->getComponent('valarm')) {
            $position++;
            $action = $valarm->getProperty('action');
            if ($action !== 'DISPLAY') {
                continue;
            }

            $reminder = Reminder::createFromiCalcreator($valarm, $position);

            if ($reminder !== null) {
                $parsed_reminders[$position] = $reminder;
            }
        }

        return $parsed_reminders;
    }

    /**
     * Adds or replaces VALARM components (reminders) for a given VEVENT
     * resource. Removes VALARMs that were deleted by user
     */
    function set_valarms(&$resource, $reminders, $old_visible_reminders =
            array()) {
        foreach ($reminders as $reminder) {
            $valarm = $reminder->generateVAlarm();
            $current_position = $reminder->getPosition();
            if ($current_position !== null) {
                $resource = $this->replace_component($resource, 'valarm', $current_position, $valarm);
                unset($old_visible_reminders[$current_position]);
            } else {
                $resource->setComponent($valarm);
            }
        }

        // Any VALARMs left that was not present?
        $remove_valarms = array_keys($old_visible_reminders);
        foreach ($remove_valarms as $n) {
            $resource->deleteComponent('valarm', $n);
        }

        return $resource;
    }


}

