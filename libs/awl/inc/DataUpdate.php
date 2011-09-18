<?php
/**
* Some functions and a base class to help with updating records.
*
* This subpackage provides some functions that are useful around single
* record database activities such as insert and update.
*
* @package   awl
* @subpackage   DataUpdate
* @author Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once('AWLUtilities.php');
require_once('AwlQuery.php');


/**
* Build SQL INSERT/UPDATE statement from an associative array of fieldnames => values.
* @param array $obj The object  of fieldnames => values.
* @param string $type The word "update" or something else (which implies "insert").
* @param string $tablename The name of the table being updated.
* @param string $where What the "WHERE ..." clause needs to be for an UPDATE statement.
* @param string $fprefix An optional string which all fieldnames in $assoc are prefixed with.
* @return string An SQL Update or Insert statement with all fields/values from the array.
*/
function sql_from_object( $obj, $type, $tablename, $where, $fprefix = "" ) {
  $fields = get_fields($tablename);
  $update = strtolower($type) == "update";
  if ( $update )
    $sql = "UPDATE $tablename SET ";
  else
    $sql = "INSERT INTO $tablename (";

  $flst = "";
  $vlst = "";
  foreach( $fields as $fn => $typ ) {
    // $prefixed_fn = $fprefix . $fn;
    dbg_error_log( "DataUpdate", ":sql_from_object: %s => %s (%s)", $fn, $typ, (isset($obj->{$fn})?$obj->{$fn}:"[undefined value]"));
    if ( !isset($obj->{$fn}) && isset($obj->{"xxxx$fn"}) ) {
      // Sometimes we will have prepended 'xxxx' to the field name so that the field
      // name differs from the column name in the database.
      $obj->{$fn} = $obj->{"xxxx$fn"};
    }
    if ( !isset($obj->{$fn}) ) continue;
    $value = str_replace( "'", "''", str_replace("\\", "\\\\", $obj->{$fn}));
    if ( $fn == "password" ) {
      if ( $value == "******" || $value == "" ) continue;
      if ( !preg_match('/\*[0-9a-z]+\*[0-9a-z{}]+/i', $value ) )
        $value = (function_exists("session_salted_sha1")
                   ? session_salted_sha1($value)
                   : (function_exists('session_salted_md5')
                       ? session_salted_md5($value)
                       : md5($value)
                     )
                 );
    }
    if ( preg_match('{^(time|date|interval)}i', $typ ) && $value == "" ) {
      $value = "NULL";
    }
    else if ( preg_match('{^bool}i', $typ) )  {
      $value = ( $value == false || $value == "f" || $value == "off" || $value == "no" ? "FALSE"
                  : ( $value == true || $value == "t" || $value == "on" || $value == "yes" ? "TRUE"
                      : "NULL" ));
    }
    else if ( preg_match('{^interval}i', $typ) )  {
      $value = "'$value'::$typ";
    }
    else if ( preg_match('{^int}i', $typ) )  {
      $value = ($value == '' || $value === null ? 'NULL' : intval( $value ));
    }
    else if ( preg_match('{^bit}i', $typ) )  {
      $value = ($value == '' || $value === null ? 'NULL' : "'$value'");
    }
    else if ( preg_match('{^(text|varchar)}i', $typ) )  {
      $value = "'$value'";
    }
    else
      $value = "'$value'::$typ";

    if ( $update )
      $flst .= ", $fn = $value";
    else {
      $flst .= ", $fn";
      $vlst .= ", $value";
    }
  }
  $flst = substr($flst,2);
  $vlst = substr($vlst,2);
  $sql .= $flst;
  if ( $update ) {
    $sql .= " $where; ";
  }
  else {
    $sql .= ") VALUES( $vlst ); ";
  }
 return $sql;
}


/**
* Build SQL INSERT/UPDATE statement from the $_POST associative array
* @param string $type The word "update" or something else (which implies "insert").
* @param string $tablename The name of the table being updated.
* @param string $where What the "WHERE ..." clause needs to be for an UPDATE statement.
* @param string $fprefix An optional string which all fieldnames in $assoc are prefixed with.
* @return string An SQL Update or Insert statement with all fields/values from the array.
*/
function sql_from_post( $type, $tablename, $where, $fprefix = "" ) {
  $fakeobject = (object) $_POST;
  return sql_from_object( $fakeobject, $type, $tablename, $where, $fprefix );
}


