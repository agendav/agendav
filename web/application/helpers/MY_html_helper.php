<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Script
*
* Generates a script inclusion of a JavaScript file
* Based on the CodeIgniters original Link Tag.
*
* Author(s): Isern Palaus <ipalaus@ipalaus.es>, Viktor Rutberg <wishie@gmail.com>
*
* @access    public
* @param    mixed    javascript sources or an array
* @param    string    language
* @param    string    type
* @param    boolean    should index_page be added to the javascript path
* @return    string
*/    

if ( ! function_exists('script_tag'))
{
    function script_tag($src = '', $language = 'javascript', $type = 'text/javascript', $index_page = FALSE)
    {
        $CI =& get_instance();

        $script = '<script ';
        
        if(is_array($src))
        {
            foreach($src as $v)
            {
                if ($k == 'src' AND strpos($v, '://') === FALSE)
                {
                    if ($index_page === TRUE)
                    {
                        $script .= ' src="'.$CI->config->site_url($v).'"';
                    }
                    else
                    {
                        $script .= ' src="'.$CI->config->slash_item('base_url').$v.'"';
                    }
                }
                else
                {
                    $script .= "$k=\"$v\"";
                }
            }
            
            $script .= ">\n";
        }
        else
        {
            if ( strpos($src, '://') !== FALSE)
            {
                $script .= ' src="'.$src.'" ';
            }
            elseif ($index_page === TRUE)
            {
                $script .= ' src="'.$CI->config->site_url($src).'" ';
            }
            else
            {
                $script .= ' src="'.$CI->config->slash_item('base_url').$src.'" ';
            }
                
            $script .= 'language="'.$language.'" type="'.$type.'"';
            
            $script .= '>'."\n";
        }

        
        $script .= '</script>';
        
        return $script;
    }
}

if ( ! function_exists('formelement'))
{
    function formelement($label, $input, $help = '') {
        ?>
            <div class="control-group">
            <?php 
            echo form_label($label, '', array('class' => 'control-label'));
            echo '<div class="controls">' . $input;
            if (!empty($help)) {
                echo '<p class="help-block">'.$help.'</p>';
            }
            echo '</div>';
            ?>
            </div>
        <?php
    }
}


/*
 * Returns small AgenDAV logo
 */

function agendav_small_logo() {
    return '<div id="logo" class="block">'
        . img(array(
                'src' => 'img/agendav_small.png',
                'alt' => 'AgenDAV',
                ))
        . '</div>';
}

/*
 * Returns app defined logo
 */

function custom_logo($filename, $title = '') {
    return '<span class="brand">'
        . img(array(
                'src' => 'img/' . $filename,
                'alt' => $title,
                'title' => $title,
                ))
        . '</span>';
}

/*
 * Returns app defined logo
 */

function custom_logo_login($filename, $title = '') {
    return '<div class="loginlogo">'
        . img(array(
                'src' => 'img/' . $filename,
                'alt' => $title,
                'title' => $title,
                ))
        . '</div>';
}

