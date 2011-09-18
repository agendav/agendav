<?php
/**
* Classes to handle validation of form data.
*
* @package   awl
* @subpackage   Validation
* @author    Emily Mossman <emily@mcmillan.net.nz>
* @copyright Catalyst IT Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
require_once("AWLUtilities.php");

/**
* Rules used for validation of form fields.
* @package   awl
*/
class Validation
{
  /**#@+
  * @access private
  */
  /**
  * List of rules for validation
  * @var rules
  */
  var $rules = array();

  /**
  * The javascript function name to call onsubmit of the form
  * @var func_name
  */
  var $func_name = "";

  /**#@-*/

  /**
  * Initialise a new validation.
  * @param string $func_name The javascript function name to call onsubmit of the form
  */
  function Validation($func_name)
  {
    $this->func_name = $func_name;
  }


  /**
  * Adds a validation rule for a specific field upon submission of the form.
  * You must call RenderRules below RenderFields when outputing the page
  * @param string $fieldname The name of the field.
  * @param string $error_message The message to display on unsuccessful validation.
  * @param string $function_name The function to call to validate the field
  */
  function AddRule( $fieldname, $error_message, $function_name )
  {
    $this->rules[] = array($fieldname, $error_message, $function_name );
  }

  /**
  * Returns the javascript for form validation using the rules.
  * @param string $onsubmit The name of the function called on submission of the form.
  * @param string $prefix Optional prefix for form fields.
  * @return string HTML/Javascript for form validation.
  */
  function RenderJavascript($prefix = "")
  {
    if(! count($this->rules) ) return "";

    $html = <<<EOHTML
<script language="JavaScript">
function $this->func_name(form)
{
  var error_message = "";\n
EOHTML;

    foreach($this->rules as $rule) {
      list($fieldname, $error_message, $function_name) = $rule;

    $html .= <<<EOHTML
if(!$function_name(form.$prefix$fieldname)) error_message += "$error_message\\n";
EOHTML;
    }

    $html .= <<<EOHTML
if(error_message == "") return true;
alert("Errors:"+"\\n"+error_message);
return false;
}
</script>
EOHTML;

    return $html;
  }

  /**
  * Validates the form according to it's rules.
  * @param object $object The data object that requires form validation.
  * @return boolean True if the validation succeeded.
  */
  function Validate($object)
  {
    global $c;

    if(! count($this->rules) ) return;

    $valid = true;

    foreach($this->rules as $rule) {
      list($fieldname, $error_message, $function_name) = $rule;

      if (!$this->$function_name($object->Get($fieldname))) {
        $valid = false;
        $c->messages[] = $error_message;
      }

    }

    return $valid;
  }

///////////////////////////
// VALIDATION FUNCTIONS
///////////////////////////

  /**
  * Checks if a string is empty
  * @param string $field_string The field value that is being checked.
  * @return boolean True if the string is not empty.
  */
  function not_empty($field_string)
  {
    return ($field_string != "");
  }

  /**
  * Checks that a string is not empty or zero
  * @param string $select_string The select value that is being checked.
  * @return boolean True if the string is not empty or equal to 0.
  */
  function selected($field_string)
  {
    return (!($field_string == "" || $field_string == "0"));
  }

  /**
  * Check that the given string is a positive dollar amount.
  * Use not_empty first if string is required.
  * @param string $field_string The amount to be checked.
  * @return boolean Returns true if the given string is a positive dollar amount.
  */
  function positive_dollars($field_string)
  {
   if(!$field_string) return true;
   if( preg_match('/^\$?[0-9]*\.?[0-9]?[0-9]?$/', $field_string) ) {
     $field_string = preg_replace("/\$/", "", $field_string);
     $field_string = preg_replace("/\./", "", $field_string);
     if( intval($field_string) > 0 ) return true;
   }
   return false;
  }

  /**
  * Check that the given string is a positive integer.
  * Use not_empty first if string is required.
  * @param string $field_string The amount to be checked.
  * @return boolean Returns true if the given string is a positive integer.
  */
  function positive_integer($field_string)
  {
   if(!$field_string) return true;
    return ( preg_match('/^[0-9]*$/', $field_string) );
  }

  /**
  * Check that the given string is a valid email address.
  * Use not_empty first if string is required.
  * @param string $field_string The string to be checked.
  * @return boolean Returns true if the given string is a valid email address.
  */
  function valid_email_format($field_string)
  {
   if(!$field_string) return true;
   // Anything printable, followed by between 1 & 5 valid domain components, with a TLD to finish
   $pattern = "/^[[:print:]]+@([a-z0-9][a-z0-9-]*\.){1,5}[a-z]{2,5}$/i";
   return (preg_match($pattern, $field_string));
  }

  /**
  * Check that the given string matches the user's date format.
  * Use not_empty first if string is required.
  * @param string $field_string The string to be checked.
  * @return boolean Returns true if the given string matches the user's date format from session.
  */
  function valid_date_format($field_string)
  {
   global $session;

   if(!$field_string) return true;

   switch($session->date_format_type) {
      case 'J':
        if (!preg_match('/^([0-9]{4})[\/\-]([0-9]{1,2})[\/\-]([0-9]{1,2})$/', $field_string, $regs)) return false;
        $day = intval($regs[3]);
        $month = intval($regs[2]);
        $year = intval($regs[1]);
        break;

      case 'U':
        if (!preg_match('/^([0-9]{1,2})[\/\-]([0-9]{1,2})[\/\-]([0-9]{4})$/', $field_string, $regs)) return false;
        $day = intval($regs[2]);
        $month = intval($regs[1]);
        $year = intval($regs[3]);
        break;

      case 'E':
      default:
        if (!preg_match('/^([0-9]{1,2})[\/\-]([0-9]{1,2})[\/\-]([0-9]{4})$/', $field_string, $regs)) return false;
        $day = intval($regs[1]);
        $month = intval($regs[2]);
        $year = intval($regs[3]);
   }
   return (checkdate ($month, $day, $year));
  }
}

