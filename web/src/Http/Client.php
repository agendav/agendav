<?php
namespace AgenDAV\Http;

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

use AgenDAV\Version;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Stream\Stream;

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
        $this->options = $custom_options;

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
     * Retrieves a previously set request header
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
     * @return \Guzzle\Http\Message\Response
     * @throws \GuzzleHttp\Exception\BadResponseException On 4xx and 5xx HTTP errors
     **/
    public function request($method, $url, $body = '')
    {
        $this->setHeader('User-Agent', 'AgenDAV/' . Version::V);
        $this->options['headers'] = $this->request_headers;
        $this->request = $this->guzzle->createRequest(
            $method,
            $url,
            $this->options
        );

        if ($body !== '') {
            $this->request->setBody(Stream::factory($body));
        }

        // Clean current headers
        $this->request_headers = array();

        try {
            $this->response = $this->guzzle->send($this->request);
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            switch ($exception->getCode()) {
                case 401:
                    throw new \AgenDAV\Exception\NotAuthenticated($exception);
                    break;
                case 403:
                    throw new \AgenDAV\Exception\PermissionDenied($exception);
                    break;
                case 404:
                    throw new \AgenDAV\Exception\NotFound($exception);
                    break;
                case 412:
                    throw new \AgenDAV\Exception\ElementModified($exception);
                    break;
                default:
                    throw new \AgenDAV\Exception($exception);
            }
        }

        return $this->response;
    }

    /**
     * Sets Content-Type for next request to be XML
     */
    public function setContentTypeXML()
    {
        $this->setHeader('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Sets Content-Type for next request to be an iCalendar resource
     */
    public function setContentTypeiCalendar()
    {
        $this->setHeader('Content-Type', 'text/calendar');
    }

}
