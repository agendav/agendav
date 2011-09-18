<?php
/**
* @package   awl
* @subpackage   AwlDatabase
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @compatibility Requires PHP 5.1 or later
*/

require_once('AwlDatabase.php');

/**
* Database query class and associated functions
*
* This subpackage provides some functions that are useful around database
* activity and an AwlQuery class to simplify handling of database queries.
*
* The class is intended to be a very lightweight wrapper with no pretentions
* towards database independence, but it does include some features that have
* proved useful in developing and debugging web-based applications:
*  - All queries are timed, and an expected time can be provided.
*  - Parameters replaced into the SQL will be escaped correctly in order to
*    minimise the chances of SQL injection errors.
*  - Queries which fail, or which exceed their expected execution time, will
*    be logged for potential further analysis.
*  - Debug logging of queries may be enabled globally, or restricted to
*    particular sets of queries.
*  - Simple syntax for iterating through a result set.
*
* This class is intended as a transitional mechanism for moving from the
* PostgreSQL-specific Pg Query class to something which uses PDO in a more
* replaceable manner.
*
*/

/**
* Connect to the database defined in the $c->db_connect[] (or $c->pg_connect) arrays
*/
function _awl_connect_configured_database() {
  global $c, $_awl_dbconn;

  /**
  * Attempt to connect to the configured connect strings
  */
  $_awl_dbconn = false;

  if ( isset($c->db_connect) ) {
    $connection_strings = $c->db_connect;
  }
  elseif ( isset($c->pg_connect) ) {
    $connection_strings = $c->pg_connect;
  }

  foreach( $connection_strings AS $k => $v ) {
    $dbuser = null;
    $dbpass = null;
    if ( is_array($v) ) {
      $dsn = $v['dsn'];
      if ( isset($v['dbuser']) ) $dbuser = $v['dbuser'];
      if ( isset($v['dbpass']) ) $dbpass = $v['dbpass'];
    }
    elseif ( preg_match( '/^(\S+:)?(.*)( user=(\S+))?( password=(\S+))?$/', $v, $matches ) ) {
      $dsn = $matches[2];
      if ( isset($matches[1]) && $matches[1] != '' ) {
        $dsn = $matches[1] . $dsn;
      }
      else {
        $dsn = 'pgsql:' . $dsn;
      }
      if ( isset($matches[4]) && $matches[4] != '' ) $dbuser = $matches[4];
      if ( isset($matches[6]) && $matches[6] != '' ) $dbpass = $matches[6];
    }
    if ( $_awl_dbconn = new AwlDatabase( $dsn, $dbuser, $dbpass, (isset($c->use_persistent) && $c->use_persistent ? array(PDO::ATTR_PERSISTENT => true) : null) ) ) break;
  }

  if ( ! $_awl_dbconn ) {
    echo <<<EOERRMSG
  <html><head><title>Database Connection Failure</title></head><body>
  <h1>Database Error</h1>
  <h3>Could not connect to database</h3>
  </body>
  </html>
EOERRMSG;
    exit;
  }

  if ( isset($c->db_schema) && $c->db_schema != '' ) {
    $_awl_dbconn->SetSearchPath( $c->db_schema . ',public' );
  }

  $c->_awl_dbversion = $_awl_dbconn->GetVersion();
}


/**
* The AwlQuery Class.
*
* This class builds and executes SQL Queries and traverses the
* set of results returned from the query.
*
* <b>Example usage</b>
* <code>
* $sql = "SELECT * FROM mytable WHERE mytype = ?";
* $qry = new AwlQuery( $sql, $myunsanitisedtype );
* if ( $qry->Exec("typeselect", __line__, __file__ )
*      && $qry->rows > 0 )
* {
*   while( $row = $qry->Fetch() ) {
*     do_something_with($row);
*   }
* }
* </code>
*
* @package   awl
*/
class AwlQuery
{
  /**#@+
  * @access private
  */
  /**
  * Our database connection, normally copied from a global one
  * @var resource
  */
  protected $connection;

