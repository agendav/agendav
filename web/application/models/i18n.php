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

class I18n extends CI_Model {
    private $langname;
    
    private $lang_path;

    private $lang_contents;

    private $lang_relations;

    function __construct() {
        parent::__construct();

        // Load language relations file
        $this->config->load('languages');


        $this->lang_path = APPPATH . '../lang';
        $this->langname = $this->config->item('default_language');

        /** @var \AgenDAV\User $user */
        $user = $this->container['user'];
        // Overwrite default setting by users preferences
        if ($user->getPreferences()->language !== null)
            $this->langname = $user->getPreferences()->language;

        if (!is_dir($this->lang_path)) {
            log_message('ERROR', 'Language path is not a directory');
            die();
        }

        // Defined language?
        $lang_rels = $this->config->item('lang_rels');
        if (!isset($lang_rels[$this->langname])) {
            log_message('ERROR', 'Language ' .
                    $this->langname . ' not registered');
            $this->langname = 'en';
        }

        $this->lang_relations = $lang_rels[$this->langname];

        if (FALSE === ($this->lang_contents =
                    $this->parse_language($this->langname))) {
            log_message('ERROR', 'Language '
                    . $this->langname . ' not found');
            $this->langname = 'en';
            $this->lang_contents = $this->parse_language($this->langname);
        }

        // Locale
        $this->setlocale();

        // Set CodeIgniter language
        $this->set_ci_language();
    }

    /**
     * Loads a language file and returns it
     *
     * @param   string  Language to load
     * @return  array   Array with labels and messages, FALSE on error
     */
    private function parse_language($lang) {
        $file = $this->lang_path . '/' . $lang . '/' . $lang . '.php';
        $file_formats = $this->lang_path . '/' . $lang . '/formats.php';

        if (!is_file($file) || !is_file($file_formats)) {
            return FALSE;
        } else {
            $messages = array();
            $labels = array();
            $formats = array();

            @include($file);
            @include($file_formats);

            return array(
                'messages' => $messages,
                'labels' => $labels,
                'formats' => $formats,
            );
        }
    }

    /**
     * Translates a string from current language
     *
     * @param   string  Type (label or message)
     * @param   string  Label/message id
     * @param   array   Associative array of parameters
     * @return  string  Translated string
     */
    public function _($type, $id, $params = array()) {
        $contents = $this->lang_contents;
        $raw = (isset($contents[$type][$id])) ? 
            $contents[$type][$id] : 
            '[' . $type . ':' . $id . ']';

        foreach ($params as $key => $replacement) {
            $raw = mb_ereg_replace($key, $replacement, $raw);
        }

        return $raw;
    }

    /**
     * Sets current locale
     */
    private function setlocale() {
        setlocale(LC_ALL, $this->langname . '.utf8');
    }

    /**
     * Sets CodeIgniter language
     */
    private function set_ci_language() {
        $this->config->set_item('language',
                $this->lang_relations['codeigniter']);
    }

    public function getCurrent()
    {
        return $this->langname;
    }

    /**
     * Dumps language contents 
     */
    public function dump() {
        return $this->lang_contents;
    }
}
