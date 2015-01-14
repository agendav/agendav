<?php

namespace AgenDAV;

/*
 * Copyright 2015 Jorge López Pérez <jorge@adobo.org>
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


/**
 * This class is used to find all accessible calendars for an user
 */
abstract class JSONController extends \MY_Controller
{

    /**
     * @var \AgenDAV\CalDAV\Client
     */
    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = $this->container['caldav_client'];
    }

    public function index()
    {
        if (!$this->container['session']->isAuthenticated()) {
            $response = $this->generateError(
                $this->i18n->_('messages', 'error_loginagain')
            );
            $this->sendResponse($response);
            return;
        }

        $input = $this->input->post(null, true);
        if ($input === false || !$this->validateInput($input)) {
            $response = $this->generateError(
                $this->i18n->_('messages', 'error_empty_fields')
            );
            $this->sendResponse($response);
            return;
        }

        $response = $this->controlledExecution($input);
        $this->sendResponse($response);
    }

    /**
     * Proceeds to execute this action, taking care of possible exceptions
     *
     * @param array $input
     * @result mixed Output to be sent to the browser
     */
    protected function controlledExecution(array $input)
    {
        try {

            $result = $this->execute($input);
            return $result;

        } catch (\AgenDAV\Exception\PermissionDenied $exception) {
            return $this->generateException(
                $this->i18n->_('messages', 'error_denied')
            );

        } catch (\AgenDAV\Exception\NotFound $exception) {
            return $this->generateException(
                $this->i18n->_('messages', 'error_element_not_found')
            );

        } catch (\AgenDAV\Exception\ElementModified $exception) {
            return $this->generateException(
                $this->i18n->_('messages', 'error_element_changed')
            );

        } catch (\AgenDAV\Exception $exception) {
            return $this->generateError(
                $this->i18n->_('messages', 'error_unknownhttpcode', ['%res' => $exception->getCode()])
            );

        } catch (\Exception $exception) {
            return $this->generateError(
                $this->i18n->_('messages', 'error_oops')
            );
        }
    }

    /**
     * Validates user input
     *
     * @param array $input
     * @return bool
     */
    abstract protected function validateInput(array $input);

    /**
     * Performs an operation using the information from input
     *
     * @param array $input
     * @return array
     */
    abstract protected function execute(array $input);

    /**
     * Sends data back to the browser
     *
     * @param mixed $response
     */
    protected function sendResponse($response)
    {
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($response));
    }

    /**
     * Generates an exception message
     *
     * @param string $message
     */
    protected function generateException($message)
    {
        $result = [
            'result' => 'EXCEPTION',
            'message' => $message
        ];

        return $result;
    }

    /**
     * Generates an error message
     *
     * @param string $message
     */
    protected function generateError($message)
    {
        $result = [
            'result' => 'ERROR',
            'message' => $message
        ];

        return $result;
    }
    /**
     * Generates a success message
     *
     * @param string $message
     */
    protected function generateSuccess($message)
    {
        $result = [
            'result' => 'SUCCESS',
            'message' => $message
        ];

        return $result;
    }

}
