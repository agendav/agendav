<?php
/**
* Utility functions of a general nature which are used by
* most AWL library classes.
*
* @package   awl
* @subpackage   Utilities
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://www.gnu.org/licenses/lgpl-3.0.txt  GNU LGPL version 3 or later
*/

if ( !function_exists('dbg_error_log') ) {
  /**
  * Writes a debug message into the error log using printf syntax.  If the first
  * parameter is "ERROR" then the message will _always_ be logged.
  * Otherwise, the first parameter is a "component" name, and will only be logged
  * if $c->dbg["component"] is set to some non-null value.
  *
  * If you want to see every log message then $c->dbg["ALL"] can be set, to
  * override the debugging status of the individual components.
  *
  * @var string $component The component to identify itself, or "ERROR", or "LOG:component"
  * @var string $format A format string for the log message
  * @var [string $parameter ...] Parameters for the format string.
  */
  function dbg_error_log() {
    global $c;
    $args = func_get_args();
    $type = "DBG";
    $component = array_shift($args);
    if ( substr( $component, 0, 3) == "LOG" ) {
      // Special escape case for stuff that always gets logged.
      $type = 'LOG';
      $component = substr($component,4);
    }
    else if ( $component == "ERROR" ) {
      $type = "***";
    }
    else if ( isset($c->dbg["ALL"]) ) {
      $type = "ALL";
    }
    else if ( !isset($c->dbg[strtolower($component)]) ) return;

    $argc = func_num_args();
    if ( 2 <= $argc ) {
      $format = array_shift($args);
    }
    else {
      $format = "%s";
    }
    @error_log( $c->sysabbr.": $type: $component:". vsprintf( $format, $args ) );
  }
}



