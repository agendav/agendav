<?php
/**
* AwlDatabase - support for different SQL dialects
*
* This subpackage provides dialect specific support for PostgreSQL, and
* may, over time, be extended to provide support for other SQL dialects.
*
* See http://wiki.davical.org/w/Coding/AwlQuery for design and usage information.
*
* @package   awl
* @subpackage   AwlDatabase
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @compatibility Requires PHP 5.1 or later
*/

if ( !defined('E_USER_ERROR') ) define('E_USER_ERROR',256);

/**
* The AwlDBDialect class handles support for different SQL dialects
*
* This subpackage provides dialect specific support for PostgreSQL, and
* may, over time, be extended to provide support for other SQL dialects.
*
* If you are looking for the place to add support for other SQL dialects,
* this is the class that you should be looking at.  You might also look at
* the AwlDatabase class which extends this one, but these are the core
* capabilities which most probably need attention.
*
* @package awl
*/
class AwlDBDialect {
  /**#@+
  * @access private
  */

  /**
  * Holds the name of the database dialect
  */
  protected $dialect;

  /**
  * Holds the PDO database connection
  */
  protected $db;

  /**
  * Holds the version
  */
  private $version;

  /**#@-*/


  /**
  * Parses the connection string to ascertain the database dialect. Returns true if the dialect is supported
  * and fails if the dialect is not supported. All code to support any given database should be within in an
  * external include.
  *
  * The database will be opened.
  *
  * @param string $connection_string The PDO connection string, in all it's glory
  * @param string $dbuser The database username to connect as
  * @param string $dbpass The database password to connect with
  * @param array  $options An array of driver options
  */
  function __construct( $connection_string, $dbuser=null, $dbpass=null, $options=null ) {
    if ( preg_match( '/^(pgsql):/', $connection_string, $matches ) ) {
      $this->dialect = $matches[1];
    }
    else {
      trigger_error("Unsupported database connection '".$connection_string."'",E_USER_ERROR);
    }
    try {
      $this->db = new PDO( $connection_string, $dbuser, $dbpass, $options );
    } catch (PDOException $e) {
      trigger_error("PDO connection error '".$connection_string."': ".$e->getMessage(),E_USER_ERROR);
    }
  }



  /**
  * Sets the current search path for the database.
  */
  function SetSearchPath( $search_path = null ) {
    if ( !isset($this->dialect) ) {
      trigger_error("Unsupported database dialect",E_USER_ERROR);
    }

    switch ( $this->dialect ) {
      case 'pgsql':
        if ( $search_path == null ) $search_path = 'public';
        $sql = "SET search_path TO " . $this->Quote( $search_path, 'identifier' );
        return $sql;
    }
  }


  /**
  * Sets the current search path for the database.
  * @param handle $pdo A handle to an opened database
  */
  function GetVersion( ) {
    if ( isset($this->version) ) return $this->version;
    if ( !isset($this->dialect) ) {
      trigger_error("Unsupported database dialect", E_USER_ERROR);
    }

    $version = $this->dialect.':';

    switch ( $this->dialect ) {
      case 'pgsql':
        $sql = "SELECT version()";
        if ( $sth = $this->db->query($sql) ) {
          $row = $sth->fetch(PDO::FETCH_NUM);
          $version .= preg_replace( '/^PostgreSQL (\d+\.\d+)\..*$/i', '$1', $row[0]);
        }
        break;
      default:
        return null;
    }
    $this->version = $version;
    return $version;
  }


  /**
  * Returns the SQL for the current database dialect which will return a two-column resultset containing a
  * list of fields and their associated data types.
  * @param string $tablename_string The name of the table we want fields from
  */
  function GetFields( $tablename_string ) {
    if ( !isset($this->dialect) ) {
      trigger_error("Unsupported database dialect", E_USER_ERROR);
    }

    switch ( $this->dialect ) {
      case 'pgsql':
        $tablename_string = $this->Quote($tablename_string,PDO::PARAM_STR);
        $sql = 'SELECT f.attname AS fieldname, t.typname AS typename, f.atttypmod AS precision FROM pg_attribute f';
        $sql .= ' JOIN pg_class c ON ( f.attrelid = c.oid )';
        $sql .= ' JOIN pg_type t ON ( f.atttypid = t.oid )';
        $sql .= ' WHERE relname = '.$tablename_string.' AND attnum >= 0 order by f.attnum';
        return $sql;
    }
  }


