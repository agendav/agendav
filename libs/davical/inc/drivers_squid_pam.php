<?php
/**
* Manages PAM repository connection with SQUID help
*
* @package   davical
* @category Technical
* @subpackage   ldap
* @author    Eric Seigne <eric.seigne@ryxeo.com>
* @copyright Eric Seigne
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/

require_once("auth-functions.php");

class squidPamDrivers
{
  /**#@+
  * @access private
  */

  /**#@-*/


  /**
  * Constructor.
  * @param string $config path where /usr/lib/squid/pam_auth is
  */
  function squidPamDrivers($config){
      $this->__construct($config);
  }


  /**
  * The constructor
  *
  * @param string $config path where /usr/lib/squid/pam_auth is
  */
  function __construct($config)
  {
      global $c;
      if (! file_exists($config)){
          $c->messages[] = sprintf(i18n( 'drivers_squid_pam : Unable to find %s file'), $config );
          $this->valid=false;
          return ;
      }
  }
}


/**
* Check the username / password against the PAM system
*/
function SQUID_PAM_check($username, $password ){
  global $c;

  /**
  * @todo Think of the children!  This is a horribly insecure use of unvalidated user input!  Probably it should be done with a popen or something, and it seems remarkably dodgy to expect that naively quoted strings will work in any way reliably.
  * Meanwhile, I've quickly hacked something basic in place to improve the situation.  No quotes/backslashes in passwords for YOU!
  */
  $username = str_replace("'","",str_replace('"',"",str_replace('\\',"",$username)));
  $password = str_replace("'","",str_replace('"',"",str_replace('\\',"",$password)));
  $cmd = "echo '" . $username . "' '" . $password . "' | " . $c->authenticate_hook['config']['script'] . " -n common-auth";
  $auth_result = exec($cmd);
  if ( $auth_result == "OK") {
    if ( $usr = getUserByName($username) ) {
      return $usr;
    }
    else {
      dbg_error_log( "PAM", "user %s doesn't exist in local DB, we need to create it",$username );
      $fullname = exec('getent passwd "'.$username.'"' );
      $fullname = preg_replace( '{^[^:]+:[^:]+:\d+:\d+:([^:,]+)(,?[^:]*):.*$}', '$1', $fullname );
      $usr = (object) array(
              'user_no' => 0,
              'username' => $username,
              'active' => 't',
              'email' => $username . "@" . $c->authenticate_hook['config']['email_base'],
              'updated' => date(),
              'fullname' => $fullname
      );

      UpdateUserFromExternal( $usr );
      return $usr;
    }
  }
  else {
    dbg_error_log( "PAM", "User %s is not a valid username (or password was wrong)", $username );
    return false;
  }

}
