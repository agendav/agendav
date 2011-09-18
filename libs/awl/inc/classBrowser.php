<?php
/**
* Table browser / lister class
*
* Browsers are constructed from BrowserColumns and can support sorting
* and other interactive behaviour.  Cells may contain data which is
* formatted as a link, or the entire row may be linked through an onclick
* action.
*
* @package   awl
* @subpackage   Browser
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("AWLUtilities.php");

/**
* Ensure that this is not set elsewhere.
*/
$BrowserCurrentRow = (object) array();



/**
* BrowserColumns are the basic building blocks.  You can specify just the
* field name, and the column header or you can get fancy and specify an
* alignment, format string, SQL formula and cell CSS class.
* @package   awl
*/
class BrowserColumn
{
  var $Field;
  var $Header;
  var $Format;
  var $Sql;
  var $Align;
  var $Class;
  var $Type;
  var $Translatable;
  var $Hook;
  var $current_row;

  /**
  * BrowserColumn constructor.  Only the first parameter is mandatory.
  *
  * @param string field The name of the column in the SQL result.
  * @param string header The text to appear in the column header on output
  *                      (@see BrowserColumn::RenderHeader()).  If this is not supplied then
  *                      a default of the field name will be used.
  * @param string align left|center|right - text alignment.  Defaults to 'left'.
  * @param string format A format (a-la-printf) to render data values within.
  *                      (@see BrowserColumn::RenderValue()).  If this is not supplied
  *                      then the default will ensure the column value is displayed as-is.
  * @param string sql Some SQL which will return the desired value to be presented as column 'field' of
  *                   the result. If this is blank then the column is assumed to be a real data column.
  * @param string class Additional classes to apply to the column header and column value cells.
  * @param string datatype This will allow 'date' or 'timestamp' to preformat the field correctly before
  *                        using it in replacements or display.  Other types may be added in future.
  * @param string $hook The name of a global function which will preprocess the column value
  *
  * The hook function should be defined as follows:
  *   function hookfunction( $column_value, $column_name, $database_row ) {
  *     ...
  *     return $value;
  *   }
  */
  function BrowserColumn( $field, $header="", $align="", $format="", $sql="", $class="", $datatype="", $hook=null ) {
    $this->Field  = $field;
    $this->Sql    = $sql;
    $this->Header = $header;
    $this->Format = $format;
    $this->Class  = $class;
    $this->Align  = $align;
    $this->Type   = $datatype;
    $this->Translatable = false;
    $this->Hook   = $hook;
  }

  /**
  * GetTarget
  *
  * Retrieves a 'field' or '...SQL... AS field' definition for the target list of the SQL.
  */
  function GetTarget() {
    if ( $this->Sql == "" ) return $this->Field;
    return "$this->Sql AS $this->Field";
  }

  /**
  * RenderHeader
  * Renders the column header cell for this column.  This will be rendered as a <th>...</th>
  * with class and alignment applied to it.  Browser column headers are clickable, and the
  * ordering will also display an 'up' or 'down' triangle with the column header that the SQL
  * is sorted on at the moment.
  *
  * @param string order_field The name of the field currently being sorted on.
  * @param string order_direction Whether the sort is Ascending or Descending.
  * @param int browser_array_key Used this to help handle separate ordering of
  *                              multiple browsers on the same page.
  * @param string forced_order If true, then we don't allow order to be changed.
  */
  function RenderHeader( $order_field, $order_direction, $browser_array_key=0, $forced_order=false ) {
    global $c;
    if ( $this->Align == "" ) $this->Align = "left";
    $html = '<th class="'.$this->Align.'" '. ($this->Class == "" ? "" : "class=\"$this->Class\"") . '>';

    $direction = 'A';
    $image = "";
    if ( !$forced_order && $order_field == $this->Field ) {
      if ( strtoupper( substr( $order_direction, 0, 1) ) == 'A' ) {
        $image = 'down';
        $direction = 'D';
      }
      else {
        $image = 'up';
      }
      $image = "<img class=\"order\" src=\"$c->images/$image.gif\" alt=\"$image\" />";
    }
    if ( !isset($browser_array_key) || $browser_array_key == '' ) $browser_array_key = 0;
    if ( !$forced_order ) $html .= '<a href="'.replace_uri_params( $_SERVER['REQUEST_URI'], array( "o[$browser_array_key]" => $this->Field, "d[$browser_array_key]" => $direction ) ).'" class="order">';
    $html .= ($this->Header == "" ? $this->Field : $this->Header);
    if ( !$forced_order ) $html .= "$image</a>";
    $html .= "</th>\n";
    return $html;
  }

