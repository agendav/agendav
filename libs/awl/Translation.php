<?php
/**
* Functions involved in translating with gettext
* @package awl
* @subpackage   Translation
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

if ( !function_exists('i18n') ) {
  /**
  * Mark a string as being internationalized.  This is a semaphore method; it
  * does nothing but it allows us to easily identify strings that require
  * translation.  Generally this is used to mark strings that will be stored
  * in the database (like descriptions of permissions).
  *
  * AWL uses GNU gettext for internationalization (i18n) and localization (l10n) of
  * text presented to the user. Gettext needs to know about all places involving strings,
  * that must be translated. Mark any place, where localization at runtime shall take place
  * by using the function translate().
  *
  * In the help I have used 'xlate' rather than 'translate' and 'x18n' rather than 'i18n'
  * so that the tools skip this particular file for translation :-)
  *
  * E.g. instead of:
  *   print 'TEST to be displayed in different languages';
  * use:
  *   print xlate('TEST to be displayed in different languages');
  * and you are all set for pure literals. The translation teams will receive that literal
  * string as a job to translate and will translate it (when the message is clear enough).
  * At runtime the message is then localized when printed.
  * The input string can contain a hint to assist translators:
  *   print xlate('TT <!-- abbreviation for Translation Test -->');
  * The hint portion of the string will not be printed.
  *
  * But consider this case:
  *   $message_to_be_localized = 'TEST to be displayed in different languages';
  *   print xlate($message_to_be_localized);
  *
  * The translate() function is called in the right place for runtime handling, but there
  * is no message at gettext preprocessing time to be given to the translation teams,
  * just a variable name. Translation of the variable name would break the code! So all
  * places potentially feeding this variable have to be marked to be given to translation
  * teams, but not translated at runtime!
  *
  * This method resolves all such cases. Simply mark the candidates:
  *   $message_to_be_localized = x18n('TEST to be displayed in different languages');
  *   print xlate($message_to_be_localized);
  *
  * @param string the value
  * @return string the same value
  */
  function i18n($value) {
    return $value;  /* Just pass the value through */
  }
}


if ( !function_exists('translate') ) {
  /**
  * Convert a string in English to whatever this user's locale is
  */
  if ( function_exists('gettext') ) {
    function translate( $en ) {
      if ( ! isset($en) || $en == '' ) return $en;
      $xl = gettext($en);
      dbg_error_log('I18N','Translated =%s= into =%s=', $en, $xl );
      return $xl;
    }
  }
  else {
    function translate( $en ) {
      return $en;
    }
  }
}


if ( !function_exists('init_gettext') ) {
  /**
  * Initialise our use of Gettext
  */
  function init_gettext( $domain, $location ) {
    if ( !function_exists('bindtextdomain') ) return;
    bindtextdomain( $domain, $location );
    $codeset = bind_textdomain_codeset( $domain, 'UTF-8' );
    textdomain( $domain );
    dbg_error_log('I18N','Bound domain =%s= to location =%s= using character set =%s=', $domain, $location, $codeset );
  }
}


if ( !function_exists('awl_set_locale') ) {
  /**
  * Set the translation to the user's locale.  At this stage all we do is
  * call the gettext function.
  */
  function awl_set_locale( $locale ) {
    global $c;

    if ( !is_array($locale) && ! preg_match('/^[a-z]{2}(_[A-Z]{2})?\./', $locale ) ) {
      $locale = array( $locale, $locale.'.UTF-8');
    }
    if ( !function_exists('setlocale') ) {
      dbg_log_array('WARN','No "setlocale()" function?  PHP gettext support missing?' );
      return;
    }
    if ( $newlocale = setlocale( LC_ALL, $locale) ) {
      dbg_error_log('I18N','Set locale to =%s=', $newlocale );
      $c->current_locale = $newlocale;
    }
    else {
      dbg_log_array('I18N','Unsupported locale: ', $locale, false );
    }
  }
}

