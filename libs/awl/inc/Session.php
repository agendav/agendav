<?php
/**
* Session handling class and associated functions
*
* This subpackage provides some functions that are useful around web
* application session management.
*
* The class is intended to be as lightweight as possible while holding
* all session data in the database:
*  - Session hash is not predictable.
*  - No clear text information is held in cookies.
*  - Passwords are generally salted MD5 hashes, but individual users may
*    have plain text passwords set by an administrator.
*  - Temporary passwords are supported.
*  - Logout is supported
*  - "Remember me" cookies are supported, and will result in a new
*    Session for each browser session.
*
* @package   awl
* @subpackage   Session
* @author Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
require_once('AWLUtilities.php');
require_once('AwlQuery.php');
require_once('EMail.php');


/**
* Checks what a user entered against any currently valid temporary passwords on their account.
* @param string $they_sent What the user entered.
* @param int $user_no Which user is attempting to log on.
* @return boolean Whether or not the user correctly guessed a temporary password within the necessary window of opportunity.
*/
function check_temporary_passwords( $they_sent, $user_no ) {
  $sql = 'SELECT 1 AS ok FROM tmp_password WHERE user_no = ? AND password = ? AND valid_until > current_timestamp';
  $qry = new AwlQuery( $sql, $user_no, $they_sent );
  if ( $qry->Exec('Session::check_temporary_passwords') ) {
    dbg_error_log( "Login", " check_temporary_passwords: Rows = ".$qry->rows());
    if ( $row = $qry->Fetch() ) {
      dbg_error_log( "Login", " check_temporary_passwords: OK = $row->ok");
      // Remove all the temporary passwords for that user...
      $sql = 'DELETE FROM tmp_password WHERE user_no = ? ';
      $qry = new AwlQuery( $sql, $user_no );
      $qry->Exec('Login',__LINE__,__FILE__);
      return true;
    }
  }
  return false;
}

/**
* A class for creating and holding session information.
*
* @package   awl
*/
class Session
{
  /**#@+
  * @access private
  */
  var $roles;
  var $cause = '';
  /**#@-*/

  /**#@+
  * @access public
  */

  /**
  * The user_no of the logged in user.
  * @var int
  */
  var $user_no;

  /**
  * A unique id for this user's logged-in session.
  * @var int
  */
  var $session_id = 0;

  /**
  * The user's username used to log in.
  * @var int
  */
  var $username = 'guest';

  /**
  * The user's full name from their usr record.
  * @var int
  */
  var $fullname = 'Guest';

  /**
  * The user's email address from their usr record.
  * @var int
  */
  var $email = '';

  /**
  * Whether this user has actually logged in.
  * @var int
  */
  var $logged_in = false;

  /**
  * Whether the user logged in to view the current page.  Perhaps some details on the
  * login form might pollute an editable form and result in an unplanned submit.  This
  * can be used to program around such a problem.
  * @var boolean
  */
  var $just_logged_in = false;

  /**
  * The date and time that the user logged on during their last session.
  * @var string
  */
  var $last_session_start;

  /**
  * The date and time that the user requested their last page during their last
  * session.
  * @var string
  */
  var $last_session_end;
  /**#@-*/