  function SetTranslatable() {
    $this->Translatable = true;
  }

  function RenderValue( $value, $extraclass = "" ) {
    global $session;

    if ( $this->Type == 'date' || $this->Type == 'timestamp') {
      $value = $session->FormattedDate( $value, $this->Type );
    }

    if ( $this->Hook && function_exists($this->Hook) ) {
      dbg_error_log( "Browser", ":Browser: Hook for $this->Hook on column $this->Field");
      $value = call_user_func( $this->Hook, $value, $this->Field, $this->current_row );
    }

    if ( $this->Translatable ) {
      $value = translate($value);
    }

    $value = str_replace( "\n", "<br />", $value );
    if ( substr(strtolower($this->Format),0,3) == "<td" ) {
      $html = sprintf($this->Format,$value);
    }
    else {
      // These quite probably don't work.  The CSS standard for multiple classes is 'class="a b c"' but is lightly
      // implemented according to some web references.  Perhaps modern browsers are better?
      $class = $this->Align . ($this->Class == "" ? "" : " $this->Class") . ($extraclass == "" ? "" : " $extraclass");
      if ( $class != "" ) $class = ' class="'.$class.'"';
      $html = sprintf('<td%s>',$class);
      $html .= ($this->Format == "" ? $value : sprintf($this->Format,$value,$value));
      $html .= "</td>\n";
    }
    return $html;
  }
}


/**
* Start a new Browser, add columns, set a join and Render it to create a basic
* list of records in a table.
* You can, of course, get a lot fancier with setting ordering, where clauses
* totalled columns and so forth.
* @package   awl
*/
class Browser
{
  var $Title;
  var $SubTitle;
  var $FieldNames;
  var $Columns;
  var $HiddenColumns;
  var $Joins;
  var $Where;
  var $Distinct;
  var $Union;
  var $Order;
  var $OrderField;
  var $OrderDirection;
  var $OrderBrowserKey;
  var $ForcedOrder;
  var $Grouping;
  var $Limit;
  var $Offset;
  var $Query;
  var $BeginRow;
  var $CloseRow;
  var $BeginRowArgs;
  var $Totals;
  var $TotalFuncs;
  var $ExtraRows;
  var $match_column;
  var $match_value;
  var $match_function;
  var $DivOpen;
  var $DivClose;

  /**
  * The Browser class constructor
  *
  * @param string $title A title for the browser (optional).
  */
  function Browser( $title = "" ) {
    global $c;
    $this->Title = $title;
    $this->SubTitle = "";
    $this->Distinct = "";
    $this->Order = "";
    $this->Limit = "";
    $this->Offset = "";
    $this->BeginRow = "<tr class=\"row%d\">\n";
    $this->CloseRow = "</tr>\n";
    $this->BeginRowArgs = array('#even');
    $this->Totals = array();
    $this->Columns = array();
    $this->HiddenColumns = array();
    $this->FieldNames = array();
    $this->DivOpen = '<div id="browser">';
    $this->DivClose = '</div>';
    $this->ForcedOrder = false;
    dbg_error_log( "Browser", ":Browser: New browser called $title");
  }

