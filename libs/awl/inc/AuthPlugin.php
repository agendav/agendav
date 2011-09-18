<?php
/**
* Authentication handling class
*
* This class provides a basic set of methods which are used by the Session
* class to provide authentication.
*
* This class is expected to be replaced, overridden or extended in some
* instances to enable different pluggable authentication methods.
*
* @package   awl
* @subpackage   AuthPlugin
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

/**
* A class for authenticating and retrieving user information
*
* @package   awl
*/
class AuthPlugin
{
  /**#@+
  * @access private
  */
  var $usr;
  var $success;
  /**#@-*/

  /**#@+
  * @access public
  */

  /**#@-*/

  /**
  * Create a new AuthPlugin object.  This is as lightweight as possible.
  *
  * @param array $authparams An array of parameters used for this authentication method.
  */
  function AuthPlugin( $authparams ) {
    $this->success = false;
  }

  /**
  * Authenticate.  Do whatever we need to authenticate a username / password.
  *
  * @param string $username The username of the person attempting to log in
  * @param string $password The password the person is trying to log in with
  * @return object The "user" object, containing fields matching the 'usr' database table
  */
  function Authenticate( $username, $password ) {
  }

}
/**
* Notes:
*  This could also be done as a process of "registering" functions to perform the authentication,
* so in the Session we call something to (e.g.) do the authentication and that looks in (e.g.)
* $c->authenticate_hook for a function to be called, or it just does the normal thing if
* that is not set.
*  It might be a better way.  I think I need to bounce these two ideas off some other people...
*
* In either case Session will need to split the fetching of session data from the fetching of
* usr data.  So I'll look at doing that first.
*/

