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
* @package   awl
* @subpackage   AuthPlugin
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once('AWLUtilities.php');
require_once('DataUpdate.php');

/**
* Authenticate against a different PostgreSQL database which contains a usr table in
* the AWL format.
*
* @package   awl
*/
function auth_other_awl( $username, $password ) {
  global $c;

  $authconn = pg_Connect($c->authenticate_hook['config']['connection']);
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

  if ( isset($c->authenticate_hook['config']['columns']) )
    $cols = $c->authenticate_hook['config']['columns'];
  else
    $cols = "*";

  if ( isset($c->authenticate_hook['config']['where']) )
    $andwhere = " AND ".$c->authenticate_hook['config']['where'];
  else
    $andwhere = "";

  $qry = new AwlQuery("SELECT $cols FROM usr WHERE lower(username) = ? $andwhere", strtolower($username) );
  $qry->SetConnection($authconn);
  if ( $qry->Exec('Login',__LINE,__FILE__) && $qry->rows() == 1 ) {
    $usr = $qry->Fetch();
    if ( session_validate_password( $password, $usr->password ) ) {

      $qry = new AwlQuery("SELECT * FROM usr WHERE user_no = $usr->user_no;" );
      if ( $qry->Exec('Login',__LINE,__FILE__) && $qry->rows() == 1 )
        $type = "UPDATE";
      else
        $type = "INSERT";

      $qry = new AwlQuery( sql_from_object( $usr, $type, 'usr', "WHERE user_no=$usr->user_no" ) );
      $qry->Exec('Login',__LINE,__FILE__);

      /**
      * We disallow login by inactive users _after_ we have updated the local copy
      */
      if ( isset($usr->active) && $usr->active == 'f' ) return false;

      return $usr;
    }
  }

  return false;

}


/**
* Authentication has already happened.  We know the username, we just need
* to do the authorisation / access control.  The password is ignored.
*
* @package   awl
*/
function auth_external( $username, $password ) {
  global $c;

  $qry = new AwlQuery("SELECT * FROM usr WHERE active AND lower(username) = ? ", strtolower($username) );
  if ( $qry->Exec('Login',__LINE,__FILE__) && $qry->rows() == 1 ) {
    $usr = $qry->Fetch();
    return $usr;
  }

  return false;

}