  /**
  * Translates the given SQL string into a form that will hopefully work for this database dialect. This hook
  * is intended to be used by developers to provide support for differences in database operation by translating
  * the query string in an arbitrary way, such as through a file or database lookup.
  *
  * The actual translation to other SQL dialects will be application-specific, so that any routines
  * called by this will be external to this library, or will use resources loaded from some source
  * external to this library.
  *
  * The application developer is expected to use this functionality to solve harder translation problems,
  * but is less likely to call this directly, hopefully switching ->Prepare to ->PrepareTranslated in those
  * cases, and then adding that statement to whatever SQL translation infrastructure is in place.
  */
  function TranslateSQL( $sql_string ) {
    // Noop for the time being...
    return $sql_string;
  }



  /**
  * Returns $value escaped in an appropriate way for this database dialect.
  * @param mixed $value The value to be escaped
  * @param string $value_type The type of escaping desired.  If blank this will
  *                be worked out from the type of the $value.  The special type
  *                of 'identifier' can also be used for escaping of SQL identifiers.
  */
  function Quote( $value, $value_type = null ) {
    if ( isset($value_type) && $value_type == 'identifier' ) {
      if ( $this->dialect == 'mysql' ) {
        /** @TODO: Someone should confirm this is correct for MySql */
        $rv = '`' . str_replace('`', '\\`', $value ) . '`';
      }
      else {
        $rv = '"' . str_replace('"', '\\"', $value ) . '"';
      }
      return $rv;
    }

    if ( !isset($value_type) ) {
      if ( !isset($value) )       $value_type = PDO::PARAM_NULL;
      elseif ( is_bool($value) )  $value_type = PDO::PARAM_BOOL;
      elseif ( is_float($value) ) $value_type = PDO::PARAM_INT;
      elseif ( is_numeric($value)) {
        if ( preg_match('{^(19|20)\d\d(0[1-9]|1[012])([012]\d|30|31)$}', $value) )
          $value_type = PDO::PARAM_STR; // YYYYMMDD
        elseif ( preg_match('{^0x}i', $value) )
          $value_type = PDO::PARAM_STR; // Any hex numbers will need to be explicitly cast in SQL
        elseif ( preg_match('{^[0-9+-]+e[0-9+-]+$}i', $value) )
          $value_type = PDO::PARAM_STR; // 72e57650 could easily be a string and will need an explicit cast also
        else
          $value_type = PDO::PARAM_INT;
      }
      else
        $value_type = PDO::PARAM_STR;
    }

    if ( is_string($value_type) ) {
      switch( $value_type ) {
        case 'null':
          $value_type = PDO::PARAM_NULL;
          break;
        case 'integer':
        case 'double' :
          $value_type = PDO::PARAM_INT;
          break;
        case 'boolean':
          $value_type = PDO::PARAM_BOOL;
          break;
        case 'string':
          $value_type = PDO::PARAM_STR;
          break;
      }
    }

    switch ( $value_type ) {
      case PDO::PARAM_NULL:
        $rv = 'NULL';
        break;
      case PDO::PARAM_INT:
        $rv = $value;
        break;
      case PDO::PARAM_BOOL:
        $rv = ($value ? 'TRUE' : 'FALSE');
        break;
      case PDO::PARAM_STR:
      default:
        /**
        * PDO handling of \ seems unreliable.  We can't use $$string$$ syntax because it also doesn't
        * work.  We need to replace ':' so no other named parameters accidentally rewrite the content
        * inside this string(!), and since we're using ' to delimit the string we need SQL92-compliant
        * '' to replace it.
        */
        $rv = "'".str_replace("'", "''", str_replace(':', '\\x3a', str_replace('\\', '\\x5c', $value)))."'";

        if ( $this->dialect == 'pgsql' && strpos( $rv, '\\' ) !== false ) {
          /**
          * PostgreSQL wants to know when a string might contain escapes, and if this
          * happens old versions of PHP::PDO need the ? escaped as well...
          */
          $rv = 'E'.str_replace('?', '\\x3f', $rv);
        }

    }

    return $rv;

  }