if ( !function_exists('apache_request_headers') ) {
  /**
  * Compatibility so we can use the apache function name and still work with CGI
  * @package awl
  */
  eval('
    function apache_request_headers() {
        foreach($_SERVER as $key=>$value) {
            if (substr($key,0,5)=="HTTP_") {
                $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                $out[$key]=$value;
            }
        }
        return $out;
    }
  ');
}



if ( !function_exists('dbg_log_array') ) {
  /**
  * Function to dump an array to the error log, possibly recursively
  *
  * @var string $component Which component should this log message identify itself from
  * @var string $name What name should this array dump identify itself as
  * @var array $arr The array to be dumped.
  * @var boolean $recursive Should the dump recurse into arrays/objects in the array
  */
  function dbg_log_array( $component, $name, $arr, $recursive = false ) {
    if ( !isset($arr) || (gettype($arr) != 'array' && gettype($arr) != 'object') ) {
      dbg_error_log( $component, "%s: array is not set, or is not an array!", $name);
      return;
    }
    foreach ($arr as $key => $value) {
      dbg_error_log( $component, "%s: >>%s<< = >>%s<<", $name, $key,
                      (gettype($value) == 'array' || gettype($value) == 'object' ? gettype($value) : $value) );
      if ( $recursive && (gettype($value) == 'array' || (gettype($value) == 'object' && "$key" != 'self' && "$key" != 'parent') ) ) {
        dbg_log_array( $component, "$name"."[$key]", $value, $recursive );
      }
    }
  }
}



if ( !function_exists("session_simple_md5") ) {
  /**
  * Make a plain MD5 hash of a string, identifying the type of hash it is
  *
  * @param string $instr The string to be salted and MD5'd
  * @return string The *MD5* and the MD5 of the string
  */
  function session_simple_md5( $instr ) {
    global $c;
    if ( isset($c->dbg['password']) ) dbg_error_log( "Login", "Making plain MD5: instr=$instr, md5($instr)=".md5($instr) );
    return ( '*MD5*'. md5($instr) );
  }
}



if ( !function_exists("session_salted_md5") ) {
  /**
  * Make a salted MD5 string, given a string and (possibly) a salt.
  *
  * If no salt is supplied we will generate a random one.
  *
  * @param string $instr The string to be salted and MD5'd
  * @param string $salt Some salt to sprinkle into the string to be MD5'd so we don't get the same PW always hashing to the same value.
  * @return string The salt, a * and the MD5 of the salted string, as in SALT*SALTEDHASH
  */
  function session_salted_md5( $instr, $salt = "" ) {
    if ( $salt == "" ) $salt = substr( md5(rand(100000,999999)), 2, 8);
    global $c;
    if ( isset($c->dbg['password']) ) dbg_error_log( "Login", "Making salted MD5: salt=$salt, instr=$instr, md5($salt$instr)=".md5($salt . $instr) );
    return ( sprintf("*%s*%s", $salt, md5($salt . $instr) ) );
  }
}



if ( !function_exists("session_salted_sha1") ) {
  /**
  * Make a salted SHA1 string, given a string and (possibly) a salt.  PHP5 only (although it
  * could be made to work on PHP4 (@see http://www.openldap.org/faq/data/cache/347.html). The
  * algorithm used here is compatible with OpenLDAP so passwords generated through this function
  * should be able to be migrated to OpenLDAP by using the part following the second '*', i.e.
  * the '{SSHA}....' part.
  *
  * If no salt is supplied we will generate a random one.
  *
  * @param string $instr The string to be salted and SHA1'd
  * @param string $salt Some salt to sprinkle into the string to be SHA1'd so we don't get the same PW always hashing to the same value.
  * @return string A *, the salt, a * and the SHA1 of the salted string, as in *SALT*SALTEDHASH
  */
  function session_salted_sha1( $instr, $salt = "" ) {
    if ( $salt == "" ) $salt = substr( str_replace('*','',base64_encode(sha1(rand(100000,9999999),true))), 2, 9);
    global $c;
    if ( isset($c->dbg['password']) ) dbg_error_log( "Login", "Making salted SHA1: salt=$salt, instr=$instr, encoded($instr$salt)=".base64_encode(sha1($instr . $salt, true).$salt) );
    return ( sprintf("*%s*{SSHA}%s", $salt, base64_encode(sha1($instr.$salt, true) . $salt ) ) );
  }
}


if ( !function_exists("session_validate_password") ) {
  /**
  * Checks what a user entered against the actual password on their account.
  * @param string $they_sent What the user entered.
  * @param string $we_have What we have in the database as their password.  Which may (or may not) be a salted MD5.
  * @return boolean Whether or not the users attempt matches what is already on file.
  */
  function session_validate_password( $they_sent, $we_have ) {
    if ( preg_match('/^\*\*.+$/', $we_have ) ) {
      //  The "forced" style of "**plaintext" to allow easier admin setting
      return ( "**$they_sent" == $we_have );
    }

    if ( preg_match('/^\*(.+)\*{[A-Z]+}.+$/', $we_have, $regs ) ) {
      if ( function_exists("session_salted_sha1") ) {
        // A nicely salted sha1sum like "*<salt>*{SSHA}<salted_sha1>"
        $salt = $regs[1];
        $sha1_sent = session_salted_sha1( $they_sent, $salt ) ;
        return ( $sha1_sent == $we_have );
      }
      else {
        dbg_error_log( "ERROR", "Password is salted SHA-1 but you are using PHP4!" );
        echo <<<EOERRMSG
<html>
<head>
<title>Salted SHA1 Password format not supported with PHP4</title>
</head>
<body>
<h1>Salted SHA1 Password format not supported with PHP4</h1>
<p>At some point you have used PHP5 to set the password for this user and now you are
   using PHP4.  You will need to assign a new password to this user using PHP4, or ensure
   you use PHP5 everywhere (recommended).</p>
<p>AWL has now switched to using salted SHA-1 passwords by preference in a format
   compatible with OpenLDAP.</p>
</body>
</html>
EOERRMSG;
        exit;
      }
    }

    if ( preg_match('/^\*MD5\*.+$/', $we_have, $regs ) ) {
      // A crappy unsalted md5sum like "*MD5*<md5>"
      $md5_sent = session_simple_md5( $they_sent ) ;
      return ( $md5_sent == $we_have );
    }
    else if ( preg_match('/^\*(.+)\*.+$/', $we_have, $regs ) ) {
      // A nicely salted md5sum like "*<salt>*<salted_md5>"
      $salt = $regs[1];
      $md5_sent = session_salted_md5( $they_sent, $salt ) ;
      return ( $md5_sent == $we_have );
    }

    // Anything else is bad
    return false;

  }
}



if ( !function_exists("replace_uri_params") ) {
  /**
  * Given a URL (presumably the current one) and a parameter, replace the value of parameter,
  * extending the URL as necessary if the parameter is not already there.
  * @param string $uri The URI we will be replacing parameters in.
  * @param array $replacements An array of replacement pairs array( "replace_this" => "with this" )
  * @return string The URI with the replacements done.
  */
  function replace_uri_params( $uri, $replacements ) {
    $replaced = $uri;
    foreach( $replacements AS $param => $new_value ) {
      $rxp = preg_replace( '/([\[\]])/', '\\\\$1', $param );  // Some parameters may be arrays.
      $regex = "/([&?])($rxp)=([^&]+)/";
      dbg_error_log("core", "Looking for [%s] to replace with [%s] regex is %s and searching [%s]", $param, $new_value, $regex, $replaced );
      if ( preg_match( $regex, $replaced ) )
        $replaced = preg_replace( $regex, "\$1$param=$new_value", $replaced);
      else
        $replaced .= "&$param=$new_value";
    }
    if ( ! preg_match( '/\?/', $replaced  ) ) {
      $replaced = preg_replace("/&(.+)$/", "?\$1", $replaced);
    }
    $replaced = str_replace("&amp;", "--AmPeRsAnD--", $replaced);
    $replaced = str_replace("&", "&amp;", $replaced);
    $replaced = str_replace("--AmPeRsAnD--", "&amp;", $replaced);
    dbg_error_log("core", "URI <<$uri>> morphed to <<$replaced>>");
    return $replaced;
  }
}


if ( !function_exists("uuid") ) {
/**
 * Generates a Universally Unique IDentifier, version 4.
 *
 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
 * Unique IDentifiers (GUID), as well as several methods for producing them. One
 * such method, described in section 4.4, is based on truly random or pseudo-random
 * number generators, and is therefore implementable in a language like PHP.
 *
 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
 * are random.
 *
 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
 * be 01. We try to conform to that definition as efficiently as possible, generating
 * smaller values where possible, and minimizing the number of base conversions.
 *
 * @copyright  Copyright (c) CFD Labs, 2006. This function may be used freely for
 *              any purpose ; it is distributed without any form of warranty whatsoever.
 * @author      David Holmes <dholmes@cfdsoftware.net>
 *
 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
 */

  function uuid() {

    // The field names refer to RFC 4122 section 4.1.2

    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
        mt_rand(0, 65535), // 16 bits for "time_mid"
        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
    );
  }
}