  /**
  * The original query string
  * @var string
  */
  protected $querystring;

  /**
  * The actual query string, after we've replaced parameters in it
  * @var string
  */
  protected $bound_querystring;

  /**
  * The current array of bound parameters
  * @var array
  */
  protected $bound_parameters;

  /**
  * The PDO statement handle, or null if we don't have one yet.
  * @var string
  */
  protected $sth;

  /**
  * Result of the last execution
  * @var resource
  */
  protected $result;

  /**
  * number of current row - use accessor to get/set
  * @var int
  */
  protected $rownum = null;

  /**
  * number of rows from pg_numrows - use accessor to get value
  * @var int
  */
  protected $rows;

  /**
  * The Database error information, if the query fails.
  * @var string
  */
  protected $error_info;

  /**
  * Stores the query execution time - used to deal with long queries.
  * should be read-only
  * @var string
  */
  protected $execution_time;

  /**#@-*/

  /**#@+
  * @access public
  */
  /**
  * Where we called this query from so we can find it in our code!
  * Debugging may also be selectively enabled for a $location.
  * @var string
  */
  public $location;

  /**
  * How long the query should take before a warning is issued.
  *
  * This is writable, but a method to set it might be a better interface.
  * The default is 0.3 seconds.
  * @var double
  */
  public $query_time_warning = 0.3;
  /**#@-*/


 /**
  * Constructor
  * @param  string The query string in PDO syntax with replacable '?' characters or bindable parameters.
  * @param mixed The values to replace into the SQL string.
  * @return The AwlQuery object
  */
  function __construct() {
    global $_awl_dbconn;
    $this->rows = null;
    $this->execution_time = 0;
    $this->error_info = null;
    $this->rownum = -1;
    if ( isset($_awl_dbconn) ) $this->connection = $_awl_dbconn;
    else                       $this->connection = null;

    $argc = func_num_args();
    $args = func_get_args();

    $this->querystring = array_shift($args);
    if ( 1 < $argc ) {
      if ( is_array($args[0]) )
        $this->bound_parameters = $args[0];
      else
        $this->bound_parameters = $args;
//      print_r( $this->bound_parameters );
    }

    return $this;
  }


 /**
  * Use a different database connection for this query
  * @param  resource $new_connection The database connection to use.
  */
  function SetConnection( $new_connection, $options = null ) {
    if ( is_string($new_connection) || is_array($new_connection) ) {
      $dbuser = null;
      $dbpass = null;
      if ( is_array($new_connection) ) {
        $dsn = $new_connection['dsn'];
        if ( isset($new_connection['dbuser']) ) $dbuser = $new_connection['dbuser'];
        if ( isset($new_connection['dbpass']) ) $dbpass = $new_connection['dbpass'];
      }
      elseif ( preg_match( '/^(\S+:)?(.*)( user=(\S+))?( password=(\S+))?$/', $new_connection, $matches ) ) {
        $dsn = $matches[2];
        if ( isset($matches[1]) && $matches[1] != '' ) {
          $dsn = $matches[1] . $dsn;
        }
        else {
          $dsn = 'pgsql:' . $dsn;
        }
        if ( isset($matches[4]) && $matches[4] != '' ) $dbuser = $matches[4];
        if ( isset($matches[6]) && $matches[6] != '' ) $dbpass = $matches[6];
      }
      if ( $new_connection = new AwlDatabase( $dsn, $dbuser, $dbpass, $options ) ) break;
    }
    $this->connection = $new_connection;
    return $new_connection;
  }



