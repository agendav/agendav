<?php
/**
 * Manages PAM repository connection with pwauth
 *
 * @package   davical
 * @category Technical
 * @subpackage   pwauth
 * @author    Eric Seigne <eric.seigne@ryxeo.com>,
 *   Michael B. Trausch <mike@trausch.us>
 * @copyright Eric Seigne
 * @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
 *
 * Based on drivers_squid_pam.php
 */

require_once("auth-functions.php");

class pwauthPamDrivers
{
  /**#@+
   * @access private
   */

  /**#@-*/


  /**
   * Constructor.
   * @param string $config path where pwauth is
   */
  function pwauthPamDrivers($config){
    $this->__construct($config);
  }


  /**
   * The constructor
   *
   * @param string $config path where pwauth is
   */
  function __construct($config)
  {
    global $c;
    if(!file_exists($config)) {
      $c->messages[] =
	sprintf(i18n('drivers_pwauth_pam : Unable to find %s file'), $config);
      $this->valid=false;
      return ;
    }
  }
}


/**
 * Check the username / password against the PAM system
 */
function PWAUTH_PAM_check($username, $password) {
  global $c;
  $program = $c->authenticate_hook['config']['path'];
  $email_base = $c->authenticate_hook['config']['email_base'];

  $pipe = popen(escapeshellarg($program), 'w');
  $authinfo = sprintf("%s\n%s\n", $username, $password);
  $written = fwrite($pipe, $authinfo);
  dbg_error_log('pwauth', 'Bytes written: %d of %d', $written,
		strlen($authinfo));
  $return_status = pclose($pipe);

  switch($return_status) {
  case 0:
    // STATUS_OK: Authentication succeeded.
    dbg_error_log('pwauth', 'User %s successfully authenticated', $username);
    if($user = getUserByName($username)) {
      return($user);
    } else {
      dbg_error_log('pwauth', 'User %s does not exist in local db, creating',
		    $username);
      $fullname = exec(sprintf('getent passwd %s', escapeshellarg($username)));
      $fullname = preg_replace('{^[^:]+:[^:]+:\d+:\d+:([^:,]+)(,[^:]*):.*$}',
			       '$1', $fullname);
      $user = (object) array('user_no' => 0,
			     'username' => $username,
			     'active' => 't',
			     'email' => sprintf('%s@%s', $username,
						$email_base),
			     'updated' => date('%r'),
			     'fullname' => $fullname);

      UpdateUserFromExternal($user);
      return($user);
    }
    break;

    /*
     * Note that for system configurations using PAM instead of
     * reading the password database directly, if PAM is unable to
     * read the password database, pwauth will return status 1.
     */
  case 1:
  case 2:
    // (1) STATUS_UNKNOWN: Invalid username or password.
    // (2) STATUS_INVALID: Invalid password.
    dbg_error_log('pwauth', 'Invalid username or password (username: %s)',
		  $username);
    break;

  case 3:
    // STATUS_BLOCKED: UID for username is < pwauth's MIN_UNIX_UID
    dbg_error_log('pwauth', 'UID for username %s is < pwauth MIN_UNIX_UID',
		  $username);
    break;

  case 4:
    // STATUS_EXPIRED: The user account has expired.
    dbg_error_log('pwauth', 'The account for %s has expired', $username);
    break;

  case 5:
    // STATUS_PW_EXPIRED: The user account's password has expired.
    dbg_error_log('pwauth', 'The account password for user %s has expired',
		  $username);
    break;

  case 6:
    // STATUS_NOLOGIN: Logins to the system are administratively disabled.
    dbg_error_log('pwauth', 'Logins administratively disabled (%s)', $username);
    break;

  case 7:
    // STATUS_MANYFAILS: Too many login failures for user account.
    dbg_error_log('pwauth', 'Login rejected for %s, too many failures',
		  $username);
    break;

  case 50:
    // STATUS_INT_USER: Configuration error, Web server cannot use pwauth
    dbg_error_log('pwauth', 'config error: see pwauth man page (%s)',
		  'STATUS_INT_USER');
    break;

  case 51:
    // STATUS_INT_ARGS: pwauth received no username/passwd to check
    dbg_error_log('pwauth', 'error: pwauth received no username/password');
    break;

  case 52:
    // STATUS_INT_ERR: unknown error
    dbg_error_log('pwauth', 'error: see pwauth man page (%s)',
		  'STATUS_INT_ERR');
    break;

  case 53:
    // STATUS_INT_NOROOT: pwauth could not read the password database
    dbg_error_log('pwauth', 'config error: cannot read password database (%s)',
		  'STATUS_INT_NOROOT');

  default:
    // Unknown error code.
    dbg_error_log('pwauth', 'An unknown error (%d) has occurred',
		  $return_status);
  }

  return(FALSE);
}