if ( !function_exists("translate") ) {
  require("Translation.php");
}

 if ( !function_exists("clone") && version_compare(phpversion(), '5.0') < 0) {
  /**
  * PHP5 screws with the assignment operator changing so that $a = $b means that
  * $a becomes a reference to $b.  There is a clone() that we can use in PHP5, so
  * we have to emulate that for PHP4.  Bleargh.
  */
  eval( 'function clone($object) { return $object; }' );
}

if ( !function_exists("quoted_printable_encode") ) {
  /**
  * Process a string to fit the requirements of RFC2045 section 6.7.  Note that
  * this works, but replaces more characters than the minimum set. For readability
  * the spaces aren't encoded as =20 though.
  */
  function quoted_printable_encode($string) {
    return preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", str_replace("%","=",str_replace("%20"," ",rawurlencode($string))));
  }
}


if ( !function_exists("check_by_regex") ) {
  /**
  * Verify a value is OK by testing a regex against it.  If it is an array apply it to
  * each element in the array recursively.  If it is an object we don't mess
  * with it.
  */
  function check_by_regex( $val, $regex ) {
    if ( is_null($val) ) return null;
    switch( $regex ) {
      case 'int':     $regex = '#^\d+$#';     break;
    }
    if ( is_array($val) ) {
      foreach( $val AS $k => $v ) {
        $val[$k] = check_by_regex($v,$regex);
      }
    }
    else if ( ! is_object($val) ) {
      if ( preg_match( $regex, $val, $matches) ) {
        $val = $matches[0];
      }
      else {
        $val = '';
      }
    }
    return $val;
  }
}


