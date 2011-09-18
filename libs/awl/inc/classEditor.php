<?php
/**
* Class for editing a record using a templated form.
*
* @package   awl
* @subpackage   classEditor
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/

require_once("DataUpdate.php");
require_once("DataEntry.php");

/**
* A class for the fields in the editor
* @package   awl
*/
class EditorField
{
  var $Field;
  var $Sql;
  var $Value;
  var $Attributes;
  var $LookupSql;
  var $OptionList;

  function __construct( $field, $sql="", $lookup_sql="" ) {
    global $session;
    $this->Field      = $field;
    $this->Sql        = $sql;
    $this->LookupSql  = $lookup_sql;
    $this->Attributes = array();
  }

  function Set($value) {
    $this->Value = $value;
  }

  function SetSql( $sql ) {
    $this->Sql  = $sql;
  }

  function SetLookup( $lookup_sql ) {
    $this->LookupSql  = $lookup_sql;
  }

  function SetOptionList( $options, $current = null, $parameters = null) {
    if ( gettype($options) == 'array' ) {
      $this->OptionList = '';

      if ( is_array($parameters) ) {
        if ( isset($parameters['maxwidth']) ) $maxwidth = max(4,intval($parameters['maxwidth']));
        if ( isset($parameters['translate']) ) $translate = true;
      }

      foreach( $options AS $k => $v ) {
        if (is_array($current)) {
          $selected = ( ( in_array($k,$current,true) || in_array($v,$current,true)) ? ' selected="selected"' : '' );
        }
        else {
          $selected = ( ( "$k" == "$current" || "$v" == "$current" ) ? ' selected="selected"' : '' );
        }
        if ( isset($translate) ) $v = translate( $v );
        if ( isset($maxwidth) ) $v = substr( $v, 0, $maxwidth);
        $this->OptionList .= "<option value=\"".htmlspecialchars($k)."\"$selected>".htmlspecialchars($v)."</option>";
      }
    }
    else {
      $this->OptionList = $options;
    }
  }

  function GetTarget() {
    if ( $this->Sql == "" ) return $this->Field;
    return "$this->Sql AS $this->Field";
  }

  function AddAttribute( $k, $v ) {
    $this->Attributes[$k] = $v;
  }

  function RenderAttributes() {
    $attributes = "";
    if ( count($this->Attributes) == 0 ) return $attributes;
    foreach( $this->Attributes AS $k => $v ) {
      $attributes .= " $k=\"" . str_replace('"', '&#39;', $v) . '"';
    }
    return $attributes;
  }

  
  
}



/**
* The class for the Editor form in full
* @package awl
*/
class Editor
{
  var $Title;
  var $Action;
  var $Fields;
  var $OrderedFields;
  var $BaseTable;
  var $Joins;
  var $Where;
  var $NewWhere;
  var $Order;
  var $Limit;
  var $Query;
  var $Template;
  var $RecordAvailable;
  var $Record;
  var $SubmitName;
  var $Id;

  function __construct( $title = "", $fields = null ) {
    global $c, $session, $form_id_increment;
    $this->Title = $title;
    $this->Order = "";
    $this->Limit = "";
    $this->Template = "";
    $this->RecordAvailable = false;
    $this->SubmitName = 'submit';
    $form_id_increment = (isset($form_id_increment)? ++$form_id_increment : 1);
    $this->Id = 'editor_'.$form_id_increment;

    if ( isset($fields) ) {
      if ( is_array($fields) ) {
        foreach( $fields AS $k => $v ) {
          $this->AddField($v);
        }
      }
      else if ( is_string($fields) ) {
        // We've been given a table name, so get all fields for it.
        $this->BaseTable = $fields;
        $field_list = get_fields($fields);
        foreach( $field_list AS $k => $v ) {
          $this->AddField($k);
        }
      }
    }
    @dbg_error_log( 'editor', 'DBG: New editor called %s', $title);
  }

  function &AddField( $field, $sql="", $lookup_sql="" ) {
    $this->Fields[$field] = new EditorField( $field, $sql, $lookup_sql );
    $this->OrderedFields[] = $field;
    return $this->Fields[$field];
  }

  function SetSql( $field, $sql ) {
    $this->Fields[$field]->SetSql( $sql );
  }

  function SetLookup( $field, $lookup_sql ) {
    if (is_object($this->Fields[$field])) {
      $this->Fields[$field]->SetLookup( $lookup_sql );
    }
  }

