<?php
/**
* AwlDatabase query/statement class and associated functions
*
* This subpackage provides some functions that are useful around database
* activity and a AwlDBDialect, AwlDatabase and AwlStatement classes to simplify
* handling of database queries and provide some access for a limited
* ability to handle varying database dialects.
*
* The class is intended to be a very lightweight wrapper with some features
* that have proved useful in developing and debugging web-based applications:
*  - All queries are timed, and an expected time can be provided.
*  - Parameters replaced into the SQL will be escaped correctly in order to
*    minimise the chances of SQL injection errors.
*  - Queries which fail, or which exceed their expected execution time, will
*    be logged for potential further analysis.
*  - Debug logging of queries may be enabled globally, or restricted to
*    particular sets of queries.
*  - Simple syntax for iterating through a result set.
*
* See http://wiki.davical.org/w/AwlDatabase for design and usage information.
*
* If not already connected, AwlDatabase will attempt to connect to the database,
* successively applying connection parameters from the array in $c->pdo_connect.
*
* We will die if the database is not currently connected and we fail to find
* a working connection.
*
* @package   awl
* @subpackage   AwlDatabase
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @compatibility Requires PHP 5.1 or later
*/

require_once('AwlDBDialect.php');

if ( !defined('E_USER_ERROR') ) define('E_USER_ERROR',256);


/**
* Methods in the AwlDBDialect class which we inherit, include:
*  __construct()
*  SetSearchPath( $search_path )
*  GetVersion()
*  GetFields( $tablename_string )
*  TranslateSQL( $sql_string )
*  Quote( $value, $value_type = null )
*  ReplaceParameters( $query_string [, param [, ...]] )
*/


/**
* Typically there will only be a single instance of the database level class in an application.
* @package awl
*/
class AwlDatabase extends AwlDBDialect {
  /**#@+
  * @access private
  */

  /**
  * Holds the state of the transaction 0 = not started, 1 = in progress, -1 = error pending rollback/commit
  */
  protected $txnstate = 0;

  /**
  * Holds whether we are translating all statements.
  */
  protected $translate_all = false;

  /**#@-*/

  /**
  * Returns a PDOStatement object created using this database, the supplied SQL string, and any parameters given.
  * @param string $sql_query_string The SQL string containing optional variable replacements
  * @param array $driver_options PDO driver options to the prepare statement, commonly to do with cursors
  */
  function prepare( $statement, $driver_options = array() ) {
    if ( isset($this->translate_all) && $this->translate_all ) {
      $statement = $this->TranslateSQL( $statement );
    }
    return $this->db->prepare( $statement, $driver_options );
  }


  /**
  * Returns a PDOStatement object created using this database, the supplied SQL string, and any parameters given.
  * @param string $sql_query_string The SQL string containing optional variable replacements
  * @param mixed ... Subsequent arguments are positionally replaced into the $sql_query_string
  */
  function query( $statement ) {
    return $this->db->query( $statement );
  }


  /**
  * Begin a transaction.
  */
  function Begin() {
    if ( $this->txnstate == 0 ) {
      $this->db->beginTransaction();
      $this->txnstate = 1;
    }
    else {
      trigger_error("Cannot begin a transaction while a transaction is already active.",E_USER_ERROR);
    }
    return true;
  }


  /**
  * Complete a transaction.
  */
  function Commit() {
    if ( $this->txnstate != 0 ) {
      $this->db->commit();
      $this->txnstate = 0;
    }
    return true;
  }


  /**
  * Cancel a transaction in progress.
  */
  function Rollback() {
    if ( $this->txnstate != 0 ) {
      $this->db->rollBack();
      $this->txnstate = 0;
    }
    else {
      trigger_error("Cannot rollback unless a transaction is already active.",E_USER_ERROR);
    }
    return true;
  }


  /**
  * Returns the current state of a transaction, indicating if we have begun a transaction, whether the transaction
  * has failed, or if we are not in a transaction.
  */
  function TransactionState() {
    return $this->txnstate;
  }


  /**
  * Operates identically to AwlDatabase::Prepare, except that $this->Translate() will be called on the query
  * before any processing.
  */
  function PrepareTranslated( $statement, $driver_options = array() ) {
    $statement = $this->TranslateSQL( $statement );
    return $this->prepare( $statement, $driver_options );
  }


  /**
  * Switches on or off the processing flag controlling whether subsequent calls to AwlDatabase::Prepare are translated
  * as if PrepareTranslated() had been called.
  */
  function TranslateAll( $onoff_boolean ) {
    $this->translate_all = $onoff_boolean;
    return $onoff_boolean;
  }


  /**
  *
  */
  function ErrorInfo() {
    return $this->db->errorInfo();
  }

}