  /**
  * Replaces query parameters with appropriately escaped substitutions.
  *
  * The function takes a variable number of arguments, the first is the
  * SQL string, with replaceable '?' characters (a la DBI).  The subsequent
  * parameters being the values to replace into the SQL string.
  *
  * The values passed to the routine are analyzed for type, and quoted if
  * they appear to need quoting.  This can go wrong for (e.g.) NULL or
  * other special SQL values which are not straightforwardly identifiable
  * as needing quoting (or not).  In such cases the parameter can be forced
  * to be inserted unquoted by passing it as "array( 'plain' => $param )".
  *
  * @param  string The query string with replacable '?' characters.
  * @param mixed The values to replace into the SQL string.
  * @return The built query string
  */
  function ReplaceParameters() {
    $argc = func_num_args();
    $args = func_get_args();

    if ( is_array($args[0]) ) {
      /**
      * If the first argument is an array we treat that as our arguments instead
      */
      $args = $args[0];
      $argc = count($args);
    }
    $qry = array_shift($args);

    if ( is_array($args[0]) ) {
      $args = $args[0];
      $argc = count($args);
    }

    if ( ! isset($args[0]) ) return $this->ReplaceNamedParameters($qry,$args);

    /**
    * We only split into a maximum of $argc chunks.  Any leftover ? will remain in
    * the string and may be replaced at Exec rather than Prepare. Scary!
    */
    $parts = explode( '?', $qry, $argc + 1 );
    $querystring = $parts[0];
    $z = count($parts);

    for( $i = 0; $i < $argc; $i++ ) {
      $arg = $args[$i];
      $querystring .= $this->Quote($arg);  //parameter
      $z = $i+1;
      if ( isset($parts[$z]) ) $querystring .= $parts[$z];
    }

    return $querystring;
  }

  /**
  * Replaces named query parameters of the form :name with appropriately
  * escaped substitutions.
  *
  * The function takes a variable number of arguments, the first is the
  * SQL string, with replaceable ':name' characters (a la DBI).  The
  * subsequent parameters being the values to replace into the SQL string.
  *
  * The values passed to the routine are analyzed for type, and quoted if
  * they appear to need quoting.  This can go wrong for (e.g.) NULL or
  * other special SQL values which are not straightforwardly identifiable
  * as needing quoting (or not).
  *
  * @param  string The query string with replacable ':name' identifiers
  * @param mixed A ':name' => 'value' hash of values to replace into the
  * SQL string.
  * @return The built query string
  */
  function ReplaceNamedParameters() {
    $argc = func_num_args();
    $args = func_get_args();

    if ( is_array($args[0]) ) {
      /**
      * If the first argument is an array we treat that as our arguments instead
      */
      $args = $args[0];
      $argc = count($args);
    }
    $querystring = array_shift($args);

    if ( is_array($args[0]) ) {
      $args = $args[0];
      $argc = count($args);
    }

    foreach( $args AS $name => $value ) {
      if ( substr($name, 0, 1) != ':' ) {
        dbg_error_log( "ERROR", "AwlDBDialect: Named parameter '%s' does not begin with a colon.", $name);
      }
      $replacement = str_replace('$', '\\$', $this->Quote($value));  // No positional replacement in $replacement!
      $querystring = preg_replace( '{\Q'.$name.'\E\b}s', $replacement, $querystring );
    }

    return $querystring;
  }

}