if ( !function_exists("param_to_global") ) {
  /**
  * Convert a parameter to a global.  We first look in _POST and then in _GET,
  * and if they passed in a bunch of valid characters, we will make sure the
  * incoming is cleaned to only match that set.
  *
  * @param string $varname The name of the global variable to put the answer in
  * @param string $match_regex The part of the parameter matching this regex will be returned
  * @param string $alias1  An alias for the name that we should look for first.
  * @param    "    ...     More aliases, in the order which they should be examined.  $varname will be appended to the end.
  */
  function param_to_global( ) {
    $args = func_get_args();

    $varname = array_shift($args);
    $GLOBALS[$varname] = null;

    $match_regex = null;
    $argc = func_num_args();
    if ( $argc > 1 ) {
      $match_regex = array_shift($args);
    }

    $args[] = $varname;
    foreach( $args AS $k => $name ) {
      if ( isset($_POST[$name]) ) {
        $result = $_POST[$name];
        break;
      }
      else if ( isset($_GET[$name]) ) {
        $result = $_GET[$name];
        break;
      }
    }
    if ( !isset($result) ) return null;

    if ( isset($match_regex) ) {
      $result = check_by_regex( $result, $match_regex );
    }

    $GLOBALS[$varname] = $result;
    return $result;
  }
}


if ( !function_exists("get_fields") ) {
  /**
  * @var array $_AWL_field_cache is a cache of the field names for a table
  */
  $_AWL_field_cache = array();
  
  /**
  * Get the names of the fields for a particular table
  * @param string $tablename The name of the table.
  * @return array of string The public fields in the table.
  */
  function get_fields( $tablename ) {
    global $_AWL_field_cache;

    if ( !isset($_AWL_field_cache[$tablename]) ) {
      dbg_error_log( "core", ":get_fields: Loading fields for table '$tablename'" );
      $qry = new AwlQuery();
      $db = $qry->GetConnection();
      $qry->SetSQL($db->GetFields($tablename));
      $qry->Exec("core");
      $fields = array();
      while( $row = $qry->Fetch() ) {
        $fields[$row->fieldname] = $row->typename . ($row->precision >= 0 ? sprintf('(%d)',$row->precision) : '');
      }
      $_AWL_field_cache[$tablename] = $fields;
    }
    return $_AWL_field_cache[$tablename];
  }
}