  /**
  * Add a column to the Browser.
  *
  * This constructs a new BrowserColumn, appending it to the array of columns
  * in this Browser.
  *
  * Note that if the $format parameter starts with '<td>' the format will replace
  * the column format, otherwise it will be used within '<td>...</td>' tags.
  * @see BrowserColumn
  *
  * @param string $field The name of the field.
  * @param string $header A column header for the field.
  * @param string $align An alignment for column values.
  * @param string $format A sprintf format for displaying column values.
  * @param string $sql An SQL fragment for calculating the value.
  * @param string $class A CSS class to apply to the cells of this column.
  * @param string $hook The name of a global function which will preprocess the column value
  *
  * The hook function should be defined as follows:
  *   function hookfunction( $column_value, $column_name, $database_row ) {
  *     ...
  *     return $value;
  *   }
  *
  */
  function AddColumn( $field, $header="", $align="", $format="", $sql="", $class="", $datatype="", $hook=null ) {
    $this->Columns[] = new BrowserColumn( $field, $header, $align, $format, $sql, $class, $datatype, $hook );
    $this->FieldNames[$field] = count($this->Columns) - 1;
  }

  /**
  * Add a hidden column - one that is present in the SQL result, but for
  * which there is no column displayed.
  *
  * This can be useful for including a value in (e.g.) clickable links or title
  * attributes which is not actually displayed as a visible column.
  *
  * @param string $field The name of the field.
  * @param string $sql An SQL fragment to calculate the field, if it is calculated.
  */
  function AddHidden( $field, $sql="" ) {
    $this->HiddenColumns[] = new BrowserColumn( $field, "", "", "", $sql );
    $this->FieldNames[$field] = count($this->Columns) - 1;
  }

  /**
  * Set the Title for the browse.
  *
  * This can also be set in the constructor but if you create a template Browser
  * and then clone it in a loop you may want to assign a different Title for each
  * instance.
  *
  * @param string $new_title The new title for the browser
  */
  function SetTitle( $new_title ) {
    $this->Title = $new_title;
  }


  /**
  * Accessor for the Title for the browse, which could set the title also.
  *
  * @param string $new_title The new title for the browser
  * @return string The current title for the browser
  */
  function Title( $new_title = null ) {
    if ( isset($new_title) ) $this->Title = $new_title;
    return $this->Title;
  }


  /**
  * Set the named columns to be translatable
  *
  * @param array $column_list The list of columns which are translatable
  */
  function SetTranslatable( $column_list ) {
    $top = count($this->Columns);
    for( $i=0; $i < $top; $i++ ) {
      dbg_error_log( "Browser", "Comparing %s with column name list", $this->Columns[$i]->Field);
      if ( in_array($this->Columns[$i]->Field,$column_list) ) $this->Columns[$i]->SetTranslatable();
    }
    $top = count($this->HiddenColumns);
    for( $i=0; $i < $top; $i++ ) {
      dbg_error_log( "Browser", "Comparing %s with column name list", $this->HiddenColumns[$i]->Field);
      if ( in_array($this->HiddenColumns[$i]->Field,$column_list) ) $this->HiddenColumns[$i]->SetTranslatable();
    }
  }

  /**
  * Set a Sub Title for the browse.
  *
  * @param string $sub_title The sub title string
  */
  function SetSubTitle( $sub_title ) {
    $this->SubTitle = $sub_title;
  }

  /**
  * Set a div for wrapping the browse.
  *
  * @param string $open_div The HTML to open the div
  * @param string $close_div The HTML to open the div
  */
  function SetDiv( $open_div, $close_div ) {
    $this->DivOpen = $open_div;
    $this->DivClose = $close_div;
  }

  /**
  * Set the tables and joins for the SQL.
  *
  * For a single table this should just contain the name of that table, but for
  * multiple tables it should be the full content of the SQL 'FROM ...' clause
  * (excluding the actual 'FROM' keyword).
  *
  * @param string $join_list
  */
  function SetJoins( $join_list ) {
    $this->Joins = $join_list;
  }

  /**
  * Set a Union SQL statement.
  *
  * In rare cases this might be useful.  It's currently a fairly simple hack
  * which requires you to put an entire valid (& matching) UNION subclause
  * (although without the UNION keyword).
  *
  * @param string $union_select
  */
  function SetUnion( $union_select ) {
    $this->Union = $union_select;
  }

  /**
  * Set the SQL Where clause to a specific value.
  *
  * The WHERE keyword should not be included.
  *
  * @param string $where_clause A valide SQL WHERE ... clause.
  */
  function SetWhere( $where_clause ) {
    $this->Where = $where_clause;
  }

