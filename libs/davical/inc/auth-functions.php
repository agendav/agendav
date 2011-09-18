<?php
/**
* The authentication handling plugins can be used by the Session class to
* provide authentication.
*
* Each authenticate hook needs to:
*   - Accept a username / password
*   - Confirm the username / password are correct
*   - Create (or update) a 'usr' record in our database
*   - Return the 'usr' record as an object
*   - Return === false when authentication fails
*
* It can expect that:
*   - Configuration data will be in $c->authenticate_hook['config'], which might be an array, or whatever is needed.
*
* In order to be called:
*   - This file should be included
*   - $c->authenticate_hook['call'] should be set to the name of the plugin
*   - $c->authenticate_hook['config'] should be set up with any configuration data for the plugin
*
* @package   davical
* @subpackage   authentication
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("DataUpdate.php");


/**
* Create a default home calendar for the user.
* @param string $username The username of the user we are creating relationships for.
*/
function CreateHomeCalendar( $username ) {
  global $session, $c;
  if ( ! isset($c->home_calendar_name) || strlen($c->home_calendar_name) == 0 ) return true;

  $usr = getUserByName( $username );
  $parent_path = "/".$username."/";
  $calendar_path = $parent_path . $c->home_calendar_name."/";
  $dav_etag = md5($usr->user_no . $calendar_path);
  $qry = new AwlQuery( 'SELECT 1 FROM collection WHERE dav_name = :dav_name', array( ':dav_name' => $calendar_path) );
  if ( $qry->Exec() ) {
    if ( $qry->rows() > 0 ) {
      $c->messages[] = i18n("Home calendar already exists.");
      return true;
    }
  }
  else {
    $c->messages[] = i18n("There was an error writing to the database.");
    return false;
  }

  $sql = 'INSERT INTO collection (user_no, parent_container, dav_name, dav_etag, dav_displayname, is_calendar, created, modified, resourcetypes) ';
  $sql .= 'VALUES( :user_no, :parent_container, :calendar_path, :dav_etag, :displayname, true, current_timestamp, current_timestamp, :resourcetypes );';
  $params = array(
      ':user_no' => $usr->user_no,
      ':parent_container' => $parent_path,
      ':calendar_path' => $calendar_path,
      ':dav_etag' => $dav_etag,
      ':displayname' => $usr->fullname,
      ':resourcetypes' => '<DAV::collection/><urn:ietf:params:xml:ns:caldav:calendar/>'
  );
  $qry = new AwlQuery( $sql, $params );
  if ( $qry->Exec() ) {
    $c->messages[] = i18n("Home calendar added.");
    dbg_error_log("User",":Write: Created user's home calendar at '%s'", $calendar_path );
  }
  else {
    $c->messages[] = i18n("There was an error writing to the database.");
    return false;
  }
  return true;
}


/**
* Defunct function for creating default relationships.
* @param string $username The username of the user we are creating relationships for.
*/
function CreateDefaultRelationships( $username ) {
  return true;
}


