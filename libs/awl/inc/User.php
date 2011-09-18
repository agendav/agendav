<?php
/**
* A class to handle reading, writing, viewing, editing and validating
* usr records.
*
* @package   awl
* @author Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
require_once("AWLUtilities.php");

/**
* We need to access some session information.
*/
require_once("Session.php");

/**
* We use the DataEntry class for data display and updating
*/
require_once("DataEntry.php");

/**
* We use the DataUpdate class and inherit from DBRecord
*/
require_once("DataUpdate.php");

/**
* A class to handle reading, writing, viewing, editing and validating
* usr records.
* @package   awl
* @subpackage   User
*/
class User extends DBRecord {
  /**#@+
  * @access private
  */
  /**
  * A unique user number that is auto assigned on creation and invariant thereafter
  * @var string
  */
  var $user_no;

  /**
  * Something to prefix all field names with before rendering them.
  * @var string
  */
  var $prefix;

  /**#@-*/

  /**
  * The constructor initialises a new record, potentially reading it from the database.
  * @param int $id The user_no, or 0 if we are creating a new one
  * @param string $prefix The prefix for entry fields
  */
  function User( $id , $prefix = "") {
    global $session;

    // Call the parent constructor
    $this->DBRecord();

    $this->prefix = $prefix;

    $this->user_no = 0;
    $keys = array();

    $id = intval("$id");
    if ( $id > 0 ) {
      // Initialise
      $keys['user_no'] = $id;
      $this->user_no = $id;
    }

    // Initialise the record, possibly from the file.
    $this->Initialise('usr',$keys);
    $this->Read();
    $this->GetRoles();

    $this->EditMode = ( (isset($_GET['edit']) && $_GET['edit'] && $this->AllowedTo($this->WriteType))
                    || (0 == $this->user_no && $this->AllowedTo("insert") ) );

    if ( $this->user_no == 0 ) {
      dbg_error_log("User", "Initialising new user values");

      // Initialise to standard default values
      $this->active = true;

    }
  }


  /**
  * Can the user do this?
  * @param string $whatever What the user wants to do
  * @return boolean Whether they are allowed to.
  */
  function AllowedTo ( $whatever )
  {
    global $session;

    $rc = false;

    /**
    * First we globally short-circuit the 'admin can do anything'
    */
    if ( $session->AllowedTo("Admin") ) {
      $rc = true;
      dbg_error_log("User",":AllowedTo: Admin is always allowed to %s", $whatever );
      return $rc;
    }

    switch( strtolower($whatever) ) {

      case 'view':
        $rc = ( $this->user_no > 0 && $session->user_no == $this->user_no );
        break;

      case 'update':
        $rc = ( $this->user_no > 0 && $session->user_no == $this->user_no );
        break;

      case 'changepassword':
        $rc = ( ($this->user_no > 0 && $session->user_no == $this->user_no)
                || ("insert" == $this->WriteType) );
        break;

      case 'changeusername':  // Administrator only
      case 'changeactive':    // Administrator only
      case 'admin':

      case 'create':

      case 'insert':
        $rc = false;
        break;

      default:
        $rc = ( isset($session->roles[$whatever]) && $session->roles[$whatever] );
    }
    dbg_error_log("User",":AllowedTo: %s is%s allowed to %s", (isset($this->username)?$this->username:null), ($rc?"":" not"), $whatever );
    return $rc;
  }


  /**
  * Get the group memberships for the user
  */
  function GetRoles () {
    $this->roles = array();
    $qry = new AwlQuery( 'SELECT role_name FROM role_member JOIN roles USING (role_no) WHERE user_no = ? ', $this->user_no );
    if ( $qry->Exec("User") && $qry->rows() > 0 ) {
      while( $role = $qry->Fetch() ) {
        $this->roles[$role->role_name] = 't';
      }
    }
  }


  /**
  * Render the form / viewer as HTML to show the user
  * @return string An HTML fragment to display in the page.
  */
  function Render( ) {
    $html = "";
    dbg_error_log("User", ":Render: type=$this->WriteType, edit_mode=$this->EditMode" );

    $ef = new EntryForm( $REQUEST_URI, $this->Values, $this->EditMode );
    $ef->NoHelp();  // Prefer this style, for the moment

    if ( $ef->EditMode ) {
      $html .= $ef->StartForm( array("autocomplete" => "off" ) );
      if ( $this->user_no > 0 ) $html .= $ef->HiddenField( "user_no", $this->user_no );
    }

    $html .= "<table width=\"100%\" class=\"data\" cellspacing=\"0\" cellpadding=\"0\">\n";

    $html .= $this->RenderFields($ef);
    $html .= $this->RenderRoles($ef);

    $html .= "</table>\n";
    if ( $ef->EditMode ) {
      $html .= '<div id="footer">';
      $html .= $ef->SubmitButton( "submit", (("insert" == $this->WriteType) ? translate("Create") : translate("Update")) );
      $html .= '</div>';
      $html .= $ef->EndForm();
    }

    return $html;
  }