  /**
  * Create a new Session object.
  *
  * If a session identifier is supplied, or we can find one in a cookie, we validate it
  * and consider the person logged in.  We read some useful session and user data in
  * passing as we do this.
  *
  * The session identifier contains a random value, hashed, to provide validation. This
  * could be hijacked if the traffic was sniffable so sites who are paranoid about security
  * should only do this across SSL.
  *
  * A worthwhile enhancement would be to add some degree of external configurability to
  * that read.
  *
  * @param string $sid A session identifier.
  */
  function Session( $sid="" )
  {
    global $sid, $sysname;

    $this->roles = array();
    $this->logged_in = false;
    $this->just_logged_in = false;
    $this->login_failed = false;

    if ( $sid == "" ) {
      if ( ! isset($_COOKIE['sid']) ) return;
      $sid = $_COOKIE['sid'];
    }

    list( $session_id, $session_key ) = explode( ';', $sid, 2 );

    /**
    * We regularly want to override the SQL for joining against the session record.
    * so the calling application can define a function local_session_sql() which
    * will return the SQL to join (up to and excluding the WHERE clause.  The standard
    * SQL used if this function is not defined is:
    * <code>
    * SELECT session.*, usr.* FROM session JOIN usr ON ( user_no )
    * </code>
    */
    if ( function_exists('local_session_sql') ) {
      $sql = local_session_sql();
    }
    else {
      $sql = "SELECT session.*, usr.* FROM session JOIN usr USING ( user_no )";
    }
    $sql .= " WHERE session.session_id = ? AND (md5(session.session_start::text) = ? OR session.session_key = ?) ORDER BY session.session_start DESC LIMIT 2";

    $qry = new AwlQuery($sql, $session_id, $session_key, $session_key);
    if ( $qry->Exec('Session') && 1 == $qry->rows() ) {
      $this->AssignSessionDetails( $qry->Fetch() );
      $qry = new AwlQuery('UPDATE session SET session_end = current_timestamp WHERE session_id=?', $session_id);
      $qry->Exec('Session');
    }
    else {
      //  Kill the existing cookie, which appears to be bogus
      setcookie('sid', '', 0,'/');
      $this->cause = 'ERR: Other than one session record matches. ' . $qry->rows();
      $this->Log( "WARN: Login $this->cause" );
    }
  }


  /**
  * DEPRECATED Utility function to log stuff with printf expansion.
  *
  * This function could be expanded to log something identifying the session, but
  * somewhat strangely this has not yet been done.
  *
  * @param string $whatever A log string
  * @param mixed $whatever... Further parameters to be replaced into the log string a la printf
  */
  function Log( $whatever )
  {
    global $c;

    $argc = func_num_args();
    $format = func_get_arg(0);
    if ( $argc == 1 || ($argc == 2 && func_get_arg(1) == "0" ) ) {
      error_log( "$c->sysabbr: $format" );
    }
    else {
      $args = array();
      for( $i=1; $i < $argc; $i++ ) {
        $args[] = func_get_arg($i);
      }
      error_log( "$c->sysabbr: " . vsprintf($format,$args) );
    }
  }

  /**
  * DEPRECATED Utility function to log debug stuff with printf expansion, and the ability to
  * enable it selectively.
  *
  * The enabling is done by setting a variable "$debuggroups[$group] = 1"
  *
  * @param string $group The name of an arbitrary debug group.
  * @param string $whatever A log string
  * @param mixed $whatever... Further parameters to be replaced into the log string a la printf
  */
  function Dbg( $whatever )
  {
    global $debuggroups, $c;

    $argc = func_num_args();
    $dgroup = func_get_arg(0);

    if ( ! (isset($debuggroups[$dgroup]) && $debuggroups[$dgroup]) ) return;

    $format = func_get_arg(1);
    if ( $argc == 2 || ($argc == 3 && func_get_arg(2) == "0" ) ) {
      error_log( "$c->sysabbr: DBG: $dgroup: $format" );
    }
    else {
      $args = array();
      for( $i=2; $i < $argc; $i++ ) {
        $args[] = func_get_arg($i);
      }
      error_log( "$c->sysabbr: DBG: $dgroup: " . vsprintf($format,$args) );
    }
  }