 /**
  * Get the current database connection for this query
  */
  function GetConnection() {
    return $this->connection;
  }

  
  /**
  * Log query, optionally with file and line location of the caller.
  *
  * This function should not really be used outside of AwlQuery.  For a more
  * useful generic logging interface consider calling dbg_error_log(...);
  *
  * @param string $locn    A string identifying the calling location.
  * @param string $tag     A tag string, e.g. identifying the type of event.
  * @param string $string  The information to be logged.
  * @param int    $line    The line number where the logged event occurred.
  * @param string $file    The file name where the logged event occurred.
  */
  function _log_query( $locn, $tag, $string, $line = 0, $file = "") {
    // replace more than one space with one space
    $string = preg_replace('/\s+/', ' ', $string);

    if ( ($tag == 'QF' || $tag == 'SQ') && ( $line != 0 && $file != "" ) ) {
      dbg_error_log( "LOG-$locn", " Query: %s: %s in '%s' on line %d", ($tag == 'QF' ? 'Error' : 'Possible slow query'), $tag, $file, $line );
    }

    while( strlen( $string ) > 0 )  {
      dbg_error_log( "LOG-$locn", " Query: %s: %s", $tag, substr( $string, 0, 240) );
      $string = substr( "$string", 240 );
    }
  }


  /**
  * Quote the given string so it can be safely used within string delimiters
  * in a query.  To be avoided, in general.
  *
  * @param mixed $str Data to be converted to a string suitable for including as a value in SQL.
  * @return string NULL, TRUE, FALSE, a plain number, or the original string quoted and with ' and \ characters escaped
  */
  public static function quote($str = null) {
    global $_awl_dbconn;
    if ( !isset($_awl_dbconn) ) {
      _awl_connect_configured_database();
    }
    return $_awl_dbconn->Quote($str);
  }


  /**
  * Bind some parameters.  This can be called in three ways:
  * 1) As Bind(':key','value), when using named parameters
  * 2) As Bind('value'), when using ? placeholders
  * 3) As Bind(array()), to overwrite the existing bound parameters.  The array may
  *    be ':name' => 'value' pairs or ordinal values, depending on whether the SQL
  *    is using ':name' or '?' style placeholders.
  *
  * @param mixed $args See details above.
  */
  function Bind() {
    $argc = func_num_args();
    $args = func_get_args();

    if ( $argc == 1 ) {
      if ( gettype($args[0]) == 'array' ) {
        $this->bound_parameters = $args[0];
      }
      else {
        $this->bound_parameters[] = $args[0];
      }
    }
    else {
      $this->bound_parameters[$args[0]] = $args[1];
    }
  }


  /**
  * Tell the database to prepare the query that we will execute
  */
  function Prepare() {
    global $c;

    if ( isset($this->sth) ) return; // Already prepared
    if ( isset($c->expand_pdo_parameters) && $c->expand_pdo_parameters ) return; //  No-op if we're expanding internally

    if ( !isset($this->connection) ) {
      _awl_connect_configured_database();
      $this->connection = $GLOBALS['_awl_dbconn'];
    }

    $this->sth = $this->connection->prepare( $this->querystring );

    if ( ! $this->sth ) {
      $this->error_info = $this->connection->errorInfo();
    }
    else $this->error_info = null;
  }


  /**
  * Tell the database to execute the query
  */
  function Execute() {
    global $c;

    if ( !isset($this->connection) ) {
      _awl_connect_configured_database();
      $this->connection = $GLOBALS['_awl_dbconn'];
    }

    if ( isset($c->expand_pdo_parameters) && $c->expand_pdo_parameters ) {
      $this->bound_querystring = $this->querystring;
      if ( isset($this->bound_parameters) ) {
        $this->bound_querystring = $this->connection->ReplaceParameters($this->querystring,$this->bound_parameters);
//        printf( "\n=============================================================== OQ\n%s\n", $this->querystring);
//        printf( "\n=============================================================== QQ\n%s\n", $this->bound_querystring);
//        print_r( $this->bound_parameters );
      }
      $t1 = microtime(true); // get start time
      $this->sth = $this->connection->query($this->bound_querystring);
    }
    else {
      $t1 = microtime(true); // get start time
      $this->sth = $this->connection->prepare($this->querystring);
      if ( $this->sth ) $this->sth->execute($this->bound_parameters);
//      printf( "\n=============================================================== OQ\n%s\n", $this->querystring);
//      print_r( $this->bound_parameters );
    }
    $this->bound_querystring = null;

    if ( ! $this->sth ) {
      $this->error_info = $this->connection->errorInfo();
      return false;
    }
    $this->rows = $this->sth->rowCount();

    $i_took = microtime(true) - $t1;
    $c->total_query_time += $i_took;
    $this->execution_time = sprintf( "%2.06lf", $i_took);

    $this->error_info = null;
    return true;
  }