if ( !function_exists("force_utf8") ) {
  function define_byte_mappings() {
    global $byte_map, $nibble_good_chars;

    # Needed for using Grant McLean's byte mappings code
    $ascii_char = '[\x00-\x7F]';
    $cont_byte  = '[\x80-\xBF]';

    $utf8_2     = '[\xC0-\xDF]' . $cont_byte;
    $utf8_3     = '[\xE0-\xEF]' . $cont_byte . '{2}';
    $utf8_4     = '[\xF0-\xF7]' . $cont_byte . '{3}';
    $utf8_5     = '[\xF8-\xFB]' . $cont_byte . '{4}';

    $nibble_good_chars = "/^($ascii_char+|$utf8_2|$utf8_3|$utf8_4|$utf8_5)(.*)$/s";

    # From http://unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP1252.TXT
    $byte_map = array(
        "\x80" => "\xE2\x82\xAC",  # EURO SIGN
        "\x82" => "\xE2\x80\x9A",  # SINGLE LOW-9 QUOTATION MARK
        "\x83" => "\xC6\x92",      # LATIN SMALL LETTER F WITH HOOK
        "\x84" => "\xE2\x80\x9E",  # DOUBLE LOW-9 QUOTATION MARK
        "\x85" => "\xE2\x80\xA6",  # HORIZONTAL ELLIPSIS
        "\x86" => "\xE2\x80\xA0",  # DAGGER
        "\x87" => "\xE2\x80\xA1",  # DOUBLE DAGGER
        "\x88" => "\xCB\x86",      # MODIFIER LETTER CIRCUMFLEX ACCENT
        "\x89" => "\xE2\x80\xB0",  # PER MILLE SIGN
        "\x8A" => "\xC5\xA0",      # LATIN CAPITAL LETTER S WITH CARON
        "\x8B" => "\xE2\x80\xB9",  # SINGLE LEFT-POINTING ANGLE QUOTATION MARK
        "\x8C" => "\xC5\x92",      # LATIN CAPITAL LIGATURE OE
        "\x8E" => "\xC5\xBD",      # LATIN CAPITAL LETTER Z WITH CARON
        "\x91" => "\xE2\x80\x98",  # LEFT SINGLE QUOTATION MARK
        "\x92" => "\xE2\x80\x99",  # RIGHT SINGLE QUOTATION MARK
        "\x93" => "\xE2\x80\x9C",  # LEFT DOUBLE QUOTATION MARK
        "\x94" => "\xE2\x80\x9D",  # RIGHT DOUBLE QUOTATION MARK
        "\x95" => "\xE2\x80\xA2",  # BULLET
        "\x96" => "\xE2\x80\x93",  # EN DASH
        "\x97" => "\xE2\x80\x94",  # EM DASH
        "\x98" => "\xCB\x9C",      # SMALL TILDE
        "\x99" => "\xE2\x84\xA2",  # TRADE MARK SIGN
        "\x9A" => "\xC5\xA1",      # LATIN SMALL LETTER S WITH CARON
        "\x9B" => "\xE2\x80\xBA",  # SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
        "\x9C" => "\xC5\x93",      # LATIN SMALL LIGATURE OE
        "\x9E" => "\xC5\xBE",      # LATIN SMALL LETTER Z WITH CARON
        "\x9F" => "\xC5\xB8",      # LATIN CAPITAL LETTER Y WITH DIAERESIS
    );

    for( $i=160; $i < 256; $i++ ) {
      $ch = chr($i);
      $byte_map[$ch] = iconv('ISO-8859-1', 'UTF-8', $ch);
    }
  }
  define_byte_mappings();

  function force_utf8( $input ) {
    global $byte_map, $nibble_good_chars;

    $output = '';
    $char   = '';
    $rest   = '';
    while( $input != '' ) {
      if ( preg_match( $nibble_good_chars, $input, $matches ) ) {
        $output .= $matches[1];
        $rest = $matches[2];
      }
      else {
        preg_match( '/^(.)(.*)$/s', $input, $matches );
        $char = $matches[1];
        $rest = $matches[2];
        if ( isset($byte_map[$char]) ) {
          $output .= $byte_map[$char];
        }
        else {
          # Must be valid UTF8 already
          $output .= $char;
        }
      }
      $input = $rest;
    }
    return $output;
  }

}


/**
* Try and extract something like "Pacific/Auckland" or "America/Indiana/Indianapolis" if possible.
*/
function olson_from_tzstring( $tzstring ) {
  if ( in_array($tzstring,timezone_identifiers_list()) ) return $tzstring;
  if ( preg_match( '{((Antarctica|America|Africa|Atlantic|Asia|Australia|Indian|Europe|Pacific)/(([^/]+)/)?[^/]+)$}', $tzstring, $matches ) ) {
//    dbg_error_log( 'INFO', 'Found timezone "%s" from string "%s"', $matches[1], $tzstring );
    return $matches[1];
  }
  switch( $tzstring ) {
    case 'New Zealand Standard Time': case 'New Zealand Daylight Time': return 'Pacific/Auckland'; break;
    case 'Central Standard Time':     case 'Central Daylight Time':     return 'America/Chicago';  break;
    case 'Eastern Standard Time':     case 'Eastern Daylight Time':     return 'America/New_York'; break;
    case 'Pacific Standard Time':     case 'Pacific Daylight Time':     return 'America/Los_Angeles'; break;
  }
  return null;
}


/**
 * Return the AWL version
 */
function awl_version() {
  global $c;
$c->awl_library_version = 0.46;
  return $c->awl_library_version;
}