  /**
  * Checks whether a user is allowed to do something.
  *
  * The check is performed to see if the user has that role.
  *
  * @param string $whatever The role we want to know if the user has.
  * @return boolean Whether or not the user has the specified role.
  */
  function AllowedTo ( $whatever ) {
    return ( $this->logged_in && isset($this->roles[$whatever]) && $this->roles[$whatever] );
  }


/**
* Internal function used to get the user's roles from the database.
*/
  function GetRoles () {
    $this->roles = array();
    $qry = new AwlQuery( 'SELECT role_name FROM role_member m join roles r ON r.role_no = m.role_no WHERE user_no = ? ', $this->user_no );
    if ( $qry->Exec('Session::GetRoles') && $qry->rows() > 0 ) {
      while( $role = $qry->Fetch() ) {
        $this->roles[$role->role_name] = true;
      }
    }
  }


/**
* Internal function used to assign the session details to a user's new session.
* @param object $u The user+session object we (probably) read from the database.
*/
  function AssignSessionDetails( $u ) {
    // Assign each field in the selected record to the object
    foreach( $u AS $k => $v ) {
      $this->{$k} = $v;
    }

    $qry = new AwlQuery( "SET DATESTYLE TO ?;", ($this->date_format_type == 'E' ? 'European,ISO' : ($this->date_format_type == 'U' ? 'US,ISO' : 'ISO')) );
    $qry->Exec();

    $this->GetRoles();
    $this->logged_in = true;
  }


/**
* Attempt to perform a login action.
*
* This will validate the user's username and password.  If they are OK then a new
* session id will be created and the user will be cookied with it for subsequent
* pages.  A logged in session will be created, and the $_POST array will be cleared
* of the username, password and submit values.  submit will also be cleared from
* $_GET and $GLOBALS, just in case.
*
* @param string $username The user's login name, or at least what they entered it as.
* @param string $password The user's password, or at least what they entered it as.
* @param string $authenticated If true, then authentication has already happened and the password is not checked, though the user must still exist.
* @return boolean Whether or not the user correctly guessed a temporary password within the necessary window of opportunity.
*/
  function Login( $username, $password, $authenticated = false ) {
    global $c;
    $rc = false;
    dbg_error_log( "Login", " Login: Attempting login for $username" );
    if ( isset($usr) ) unset($usr);  /** In case someone is running with register_globals on */

    /**
    * @todo In here we will need to put code to call the auth plugin, in order to
    * ensure the 'usr' table has current valid data.  At this stage we are just
    * thinking it through... like ...
    *
    */
    if ( !$authenticated && isset($c->authenticate_hook) && isset($c->authenticate_hook['call']) && function_exists($c->authenticate_hook['call']) ) {
      /**
      * The authenticate hook needs to:
      *   - Accept a username / password
      *   - Confirm the username / password are correct
      *   - Create (or update) a 'usr' record in our database
      *   - Return the 'usr' record as an object
      *   - Return === false when authentication fails
      * It can expect that:
      *   - Configuration data will be in $c->authenticate_hook['config'], which might be an array, or whatever is needed.
      */
      $usr = call_user_func( $c->authenticate_hook['call'], $username, $password );
      if ( $usr === false ) unset($usr); else $authenticated = true;
    }

    $sql = "SELECT * FROM usr WHERE lower(username) = ? AND active";
    $qry = new AwlQuery( $sql, strtolower($username) );
    if ( isset($usr) || ($qry->Exec('Login',__LINE__,__FILE__) && $qry->rows() == 1 && $usr = $qry->Fetch() ) ) {
      if ( $authenticated || session_validate_password( $password, $usr->password ) || check_temporary_passwords( $password, $usr->user_no ) ) {
        // Now get the next session ID to create one from...
        $qry = new AwlQuery( "SELECT nextval('session_session_id_seq')" );
        if ( $qry->Exec('Login') && $qry->rows() == 1 ) {
          $seq = $qry->Fetch();
          $session_id = $seq->nextval;
          $session_key = md5( rand(1010101,1999999999) . microtime() );  // just some random shite
          dbg_error_log( "Login", " Login: Valid username/password for $username ($usr->user_no)" );

          // Set the last_used timestamp to match the previous login.
          $qry = new AwlQuery('UPDATE usr SET last_used = (SELECT session_start FROM session WHERE session.user_no = ? ORDER BY session_id DESC LIMIT 1) WHERE user_no = ?;', $usr->user_no, $usr->user_no);
          $qry->Exec('Session');

          // And create a session
          $sql = "INSERT INTO session (session_id, user_no, session_key) VALUES( ?, ?, ? )";
          $qry = new AwlQuery( $sql, $session_id, $usr->user_no, $session_key );
          if ( $qry->Exec('Login') ) {
            // Assign our session ID variable
            $sid = "$session_id;$session_key";

            //  Create a cookie for the sesssion
            setcookie('sid',$sid, 0,'/');
            // Recognise that we have started a session now too...
            $this->Session($sid);
            dbg_error_log( "Login", " Login: New session $session_id started for $username ($usr->user_no)" );
            if ( isset($_POST['remember']) && intval($_POST['remember']) > 0 ) {
              $cookie = md5( $usr->user_no ) . ";";
              $cookie .= session_salted_md5($usr->user_no . $usr->username . $usr->password);
              $GLOBALS['lsid'] = $cookie;
              setcookie( "lsid", $cookie, time() + (86400 * 3600), "/" );   // will expire in ten or so years
            }
            $this->just_logged_in = true;

            // Unset all of the submitted values, so we don't accidentally submit an unexpected form.
            unset($_POST['username']);
            unset($_POST['password']);
            unset($_POST['submit']);
            unset($_GET['submit']);
            unset($GLOBALS['submit']);

            if ( function_exists('local_session_sql') ) {
              $sql = local_session_sql();
            }
            else {
              $sql = "SELECT session.*, usr.* FROM session JOIN usr USING ( user_no )";
            }
            $sql .= " WHERE session.session_id = ? AND (md5(session.session_start::text) = ? OR session.session_key = ?) ORDER BY session.session_start DESC LIMIT 2";

            $qry = new AwlQuery($sql, $session_id, $session_key, $session_key);
            if ( $qry->Exec('Session') && 1 == $qry->rows() ) {
              $this->AssignSessionDetails( $qry->Fetch() );
            }

            $rc = true;
            return $rc;
          }
   // else ...
          $this->cause = 'ERR: Could not create new session.';
        }
        else {
          $this->cause = 'ERR: Could not increment session sequence.';
        }
      }
      else {
        $c->messages[] = i18n('Invalid username or password.');
        if ( isset($c->dbg['Login']) || isset($c->dbg['ALL']) )
          $this->cause = 'WARN: Invalid password.';
        else
          $this->cause = 'WARN: Invalid username or password.';
      }
    }
    else {
    $c->messages[] = i18n('Invalid username or password.');
    if ( isset($c->dbg['Login']) || isset($c->dbg['ALL']) )
      $this->cause = 'WARN: Invalid username.';
    else
      $this->cause = 'WARN: Invalid username or password.';
    }

    $this->Log( "Login failure: $this->cause" );
    $this->login_failed = true;
    $rc = false;
    return $rc;
  }



/**
* Attempts to logs in using a long-term session ID
*
* This is all horribly insecure, but its hard not to be.
*
* @param string $lsid The user's value of the lsid cookie.
* @return boolean Whether or not the user's lsid cookie got them in the door.
*/
  function LSIDLogin( $lsid ) {
    global $c;
    dbg_error_log( "Login", " LSIDLogin: Attempting login for $lsid" );

    list($md5_user_no,$validation_string) = explode( ';', $lsid );
    $qry = new AwlQuery( "SELECT * FROM usr WHERE md5(user_no::text)=? AND active", $md5_user_no );
    if ( $qry->Exec('Login') && $qry->rows() == 1 ) {
      $usr = $qry->Fetch();
      list( $x, $salt, $y) = explode('*', $validation_string);
      $my_validation = session_salted_md5($usr->user_no . $usr->username . $usr->password, $salt);
      if ( $validation_string == $my_validation ) {
        // Now get the next session ID to create one from...
        $qry = new AwlQuery( "SELECT nextval('session_session_id_seq')" );
        if ( $qry->Exec('Login') && $qry->rows() == 1 ) {
          $seq = $qry->Fetch();
          $session_id = $seq->nextval;
          $session_key = md5( rand(1010101,1999999999) . microtime() );  // just some random shite
          dbg_error_log( "Login", " LSIDLogin: Valid username/password for $username ($usr->user_no)" );

          // And create a session
          $sql = "INSERT INTO session (session_id, user_no, session_key) VALUES( ?, ?, ? )";
          $qry = new AwlQuery( $sql, $session_id, $usr->user_no, $session_key );
          if ( $qry->Exec('Login') ) {
            // Assign our session ID variable
            $sid = "$session_id;$session_key";

            //  Create a cookie for the sesssion
            setcookie('sid',$sid, 0,'/');
            // Recognise that we have started a session now too...
            $this->Session($sid);
            dbg_error_log( "Login", " LSIDLogin: New session $session_id started for $this->username ($usr->user_no)" );

            $this->just_logged_in = true;

            // Unset all of the submitted values, so we don't accidentally submit an unexpected form.
            unset($_POST['username']);
            unset($_POST['password']);
            unset($_POST['submit']);
            unset($_GET['submit']);
            unset($GLOBALS['submit']);

            if ( function_exists('local_session_sql') ) {
              $sql = local_session_sql();
            }
            else {
              $sql = "SELECT session.*, usr.* FROM session JOIN usr USING ( user_no )";
            }
            $sql .= " WHERE session.session_id = ? AND (md5(session.session_start::text) = ? OR session.session_key = ?) ORDER BY session.session_start DESC LIMIT 2";

            $qry = new AwlQuery($sql, $session_id, $session_key, $session_key);
            if ( $qry->Exec('Session') && 1 == $qry->rows() ) {
              $this->AssignSessionDetails( $qry->Fetch() );
            }

            $rc = true;
            return $rc;
          }
   // else ...
          $this->cause = 'ERR: Could not create new session.';
        }
        else {
          $this->cause = 'ERR: Could not increment session sequence.';
        }
      }
      else {
        dbg_error_log( "Login", " LSIDLogin: $validation_string != $my_validation ($salt - $usr->user_no, $usr->username, $usr->password)");
        $client_messages[] = i18n('Invalid username or password.');
        if ( isset($c->dbg['Login']) || isset($c->dbg['ALL']) )
          $this->cause = 'WARN: Invalid password.';
        else
          $this->cause = 'WARN: Invalid username or password.';
      }
    }
    else {
    $client_messages[] = i18n('Invalid username or password.');
    if ( isset($c->dbg['Login']) || isset($c->dbg['ALL']) )
      $this->cause = 'WARN: Invalid username.';
    else
      $this->cause = 'WARN: Invalid username or password.';
    }

    dbg_error_log( "Login", " LSIDLogin: $this->cause" );
    return false;
  }


/**
* Renders some HTML for a basic login panel
*
* @return string The HTML to display a login panel.
*/
  function RenderLoginPanel() {
    $action_target = htmlspecialchars(preg_replace('/\?logout.*$/','',$_SERVER['REQUEST_URI']));
    dbg_error_log( "Login", " RenderLoginPanel: action_target='%s'", $action_target );
    $userprompt = translate("User Name");
    $pwprompt = translate("Password");
    $rememberprompt = str_replace( ' ', '&nbsp;', translate("forget me not"));
    $gobutton = htmlspecialchars(translate("GO!"));
    $gotitle = htmlspecialchars(translate("Enter your username and password then click here to log in."));
    $temppwprompt = translate("If you have forgotten your password then");
    $temppwbutton = htmlspecialchars(translate("Help! I've forgotten my password!"));
    $temppwtitle = htmlspecialchars(translate("Enter a username, if you know it, and click here, to be e-mailed a temporary password."));
    $html = <<<EOTEXT
<div id="logon">
<form action="$action_target" method="post">
<table>
<tr>
<th class="prompt">$userprompt:</th>
<td class="entry">
<input class="text" type="text" name="username" size="12" /></td>
</tr>
<tr>
<th class="prompt">$pwprompt:</th>
<td class="entry">
<input class="password" type="password" name="password" size="12" />
 &nbsp;<label>$rememberprompt: <input class="checkbox" type="checkbox" name="remember" value="1" /></label>
</td>
</tr>
<tr>
<th class="prompt">&nbsp;</th>
<td class="entry">
<input type="submit" value="$gobutton" title="$gotitle" name="submit" class="submit" />
</td>
</tr>
</table>
<p>
$temppwprompt: <input type="submit" value="$temppwbutton" title="$temppwtitle" name="lostpass" class="submit" />
</p>
</form>
</div>

EOTEXT;
    return $html;
  }


/**
* Checks that this user is logged in, and presents a login screen if they aren't.
*
* The function can optionally confirm whether they are a member of one of a list
* of groups, and deny access if they are not a member of any of them.
*
* @param string $groups The list of groups that the user must be a member of one of to be allowed to proceed.
* @return boolean Whether or not the user is logged in and is a member of one of the required groups.
*/
  function LoginRequired( $groups = "" ) {
    global $c, $session;

    if ( $this->logged_in && $groups == "" ) return;
    if ( ! $this->logged_in ) {
      $c->messages[] = i18n("You must log in to use this system.");
      if ( function_exists("local_index_not_logged_in") ) {
        local_index_not_logged_in();
      }
      else {
        $login_html = translate( "<h1>Log On Please</h1><p>For access to the %s you should log on withthe username and password that have been issued to you.</p><p>If you would like to request access, please e-mail %s.</p>");
        $page_content = sprintf( $login_html, $c->system_name, $c->admin_email );
        $page_content .= $this->RenderLoginPanel();
        if ( isset($page_elements) && gettype($page_elements) == 'array' ) {
          $page_elements[] = $page_content;
          @include("page-renderer.php");
          exit(0);
        }
        @include("page-header.php");
        echo $page_content;
        @include("page-footer.php");
      }
    }
    else {
      $valid_groups = explode(",", $groups);
      foreach( $valid_groups AS $k => $v ) {
        if ( $this->AllowedTo($v) ) return;
      }
      $c->messages[] = i18n("You are not authorised to use this function.");
      if ( isset($page_elements) && gettype($page_elements) == 'array' ) {
        @include("page-renderer.php");
        exit(0);
      }
      @include("page-header.php");
      @include("page-footer.php");
    }

    exit;
  }



/**
* E-mails a temporary password in response to a request from a user.
*
* This could be called from somewhere within the application that allows
* someone to set up a user and invite them.
*
* This function includes EMail.php to actually send the password.
*/
  function EmailTemporaryPassword( $username, $email_address, $body_template="" ) {
    global $c;

    $password_sent = false;
    $where = "";
    $params = array();
    if ( isset($username) && $username != "" ) {
      $where = 'WHERE active AND lower(usr.username) = :lcusername';
      $params[':lcusername'] = strtolower($username);
    }
    else if ( isset($email_address) && $email_address != "" ) {
      $where = 'WHERE active AND lower(usr.email) = :lcemail';
      $params[':lcemail'] = strtolower($email_address);
    }

    if ( $where != '' ) {
      if ( !isset($body_template) || $body_template == "" ) {
        $body_template = <<<EOTEXT

@@debugging@@A temporary password has been requested for @@system_name@@.

Temporary Password: @@password@@

This has been applied to the following usernames:

@@usernames@@
and will be valid for 24 hours.

If you have any problems, please contact the system administrator.

EOTEXT;
      }

      $qry = new AwlQuery( 'SELECT * FROM usr '.$where, $params );
      $qry->Exec('Session::EmailTemporaryPassword');
      if ( $qry->rows() > 0 ) {
        $q2 = new AwlQuery();
        $q2->Begin();

        while ( $row = $qry->Fetch() ) {
          $mail = new EMail( "Access to $c->system_name" );
          $mail->SetFrom($c->admin_email );
          $usernames = "";
          $debug_to = "";
          if ( isset($c->debug_email) ) {
            $debug_to = "This e-mail would normally be sent to:\n ";
            $mail->AddTo( "Tester <$c->debug_email>" );
          }

          $tmp_passwd = '';
          for ( $i=0; $i < 8; $i++ ) {
            $tmp_passwd .= substr( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ+#.-=*%@0123456789abcdefghijklmnopqrstuvwxyz', rand(0,69), 1);
          }

          $q2->QDo('INSERT INTO tmp_password (user_no, password) VALUES(?,?)', array($row->user_no, $tmp_passwd));
          if ( isset($c->debug_email) ) {
            $debug_to .= "$row->fullname <$row->email> ";
          }
          else {
            $mail->AddTo( "$row->fullname <$row->email>" );
          }
          $usernames .= "        $row->username\n";

          if ( $mail->To != "" ) {
            if ( isset($c->debug_email) ) {
              $debug_to .= "\n============================================================\n";
            }
            $sql .= "COMMIT;";
            $qry = new AwlQuery( $sql );
            $qry->Exec("Session::SendTemporaryPassword");
            $body = str_replace( '@@system_name@@', $c->system_name, $body_template);
            $body = str_replace( '@@password@@', $tmp_passwd, $body);
            $body = str_replace( '@@usernames@@', $usernames, $body);
            $body = str_replace( '@@debugging@@', $debug_to, $body);
            $mail->SetBody($body);
            $mail->Send();
            $password_sent = true;
          }
        }
      }
    }
    return $password_sent;
  }


/**
* Sends a temporary password in response to a request from a user.
*
* This is probably only going to be called from somewhere internal.  An external
* caller will probably just want the e-mail, without the HTML that this displays.
*
*/
  function SendTemporaryPassword( ) {
    global $c, $page_elements;

    $password_sent = $this->EmailTemporaryPassword( (isset($_POST['username'])?$_POST['username']:null), (isset($_POST['email_address'])?$_POST['email_address']:null) );

    if ( ! $password_sent && ((isset($_POST['username']) && $_POST['username'] != "" )
                              || (isset($_POST['email_address']) && $_POST['email_address'] != "" )) ) {
      // Username or EMail were non-null, but we didn't find that user.

      $page_content = <<<EOTEXT
<div id="logon">
<h1>Unable to Reset Password</h1>
<p>We were unable to reset your password at this time.  Please contact
<a href="mailto:$c->admin_email">$c->admin_email</a>
to arrange for an administrator to reset your password.</p>
<p>Thank you.</p>
</div>
EOTEXT;
    }

    if ( $password_sent ) {
      $page_content = <<<EOTEXT
<div id="logon">
<h1>Temporary Password Sent</h1>
<p>A temporary password has been e-mailed to you.  This password
will be valid for 24 hours and you will be required to change
your password after logging in.</p>
<p><a href="/">Click here to return to the login page.</a></p>
</div>
EOTEXT;
    }
    else {
      $page_content = <<<EOTEXT
<div id="logon">
<h1>Temporary Password</h1>
<form action="$action_target" method="post">
<table>
<tr>
<th class="prompt" style="white-space: nowrap;">Enter your User Name:</th>
<td class="entry"><input class="text" type="text" name="username" size="12" /></td>
</tr>
<tr>
<th class="prompt" style="white-space: nowrap;">Or your EMail Address:</th>
<td class="entry"><input class="text" type="text" name="email_address" size="50" /></td>
</tr>
<tr>
<th class="prompt" style="white-space: nowrap;">and click on -></th>
<td class="entry">
<input class="submit" type="submit" value="Send me a temporary password" alt="Enter a username, or e-mail address, and click here." name="lostpass" />
</td>
</tr>
</table>
<p>Note: If you have multiple accounts with the same e-mail address, they will <em>all</em>
be assigned a new temporary password, but only the one(s) that you use that temporary password
on will have the existing password invalidated.</p>
<h2>The temporary password will only be valid for 24 hours.</h2>
<p>You will need to log on and change your password during this time.</p>
</form>
</div>
EOTEXT;
    }
    if ( isset($page_elements) && gettype($page_elements) == 'array' ) {
      $page_elements[] = $page_content;
      @include("page-renderer.php");
      exit(0);
    }
    @include("page-header.php");
    echo $page_content;
    @include("page-footer.php");
    exit(0);
  }

  static function _CheckLogout() {
    if ( isset($_GET['logout']) ) {
      dbg_error_log( "Login", ":_CheckLogout: Logging out");
      setcookie( 'sid', '', 0,'/');
      unset($_COOKIE['sid']);
      unset($GLOBALS['sid']);
      unset($_COOKIE['lsid']); // Allow a cookied person to be un-logged-in for one page view.
      unset($GLOBALS['lsid']);

      if ( isset($_GET['forget']) ) setcookie( 'lsid', '', 0,'/');
    }
  }

  function _CheckLogin() {
    global $c;
    if ( isset($_POST['lostpass']) ) {
      dbg_error_log( "Login", ":_CheckLogin: User '$_POST[username]' has lost the password." );
      $this->SendTemporaryPassword();
    }
    else if ( isset($_POST['username']) && isset($_POST['password']) ) {
      // Try and log in if we have a username and password
      $this->Login( $_POST['username'], $_POST['password'] );
      @dbg_error_log( "Login", ":_CheckLogin: User %s(%s) - %s (%d) login status is %d", $_POST['username'], $this->fullname, $this->user_no, $this->logged_in );
    }
    else if ( !isset($_COOKIE['sid']) && isset($_COOKIE['lsid']) && $_COOKIE['lsid'] != "" ) {
      // Validate long-term session details
      $this->LSIDLogin( $_COOKIE['lsid'] );
      dbg_error_log( "Login", ":_CheckLogin: User $this->username - $this->fullname ($this->user_no) login status is $this->logged_in" );
    }
    else if ( !isset($_COOKIE['sid']) && isset($c->authenticate_hook['server_auth_type']) && $c->authenticate_hook['server_auth_type'] == $_SERVER['AUTH_TYPE']) {
      /**
      * The authentication has happened in the server, and we should accept it.
      * Perhaps this 'split' is not a good idea though.  People may want to use the
      * full ID as the username.  A further option may be desirable.
      */
      list($username) = explode('@', $_SERVER['REMOTE_USER']);
      $this->Login($username, "", true);  // Password will not be checked.
    }
  }


  /**
  * Function to reformat an ISO date to something nicer and possibly more localised
  * @param string $indate The ISO date to be formatted.
  * @param string $type If 'timestamp' then the time will also be shown.
  * @return string The nicely formatted date.
  */
  function FormattedDate( $indate, $type='date' ) {
    $out = "";
    if ( preg_match( '#^\s*$#', $indate ) ) {
      // Looks like it's empty - just return empty
      return $indate;
    }
    if ( preg_match( '#^\d{1,2}[/-]\d{1,2}[/-]\d{2,4}#', $indate ) ) {
      // Looks like it's nice already - don't screw with it!
      return $indate;
    }
    $yr = substr($indate,0,4);
    $mo = substr($indate,5,2);
    $dy = substr($indate,8,2);
    switch ( $this->date_format_type ) {
      case 'U':
        $out = sprintf( "%d/%d/%d", $mo, $dy, $yr );
        break;
      case 'E':
        $out = sprintf( "%d/%d/%d", $dy, $mo, $yr );
        break;
      default:
        $out = sprintf( "%d-%02d-%02d", $yr, $mo, $dy );
        break;
    }
    if ( $type == 'timestamp' ) {
      $out .= substr($indate,10,6);
    }
    return $out;
  }


  /**
  * Build a hash which we can use for confirmation that we didn't get e-mailed
  * a bogus link by someone, and that we actually got here by traversing the
  * website.
  *
  * @param string $method Either 'GET' or 'POST' depending on the way we will use this.
  * @param string $varname The name of the variable which we will confirm
  * @return string A string we can use as either a GET or POST value (i.e. a hidden field, or a varname=hash pair.
  */
  function BuildConfirmationHash( $method, $varname ) {
    /**
    * We include session_start in this because it is never passed to the client
    * and since it includes microseconds would be very hard to predict.
    */
    $confirmation_hash = session_salted_md5( $this->session_start.$varname.$this->session_key, "" );
    if ( $method == 'GET' ) {
      $confirm = $varname .'='. urlencode($confirmation_hash);
    }
    else {
      $confirm = sprintf( '<input type="hidden" name="%s" value="%s">', $varname, htmlspecialchars($confirmation_hash) );
    }
    return $confirm;
  }


  /**
  * Check a hash which we created through BuildConfirmationHash
  *
  * @param string $method Either 'GET' or 'POST' depending on the way we will use this.
  * @param string $varname The name of the variable which we will confirm
  * @return string A string we can use as either a GET or POST value (i.e. a hidden field, or a varname=hash pair.
  */
  function CheckConfirmationHash( $method, $varname ) {
    if ( $method == 'GET' && isset($_GET[$varname])) {
      $hashwegot = $_GET[$varname];
      dbg_error_log('Session',':CheckConfirmationHash: We got "%s" from GET', $hashwegot );
    }
    else if ( isset($_POST[$varname]) ) {
      $hashwegot = $_POST[$varname];
      dbg_error_log('Session',':CheckConfirmationHash: We got "%s" from POST', $hashwegot );
    }
    else {
      return false;
    }

    if ( preg_match('{^\*(.+)\*.+$}i', $hashwegot, $regs ) ) {
      // A nicely salted md5sum like "*<salt>*<salted_md5>"
      $salt = $regs[1];
      dbg_error_log('Session',':CheckConfirmationHash: Salt "%s"', $salt );
      $test_against = session_salted_md5( $this->session_start.$varname.$this->session_key, $salt ) ;
      dbg_error_log('Session',':CheckConfirmationHash: Testing against "%s"', $test_against );
      
      return ($hashwegot == $test_against);
    }
    return false;
  }

}


/**
* @global resource $session
* @name $session
* The session object is global.
*/

if ( !isset($session) ) {
  Session::_CheckLogout();
  $session = new Session();
  $session->_CheckLogin();
}