  /**
   * Gets the value of a field in the record currently assigned to this editor.
   * @param string $value_field_name
   */
  function Value( $value_field_name ) {
    if ( !isset($this->Record->{$value_field_name}) ) return null;
    return $this->Record->{$value_field_name};
  }

  /**
   * Assigns the value of a field in the record currently associated with this editor.
   * @param string $value_field_name
   * @param string $new_value
   */
  function Assign( $value_field_name, $new_value ) {
    if ( !isset($this->Record) ) $this->Record = (object) array();
    $this->Record->{$value_field_name} = $new_value;
  }

  /**
   * Sets or returns the form ID used for differentiating this form from others in the page.
   * @param string $id
   */
  function Id( $id = null ) {
    if ( isset($id) ) $this->Id = preg_replace( '#[^a-z0-9_+-]#', '', $id);
    return $this->Id;
  }

  function SetOptionList( $field, $options, $current = null, $parameters = null) {
    $this->Fields[$field]->SetOptionList( $options, $current, $parameters );
  }

  function AddAttribute( $field, $k, $v ) {
    $this->Fields[$field]->AddAttribute($k,$v);

  }

  function SetBaseTable( $base_table ) {
    $this->BaseTable = $base_table;
  }

  function SetJoins( $join_list ) {
    $this->Joins = $join_list;
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


  function SetSubmitName( $new_submit ) {
    $this->SubmitName = $new_submit;
  }

  function IsSubmit() {
    return isset($_POST[$this->SubmitName]);
  }

  function IsUpdate() {
    $is_update = $this->Available();
    if ( isset( $_POST['_editor_action']) && isset( $_POST['_editor_action'][$this->Id]) ) {
      $is_update = ( $_POST['_editor_action'][$this->Id] == 'update' );
      @dbg_error_log( 'editor', 'Checking update: %s => %d', $_POST['_editor_action'][$this->Id], $is_update );
    }
    return $is_update;
  }

  function IsCreate() {
    return ! $this->IsUpdate();
  }

  function SetWhere( $where_clause ) {
    $this->Where = $where_clause;
  }

  function WhereNewRecord( $where_clause ) {
    $this->NewWhere = $where_clause;
  }

  function MoreWhere( $operator, $more_where ) {
    if ( $this->Where == "" ) {
      $this->Where = $more_where;
      return;
    }
    $this->Where = "$this->Where $operator $more_where";
  }

  function AndWhere( $more_where ) {
    $this->MoreWhere("AND",$more_where);
  }

  function OrWhere( $more_where ) {
    $this->MoreWhere("OR",$more_where);
  }

  function SetTemplate( $template ) {
    $this->Template = $template;
  }

  function Layout( $template ) {
    if ( strstr( $template, '##form##' ) === false && stristr( $template, '<form' ) === false ) $template = '##form##' . $template;
    if ( stristr( $template, '</form' ) === false ) $template .= '</form>';
    $this->Template = $template;
  }

  function Available( ) {
    return $this->RecordAvailable;
  }

  function SetRecord( $row ) {
    $this->Record = $row;
    $this->RecordAvailable = is_object($this->Record);
    return $this->Record;
  }

  /**
  * Set some particular values to the ones from the array.
  *
  * @param array $values An array of fieldname / value pairs
  */
  function Initialise( $values ) {
    $this->RecordAvailable = false;
    if ( !isset($this->Record) ) $this->Record = (object) array();
    foreach( $values AS $fname => $value ) {
      $this->Record->{$fname} = $value;
    }
  }


  /**
  * This will assign $_POST values to the internal Values object for each
  * field that exists in the Fields array.
  */
  function PostToValues( $prefix = '' ) {
    foreach ( $this->Fields AS $fname => $fld ) {
      @dbg_error_log( 'editor', ":PostToValues: %s => %s", $fname, $_POST["$prefix$fname"] );
      if ( isset($_POST[$prefix.$fname]) ) {
        $this->Record->{$fname} = $_POST[$prefix.$fname];
        @dbg_error_log( 'editor', ":PostToValues: %s => %s", $fname, $_POST["$prefix$fname"] );
      }
    }
  }

  function GetRecord( $where = "" ) {
    global $session;
    $target_fields = "";
    foreach( $this->Fields AS $k => $column ) {
      if ( $target_fields != "" ) $target_fields .= ", ";
      $target_fields .= $column->GetTarget();
    }
    if ( $where == "" ) $where = $this->Where;
    $sql = sprintf( "SELECT %s FROM %s %s WHERE %s %s %s",
             $target_fields, $this->BaseTable, $this->Joins, $where, $this->Order, $this->Limit);
    $this->Query = new AwlQuery( $sql );
    @dbg_error_log( 'editor', "DBG: EditorGetQry: %s", $sql );
    if ( $this->Query->Exec("Browse:$this->Title:DoQuery") ) {
      $this->Record = $this->Query->Fetch();
      $this->RecordAvailable = is_object($this->Record);
    }
    if ( !$this->RecordAvailable ) {
      $this->Record = (object) array();
    }
    return $this->Record;
  }


  /**
  * Replace parts into the form template.
  * @param array $matches The matches found which preg_replace_callback is calling us for.
  * @return string What we want to replace this match with.
  */
  function ReplaceEditorPart($matches)
  {
    global $session;

    // $matches[0] is the complete match
    switch( $matches[0] ) {
      case "##form##": /** @todo It might be nice to construct a form ID */
        return sprintf('<form method="POST" enctype="multipart/form-data" class="editor" id="%s">', $this->Id);
      case "##submit##":
        $action =  ( $this->RecordAvailable ? 'update' : 'insert' );
        $submittype = ($this->RecordAvailable ? translate('Apply Changes') : translate('Create'));
        return sprintf('<input type="hidden" name="_editor_action[%s]" value="%s"><input type="submit" class="submit" name="%s" value="%s">',
                                                              $this->Id, $action,                           $this->SubmitName, $submittype );
    }

    // $matches[1] the match for the first subpattern
    // enclosed in '(...)' and so on
    $field_name = $matches[1];
    $what_part = $matches[3];
    $part3 = (isset($matches[5]) ? $matches[5] : null);

    $value_field_name = $field_name;
    if ( substr($field_name,0,4) == 'xxxx' ) {
        // Sometimes we will prepend 'xxxx' to the field name so that the field
        // name differs from the column name in the database.  We also remove it
        // when it's submitted.
        $value_field_name = substr($field_name,4);
    }

    $attributes = "";
    if ( isset($this->Fields[$field_name]) && is_object($this->Fields[$field_name]) ) {
      $field = $this->Fields[$field_name];
      $attributes = $field->RenderAttributes();
    }
    $field_value = (isset($this->Record->{$value_field_name}) ? $this->Record->{$value_field_name} : null);

    switch( $what_part ) {
      case "options":
        $currval = $part3;
        if ( ! isset($currval) && isset($field_value) )
          $currval = $field_value;
        if ( isset($field->OptionList) && $field->OptionList != "" ) {
          $option_list = $field->OptionList;
        }
        else {
          @dbg_error_log( 'editor', "DBG: Current=%s, OptionQuery: %s", $currval, $field->LookupSql );
          $opt_qry = new AwlQuery( $field->LookupSql );
          $option_list = EntryField::BuildOptionList($opt_qry, $currval, "FieldOptions: $field_name" );
          $field->OptionList = $option_list;
        }
        return $option_list;
      case "select":
        $currval = $part3;
        if ( ! isset($currval) && isset($field_value) )
          $currval = $field_value;
        if ( isset($field->OptionList) && $field->OptionList != "" ) {
          $option_list = $field->OptionList;
        }
        else {
          @dbg_error_log( 'editor', 'DBG: Current=%s, OptionQuery: %s', $currval, $field->LookupSql );
          $opt_qry = new AwlQuery( $field->LookupSql );
          $option_list = EntryField::BuildOptionList($opt_qry, $currval, 'FieldOptions: '.$field_name );
          $field->OptionList = $option_list;
        }
        return '<select class="entry" name="'.$field_name.'"'.$attributes.'>'.$option_list.'</select>';
      case "checkbox":
        if ( $field_value === true ) {
          $checked = ' CHECKED';
        }
        else {
          switch ( $field_value ) {
            case 'f':
            case 'off':
            case 'false':
            case '':
            case '0':
              $checked = "";
              break;
  
            default:
              $checked = ' CHECKED';
          }
        }
        return '<input type="hidden" value="off" name="'.$field_name.'"><input class="entry" type="checkbox" value="on" name="'.$field_name.'"'.$checked.$attributes.'>';
      case "input":
        $size = (isset($part3) ? $part3 : 6);
        return "<input class=\"entry\" value=\"".htmlspecialchars($field_value)."\" name=\"$field_name\" size=\"$size\"$attributes>";
      case "file":
        $size = (isset($part3) ? $part3 : 30);
        return "<input type=\"file\" class=\"entry\" value=\"".htmlspecialchars($field_value)."\" name=\"$field_name\" size=\"$size\"$attributes>";
      case "money":
        $size = (isset($part3) ? $part3 : 8);
        return "<input class=\"money\" value=\"".htmlspecialchars(sprintf("%0.2lf",$field_value))."\" name=\"$field_name\" size=\"$size\"$attributes>";
      case "date":
        $size = (isset($part3) ? $part3 : 10);
        return "<input class=\"date\" value=\"".htmlspecialchars($field_value)."\" name=\"$field_name\" size=\"$size\"$attributes>";
      case "textarea":
        list( $cols, $rows ) = explode( 'x', $part3);
        return "<textarea class=\"entry\" name=\"$field_name\" rows=\"$rows\" cols=\"$cols\"$attributes>".htmlspecialchars($field_value)."</textarea>";
      case "hidden":
        return sprintf( "<input type=\"hidden\" value=\"%s\" name=\"$field_name\">", htmlspecialchars($field_value) );
      case "password":
        return sprintf( "<input type=\"password\" value=\"%s\" name=\"$field_name\" size=\"10\">", htmlspecialchars($part3) );
      case "encval":
      case "enc":
        return htmlspecialchars($field_value);
      case "submit":
        $action =  ( $this->RecordAvailable ? 'update' : 'insert' );
        return sprintf('<input type="hidden" name="_editor_action[%s]" value="%s"><input type="submit" class="submit" name="%s" value="%s">',
                                                              $this->Id, $action,                           $this->SubmitName, $value_field_name );
      default:
        return str_replace( "\n", "<br />", $field_value );
    }
  }

  /**
  * Render the templated component.  The heavy lifting is done by the callback...
  */
  function Render( $title_tag = null ) {
    @dbg_error_log( 'editor', "classEditor", "Rendering editor $this->Title" );
    if ( $this->Template == "" ) $this->DefaultTemplate();

    $html = sprintf('<div class="editor" id="%s">', $this->Id);
    if ( isset($this->Title) && $this->Title != "" ) {
      if ( !isset($title_tag) ) $title_tag = 'h1';
      $html = "<$title_tag>$this->Title</$title_tag>\n";
    }

    // Stuff like "##fieldname.part## gets converted to the appropriate value
    $replaced = preg_replace_callback("/##([^#.]+)(\.([^#.]+))?(\.([^#.]+))?##/", array(&$this, "ReplaceEditorPart"), $this->Template );
    $html .= $replaced;

    $html .= '</div>';
    return $html;
  }

  /**
  * Write the record
  * @param boolean $is_update Tell the write whether it's an update or insert.  Hopefully it
  * should be able to figure it out though.
  */
  function Write( $is_update = null ) {
    global $c, $component;

    @dbg_error_log( 'editor', 'DBG: Writing editor %s', $this->Title);

    if ( !isset($is_update) ) {
      if ( isset( $_POST['_editor_action']) && isset( $_POST['_editor_action'][$this->Id]) ) {
        $is_update = ( $_POST['_editor_action'][$this->Id] == 'update' );
      }
      else {
        /** @todo Our old approach will not work for translation.  We need to have a hidden field
        * containing the submittype.  Probably we should add placeholders like ##form##, ##script## etc.
        * which the editor can use for internal purposes.
        */
        // Then we dvine the action by looking at the submit button value...
        $is_update = preg_match( '/(save|update|apply)/i', $_POST[$this->SubmitName] );
        dbg_error_log('WARN', $_SERVER['REQUEST_URI']. " is using a deprecated method for controlling insert/update" );
      }
    }
    $this->Action = ( $is_update ? "update" : "create" );
    $qry = new AwlQuery( sql_from_post( $this->Action, $this->BaseTable, "WHERE ".$this->Where ) );
    if ( !$qry->Exec("Editor::Write") ) {
      $c->messages[] = "ERROR: $qry->errorstring";
      return 0;
    }
    if ( $this->Action == "create" && isset($this->NewWhere) ) {
      $this->GetRecord($this->NewWhere);
    }
    else {
      $this->GetRecord($this->Where);
    }
    return $this->Record;
  }
}