  /**
  * Return the query string we are planning to execute
  */
  function QueryString() {
    return $this->querystring;
  }


  /**
  * Return the parameters we are planning to substitute into the query string
  */
  function Parameters() {
    return $this->bound_parameters;
  }


  /**
  * Return the count of rows retrieved/affected
  */
  function rows() {
    return $this->rows;
  }


  /**
  * Return the current rownum in the retrieved set
  */
  function rownum() {
    return $this->rownum;
  }


  /**
  * Returns the current state of a transaction, indicating if we have begun a transaction, whether the transaction
  * has failed, or if we are not in a transaction.
  */
  function TransactionState() {
    global $_awl_dbconn;
    if ( !isset($this->connection) ) {
      if ( !isset($_awl_dbconn) ) _awl_connect_configured_database();
      $this->connection = $_awl_dbconn;
    }
    return $this->connection->TransactionState();
  }


  /**
  * Wrap the parent DB class Begin() so we can $qry->Begin() sometime before we $qry->Exec()
  */
  public function Begin() {
    global $_awl_dbconn;
    if ( !isset($this->connection) ) {
      if ( !isset($_awl_dbconn) ) _awl_connect_configured_database();
      $this->connection = $_awl_dbconn;
    }
    return $this->connection->Begin();
  }


  /**
  * Wrap the parent DB class Commit() so we can $qry->Commit() sometime after we $qry->Exec()
  */
  public function Commit() {
    if ( !isset($this->connection) ) {
      trigger_error("Cannot commit a transaction without an active statement.", E_USER_ERROR);
    }
    return $this->connection->Commit();
  }


  /**
  * Wrap the parent DB class Rollback() so we can $qry->Rollback() sometime after we $qry->Exec()
  */
  public function Rollback() {
    if ( !isset($this->connection) ) {
      trigger_error("Cannot rollback a transaction without an active statement.", E_USER_ERROR);
    }
    return $this->connection->Rollback();
  }


  /**
  * Simple SetSql() class which will reset the object with the querystring from the first argument.
  * @param  string The query string in PDO syntax with replacable '?' characters or bindable parameters.
  */
  public function SetSql( $sql ) {
    $this->rows = null;
    $this->execution_time = 0;
    $this->error_info = null;
    $this->rownum = -1;
    $this->bound_parameters = null;
    $this->bound_querystring = null;
    $this->sth = null;

    $this->querystring = $sql;
  }


  /**
  * Simple QDo() class which will re-use this query for whatever was passed in, and execute it
  * returning the result of the Exec() call.  We can't call it Do() since that's a reserved word...
  * @param  string The query string in PDO syntax with replacable '?' characters or bindable parameters.
  * @param mixed The values to replace into the SQL string.
  * @return boolean Success (true) or Failure (false)
  */
  public function QDo() {
    $argc = func_num_args();
    $args = func_get_args();

    $this->SetSql( array_shift($args) );
    if ( 1 < $argc ) {
      if ( is_array($args[0]) )
        $this->bound_parameters = $args[0];
      else
        $this->bound_parameters = $args;
    }

    return $this->Exec();
  }