  /**
  * Set the SQL DISTINCT clause to a specific value.
  *
  * The whole clause (except the keyword) needs to be supplied
  *
  * @param string $distinct The whole clause, after 'DISTINCT'
  */
  function SetDistinct( $distinct ) {
    $this->Distinct = "DISTINCT ".$distinct;
  }

  /**
  * Set the SQL LIMIT clause to a specific value.
  *
  * Only the limit number should be supplied.
  *
  * @param int $limit_n A number of rows to limit the SQL selection to
  */
  function SetLimit( $limit_n ) {
    $this->Limit = "LIMIT ".intval($limit_n);
  }

  /**
  * Set the SQL OFFSET clause to a specific value.
  *
  * Only the offset number
  *
  * @param int $offset_n A number of rows to offset the SQL selection to, based from the start of the results.
  */
  function SetOffset( $offset_n ) {
    $this->Offset = "OFFSET ".intval($offset_n);
  }

  /**
  * Add an [operator] ... to the SQL Where clause
  *
  * You will generally want to call OrWhere or AndWhere rather than
  * this function, but hey: who am I to tell you how to code!
  *
  * @param string $operator The operator to combine with previous where clause parts.
  * @param string $more_where The extra part of the where clause
  */
  function MoreWhere( $operator, $more_where ) {
    if ( $this->Where == "" ) {
      $this->Where = $more_where;
      return;
    }
    $this->Where = "$this->Where $operator $more_where";
  }

  /**
  * Add an OR ...  to the SQL Where clause
  *
  * @param string $more_where The extra part of the where clause
  */
  function AndWhere( $more_where ) {
    $this->MoreWhere("AND",$more_where);
  }

  /**
  * Add an OR ... to the SQL Where clause
  *
  * @param string $more_where The extra part of the where clause
  */
  function OrWhere( $more_where ) {
    $this->MoreWhere("OR",$more_where);
  }

  function AddGrouping( $field, $browser_array_key=0 ) {
    if ( $this->Grouping == "" )
      $this->Grouping = "GROUP BY ";
    else
      $this->Grouping .= ", ";

    $this->Grouping .= clean_string($field);
  }


  /**
  * Add an ordering to the browser widget.
  *
  * The ordering can be overridden by GET parameters which will be
  * rendered into the column headers so that a user can click on
  * the column headers to control the actual order.
  *
  * @param string $field The name of the field to be ordered by.
  * @param string $direction A for Ascending, otherwise it will be descending order.
  * @param string $browser_array_key Use this to distinguish between multiple
  *               browser widgets on the same page.  Leave it empty if you only
  *               have a single browser instance.
  * @param string $secondary Use this to indicate a default secondary order
  *               which shouldn't interfere with the default primary order.
  */
  function AddOrder( $field, $direction, $browser_array_key=0, $secondary=0 ) {
    $field = check_by_regex($field,'/^[^\'"!\\\\()\[\]|*\/{}&%@~;:?<>]+$/');
    if ( ! isset($this->FieldNames[$field]) ) return;

    if ( !isset($this->Order) || $this->Order == "" )
      $this->Order = "ORDER BY ";
    else
      $this->Order .= ", ";

    if ( $secondary == 0 ) {
      $this->OrderField = $field;
      $this->OrderBrowserKey = $browser_array_key;
    }
    $this->Order .= $field;

    if ( preg_match( '/^A/i', $direction) ) {
      $this->Order .= " ASC";
      if ( $secondary == 0)
        $this->OrderDirection = 'A';
    }
    else {
      $this->Order .= " DESC";
      if ( $secondary == 0)
        $this->OrderDirection = 'D';
    }
  }


  /**
  * Force a particular ordering onto the browser widget.
  *
  * @param string $field The name of the field to be ordered by.
  * @param string $direction A for Ascending, otherwise it will be descending order.
  */
  function ForceOrder( $field, $direction ) {
    $field = clean_string($field);
    if ( ! isset($this->FieldNames[$field]) ) return;

    if ( $this->Order == "" )
      $this->Order = "ORDER BY ";
    else
      $this->Order .= ", ";

    $this->Order .= $field;

    if ( preg_match( '/^A/i', $direction) ) {
      $this->Order .= " ASC";
    }
    else {
      $this->Order .= " DESC";
    }

    $this->ForcedOrder = true;
  }