/**
* A Base class to use for records which will be read/written from the database.
* @package   awl
*/
class DBRecord
{
  /**#@+
  * @access private
  */
  /**
  * The database table that this record goes in
  * @var string
  */
  var $Table;

  /**
  * The field names for the record.  The array index is the field name
  * and the array value is the field type.
  * @var array
  */
  var $Fields;

  /**
  * The keys for the record as an array of key => value pairs
  * @var array
  */
  var $Keys;

  /**
  * The field values for the record
  * @var object
  */
  var $Values;

  /**
  * The type of database write we will want: either "update" or "insert"
  * @var object
  */
  var $WriteType;

  /**
  * A list of associated other tables.
  * @var array of string
  */
  var $OtherTable;

  /**
  * The field names for each of the other tables associated.  The array index
  * is the table name, the string is a list of field names (and perhaps aliases)
  * to stuff into the target list for the SELECT.
  * @var array of string
  */
  var $OtherTargets;

  /**
  * An array of JOIN ... clauses.  The first array index is the table name and the array value
  * is the JOIN clause like "LEFT JOIN tn t1 USING (myforeignkey)".
  * @var array of string
  */
  var $OtherJoin;

  /**
  * An array of partial WHERE clauses.  These will be combined (if present) with the key
  * where clause on the main table.
  * @var array of string
  */
  var $OtherWhere;

  /**#@-*/

  /**#@+
  * @access public
  */
  /**
  * The mode we are in for any form
  * @var object
  */
  var $EditMode;

  /**#@-*/

  /**
  * Really numbingly simple construction.
  */
  function DBRecord( ) {
    dbg_error_log( "DBRecord", ":Constructor: called" );
    $this->WriteType = "insert";
    $this->EditMode = false;
    $this->prefix = "";
    $values = (object) array();
    $this->Values = &$values;
  }

  /**
  * This will read the record from the database if it's available, and
  * the $keys parameter is a non-empty array.
  * @param string $table The name of the database table
  * @param array $keys An associative array containing fieldname => value pairs for the record key.
  */
  function Initialise( $table, $keys = array() ) {
    dbg_error_log( "DBRecord", ":Initialise: called" );
    $this->Table = $table;
    $this->Fields = get_fields($this->Table);
    $this->Keys = $keys;
    $this->WriteType = "insert";
  }

  /**
  * This will join an additional table to the maintained set
  * @param string $table The name of the database table
  * @param array $keys An associative array containing fieldname => value pairs for the record key.
  * @param string $join A PostgreSQL join clause.
  * @param string $prefix A field prefix to use for these fields to distinguish them from fields
  *                       in other joined tables with the same name.
  */
  function AddTable( $table, $target_list, $join_clause, $and_where ) {
    dbg_error_log( "DBRecord", ":AddTable: $table called" );
    $this->OtherTable[] = $table;
    $this->OtherTargets[$table] = $target_list;
    $this->OtherJoin[$table] = $join_clause;
    $this->OtherWhere[$table] = $and_where;
  }

  /**
  * This will assign $_POST values to the internal Values object for each
  * field that exists in the Fields array.
  */
  function PostToValues( $prefix = "" ) {
    foreach ( $this->Fields AS $fname => $ftype ) {
      @dbg_error_log( "DBRecord", ":PostToValues: %s => %s", $fname, $_POST["$prefix$fname"] );
      if ( isset($_POST["$prefix$fname"]) ) {
        $this->Set($fname, $_POST["$prefix$fname"]);
        @dbg_error_log( "DBRecord", ":PostToValues: %s => %s", $fname, $_POST["$prefix$fname"] );
      }
    }
  }

  /**
  * Builds a table join clause
  * @return string A simple SQL target join clause excluding the primary table.
  */
  function _BuildJoinClause() {
    $clause = "";
    foreach( $this->OtherJoins AS $t => $join ) {
      if ( ! preg_match( '/^\s*$/', $join ) ) {
        $clause .= ( $clause == "" ? "" : " " )  . $join;
      }
    }

    return $clause;
  }

