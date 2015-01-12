<?php

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

class MY_Log extends CI_Log
{

    /**
     * Logger
     *
     * @var mixed
     * @access private
     */
    private $logger;

    /**
     * CodeIgniter configuration
     *
     * @var mixed
     * @access private
     */
    private $config;

    function __construct()
    {
        parent::__construct();
        $this->config =& get_config();

        $this->logger = new \AgenDAV\Log();

        $logfile = $this->config['log_path'] . date('Y-m-d') . '.log';
        $this->logger->addLogFile($logfile);

        // Debug
        if ($this->config['enable_debug'] == true) {
            $debugfile = $this->config['log_path'] . 'debug.log';
            $processors = [
                new \Monolog\Processor\WebProcessor(null, ['ip', 'http_method', 'url']),
            ];
            $this->logger->addLogFile($debugfile, \Monolog\Logger::DEBUG, $processors);
        }
    }

    function write_log($level = 'error', $msg, $php_error = FALSE)
    {
        $level = strtoupper($level);

        return $this->logger->message($level, $msg);
    }

    /**
     * Log a message. This method just maintains backwards compatibility
     *
     * @param mixed $level
     * @param mixed $message
     * @access public
     * @return void
     */
    public function message($level, $message)
    {
        return $this->write_log($level, $message);
    }

}