  /**
  * Render the core details to show to the user
  * @param object $ef The entry form.
  * @param string $title The title to display above the entry fields.
  * @return string An HTML fragment to display in the page.
  */
  function RenderFields($ef , $title = null ) {
    global $session, $c;

    if ( $title == null ) $title = i18n("User Details");
    $html = ( $title == "" ? "" : $ef->BreakLine(translate($title)) );

    if ( $this->AllowedTo('ChangeUsername') ) {
      $html .= $ef->DataEntryLine( translate("User Name"), "%s", "text", "username",
              array( "size" => 20, "title" => translate("The name this user can log into the system with.")), $this->prefix );
    }
    else {
      $html .= $ef->DataEntryLine( translate("User Name"), $this->Get('username') );
    }
    if ( $ef->EditMode && $this->AllowedTo('ChangePassword') ) {
      $this->Set('new_password','******');
      unset($_POST['new_password']);
      $html .= $ef->DataEntryLine( translate("New Password"), "%s", "password", "new_password",
                array( "size" => 20, "title" => translate("The user's password for logging in.")), $this->prefix );
      $this->Set('confirm_password', '******');
      unset($_POST['confirm_password']);
      $html .= $ef->DataEntryLine( translate("Confirm"), "%s", "password", "confirm_password",
                array( "size" => 20, "title" => translate("Confirm the new password.")), $this->prefix );
    }

    $html .= $ef->DataEntryLine( translate("Full Name"), "%s", "text", "fullname",
              array( "size" => 50, "title" => translate("The user's full name.")), $this->prefix );

    $html .= $ef->DataEntryLine( translate("EMail"), "%s", "text", "email",
              array( "size" => 50, "title" => translate("The user's e-mail address.")), $this->prefix );

    if ( $this->AllowedTo('ChangeActive') ) {
      $html .= $ef->DataEntryLine( translate("Active"), ($this->Get('active') == 't'? translate('Yes') : translate('No')), "checkbox", "active",
                array( "_label" => translate("User is active"),
                      "title" => translate("Is this user active?")), $this->prefix );
    }
    else {
      $html .= $ef->DataEntryLine( translate("Active"), ($this->Get('active') == 't'? translate('Yes') : translate('No')) );
    }

    $html .= $ef->DataEntryLine( translate("Date Style"), ($this->Get('date_format_type') == 'E' ? 'European' : ($this->Get('date_format_type') == 'U' ? 'US of A' : 'ISO 8861')),
                     "select", "date_format_type",
                     array( "title" => translate("The style of dates used for this person."),
                       "_E" => translate("European (d/m/y)"), "_U" => translate("United States of America (m/d/y)"), "_I" => translate("ISO Format (YYYY-MM-DD)") ),
                     $this->prefix );

    if ( isset($c->default_locale) ) {
      if ( $this->Get('locale') == '' ) {
        $this->Set('locale',$c->default_locale);
      }
      $html .= $ef->DataEntryLine( translate("Language"), "%s", "lookup", "locale",
                      array( "title" => translate("The preferred language for this person."),
                        "_sql" => "SELECT locale, locale_name_locale FROM supported_locales ORDER BY locale ASC;" ),
                      $this->prefix );
    }

    $html .= $ef->DataEntryLine( translate("EMail OK"), $session->FormattedDate($this->Get('email_ok'),'timestamp'), "timestamp", "email_ok",
              array( "title" => translate("When the user's e-mail account was validated.")), $this->prefix );

    $html .= $ef->DataEntryLine( translate("Joined"), $session->FormattedDate($this->Get('joined'),'timestamp') );
    $html .= $ef->DataEntryLine( translate("Updated"), $session->FormattedDate($this->Get('updated'),'timestamp') );
    $html .= $ef->DataEntryLine( translate("Last used"), $session->FormattedDate($this->Get('last_used'),'timestamp') );

    return $html;
  }