  /**
  * Execute the query, logging any debugging.
  *
  * <b>Example</b>
  * So that you can nicely enable/disable the queries for a particular class, you
  * could use some of PHPs magic constants in your call.
  * <code>
  * $qry->Exec(__CLASS__, __LINE__, __FILE__);
  * </code>
  *
  *
  * @param string $location The name of the location for enabling debugging or just
  *                         to help our children find the source of a problem.
  * @param int $line The line number where Exec was called
  * @param string $file The file where Exec was called
  * @return boolean Success (true) or Failure (false)
  */
  function Exec( $location = null, $line = null, $file = null ) {
    global $c;
    if ( isset($location) ) $this->location = trim($location);
    if ( !isset($this->location) || $this->location == "" ) $this->location = substr($_SERVER['PHP_SELF'],1);

    if ( isset($line) )     $this->location_line = intval($line);
    else if ( isset($this->location_line) ) $line = $this->location_line;

    if ( isset($file) )     $this->location_file = trim($file);
    else if ( isset($this->location_file) ) $file = $this->location_file;

    if ( isset($c->dbg['querystring']) || isset($c->dbg['ALL']) ) {
      $this->_log_query( $this->location, 'DBGQ', $this->querystring, $line, $file );
      if ( isset($this->bound_parameters) && !isset($this->sth) ) {
        foreach( $this->bound_parameters AS $k => $v ) {
          $this->_log_query( $this->location, 'DBGQ', sprintf('    "%s" => "%s"', $k, $v), $line, $file );
        }
      }
    }

    if ( isset($this->bound_parameters) ) {
      $this->Prepare();
    }

    $success = $this->Execute();

    if ( ! $success ) {
      // query failed
      $this->errorstring = sprintf( 'SQL error "%s" - %s"', $this->error_info[0], (isset($this->error_info[2]) ? $this->error_info[2] : ''));
      if ( isset($c->dbg['print_query_errors']) && $c->dbg['print_query_errors'] ) {
        printf( "\n=====================\n" );
        printf( "%s[%d] QF: %s\n", $file, $line, $this->errorstring);
        printf( "%s\n", $this->querystring );
        if ( isset($this->bound_parameters) ) {
          foreach( $this->bound_parameters AS $k => $v ) {
            printf( "    %-18s \t=> '%s'\n", "'$k'", $v );
          }          
        }
        printf( ".....................\n" );
      }
      $this->_log_query( $this->location, 'QF', $this->errorstring, $line, $file );
      $this->_log_query( $this->location, 'QF', $this->querystring, $line, $file );
      if ( isset($this->bound_parameters) && ! ( isset($c->dbg['querystring']) || isset($c->dbg['ALL']) ) ) {
        foreach( $this->bound_parameters AS $k => $v ) {
          dbg_error_log( 'LOG-'.$this->location, ' Query: QF:     "%s" => "%s"', $k, $v);
        }
      }
    }
    elseif ( $this->execution_time > $this->query_time_warning ) {
     // if execution time is too long
      $this->_log_query( $this->location, 'SQ', "Took: $this->execution_time for $this->querystring", $line, $file ); // SQ == Slow Query :-)
    }
    elseif ( isset($c->dbg['querystring']) || isset($c->dbg[strtolower($this->location)]) || isset($c->dbg['ALL']) ) {
     // query successful, but we're debugging and want to know how long it took anyway
      $this->_log_query( $this->location, 'DBGQ', "Took: $this->execution_time to find $this->rows rows.", $line, $file );
    }

    return $success;
  }


  /**
  * Fetch the next row from the query results
  * @param boolean $as_array True if thing to be returned is array
  * @return mixed query row
  */
  function Fetch($as_array = false) {

    if ( ! $this->sth || $this->rows == 0 ) return false; // no results
    if ( $this->rownum == null ) $this->rownum = -1;
    if ( ($this->rownum + 1) >= $this->rows ) return false; // reached the end of results

    $this->rownum++;
    $row = $this->sth->fetch( ($as_array ? PDO::FETCH_NUM : PDO::FETCH_OBJ) );

    return $row;
  }


}