  /**
  * Set up the ordering for the browser.  Generally you should call this with
  * the first parameter set as a field to order by default.  Call with the second
  * parameter set to 'D' or 'DESCEND' if you want to reverse the default order.
  */
  function SetOrdering( $default_fld=null, $default_dir='A' , $browser_array_key=0 ) {
    if ( isset( $_GET['o'][$browser_array_key] ) && isset($_GET['d'][$browser_array_key] ) ) {
      $this->AddOrder( $_GET['o'][$browser_array_key], $_GET['d'][$browser_array_key], $browser_array_key );
    }
    else {
      if ( ! isset($default_fld) ) $default_fld = $this->Columns[0];
      $this->AddOrder( $default_fld, $default_dir, $browser_array_key );
    }
  }


  /**
  * Mark a column as something to be totalled.  You can also specify the name of
  * a function which may modify the value before the actual totalling.
  *
  * The callback function will be called with each row, with the first argument
  * being the entire record object and the second argument being only the column
  * being totalled.  The callback should return a number, to be added to the total.
  *
  * @param string $column_name The name of the column to be totalled.
  * @param string $total_function The name of the callback function.
  */
  function AddTotal( $column_name, $total_function = false ) {
    $this->Totals[$column_name] = 0;
    if ( $total_function != false ) {
      $this->TotalFuncs[$column_name] = $total_function;
    }
  }


  /**
  * Retrieve the total from a totalled column
  *
  * @param string $column_name The name of the column to be totalled.
  */
  function GetTotal( $column_name ) {
    return $this->Totals[$column_name];
  }


  /**
  * Set the format for an output row.
  *
  * The row format is set as an sprintf format string for the start of the row,
  * and a plain text string for the close of the row.  Subsequent arguments
  * are interpreted as names of fields, the values of which will be sprintf'd
  * into the beginrow string for each row.
  *
  * Some special field names exist beginning with the '#' character which have
  * 'magic' functionality, including '#even' which will insert '0' for even
  * rows and '1' for odd rows, allowing a nice colour alternation if the
  * beginrow format refers to it like: 'class="r%d"' so that even rows will
  * become 'class="r0"' and odd rows will be 'class="r1"'.
  *
  * At present only '#even' exists, although other magic values may be defined
  * in future.
  *
  * @param string $beginrow The new printf format for the start of the row.
  * @param string $closerow The new string for the close of the row.
  * @param string $rowargs ... The row arguments which will be sprintf'd into
  * the $beginrow format for each row
  */
  function RowFormat( $beginrow, $closerow, $rowargs )
  {
    $argc = func_num_args();
    $this->BeginRow = func_get_arg(0);
    $this->CloseRow = func_get_arg(1);

    $this->BeginRowArgs = array();
    for( $i=2; $i < $argc; $i++ ) {
      $this->BeginRowArgs[] = func_get_arg($i);
    }
  }


  /**
  * This method is used to build and execute the database query.
  *
  * You need not call this method, since Browser::Render() will call it for
  * you if you have not done so at that point.
  *
  * @return boolean The success / fail status of the AwlQuery::Exec()
  */
  function DoQuery() {
    $target_fields = "";
    foreach( $this->Columns AS $k => $column ) {
      if ( $target_fields != "" ) $target_fields .= ", ";
      $target_fields .= $column->GetTarget();
    }
    if ( isset($this->HiddenColumns) ) {
      foreach( $this->HiddenColumns AS $k => $column ) {
        if ( $target_fields != "" ) $target_fields .= ", ";
        $target_fields .= $column->GetTarget();
      }
    }
    $where_clause = ((isset($this->Where) && $this->Where != "") ? "WHERE $this->Where" : "" );
    $sql = sprintf( "SELECT %s %s FROM %s %s %s ", $this->Distinct, $target_fields,
                 $this->Joins, $where_clause, $this->Grouping );
    if ( "$this->Union" != "" ) {
      $sql .= "UNION $this->Union ";
    }
    $sql .= $this->Order . ' ' . $this->Limit . ' ' . $this->Offset;
    $this->Query = new AwlQuery( $sql );
    return $this->Query->Exec("Browse:$this->Title:DoQuery");
  }