  /**
  * Render the user's administrative roles
  *
  * @return string The string of html to be output
  */
  function RenderRoles( $ef, $title = null ) {
    global $session;
    $html = "";

    if ( $title == null ) $title = i18n("User Roles");
    $html = ( $title == "" ? "" : $ef->BreakLine(translate($title)) );

    $html .= '<tr><th class="prompt">'.translate("User Roles").'</th><td class="entry">';
    if ( $ef->EditMode ) {
      $sql = "SELECT role_name FROM roles ";
      if ( ! ($session->AllowedTo('Admin') ) ) {
        $sql .= "NATURAL JOIN role_member WHERE user_no=$session->user_no ";
      }
      $sql .= "ORDER BY roles.role_no";

      $ef->record->roles = array();

      // Select the records
      $q = new AwlQuery($sql);
      if ( $q && $q->Exec("User") && $q->rows() ) {
        $i=0;
        while( $row = $q->Fetch() ) {
          @dbg_error_log("User", ":RenderRoles: Is a member of '%s': %s", $row->role_name, $this->roles[$row->role_name] );
          $ef->record->roles[$row->role_name] = ( isset($this->roles[$row->role_name]) ? $this->roles[$row->role_name] : 'f');
          $html .= $ef->DataEntryField( "", "checkbox", "roles[$row->role_name]",
                          array("title" => translate("Does the user have the right to perform this role?"),
                                    "_label" => translate($row->role_name) ) );
        }
      }
    }
    else {
      $i = 0;
      foreach( $this->roles AS $k => $v ) {
        if ( $i++ > 0 ) $html .= ", ";
        $html .= $k;
      }
    }
    $html .= '</td></tr>'."\n";

    return $html;
  }

  /**
  * Validate the information the user submitted
  * @return boolean Whether the form data validated OK.
  */
  function Validate( ) {
    global $session, $c;
    dbg_error_log("User", ":Validate: Validating user");

    $valid = true;

    if ( $this->Get('fullname') == "" ) {
      $c->messages[] = i18n('ERROR: The full name may not be blank.');
      $valid = false;
    }

    // Password changing is a little special...
    unset($_POST['password']);
    if ( $_POST['new_password'] != "******" && $_POST['new_password'] != ""  ) {
      if ( $_POST['new_password'] == $_POST['confirm_password'] ) {
        $this->Set('password',$_POST['new_password']);
      }
      else {
        $c->messages[] = i18n('ERROR: The new password must match the confirmed password.');
        $valid = false;
      }
    }
    else {
      $this->Undefine('password');
    }

    dbg_error_log("User", ":Validate: User %s validation", ($valid ? "passed" : "failed"));
    return $valid;
  }

  /**
  * Write the User record.
  * @return Success.
  */
  function Write() {
    global $c, $session;
    if ( parent::Write() ) {
      $c->messages[] = i18n('User record written.');
      if ( $this->WriteType == 'insert' ) {
        $qry = new AwlQuery( "SELECT currval('usr_user_no_seq');" );
        $qry->Exec("User::Write");
        $sequence_value = $qry->Fetch(true);  // Fetch as an array
        $this->user_no = $sequence_value[0];
      }
      else {
        if ( $this->user_no == $session->user_no && $this->Get("date_format_type") != $session->date_format_type ) {
          // Ensure we match the date style setting
          $session->date_format_type = $this->Get("date_format_type");
          unset($_POST['email_ok']);
          $qry = new AwlQuery( "SET DATESTYLE TO ?;", ($this->Get("date_format_type") == 'E' ? 'European,ISO' : ($this->Get("date_format_type") == 'U' ? 'US,ISO' : 'ISO')) );
          $qry->Exec();
        }
      }
      return $this->WriteRoles();
    }
    return false;
  }

  /**
  * Write the roles associated with the user
  * @return Success.
  */
  function WriteRoles() {
    global $c, $session;

    if ( isset($_POST['roles']) && is_array($_POST['roles']) ) {
      $roles = "";
      $params = array();
      foreach( $_POST['roles'] AS $k => $v ) {
        if ( $v && $v != "off" ) {
          $roles .= ( $roles == '' ? '' : ', ' );
          $roles .= AwlQuery::quote($k);
        }
      }
      $qry = new AwlQuery();
      if ( $roles == '' )
        $succeeded = $qry->QDo('DELETE FROM role_member WHERE user_no = '.$this->user_no);
      else {
        $succeeded = $qry->Begin();
        $sql = 'DELETE FROM role_member WHERE user_no = '.$this->user_no;
        $sql .= ' AND role_no NOT IN (SELECT role_no FROM roles WHERE role_name IN ('.$roles.') )';
        if ( $succeeded ) $succeeded = $qry->QDo($sql);
        $sql = 'INSERT INTO role_member (role_no, user_no)';
        $sql .= ' SELECT role_no, '.$this->user_no.' FROM roles WHERE role_name IN ('.$roles.')';
        $sql .= ' EXCEPT SELECT role_no, user_no FROM role_member';
        if ( $succeeded ) $succeeded = $qry->QDo($sql);
        if ( $succeeded )
          $qry->Commit();
        else
          $qry->Rollback();
      }
      if ( ! $succeeded ) {
        $c->messages[] = i18n('ERROR: There was a database error writing the roles information!');
        $c->messages[] = i18n('Please note the time and advise the administrator of your system.');
        return false;
      }
    }
    return true;
  }
}
