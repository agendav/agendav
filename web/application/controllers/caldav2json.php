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

use AgenDAV\User;

class Caldav2json extends CI_Controller {

    private $user;

    function __construct() {
        parent::__construct();

        $this->user = new User(
            $this->session,
            $this->preferences,
            $this->encrypt
        );

        if (!$this->user->isAuthenticated()) {
            $this->extended_logs->message('INFO', 'Anonymous access attempt to ' . uri_string());
            $this->output->set_status_header('401');
            $this->output->_display();
            die();
        }

        $this->caldavoperations->setClient($this->user->createCalDAVClient());

        $this->output->set_content_type('application/json');
    }

    function index() {
    }

    /**
     * Searchs a principal using provided data
     */
    function principal_search() {
        $result = array();
        $term = $this->input->get('term');

        if (!empty($term)) {
            $result = $this->caldavoperations->principalSearch($term, $term);
        }

        $this->output->set_output(json_encode($result));
    }



    /**
     * Input validators
     */

    // Validate date format
    function _valid_date($d) {
        $obj = $this->dates->frontend2datetime($d .' ' .
                date($this->time_format));
        if (FALSE === $obj) {
            $this->form_validation->set_message('_valid_date',
                    $this->i18n->_('messages', 'error_invaliddate'));
            return FALSE;
        } else {
            return TRUE;
        }
    }

    // Validate date format (or empty string)
    function _empty_or_valid_date($d) {
        return empty($d) || $this->_valid_date($d);
    }

    // Validate empty or > 0
    function _empty_or_natural_no_zero($n) {
        return empty($n) || intval($n) > 0;
    }

    // Validate time format
    function _valid_time($t) {
        $obj = $this->dates->frontend2datetime(date($this->date_format) .' '. $t);
        if (FALSE === $obj) {
            $this->form_validation->set_message('_valid_time',
                    $this->i18n->_('messages', 'error_invalidtime'));
            return FALSE;
        } else {
            return TRUE;
        }
    }


    /**
     * Throws an exception message
     */
    function _throw_exception($message) {
        $this->output->set_output(json_encode(array(
                        'result' => 'EXCEPTION',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws an error message
     */
    function _throw_error($message) {
        $this->output->set_output(json_encode(array(
                        'result' => 'ERROR',
                        'message' => $message)));
        $this->output->_display();
        die();
    }

    /**
     * Throws a success message
     */
    function _throw_success($message = '') {
        $this->output->set_output(json_encode(array(
                        'result' => 'SUCCESS',
                        'message' => $message)));
        $this->output->_display();
        die();
    }



}