  /**
  * Add an extra arbitrary row onto the end of the browser.
  *
  * @var array $column_values Contains an array of named fields, hopefully matching the column names.
  */
  function AddRow( $column_values ) {
    if ( !isset($this->ExtraRows) || typeof($this->ExtraRows) != 'array' ) $this->ExtraRows = array();
    $this->ExtraRows[] = &$column_values;
  }


  /**
  * Replace a row where $column = $value with an extra arbitrary row, returned from calling $function
  *
  * @param string $column The name of a column to match
  * @param string $value  The value to match in the column
  * @param string $function The name of the function to call for the matched row
  */
  function MatchedRow( $column, $value, $function ) {
    $this->match_column = $column;
    $this->match_value  = $value;
    $this->match_function = $function;
  }


  /**
  * Return values from the current row for replacing into a template.
  *
  * This is used to return values from the current row, so they can
  * be inserted into a row template.  It is used as a callback
  * function for preg_replace_callback.
  *
  * @param array of string $matches An array containing a field name as offset 1
  */
  function ValueReplacement($matches)
  {
    // as usual: $matches[0] is the complete match
    // $matches[1] the match for the first subpattern
    // enclosed in '##...##' and so on

    $field_name = $matches[1];
    if ( !isset($this->current_row->{$field_name}) && substr($field_name,0,4) == "URL:" ) {
      $field_name = substr($field_name,4);
      $replacement = urlencode($this->current_row->{$field_name});
    }
    else {
      $replacement = (isset($this->current_row->{$field_name}) ? $this->current_row->{$field_name} : '');
    }
    dbg_error_log( "Browser", ":ValueReplacement: Replacing %s with %s", $field_name, $replacement);
    return $replacement;
  }


