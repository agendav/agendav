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

class Shared_calendars extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get calendars which other users are sharing with a given user.
     *
     * @param string $username Username
     * @return Array        Array of calendars (sid, user_from, calendar)
     */
    public function givenAccesses($username)
    {
        $res = $this->db->get_where('shares', array('grantee' => $username));

        $tmp = $res->result_array();
        $result = array();

        foreach ($tmp as $c) {
            $index = $c['path'];
            $result[$index] = array(
                    'sid' => $c['sid'],
                    'grantor' => $c['grantor'],
                    'rw' => $this->boolToInt($c['rw']),
                    );
            $options = unserialize($c['options']);
            if (is_array($options)) {
                $result[$index] = array_merge($result[$index], $options);
            }
        }

        return $result;
    }

    /**
     * Get a list of users who can access a calendar
     *
     * @param string $calendar Calendar
     * @return Array Calendar associative array in the form: [calendar => properties]
     */

    public function usersWithAccessTo($path)
    {
        $qry = $this->db->get_where('shares', array('path' => $path));
        $res = $qry->result_array();
        $users = array();

        foreach ($res as $c) {
            $index = $c['path'];
            $users[] = array(
                    'username' => mb_strtolower($c['grantee']),
                    'sid' => $c['sid'],
                    'rw' => $c['rw'],
            );
        }

        return $users;
    }

    /**
     * Store a shared calendar.
     *
     * @param $sid  Share id. Null means a new calendar sharing
     * @param $from User id who is sharing a calendar
     * @param $calendar Calendar being shared. Can be in the form
     *   'user:calendar'
     * @param $to   User id who's getting calendar rights
     * @param $options  Associative array with options for this calendar
     *   (color, displayname, ...)
     * @param $write_access (Optional) Use read+write profile on '1', read on '0'
     * @return boolean  false on error, true otherwise
     */
    public function store(
            $sid = null,
            $grantor = '',
            $path = '',
            $grantee = '',
            $options = array(),
            $rw = null)
    {
        if ($sid === null && (empty($grantor) || empty($path) ||
                    empty($grantee))) {
            log_message('ERROR', 
                    'Call to shared_calendars->store() with no enough parameters');
            return false;
        }

        $path = preg_replace('/^[^:]+:/', '', $path);
        $grantor = mb_strtolower($grantor);
        $grantee = mb_strtolower($grantee);

        $data = array(
                'grantor' => $grantor,
                'path' => $path,
                'grantee' => $grantee,
                'options' => serialize($options),
                );
        if ($rw !== null) {
            $data['rw'] = $rw;
        }

        $res = false;
        if (!is_null($sid)) {
            $conditions = array('sid' => $sid);
            unset($data['grantor']);
            unset($data['grantee']);
            unset($data['path']);

            // Preserve options
            if (is_null($options)) {
                unset($data['options']);
            }

            $this->db->where($conditions);
            $res = $this->db->update('shares', $data);
        } else {
            $res = $this->db->insert('shares', $data);

            if ($res === true) {
                log_message('INTERNALS', 'Granted access by '
                        . $data['grantor'] . ' on ' . $data['path'] . ' to '
                        . $data['grantee']);
            } else {
                log_message('ERROR', 'Error granting access by '
                        . $data['grantor'] . ' on ' . $data['path'] . ' to '
                        . $data['grantee']);
            }
        }


        return $res;
    }

    /**
     * Removes a share from database
     *
     * @param $sid  Sharing id
     * @return boolean  false on error, true otherwise
     */
    function remove($sid = null) {
        if (is_null($sid)) {
            log_message('ERROR',
                    'Call to shared_calendars->remove() without sid');
            return false;
        }

        $this->db->where('sid', $sid);
        $query = $this->db->get('shares');
        $row = $query->result_array();

        if (count($row) == 0) {
            log_message('ERROR', 
                    'Tried to remove nonexistant share id [' . $sid .']');
            return false;
        } else {
            $row = $row[0];
            $this->db->where('sid', $sid);
            $this->db->delete('shares');

            log_message('INTERNALS', 'Revoked access by '
                    . $row['grantor'] . ' on ' . $row['path'] . ' to '
                    . $row['grantee']);
            return true;
        }
    }

    /**
     * Translates a boolean from PostgreSQL to 1/0
     */
    private function boolToInt($value)
    {
        $ret = 0;

        switch ($value) {
            case 't':
                $ret=1;
                break;
            case 'f':
                $ret=0;
                break;
            default:
                $ret = $value;
                break;
        }

        return $ret;
    }

    /**
     * Sets user defined properties (from shared db) for each calendar. Sets
     * some special properties too
     *
     * @param Array $calendars Array of Calendar each one
     * @param Array $properties Result of get_shared_with($username)
     * @return Array Modified calendar list
     */
    function setProperties(&$calendars, $properties)
    {
        foreach ($calendars as $c) {
            $c->shared = true;
            if (isset($properties[$c->calendar])) {
                $current = $properties[$c->calendar];
                foreach (array('sid', 'rw', 'grantor', 'color', 'displayname') as $p) {
                    if (isset($current[$p])) {
                        $c->$p = $current[$p];
                    }
                }
            }
        }

        return $calendars;
    }
}

