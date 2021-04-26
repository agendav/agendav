<?php

namespace AgenDAV;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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

use \Guzzle\Http\Message\Response;
use \GuzzleHttp\Exception\BadResponseException;

/**
 * General exception
 */
class Exception extends \RunTimeException
{
    /** @var \Guzzle\Http\Message\Response */
    protected $response;

    /**
     * @param \GuzzleHttp\Exception\BadResponseException|string $message_or_exception
     */
    public function __construct($message_or_exception)
    {
        if ($message_or_exception instanceof \GuzzleHttp\Exception\BadResponseException) {
            parent::__construct(
                $message_or_exception->getMessage(),
                $message_or_exception->getCode()
            );
            $this->response = $message_or_exception->getResponse();
        } else {
            parent::__construct($message_or_exception);
        }
    }

    /*
     * Getter for response
     *
     * @return\Guzzle\Http\Message\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
