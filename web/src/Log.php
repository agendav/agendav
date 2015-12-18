<?php
namespace AgenDAV;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Formatter\LineFormatter;


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
 * This utility class is used to assist when creating new log handlers
 */
class Log
{

    /**
     * Generates a new HTTP logger
     *
     * @param string $log_path
     * @return \Monolog\Logger
     */
    public static function generateHttpLogger($log_path)
    {
        $logger = new \Monolog\Logger('http');
        $handler = new \Monolog\Handler\StreamHandler(
            $log_path . 'http-'. date('Y-m-d') .'.log',
            \Monolog\Logger::DEBUG
        );
        $formatter = new \Monolog\Formatter\LineFormatter(
            "[%datetime%] %extra% %message%\n",
            null,                                           // Default date format
            true,                                           // Allow line breaks
            true                                            // Ignore empty contexts/extra
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        $logger->pushProcessor(self::hideAuthorizationHeader());
        $logger->pushProcessor(new \Monolog\Processor\WebProcessor);

        return $logger;
    }

    /**
     * Monolog processor to hide Authorization: headers
     *
     * @return function
     */
    public static function hideAuthorizationHeader()
    {
        return function($record) {
            $record['message'] = preg_replace(
                '/^Authorization: .+$/m',
                'Authorization: ***HIDDEN***',
                $record['message']
            );

            return $record;
        };
    }
}