  /**
  * Builds a field target list
  * @return string A simple SQL target field list for each field, possibly including prefixes.
  */
  function _BuildFieldList() {
    $list = "";
    foreach( $this->Fields AS $fname => $ftype ) {
      $list .= ( $list == "" ? "" : ", " );
      $list .= "$fname" . ( $this->prefix == "" ? "" : " AS \"$this->prefix$fname\"" );
    }

    foreach( $this->OtherTargets AS $t => $targets ) {
      if ( ! preg_match( '/^\s*$/', $targets ) ) {
        $list .= ( $list == "" ? "" : ", " )  . $targets;
      }
    }

    return $list;
  }

  /**
  * Builds a where clause to match the supplied keys
  * @param boolean $overwrite_values Controls whether the data values for the key fields will be forced to match the key values
  * @return string A simple SQL where clause, including the initial "WHERE", for each key / value.
  */
  function _BuildWhereClause($overwrite_values=false) {
    $where = "";
    foreach( $this->Keys AS $k => $v ) {
      // At least assign the key fields...
      if ( $overwrite_values ) $this->Values->{$k} = $v;
      // And build the WHERE clause
      $where .= ( $where == '' ? 'WHERE ' : ' AND ' );
      $where .= $k . '=' . AwlQuery::quote($v);
    }

    if ( isset($this->OtherWhere) && is_array($this->OtherWhere) ) {
      foreach( $this->OtherWhere AS $t => $and_where ) {
        if ( ! preg_match( '/^\s*$/', $and_where ) ) {
          $where .= ($where == '' ? 'WHERE ' : ' AND (' )  . $and_where . ')';
        }
      }
    }

    return $where;
  }

  /**
  * Sets a single field in the record
  * @param string $fname The name of the field to set the value for
  * @param string $fval The value to set the field to
  * @return mixed The new value of the field (i.e. $fval).
  */
  function Set($fname, $fval) {
    dbg_error_log( "DBRecord", ":Set: %s => %s", $fname, $fval );
    $this->Values->{$fname} = $fval;
    return $fval;
  }

  /**
  * Returns a single field from the record
  * @param string $fname The name of the field to set the value for
  * @return mixed The current value of the field.
  */
  function Get($fname) {
    @dbg_error_log( "DBRecord", ":Get: %s => %s", $fname, $this->Values->{$fname} );
    return (isset($this->Values->{$fname}) ? $this->Values->{$fname} : null);
  }

  /**
  * Unsets a single field from the record
  * @param string $fname The name of the field to unset the value for
  * @return mixed The current value of the field.
  */
  function Undefine($fname) {
    if ( !isset($this->Values->{$fname}) ) return null;
    $current = $this->Values->{$fname};
    dbg_error_log( 'DBRecord', ': Unset: %s =was> %s', $fname, $current );
    unset($this->Values->{$fname});
    return $current;
  }

  /**
  * To write the record to the database
  * @return boolean Success.
  */
  function Write() {
    dbg_error_log( "DBRecord", ":Write: %s record as %s.", $this->Table, $this->WriteType );
    $sql = sql_from_object( $this->Values, $this->WriteType, $this->Table, $this->_BuildWhereClause(), $this->prefix );
    $qry = new AwlQuery($sql);
    return $qry->Exec( "DBRecord", __LINE__, __FILE__ );
  }

  /**
  * To read the record from the database.
  * If we don't have any keys then the record will be blank.
  * @return boolean Whether we actually read a record.
  */
  function Read() {
    $i_read_the_record = false;
    $values = (object) array();
    $this->EditMode = true;
    $where = $this->_BuildWhereClause(true);
    if ( "" != $where ) {
      // $fieldlist = $this->_BuildFieldList();
      $fieldlist = "*";
  //    $join = $this->_BuildJoinClause(true);
      $sql = "SELECT $fieldlist FROM $this->Table $where";
      $qry = new AwlQuery($sql);
      if ( $qry->Exec( "DBRecord", __LINE__, __FILE__ ) && $qry->rows() > 0 ) {
        $i_read_the_record = true;
        $values = $qry->Fetch();
        $this->EditMode = false;  // Default to not editing if we read the record.
        dbg_error_log( "DBRecord", ":Read: Read %s record from table.", $this->Table, $this->WriteType );
      }
    }
    $this->Values = &$values;
    $this->WriteType = ( $i_read_the_record ? "update" : "insert" );
    dbg_error_log( "DBRecord", ":Read: Record %s write type is %s.", $this->Table, $this->WriteType );
    return $i_read_the_record;
  }
}

