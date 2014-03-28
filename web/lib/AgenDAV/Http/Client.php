<?php
namespace AgenDAV\Http;

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

use AgenDAV\Version;
use GuzzleHttp\Client as GuzzleClient;

/**
 * HTTP Client for AgenDAV, based on Guzzle
 */
class Client
{

    /**
     * Internal Guzzle client
     */
    private $guzzle;

    /**
     * Request headers
     */
    private $request_headers;

    /**
     * Request object
     */
    private $request;

    /**
     * Common request options
     */
    private $options;

    /**
     * Creates a new Client
     *
     * TODO add comments to documentation about SSL certificate validation
     * @param GuzzleHttp\client $guzzle Actual Guzzle HTTP client
     * @param Array $custom_options     Options to be used on every request. Overrides default values
     */
    public function __construct(GuzzleClient $guzzle, array $custom_options = array())
    {
        $this->guzzle = $guzzle;
        $this->options = array(
            'exceptions' => false, // Do not throw an exception on 4xx/5xx
        );

        $this->options = array_merge($this->options, $custom_options);
        $this->request_headers = array();
    }

    /**
     * Sets the client authentication parameters
     *
     * @param string $username  Authentication user name
     * @param string $password  Authentication password
     * @param string $type  Optional authentication method. Supported types are 'basic' and 'digest'
     * @return void
     **/
    public function setAuthentication($username, $password, $type = 'basic')
    {
        $this->options['auth'] = array($username, $password, $type);
    }

    /**
     * Sets a header, overwriting previous values
     *
     * @param string $name  Header name
     * @param string $value  Header value
     * @return void
     **/
    public function setHeader($name, $value)
    {
        $this->request_headers[$name] = $value;
    }

    /**
     * Adds a header
     *
     * @param string $name  Header name
     * @param string $value  Header value
     * @return void
     **/
    public function addHeader($name, $value)
    {
        if (isset($this->request_headers[$name])) {
            if (is_array($this->request_headers[$name])) {
                $this->request_headers[$name][] = $value;
            } else {
                $this->request_headers[$name] = array(
                    $this->request_headers[$name],
                    $value
                );
            }
        } else {
            $this->setHeader($name, $value);
        }
    }

    /**
     * Gets a header
     *
     * @param string $name  Header name
     * @return mixed        String or array of strings in case the header is defined, null otherwise
     **/
    public function getHeader($name)
    {
        return isset($this->request_headers[$name]) ?
            $this->request_headers[$name] : null;
    }

    /**
     * Gets last request sent using this client
     *
     * @return GuzzleHttp\Message\RequestInterface     Last request sent
     * @author Jorge López Pérez <jorge@adobo.org>
     **/
    public function getLastRequest()
    {
        return $this->request;
    }


    /**
     * Sends a request
     *
     * @param string $method       HTTP verb
     * @param string $url          URL to send the request to
     * @return void
     **/
    public function request($method, $url, $body = '')
    {
        $this->request = $this->guzzle->createRequest(
            $method,
            $url,
            $this->options
        );

        $this->request->setHeaders($this->request_headers);
        $this->request->setHeader('User-Agent', 'AgenDAV/' . Version::V);

        if ($body !== '') {
            $this->request->setBody($body);
        }

        $this->response = $this->guzzle->send($this->request);

        // Clean current headers
        $this->request_headers = array();

        return $this->response;
    }
}