/**
* Update the local cache of the remote user details
* @param object $usr The user details we read from the remote.
*/
function UpdateUserFromExternal( &$usr ) {
  global $c;
  /**
  * When we're doing the create we will usually need to generate a user number
  */
  if ( !isset($usr->user_no) || intval($usr->user_no) == 0 ) {
    $qry = new AwlQuery( "SELECT nextval('usr_user_no_seq');" );
    $qry->Exec('Login',__LINE__,__FILE__);
    $sequence_value = $qry->Fetch(true);  // Fetch as an array
    $usr->user_no = $sequence_value[0];
  }

  $qry = new AwlQuery('SELECT * FROM usr WHERE user_no = :user_no', array(':user_no' => $usr->user_no) );
  if ( $qry->Exec('Login',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $type = "UPDATE";
    if ( $old = $qry->Fetch() ) {
      $changes = false;
      foreach( $usr AS $k => $v ) {
        if ( $old->{$k} != $v ) {
          $changes = true;
          dbg_error_log("Login","User '%s' field '%s' changed from '%s' to '%s'", $usr->username, $k, $old->{$k}, $v );
          break;
        }
      }
      if ( !$changes ) {
        dbg_error_log("Login","No changes to user record for '%s' - leaving as-is.", $usr->username );
        if ( isset($usr->active) && $usr->active == 'f' ) return false;
        return; // Normal case, if there are no changes
      }
      else {
        dbg_error_log("Login","Changes to user record for '%s' - updating.", $usr->username );
      }
    }
  }
  else
    $type = "INSERT";
    
  $params = array();
  if ( $type != 'INSERT' ) $params[':user_no'] = $usr->user_no;
  $qry = new AwlQuery( sql_from_object( $usr, $type, 'usr', 'WHERE user_no= :user_no' ), $params );
  $qry->Exec('Login',__LINE__,__FILE__);

  /**
  * We disallow login by inactive users _after_ we have updated the local copy
  */
  if ( isset($usr->active) && ($usr->active === 'f' || $usr->active === false) ) return false;

  if ( $type == 'INSERT' ) {
    $qry = new AwlQuery( 'INSERT INTO principal( type_id, user_no, displayname, default_privileges) SELECT 1, user_no, fullname, :privs::INT::BIT(24) FROM usr WHERE username=:username',
                          array( ':privs' => privilege_to_bits($c->default_privileges), ':username' => $usr->username) );
    $qry->Exec('Login',__LINE__,__FILE__);
    CreateHomeCalendar($usr->username);
  }
  else if ( $usr->fullname != $old->{'fullname'} ) {
    // Also update the displayname if the fullname has been updated.
    $qry->QDo( 'UPDATE principal SET displayname=:new_display WHERE user_no=:user_no',
                    array(':new_display' => $usr->fullname, ':user_no' => $usr->user_no)
             );
  }
}


/**
* Authenticate against a different PostgreSQL database which contains a usr table in
* the AWL format.
*
* Use this as in the following example config snippet:
*
* require_once('auth-functions.php');
*  $c->authenticate_hook = array(
*      'call'   => 'AuthExternalAwl',
*      'config' => array(
*           // A PgSQL database connection string for the database containing user records
*          'connection[]' => 'dbname=wrms host=otherhost port=5433 user=general',
*           // Which columns should be fetched from the database
*          'columns'    => "user_no, active, email_ok, joined, last_update AS updated, last_used, username, password, fullname, email",
*           // a WHERE clause to limit the records returned.
*          'where'    => "active AND org_code=7"
*      )
*  );
*
*/
function AuthExternalAWL( $username, $password ) {
  global $c;

  $persistent = isset($c->authenticate_hook['config']['use_persistent']) && $c->authenticate_hook['config']['use_persistent'];

  if ( isset($c->authenticate_hook['config']['columns']) )
    $cols = $c->authenticate_hook['config']['columns'];
  else
    $cols = '*';

  if ( isset($c->authenticate_hook['config']['where']) )
    $andwhere = ' AND '.$c->authenticate_hook['config']['where'];
  else
    $andwhere = '';

  $qry = new AwlQuery('SELECT '.$cols.' FROM usr WHERE lower(username) = :username '. $andwhere, array( ':username' => strtolower($username) ));
  $authconn = $qry->SetConnection($c->authenticate_hook['config']['connection'], ($persistent ? array(PDO::ATTR_PERSISTENT => true) : null));
  if ( ! $authconn ) {
    echo <<<EOERRMSG
  <html><head><title>Database Connection Failure</title></head><body>
  <h1>Database Error</h1>
  <h3>Could not connect to PostgreSQL database</h3>
  </body>
  </html>
EOERRMSG;
    exit(1);
  }

  if ( $qry->Exec('Login',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $usr = $qry->Fetch();
    if ( session_validate_password( $password, $usr->password ) ) {
      UpdateUserFromExternal($usr);

      /**
      * We disallow login by inactive users _after_ we have updated the local copy
      */
      if ( isset($usr->active) && $usr->active == 'f' ) return false;

      $qry = new AwlQuery('SELECT * FROM dav_principal WHERE username = :username', array(':username' => $usr->username) );
      if ( $qry->Exec() && $qry->rows() == 1 ) {
        $principal = $qry->Fetch();
        return $principal;
      }
      return $usr; // Somewhat optimistically
    }
  }

  return false;

}
