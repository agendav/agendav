<?php
/**
* A Class for faking sessions which are anonymous access to a resource
*
* @package davical
* @subpackage PublicSession
* @author Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

/**
* A Class for handling a public (anonymous) session
*
* @package davical
*/
class PublicSession {
  /**#@+
  * @access private
  */

  /**
  * User ID number
  * @var user_no int
  */
  var $user_no;

  /**
  * Principal ID
  * @var principal_id int
  */
  var $principal_id;

  /**
  * User e-mail
  * @var email string
  */
  var $email;

  /**
  * User full name
  * @var fullname string
  */
  var $fullname;

  /**
  * Group rights
  * @var groups array
  */
  var $groups;
  /**#@-*/

  /**
  * The constructor, which just calls the actual type configured
  */
  function PublicSession() {
    global $c;

    $this->user_no = -1;
    $this->principal_id = -1;
    $this->email = null;
    $this->username = 'guest';
    $this->fullname = 'Anonymous';
    $this->groups = ( isset($c->public_groups) ? $c->public_groups : array() );
    $this->roles = array( 'Public' => true );
    $this->logged_in = false;
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
    dbg_error_log('session', 'Checking whether "Public" is allowed to "%s"', $whatever);
    return ( isset($this->roles[$whatever]) && $this->roles[$whatever] );
  }

}

