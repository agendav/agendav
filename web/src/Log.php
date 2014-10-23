<?php 
namespace AgenDAV;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Formatter\LineFormatter;


/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
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

// TODO: make streams configurable
class Log extends \Monolog\Logger
{

    /**
     * Current application context 
     *
     * @var Array
     * @access private
     */
    private $context;

    public function __construct()
    {
        parent::__construct('agendav');

        // TODO set current context
        $this->context = array();
    }

    /**
     * Adds a new log file
     *
     * @param mixed $path Absolute path to log file
     * @param int $level Monolog minimun level to be logged
     * @param Array $processors Monolog processors to apply
     * @access public
     * @return void
     */
    public function addLogFile($path, $level = Logger::INFO, $processors = array())
    {
        $file_stream = new StreamHandler($path, $level);
        
        $log_format = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        // TODO make timestamp format configurable
        $ts_format = 'Y-m-d H:i:s.u';
        $file_stream->setFormatter(new LineFormatter($log_format, $ts_format));

        foreach ($processors as $p) {
            $file_stream->pushProcessor($p);
        }

        $this->pushHandler($file_stream);
    }

    public function message($level, $message)
    {
        switch ($level) {
            case 'DEBUG':
            case 'INTERNALS':
                $level = Logger::DEBUG;
                break;
            case 'INFO':
                $level = Logger::INFO;
                break;
            case 'WARNING':
                $level = Logger::WARNING;
                break;
            case 'ERROR':
                $level = Logger::ERROR;
                break;
            default:
                $level = Logger::INFO;
                break;
        }

        return $this->addRecord($level, $message, $this->context);
    }

}