  /**
  * This method is used to render the browser as HTML.  If the query has
  * not yet been executed then this will call DoQuery to do so.
  *
  * The browser (including the title) will be displayed in a div with id="browser" so
  * that you can style '#browser tr.header', '#browser tr.totals' and so forth.
  *
  * @param string $title_tag The tag to use around the browser title (default 'h1')
  * @return string The rendered HTML fragment to display to the user.
  */
  function Render( $title_tag = null, $subtitle_tag = null ) {
    global $c;

    if ( !isset($this->Query) ) $this->DoQuery();  // Ensure the query gets run before we render!

    dbg_error_log( "Browser", ":Render: browser $this->Title");
    $html = $this->DivOpen;
    if ( $this->Title != "" ) {
      if ( !isset($title_tag) ) $title_tag = 'h1';
      $html .= "<$title_tag>$this->Title</$title_tag>\n";
    }
    if ( $this->SubTitle != "" ) {
      if ( !isset($subtitle_tag) ) $subtitle_tag = 'h2';
      $html .= "<$subtitle_tag>$this->SubTitle</$subtitle_tag>\n";
    }

    $html .= "<table id=\"browse_table\">\n";
    $html .= "<thead><tr class=\"header\">\n";
    foreach( $this->Columns AS $k => $column ) {
      $html .= $column->RenderHeader( $this->OrderField, $this->OrderDirection, $this->OrderBrowserKey, $this->ForcedOrder );
    }
    $html .= "</tr></thead>\n<tbody>";

    $rowanswers = array();
    while( $BrowserCurrentRow = $this->Query->Fetch() ) {

      // Work out the answers to any stuff that may be being substituted into the row start
      /** @TODO: We should deprecate this approach in favour of simply doing the ValueReplacement on field names */
      foreach( $this->BeginRowArgs AS $k => $fld ) {
        if ( isset($BrowserCurrentRow->{$fld}) ) {
          $rowanswers[$k] = $BrowserCurrentRow->{$fld};
        }
        else {
          switch( $fld ) {
            case '#even':
              $rowanswers[$k] = ($this->Query->rownum() % 2);
              break;
            default:
              $rowanswers[$k] = $fld;
          }
        }
      }
      // Start the row
      $row_html = vsprintf( preg_replace("/#@even@#/", ($this->Query->rownum() % 2), $this->BeginRow), $rowanswers);

      if ( isset($this->match_column) && isset($this->match_value) && $BrowserCurrentRow->{$this->match_column} == $this->match_value ) {
        $row_html .= call_user_func( $this->match_function, $BrowserCurrentRow );
      }
      else {
        // Each column
        foreach( $this->Columns AS $k => $column ) {
          $row_html .= $column->RenderValue( (isset($BrowserCurrentRow->{$column->Field})?$BrowserCurrentRow->{$column->Field}:'') );
          if ( isset($this->Totals[$column->Field]) ) {
            if ( isset($this->TotalFuncs[$column->Field]) && function_exists($this->TotalFuncs[$column->Field]) ) {
              // Run the amount through the callback function  $floatval = my_function( $row, $fieldval );
              $this->Totals[$column->Field] += $this->TotalFuncs[$column->Field]( $BrowserCurrentRow, $BrowserCurrentRow->{$column->Field} );
            }
            else {
              // Just add the amount
              $this->Totals[$column->Field] += doubleval( preg_replace( '/[^0-9.-]/', '', $BrowserCurrentRow->{$column->Field} ));
            }
          }
        }
      }

      // Finish the row
      $row_html .= preg_replace("/#@even@#/", ($this->Query->rownum() % 2), $this->CloseRow);
      $this->current_row = $BrowserCurrentRow;
      $html .= preg_replace_callback("/##([^#]+)##/", array( &$this, "ValueReplacement"), $row_html );
    }

    if ( count($this->Totals) > 0 ) {
      $BrowserCurrentRow = (object) "";
      $row_html = "<tr class=\"totals\">\n";
      foreach( $this->Columns AS $k => $column ) {
        if ( isset($this->Totals[$column->Field]) ) {
          $row_html .= $column->RenderValue( $this->Totals[$column->Field], "totals" );
        }
        else {
          $row_html .= $column->RenderValue( "" );
        }
      }
      $row_html .= "</tr>\n";
      $this->current_row = $BrowserCurrentRow;
      $html .= preg_replace_callback("/##([^#]+)##/", array( &$this, "ValueReplacement"), $row_html );
    }


    if ( count($this->ExtraRows) > 0 ) {
      foreach( $this->ExtraRows AS $k => $v ) {
        $BrowserCurrentRow = (object) $v;
        // Work out the answers to any stuff that may be being substituted into the row start
        foreach( $this->BeginRowArgs AS $k => $fld ) {
          if ( isset( $BrowserCurrentRow->{$fld} ) ) {
            $rowanswers[$k] = $BrowserCurrentRow->{$fld};
          }
          else {
            switch( $fld ) {
              case '#even':
                $rowanswers[$k] = ($this->Query->rownum() % 2);
                break;
              default:
                $rowanswers[$k] = $fld;
            }
          }
        }

        // Start the row
        $row_html = vsprintf( preg_replace("/#@even@#/", ($this->Query->rownum() % 2), $this->BeginRow), $rowanswers);

        if ( isset($this->match_column) && isset($this->match_value) && $BrowserCurrentRow->{$this->match_column} == $this->match_value ) {
          $row_html .= call_user_func( $this->match_function, $BrowserCurrentRow );
        }
        else {
          // Each column
          foreach( $this->Columns AS $k => $column ) {
            $row_html .= $column->RenderValue( (isset($BrowserCurrentRow->{$column->Field}) ? $BrowserCurrentRow->{$column->Field} : '') );
          }
        }

        // Finish the row
        $row_html .= preg_replace("/#@even@#/", ($this->Query->rownum() % 2), $this->CloseRow);
        $this->current_row = $BrowserCurrentRow;
        $html .= preg_replace_callback("/##([^#]+)##/", array( &$this, "ValueReplacement"), $row_html );
      }
    }

    $html .= "</tbody>\n</table>\n";
    $html .= $this->DivClose;

    return $html;
  }

}
