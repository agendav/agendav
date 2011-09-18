<?php
/**
* A Class for handling iCalendar data.
*
* When parsed the underlying structure is roughly as follows:
*
*   iCalendar( array(iCalComponent), array(iCalProp) )
*
* each iCalComponent is similarly structured:
*
*   iCalComponent( array(iCalComponent), array(iCalProp) )
*
* Once parsed, $ical->component will point to the wrapping VCALENDAR component of
* the iCalendar.  This will be fine for simple iCalendar usage as sampled below,
* but more complex iCalendar such as a VEVENT with RRULE which has repeat overrides
* will need quite a bit more thought to process correctly.
*
* @example
* To create a new iCalendar from several data values:
*   $ical = new iCalendar( array('DTSTART' => $dtstart, 'SUMMARY' => $summary, 'DURATION' => $duration ) );
*
* @example
* To render it as an iCalendar string:
*   echo $ical->Render();
*
* @example
* To render just the VEVENTs in the iCalendar with a restricted list of properties:
*   echo $ical->Render( false, 'VEVENT', array( 'DTSTART', 'DURATION', 'DTEND', 'RRULE', 'SUMMARY') );
*
* @example
* To parse an existing iCalendar string for manipulation:
*   $ical = new iCalendar( array('icalendar' => $icalendar_text ) );
*
* @example
* To clear any 'VALARM' components in an iCalendar object
*   $ical->component->ClearComponents('VALARM');
*
* @example
* To replace any 'RRULE' property in an iCalendar object
*   $ical->component->SetProperties( 'RRULE', $rrule_definition );
*
* @package awl
* @subpackage iCalendar
* @author Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*
*/
require_once('XMLElement.php');
require_once('AwlQuery.php');

/**
* A Class for representing properties within an iCalendar
*
* @package awl
*/
class iCalProp {
  /**#@+
   * @access private
   */

  /**
   * The name of this property
   *
   * @var string
   */
  var $name;

  /**
   * An array of parameters to this property, represented as key/value pairs.
   *
   * @var array
   */
  var $parameters;

  /**
   * The value of this property.
   *
   * @var string
   */
  var $content;

  /**
   * The original value that this was parsed from, if that's the way it happened.
   *
   * @var string
   */
  var $rendered;

  /**#@-*/

  /**
   * The constructor parses the incoming string, which is formatted as per RFC2445 as a
   *   propname[;param1=pval1[; ... ]]:propvalue
   * however we allow ourselves to assume that the RFC2445 content unescaping has already
   * happened when iCalComponent::ParseFrom() called iCalComponent::UnwrapComponent().
   *
   * @param string $propstring The string from the iCalendar which contains this property.
   */
  function iCalProp( $propstring = null ) {
    $this->name = "";
    $this->content = "";
    $this->parameters = array();
    unset($this->rendered);
    if ( $propstring != null && gettype($propstring) == 'string' ) {
      $this->ParseFrom($propstring);
    }
  }


  /**
   * The constructor parses the incoming string, which is formatted as per RFC2445 as a
   *   propname[;param1=pval1[; ... ]]:propvalue
   * however we allow ourselves to assume that the RFC2445 content unescaping has already
   * happened when iCalComponent::ParseFrom() called iCalComponent::UnwrapComponent().
   *
   * @param string $propstring The string from the iCalendar which contains this property.
   */
  function ParseFrom( $propstring ) {
    $this->rendered = (strlen($propstring) < 72 ? $propstring : null);  // Only pre-rendered if we didn't unescape it

    $unescaped = preg_replace( '{\\\\[nN]}', "\n", $propstring);
 
    // Split into two parts on : which is not preceded by a \
    list( $start, $values) = preg_split( '{(?<!\\\\):}', $unescaped, 2);
    $this->content = preg_replace( "/\\\\([,;:\"\\\\])/", '$1', $values);

    // Split on ; which is not preceded by a \
    $parameters = preg_split( '{(?<!\\\\);}', $start);

    $parameters = explode(';',$start);
    $this->name = array_shift( $parameters );
    $this->parameters = array();
    foreach( $parameters AS $k => $v ) {
      $pos = strpos($v,'=');
      $name = substr( $v, 0, $pos);
      $value = substr( $v, $pos + 1);
      $this->parameters[$name] = $value;
    }
//    dbg_error_log('iCalendar', " iCalProp::ParseFrom found '%s' = '%s' with %d parameters", $this->name, substr($this->content,0,200), count($this->parameters) );
  }


  /**
   * Get/Set name property
   *
   * @param string $newname [optional] A new name for the property
   *
   * @return string The name for the property.
   */
  function Name( $newname = null ) {
    if ( $newname != null ) {
      $this->name = $newname;
      if ( isset($this->rendered) ) unset($this->rendered);
//      dbg_error_log('iCalendar', " iCalProp::Name(%s)", $this->name );
    }
    return $this->name;
  }


  /**
   * Get/Set the content of the property
   *
   * @param string $newvalue [optional] A new value for the property
   *
   * @return string The value of the property.
   */
  function Value( $newvalue = null ) {
    if ( $newvalue != null ) {
      $this->content = $newvalue;
      if ( isset($this->rendered) ) unset($this->rendered);
    }
    return $this->content;
  }


  /**
   * Get/Set parameters in their entirety
   *
   * @param array $newparams An array of new parameter key/value pairs
   *
   * @return array The current array of parameters for the property.
   */
  function Parameters( $newparams = null ) {
    if ( $newparams != null ) {
      $this->parameters = $newparams;
      if ( isset($this->rendered) ) unset($this->rendered);
    }
    return $this->parameters;
  }


  /**
   * Test if our value contains a string
   *
   * @param string $search The needle which we shall search the haystack for.
   *
   * @return string The name for the property.
   */
  function TextMatch( $search ) {
    if ( isset($this->content) ) return strstr( $this->content, $search );
    return false;
  }


  /**
   * Get the value of a parameter
   *
   * @param string $name The name of the parameter to retrieve the value for
   *
   * @return string The value of the parameter
   */
  function GetParameterValue( $name ) {
    if ( isset($this->parameters[$name]) ) return $this->parameters[$name];
  }

  /**
   * Set the value of a parameter
   *
   * @param string $name The name of the parameter to set the value for
   *
   * @param string $value The value of the parameter
   */
  function SetParameterValue( $name, $value ) {
    if ( isset($this->rendered) ) unset($this->rendered);
    $this->parameters[$name] = $value;
  }

  /**
  * Render the set of parameters as key1=value1[;key2=value2[; ...]] with
  * any colons or semicolons escaped.
  */
  function RenderParameters() {
    $rendered = "";
    foreach( $this->parameters AS $k => $v ) {
      $escaped = preg_replace( "/([;:\"])/", '\\\\$1', $v);
      $rendered .= sprintf( ";%s=%s", $k, $escaped );
    }
    return $rendered;
  }


  /**
  * Render a suitably escaped RFC2445 content string.
  */
  function Render() {
    // If we still have the string it was parsed in from, it hasn't been screwed with
    // and we can just return that without modification.
    if ( isset($this->rendered) ) return $this->rendered;

    $property = preg_replace( '/[;].*$/', '', $this->name );
    $escaped = $this->content;
    switch( $property ) {
        /** Content escaping does not apply to these properties culled from RFC2445 */
      case 'ATTACH':                case 'GEO':                       case 'PERCENT-COMPLETE':      case 'PRIORITY':
      case 'DURATION':              case 'FREEBUSY':                  case 'TZOFFSETFROM':          case 'TZOFFSETTO':
      case 'TZURL':                 case 'ATTENDEE':                  case 'ORGANIZER':             case 'RECURRENCE-ID':
      case 'URL':                   case 'EXRULE':                    case 'SEQUENCE':              case 'CREATED':
      case 'RRULE':                 case 'REPEAT':                    case 'TRIGGER':
        break;

      case 'COMPLETED':             case 'DTEND':
      case 'DUE':                   case 'DTSTART':
      case 'DTSTAMP':               case 'LAST-MODIFIED':
      case 'CREATED':               case 'EXDATE':
      case 'RDATE':
        if ( isset($this->parameters['VALUE']) && $this->parameters['VALUE'] == 'DATE' ) {
          $escaped = substr( $escaped, 0, 8);
        }
        break;

        /** Content escaping applies by default to other properties */
      default:
        $escaped = str_replace( '\\', '\\\\', $escaped);
        $escaped = preg_replace( '/\r?\n/', '\\n', $escaped);
        $escaped = preg_replace( "/([,;\"])/", '\\\\$1', $escaped);
    }
    $property = sprintf( "%s%s:", $this->name, $this->RenderParameters() );
    if ( (strlen($property) + strlen($escaped)) <= 72 ) {
      $this->rendered = $property . $escaped;
    }
    else if ( (strlen($property) + strlen($escaped)) > 72 && (strlen($property) < 72) && (strlen($escaped) < 72) ) {
      $this->rendered = $property . "\r\n " . $escaped;
    }
    else {
      $this->rendered = preg_replace( '/(.{72})/u', '$1'."\r\n ", $property . $escaped );
    }
    return $this->rendered;
  }

}


/**
* A Class for representing components within an iCalendar
*
* @package awl
*/
class iCalComponent {
  /**#@+
   * @access private
   */

  /**
   * The type of this component, such as 'VEVENT', 'VTODO', 'VTIMEZONE', etc.
   *
   * @var string
   */
  var $type;

  /**
   * An array of properties, which are iCalProp objects
   *
   * @var array
   */
  var $properties;

  /**
   * An array of (sub-)components, which are iCalComponent objects
   *
   * @var array
   */
  var $components;

  /**
   * The rendered result (or what was originally parsed, if there have been no changes)
   *
   * @var array
   */
  var $rendered;

  /**#@-*/

  /**
  * A basic constructor
  */
  function iCalComponent( $content = null ) {
    $this->type = "";
    $this->properties = array();
    $this->components = array();
    $this->rendered = "";
    if ( $content != null && (gettype($content) == 'string' || gettype($content) == 'array') ) {
      $this->ParseFrom($content);
    }
  }


  /**
  * Apply standard properties for a VCalendar
  * @param array $extra_properties Key/value pairs of additional properties
  */
  function VCalendar( $extra_properties = null ) {
    $this->SetType('VCALENDAR');
    $this->AddProperty('PRODID', '-//davical.org//NONSGML AWL Calendar//EN');
    $this->AddProperty('VERSION', '2.0');
    $this->AddProperty('CALSCALE', 'GREGORIAN');
    if ( is_array($extra_properties) ) {
      foreach( $extra_properties AS $k => $v ) {
        $this->AddProperty($k,$v);
      }
    }
  }

  /**
  * Collect an array of all parameters of our properties which are the specified type
  * Mainly used for collecting the full variety of references TZIDs
  */
  function CollectParameterValues( $parameter_name ) {
    $values = array();
    foreach( $this->components AS $k => $v ) {
      $also = $v->CollectParameterValues($parameter_name);
      $values = array_merge( $values, $also );
    }
    foreach( $this->properties AS $k => $v ) {
      $also = $v->GetParameterValue($parameter_name);
      if ( isset($also) && $also != "" ) {
//        dbg_error_log( 'iCalendar', "::CollectParameterValues(%s) : Found '%s'", $parameter_name, $also);
        $values[$also] = 1;
      }
    }
    return $values;
  }


  /**
  * Parse the text $content into sets of iCalProp & iCalComponent within this iCalComponent
  * @param string $content The raw RFC2445-compliant iCalendar component, including BEGIN:TYPE & END:TYPE
  */
  function ParseFrom( $content ) {
    $this->rendered = $content;
    $content = $this->UnwrapComponent($content);

    $type = false;
    $subtype = false;
    $finish = null;
    $subfinish = null;

    $length = strlen($content);
    $linefrom = 0;
    while( $linefrom < $length ) {
      $lineto = strpos( $content, "\n", $linefrom );
      if ( $lineto === false ) {
        $lineto = strpos( $content, "\r", $linefrom );
      }
      if ( $lineto > 0 ) {
        $line = substr( $content, $linefrom, $lineto - $linefrom);
        $linefrom = $lineto + 1;
      }
      else {
        $line = substr( $content, $linefrom );
        $linefrom = $length;
      }
      if ( preg_match('/^\s*$/', $line ) ) continue;
      $line = rtrim( $line, "\r\n" );
//      dbg_error_log( 'iCalendar',  "::ParseFrom: Parsing line: $line");

      if ( $type === false ) {
        if ( preg_match( '/^BEGIN:(.+)$/', $line, $matches ) ) {
          // We have found the start of the main component
          $type = $matches[1];
          $finish = "END:$type";
          $this->type = $type;
          dbg_error_log( 'iCalendar', "::ParseFrom: Start component of type '%s'", $type);
        }
        else {
          dbg_error_log( 'iCalendar', "::ParseFrom: Ignoring crap before start of component: $line");
          // unset($lines[$k]);  // The content has crap before the start
          if ( $line != "" ) $this->rendered = null;
        }
      }
      else if ( $type == null ) {
        dbg_error_log( 'iCalendar', "::ParseFrom: Ignoring crap after end of component");
        if ( $line != "" ) $this->rendered = null;
      }
      else if ( $line == $finish ) {
        dbg_error_log( 'iCalendar', "::ParseFrom: End of component");
        $type = null;  // We have reached the end of our component
      }
      else {
        if ( $subtype === false && preg_match( '/^BEGIN:(.+)$/', $line, $matches ) ) {
          // We have found the start of a sub-component
          $subtype = $matches[1];
          $subfinish = "END:$subtype";
          $subcomponent = $line . "\r\n";
          dbg_error_log( 'iCalendar', "::ParseFrom: Found a subcomponent '%s'", $subtype);
        }
        else if ( $subtype ) {
          // We are inside a sub-component
          $subcomponent .= $this->WrapComponent($line);
          if ( $line == $subfinish ) {
            dbg_error_log( 'iCalendar', "::ParseFrom: End of subcomponent '%s'", $subtype);
            // We have found the end of a sub-component
            $this->components[] = new iCalComponent($subcomponent);
            $subtype = false;
          }
//          else
//            dbg_error_log( 'iCalendar', "::ParseFrom: Inside a subcomponent '%s'", $subtype );
        }
        else {
//          dbg_error_log( 'iCalendar', "::ParseFrom: Parse property of component");
          // It must be a normal property line within a component.
          $this->properties[] = new iCalProp($line);
        }
      }
    }
  }


  /**
    * This unescapes the (CRLF + linear space) wrapping specified in RFC2445. According
    * to RFC2445 we should always end with CRLF but the CalDAV spec says that normalising
    * XML parsers often muck with it and may remove the CR.  We accept either case.
    */
  function UnwrapComponent( $content ) {
    return preg_replace('/\r?\n[ \t]/', '', $content );
  }

  /**
    * This imposes the (CRLF + linear space) wrapping specified in RFC2445. According
    * to RFC2445 we should always end with CRLF but the CalDAV spec says that normalising
    * XML parsers often muck with it and may remove the CR.  We output RFC2445 compliance.
    *
    * In order to preserve pre-existing wrapping in the component, we split the incoming
    * string on line breaks before running wordwrap over each component of that.
    */
  function WrapComponent( $content ) {
    $strs = preg_split( "/\r?\n/", $content );
    $wrapped = "";
    foreach ($strs as $str) {
      $wrapped .= preg_replace( '/(.{72})/u', '$1'."\r\n ", $str ) ."\r\n";
    }
    return $wrapped;
  }

  /**
  * Return the type of component which this is
  */
  function GetType() {
    return $this->type;
  }


  /**
  * Set the type of component which this is
  */
  function SetType( $type ) {
    if ( isset($this->rendered) ) unset($this->rendered);
    $this->type = $type;
    return $this->type;
  }


  /**
  * Get all properties, or the properties matching a particular type
  */
  function GetProperties( $type = null ) {
    $properties = array();
    foreach( $this->properties AS $k => $v ) {
      if ( $type == null || $v->Name() == $type ) {
        $properties[$k] = $v;
      }
    }
    return $properties;
  }


  /**
  * Get the value of the first property matching the name. Obviously this isn't
  * so useful for properties which may occur multiply, but most don't.
  *
  * @param string $type The type of property we are after.
  * @return string The value of the property, or null if there was no such property.
  */
  function GetPValue( $type ) {
    foreach( $this->properties AS $k => $v ) {
      if ( $v->Name() == $type ) return $v->Value();
    }
    return null;
  }


  /**
  * Get the value of the specified parameter for the first property matching the
  * name. Obviously this isn't so useful for properties which may occur multiply, but most don't.
  *
  * @param string $type The type of property we are after.
  * @param string $type The name of the parameter we are after.
  * @return string The value of the parameter for the property, or null in the case that there was no such property, or no such parameter.
  */
  function GetPParamValue( $type, $parameter_name ) {
    foreach( $this->properties AS $k => $v ) {
      if ( $v->Name() == $type ) return $v->GetParameterValue($parameter_name);
    }
    return null;
  }


  /**
  * Clear all properties, or the properties matching a particular type
  * @param string $type The type of property - omit for all properties
  */
  function ClearProperties( $type = null ) {
    if ( $type != null ) {
      // First remove all the existing ones of that type
      foreach( $this->properties AS $k => $v ) {
        if ( $v->Name() == $type ) {
          unset($this->properties[$k]);
          if ( isset($this->rendered) ) unset($this->rendered);
        }
      }
      $this->properties = array_values($this->properties);
    }
    else {
      if ( isset($this->rendered) ) unset($this->rendered);
      $this->properties = array();
    }
  }


  /**
  * Set all properties, or the ones matching a particular type
  */
  function SetProperties( $new_properties, $type = null ) {
    if ( isset($this->rendered) && count($new_properties) > 0 ) unset($this->rendered);
    $this->ClearProperties($type);
    foreach( $new_properties AS $k => $v ) {
      $this->AddProperty($v);
    }
  }


  /**
  * Adds a new property
  *
  * @param iCalProp $new_property The new property to append to the set, or a string with the name
  * @param string $value The value of the new property (default: param 1 is an iCalProp with everything
  * @param array $parameters The key/value parameter pairs (default: none, or param 1 is an iCalProp with everything)
  */
  function AddProperty( $new_property, $value = null, $parameters = null ) {
    if ( isset($this->rendered) ) unset($this->rendered);
    if ( isset($value) && gettype($new_property) == 'string' ) {
      $new_prop = new iCalProp();
      $new_prop->Name($new_property);
      $new_prop->Value($value);
      if ( $parameters != null ) $new_prop->Parameters($parameters);
      dbg_error_log('iCalendar'," Adding new property '%s'", $new_prop->Render() );
      $this->properties[] = $new_prop;
    }
    else if ( gettype($new_property) ) {
      $this->properties[] = $new_property;
    }
  }


  /**
  * Get all sub-components, or at least get those matching a type
  * @return array an array of the sub-components
  */
  function &FirstNonTimezone( $type = null ) {
    foreach( $this->components AS $k => $v ) {
      if ( $v->GetType() != 'VTIMEZONE' ) return $this->components[$k];
    }
    $result = false;
    return $result;
  }


  /**
  * Return true if the person identified by the email address is down as an
  * organizer for this meeting.
  * @param string $email The e-mail address of the person we're seeking.
  * @return boolean true if we found 'em, false if we didn't.
  */
  function IsOrganizer( $email ) {
    if ( !preg_match( '#^mailto:#', $email ) ) $email = 'mailto:$email';
    $props = $this->GetPropertiesByPath('!VTIMEZONE/ORGANIZER');
    foreach( $props AS $k => $prop ) {
      if ( $prop->Value() == $email ) return true;
    }
    return false;
  }


  /**
  * Return true if the person identified by the email address is down as an
  * attendee or organizer for this meeting.
  * @param string $email The e-mail address of the person we're seeking.
  * @return boolean true if we found 'em, false if we didn't.
  */
  function IsAttendee( $email ) {
    if ( !preg_match( '#^mailto:#', $email ) ) $email = 'mailto:$email';
    if ( $this->IsOrganizer($email) ) return true; /** an organizer is an attendee, as far as we're concerned */
    $props = $this->GetPropertiesByPath('!VTIMEZONE/ATTENDEE');
    foreach( $props AS $k => $prop ) {
      if ( $prop->Value() == $email ) return true;
    }
    return false;
  }


  /**
  * Get all sub-components, or at least get those matching a type, or failling to match,
  * should the second parameter be set to false.
  *
  * @param string $type The type to match (default: All)
  * @param boolean $normal_match Set to false to invert the match (default: true)
  * @return array an array of the sub-components
  */
  function GetComponents( $type = null, $normal_match = true ) {
    $components = $this->components;
    if ( $type != null ) {
      foreach( $components AS $k => $v ) {
        if ( ($v->GetType() != $type) === $normal_match ) {
          unset($components[$k]);
        }
      }
      $components = array_values($components);
    }
    return $components;
  }


  /**
  * Clear all components, or the components matching a particular type
  * @param string $type The type of component - omit for all components
  */
  function ClearComponents( $type = null ) {
    if ( $type != null ) {
      // First remove all the existing ones of that type
      foreach( $this->components AS $k => $v ) {
        if ( $v->GetType() == $type ) {
          unset($this->components[$k]);
          if ( isset($this->rendered) ) unset($this->rendered);
        }
        else {
          if ( ! $this->components[$k]->ClearComponents($type) ) {
            if ( isset($this->rendered) ) unset($this->rendered);
          }
        }
      }
      return isset($this->rendered);
    }
    else {
      if ( isset($this->rendered) ) unset($this->rendered);
      $this->components = array();
    }
  }


  /**
  * Sets some or all sub-components of the component to the supplied new components
  *
  * @param array of iCalComponent $new_components The new components to replace the existing ones
  * @param string $type The type of components to be replaced.  Defaults to null, which means all components will be replaced.
  */
  function SetComponents( $new_component, $type = null ) {
    if ( isset($this->rendered) ) unset($this->rendered);
    if ( count($new_component) > 0 ) $this->ClearComponents($type);
    foreach( $new_component AS $k => $v ) {
      $this->components[] = $v;
    }
  }


  /**
  * Adds a new subcomponent
  *
  * @param iCalComponent $new_component The new component to append to the set
  */
  function AddComponent( $new_component ) {
    if ( is_array($new_component) && count($new_component) == 0 ) return;
    if ( isset($this->rendered) ) unset($this->rendered);
    if ( is_array($new_component) ) {
      foreach( $new_component AS $k => $v ) {
        $this->components[] = $v;
      }
    }
    else {
      $this->components[] = $new_component;
    }
  }


  /**
  * Mask components, removing any that are not of the types in the list
  * @param array $keep An array of component types to be kept
  */
  function MaskComponents( $keep ) {
    foreach( $this->components AS $k => $v ) {
      if ( ! in_array( $v->GetType(), $keep ) ) {
        unset($this->components[$k]);
        if ( isset($this->rendered) ) unset($this->rendered);
      }
      else {
        $v->MaskComponents($keep);
      }
    }
  }


  /**
  * Mask properties, removing any that are not in the list
  * @param array $keep An array of property names to be kept
  * @param array $component_list An array of component types to check within
  */
  function MaskProperties( $keep, $component_list=null ) {
    foreach( $this->components AS $k => $v ) {
      $v->MaskProperties($keep, $component_list);
    }

    if ( !isset($component_list) || in_array($this->GetType(),$component_list) ) {
      foreach( $this->components AS $k => $v ) {
        if ( ! in_array( $v->GetType(), $keep ) ) {
          unset($this->components[$k]);
          if ( isset($this->rendered) ) unset($this->rendered);
        }
      }
    }
  }


  /**
  * Clone this component (and subcomponents) into a confidential version of it.  A confidential
  * event will be scrubbed of any identifying characteristics other than time/date, repeat, uid
  * and a summary which is just a translated 'Busy'.
  */
  function CloneConfidential() {
    $confidential = clone($this);
    $keep_properties = array( 'DTSTAMP', 'DTSTART', 'RRULE', 'DURATION', 'DTEND', 'UID', 'CLASS', 'TRANSP' );
    $resource_components = array( 'VEVENT', 'VTODO', 'VJOURNAL' );
    $confidential->MaskComponents(array( 'VTIMEZONE', 'VEVENT', 'VTODO', 'VJOURNAL' ));
    $confidential->MaskProperties($keep_properties, $resource_components );
    if ( in_array( $confidential->GetType(), $resource_components ) ) {
      $confidential->AddProperty( 'SUMMARY', translate('Busy') );
    }
    foreach( $confidential->components AS $k => $v ) {
      if ( in_array( $v->GetType(), $resource_components ) ) {
        $v->AddProperty( 'SUMMARY', translate('Busy') );
      }
    }

    return $confidential;
  }


  /**
  *  Renders the component, possibly restricted to only the listed properties
  */
  function Render( $restricted_properties = null) {

    $unrestricted = (!isset($restricted_properties) || count($restricted_properties) == 0);

    if ( isset($this->rendered) && $unrestricted )
      return $this->rendered;

    $rendered = "BEGIN:$this->type\r\n";
    foreach( $this->properties AS $k => $v ) {
      if ( method_exists($v, 'Render') ) {
        if ( $unrestricted || isset($restricted_properties[$v]) ) $rendered .= $v->Render() . "\r\n";
      }
    }
    foreach( $this->components AS $v ) {   $rendered .= $v->Render();  }
    $rendered .= "END:$this->type\r\n";

    if ( $unrestricted ) $this->rendered = $rendered;

    return $rendered;
  }


  /**
  * Return an array of properties matching the specified path
  *
  * @return array An array of iCalProp within the tree which match the path given, in the form
  *  [/]COMPONENT[/...]/PROPERTY in a syntax kind of similar to our poor man's XML queries. We
  *  also allow COMPONENT and PROPERTY to be !COMPONENT and !PROPERTY for ++fun.
  *
  * @note At some point post PHP4 this could be re-done with an iterator, which should be more efficient for common use cases.
  */
  function GetPropertiesByPath( $path ) {
    $properties = array();
    dbg_error_log( 'iCalendar', "GetPropertiesByPath: Querying within '%s' for path '%s'", $this->type, $path );
    if ( !preg_match( '#(/?)(!?)([^/]+)(/?.*)$#', $path, $matches ) ) return $properties;

    $adrift = ($matches[1] == '');
    $normal = ($matches[2] == '');
    $ourtest = $matches[3];
    $therest = $matches[4];
    dbg_error_log( 'iCalendar', "GetPropertiesByPath: Matches: %s -- %s -- %s -- %s\n", $matches[1], $matches[2], $matches[3], $matches[4] );
    if ( $ourtest == '*' || (($ourtest == $this->type) === $normal) && $therest != '' ) {
      if ( preg_match( '#^/(!?)([^/]+)$#', $therest, $matches ) ) {
        $normmatch = ($matches[1] =='');
        $proptest  = $matches[2];
        foreach( $this->properties AS $k => $v ) {
          if ( $proptest == '*' || (($v->Name() == $proptest) === $normmatch ) ) {
            $properties[] = $v;
          }
        }
      }
      else {
        /**
        * There is more to the path, so we recurse into that sub-part
        */
        foreach( $this->components AS $k => $v ) {
          $properties = array_merge( $properties, $v->GetPropertiesByPath($therest) );
        }
      }
    }

    if ( $adrift ) {
      /**
      * Our input $path was not rooted, so we recurse further
      */
      foreach( $this->components AS $k => $v ) {
        $properties = array_merge( $properties, $v->GetPropertiesByPath($path) );
      }
    }
    dbg_error_log('iCalendar', "GetPropertiesByPath: Found %d within '%s' for path '%s'\n", count($properties), $this->type, $path );
    return $properties;
  }

}

/**
************************************************************************************
* Everything below here is deprecated and should be avoided in favour
* of using, improving and enhancing the more sensible structures above.
************************************************************************************
*/

function deprecated( $method ) {
  global $c;
  if ( isset($c->dbg['ALL']) || isset($c->dbg['deprecated'])  || isset($c->dbg['icalendar']) ) {
    $stack = debug_backtrace();
    array_shift($stack);
    if ( preg_match( '{/inc/iCalendar.php$}', $stack[0]['file'] ) && $stack[0]['line'] > __LINE__ ) return;
    dbg_error_log("LOG", " iCalendar: Call to deprecated method '%s'", $method );
    foreach( $stack AS $k => $v ) {
      dbg_error_log( 'LOG', ' iCalendar: Deprecated call from line %4d of %s', $v['line'], $v['file']);
    }
  }
}

/**
* A Class for handling Events on a calendar (DEPRECATED)
*
* @package awl
*/
class iCalendar {  // DEPRECATED
  /**#@+
  * @access private
  */

  /**
  * The component-ised version of the iCalendar
  * @var component iCalComponent
  */
  var $component;

  /**
  * An array of arbitrary properties, containing arbitrary arrays of arbitrary properties
  * @var properties array
  */
  var $properties;

  /**
  * An array of the lines of this iCalendar resource
  * @var lines array
  */
  var $lines;

  /**
  * The typical location name for the standard timezone such as "Pacific/Auckland"
  * @var tz_locn string
  */
  var $tz_locn;

  /**
  * The type of iCalendar data VEVENT/VTODO/VJOURNAL
  * @var type string
  */
  var $type;

  /**#@-*/

  /**
  * @DEPRECATED: This class will be removed soon.
  * The constructor takes an array of args.  If there is an element called 'icalendar'
  * then that will be parsed into the iCalendar object.  Otherwise the array elements
  * are converted into properties of the iCalendar object directly.
  */
  function iCalendar( $args ) {
    global $c;

    deprecated('iCalendar::iCalendar');
    $this->tz_locn = "";
    if ( !isset($args) || !(is_array($args) || is_object($args)) ) return;
    if ( is_object($args) ) {
      settype($args,'array');
    }

    $this->component = new iCalComponent();
    if ( isset($args['icalendar']) ) {
      $this->component->ParseFrom($args['icalendar']);
      $this->lines = preg_split('/\r?\n/', $args['icalendar'] );
      $this->SaveTimeZones();
      $first =& $this->component->FirstNonTimezone();
      if ( $first ) {
        $this->type = $first->GetType();
        $this->properties = $first->GetProperties();
      }
      else {
        $this->properties = array();
      }
      $this->properties['VCALENDAR'] = array('***ERROR*** This class is being referenced in an unsupported way!');
      return;
    }

    if ( isset($args['type'] ) ) {
      $this->type = $args['type'];
      unset( $args['type'] );
    }
    else {
      $this->type = 'VEVENT';  // Default to event
    }
    $this->component->SetType('VCALENDAR');
    $this->component->SetProperties(
        array(
          new iCalProp('PRODID:-//davical.org//NONSGML AWL Calendar//EN'),
          new iCalProp('VERSION:2.0'),
          new iCalProp('CALSCALE:GREGORIAN')
        )
    );
    $first = new iCalComponent();
    $first->SetType($this->type);
    $this->properties = array();

    foreach( $args AS $k => $v ) {
      dbg_error_log( 'iCalendar', ":Initialise: %s to >>>%s<<<", $k, $v );
      $property = new iCalProp();
      $property->Name($k);
      $property->Value($v);
      $this->properties[] = $property;
    }
    $first->SetProperties($this->properties);
    $this->component->SetComponents( array($first) );

    $this->properties['VCALENDAR'] = array('***ERROR*** This class is being referenced in an unsupported way!');

    /**
    * @todo Need to handle timezones!!!
    */
    if ( $this->tz_locn == "" ) {
      $this->tz_locn = $this->Get("tzid");
      if ( (!isset($this->tz_locn) || $this->tz_locn == "") && isset($c->local_tzid) ) {
        $this->tz_locn = $c->local_tzid;
      }
    }
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Save any timezones by TZID in the PostgreSQL database for future re-use.
  */
  function SaveTimeZones() {
    global $c;

    deprecated('iCalendar::SaveTimeZones');
    $this->tzid_list = array_keys($this->component->CollectParameterValues('TZID'));
    if ( ! isset($this->tzid) && count($this->tzid_list) > 0 ) {
      dbg_error_log( 'iCalendar', "::TZID_List[0] = '%s', count=%d", $this->tzid_list[0], count($this->tzid_list) );
      $this->tzid = $this->tzid_list[0];
    }

    $timezones = $this->component->GetComponents('VTIMEZONE');
    if ( $timezones === false || count($timezones) == 0 ) return;
    $this->vtimezone = $timezones[0]->Render();  // Backward compatibility

    $tzid = $this->Get('TZID');
    if ( isset($c->save_time_zone_defs) && $c->save_time_zone_defs ) {
      foreach( $timezones AS $k => $tz ) {
        $tzid = $tz->GetPValue('TZID');

        $qry = new AwlQuery( "SELECT tz_locn FROM time_zone WHERE tz_id = ?;", $tzid );
        if ( $qry->Exec('iCalendar') && $qry->rows() == 1 ) {
          $row = $qry->Fetch();
          if ( !isset($first_tzid) ) $first_tzid = $row->tz_locn;
          continue;
        }

        if ( $tzid != "" && $qry->rows() == 0 ) {

          $tzname = $tz->GetPValue('X-LIC-LOCATION');
          if ( !isset($tzname) ) $tzname = olson_from_tzstring($tzid);

          $qry2 = new AwlQuery( "INSERT INTO time_zone (tz_id, tz_locn, tz_spec) VALUES( ?, ?, ? );",
                                      $tzid, $tzname, $tz->Render() );
          $qry2->Exec('iCalendar');
        }
      }
    }
    if ( ! isset($this->tzid) && isset($first_tzid) ) $this->tzid = $first_tzid;

    if ( (!isset($this->tz_locn) || $this->tz_locn == '') && isset($first_tzid) && $first_tzid != '' ) {
      $tzname = preg_replace('#^(.*[^a-z])?([a-z]+/[a-z]+)$#i','$2', $first_tzid );
      if ( preg_match( '#\S+/\S+#', $tzname) ) {
        $this->tz_locn = $tzname;
      }
      dbg_error_log( 'iCalendar', " TZCrap1: TZID '%s', Location '%s', Perhaps: %s", $tzid, $this->tz_locn, $tzname );
    }

    if ( (!isset($this->tz_locn) || $this->tz_locn == "") && isset($c->local_tzid) ) {
      $this->tz_locn = $c->local_tzid;
    }
    if ( ! isset($this->tzid) && isset($this->tz_locn) ) $this->tzid = $this->tz_locn;
  }


  /**
  * An array of property names that we should always want when rendering an iCalendar
  *
  * @DEPRECATED: This class will be removed soon.
  * @todo Remove this function.
  */
  function DefaultPropertyList() {
    dbg_error_log( "LOG", " iCalendar: Call to deprecated method '%s'", 'DefaultPropertyList' );
    return array( "UID" => 1, "DTSTAMP" => 1, "DTSTART" => 1, "DURATION" => 1,
                  "LAST-MODIFIED" => 1,"CLASS" => 1, "TRANSP" => 1, "SEQUENCE" => 1,
                  "DUE" => 1, "SUMMARY" => 1, "RRULE" => 1 );
  }

  /**
  * A function to extract the contents of a BEGIN:SOMETHING to END:SOMETHING (perhaps multiply)
  * and return just that bit (or, of course, those bits :-)
  *
  * @var string The type of thing(s) we want returned.
  * @var integer The number of SOMETHINGS we want to get.
  *
  * @return string A string from BEGIN:SOMETHING to END:SOMETHING, possibly multiple of these
  *
  * @DEPRECATED: This class will be removed soon.
  * @todo Remove this function.
  */
  function JustThisBitPlease( $type, $count=1 ) {
    deprecated('iCalendar::JustThisBitPlease' );
    $answer = "";
    $intags = false;
    $start = "BEGIN:$type";
    $finish = "END:$type";
    dbg_error_log( 'iCalendar', ":JTBP: Looking for %d subsets of type %s", $count, $type );
    reset($this->lines);
    foreach( $this->lines AS $k => $v ) {
      if ( !$intags && $v == $start ) {
        $answer .= $v . "\n";
        $intags = true;
      }
      else if ( $intags && $v == $finish ) {
        $answer .= $v . "\n";
        $intags = false;
      }
      else if ( $intags ) {
        $answer .= $v . "\n";
      }
    }
    return $answer;
  }


  /**
  * Function to parse lines from BEGIN:SOMETHING to END:SOMETHING into a nested array structure
  *
  * @var string The "SOMETHING" from the BEGIN:SOMETHING line we just met
  * @return arrayref An array of the things we found between (excluding) the BEGIN & END, some of which might be sub-arrays
  *
  * @DEPRECATED: This class will be removed soon.
  * @todo Remove this function.
  */
  function &ParseSomeLines( $type ) {
    deprecated('iCalendar::ParseSomeLines' );
    $props = array();
    $properties =& $props;
    while( isset($this->lines[$this->_current_parse_line]) ) {
      $i = $this->_current_parse_line++;
      $line =& $this->lines[$i];
      dbg_error_log( 'iCalendar', ":Parse:%s LINE %03d: >>>%s<<<", $type, $i, $line );
      if ( $this->parsing_vtimezone ) {
        $this->vtimezone .= $line."\n";
      }
      if ( preg_match( '/^(BEGIN|END):([^:]+)$/', $line, $matches ) ) {
        if ( $matches[1] == 'END' && $matches[2] == $type ) {
          if ( $type == 'VTIMEZONE' ) {
            $this->parsing_vtimezone = false;
          }
          return $properties;
        }
        else if( $matches[1] == 'END' ) {
          dbg_error_log("ERROR"," iCalendar: parse error: Unexpected END:%s when we were looking for END:%s", $matches[2], $type );
          return $properties;
        }
        else if( $matches[1] == 'BEGIN' ) {
          $subtype = $matches[2];
          if ( $subtype == 'VTIMEZONE' ) {
            $this->parsing_vtimezone = true;
            $this->vtimezone = $line."\n";
          }
          if ( !isset($properties['INSIDE']) ) $properties['INSIDE'] = array();
          $properties['INSIDE'][] = $subtype;
          if ( !isset($properties[$subtype]) ) $properties[$subtype] = array();
          $properties[$subtype][] = $this->ParseSomeLines($subtype);
        }
      }
      else {
        // Parse the property
        @list( $property, $value ) = explode(':', $line, 2 );
        if ( strpos( $property, ';' ) > 0 ) {
          $parameterlist = explode(';', $property );
          $property = array_shift($parameterlist);
          foreach( $parameterlist AS $pk => $pv ) {
            if ( $pv == "VALUE=DATE" ) {
              $value .= 'T000000';
            }
            elseif ( preg_match('/^([^;:=]+)=([^;:=]+)$/', $pv, $matches) ) {
              switch( $matches[1] ) {
                case 'TZID': $properties['TZID'] = $matches[2];  break;
                default:
                  dbg_error_log( 'iCalendar', " FYI: Ignoring Resource '%s', Property '%s', Parameter '%s', Value '%s'", $type, $property, $matches[1], $matches[2] );
              }
            }
          }
        }
        if ( $this->parsing_vtimezone && (!isset($this->tz_locn) || $this->tz_locn == "") && $property == 'X-LIC-LOCATION' ) {
          $this->tz_locn = $value;
        }
        $properties[strtoupper($property)] = $this->RFC2445ContentUnescape($value);
      }
    }
    return $properties;
  }


  /**
  * Build the iCalendar object from a text string which is a single iCalendar resource
  *
  * @var string The RFC2445 iCalendar resource to be parsed
  *
  * @DEPRECATED: This class will be removed soon.
  * @todo Remove this function.
  */
  function BuildFromText( $icalendar ) {
    deprecated('iCalendar::BuildFromText' );
    /**
     * This unescapes the (CRLF + linear space) wrapping specified in RFC2445. According
     * to RFC2445 we should always end with CRLF but the CalDAV spec says that normalising
     * XML parsers often muck with it and may remove the CR.
     */
    $icalendar = preg_replace('/\r?\n[ \t]/', '', $icalendar );

    $this->lines = preg_split('/\r?\n/', $icalendar );

    $this->_current_parse_line = 0;
    $this->properties = $this->ParseSomeLines('');

    /**
    * Our 'type' is the type of non-timezone inside a VCALENDAR
    */
    if ( isset($this->properties['VCALENDAR'][0]['INSIDE']) ) {
      foreach ( $this->properties['VCALENDAR'][0]['INSIDE']  AS $k => $v ) {
        if ( $v == 'VTIMEZONE' ) continue;
        $this->type = $v;
        break;
      }
    }

  }


  /**
  * Returns a content string with the RFC2445 escaping removed
  *
  * @param string $escaped The incoming string to be escaped.
  * @return string The string with RFC2445 content escaping removed.
  *
  * @DEPRECATED: This class will be removed soon.
  * @todo Remove this function.
  */
  function RFC2445ContentUnescape( $escaped ) {
    deprecated( 'RFC2445ContentUnescape' );
    $unescaped = str_replace( '\\n', "\n", $escaped);
    $unescaped = str_replace( '\\N', "\n", $unescaped);
    $unescaped = preg_replace( "/\\\\([,;:\"\\\\])/", '$1', $unescaped);
    return $unescaped;
  }



  /**
  * Do what must be done with time zones from on file.  Attempt to turn
  * them into something that PostgreSQL can understand...
  *
  * @DEPRECATED: This class will be removed soon.
  * @todo Remove this function.
  */
  function DealWithTimeZones() {
    global $c;

    deprecated('iCalendar::DealWithTimeZones' );
    $tzid = $this->Get('TZID');
    if ( isset($c->save_time_zone_defs) && $c->save_time_zone_defs ) {
      $qry = new AwlQuery( "SELECT tz_locn FROM time_zone WHERE tz_id = ?;", $tzid );
      if ( $qry->Exec('iCalendar') && $qry->rows() == 1 ) {
        $row = $qry->Fetch();
        $this->tz_locn = $row->tz_locn;
      }
      dbg_error_log( 'iCalendar', " TZCrap2: TZID '%s', DB Rows=%d, Location '%s'", $tzid, $qry->rows(), $this->tz_locn );
    }

    if ( (!isset($this->tz_locn) || $this->tz_locn == '') && $tzid != '' ) {
      /**
      * In case there was no X-LIC-LOCATION defined, let's hope there is something in the TZID
      * that we can use.  We are looking for a string like "Pacific/Auckland" if possible.
      */
      $tzname = preg_replace('#^(.*[^a-z])?([a-z]+/[a-z]+)$#i','$1',$tzid );
      /**
      * Unfortunately this kind of thing will never work well :-(
      *
      if ( strstr( $tzname, ' ' ) ) {
        $words = preg_split('/\s/', $tzname );
        $tzabbr = '';
        foreach( $words AS $i => $word ) {
          $tzabbr .= substr( $word, 0, 1);
        }
        $this->tz_locn = $tzabbr;
      }
      */
      if ( preg_match( '#\S+/\S+#', $tzname) ) {
        $this->tz_locn = $tzname;
      }
      dbg_error_log( 'iCalendar', " TZCrap3: TZID '%s', Location '%s', Perhaps: %s", $tzid, $this->tz_locn, $tzname );
    }

    if ( $tzid != '' && isset($c->save_time_zone_defs) && $c->save_time_zone_defs && $qry->rows() != 1 && isset($this->vtimezone) && $this->vtimezone != "" ) {
      $qry2 = new AwlQuery( "INSERT INTO time_zone (tz_id, tz_locn, tz_spec) VALUES( ?, ?, ? );",
                                   $tzid, $this->tz_locn, $this->vtimezone );
      $qry2->Exec('iCalendar');
    }

    if ( (!isset($this->tz_locn) || $this->tz_locn == "") && isset($c->local_tzid) ) {
      $this->tz_locn = $c->local_tzid;
    }
  }


  /**
  * Get the value of a property in the first non-VTIMEZONE
  * @DEPRECATED: This class will be removed soon.
  */
  function Get( $key ) {
    deprecated('iCalendar::Get' );
    if ( strtoupper($key) == 'TZID' ) {
      // backward compatibility hack
      dbg_error_log( 'iCalendar', " Get(TZID): TZID '%s', Location '%s'", (isset($this->tzid)?$this->tzid:"[not set]"), $this->tz_locn );
      if ( isset($this->tzid) ) return $this->tzid;
      return $this->tz_locn;
    }
    /**
    * The property we work on is the first non-VTIMEZONE we find.
    */
    $component =& $this->component->FirstNonTimezone();
    if ( $component === false ) return null;
    return $component->GetPValue(strtoupper($key));
  }


  /**
  * Set the value of a property
  * @DEPRECATED: This class will be removed soon.
  */
  function Set( $key, $value ) {
    deprecated('iCalendar::Set' );
    if ( $value == "" ) return;
    $key = strtoupper($key);
    $property = new iCalProp();
    $property->Name($key);
    $property->Value($value);
    if (isset($this->component->rendered) ) unset( $this->component->rendered );
    $component =& $this->component->FirstNonTimezone();
    $component->SetProperties( array($property), $key);
    return $this->Get($key);
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Add a new property/value, regardless of whether it exists already
  *
  * @param string $key The property key
  * @param string $value The property value
  * @param string $parameters Any parameters to set for the property, as an array of key/value pairs
  */
  function Add( $key, $value, $parameters = null ) {
    deprecated('iCalendar::Add' );
    if ( $value == "" ) return;
    $key = strtoupper($key);
    $property = new iCalProp();
    $property->Name($key);
    $property->Value($value);
    if ( isset($parameters) && is_array($parameters) ) {
      $property->parameters = $parameters;
    }
    $component =& $this->component->FirstNonTimezone();
    $component->AddProperty($property);
    if (isset($this->component->rendered) ) unset( $this->component->rendered );
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Get all sub-components, or at least get those matching a type, or failling to match,
  * should the second parameter be set to false.
  *
  * @param string $type The type to match (default: All)
  * @param boolean $normal_match Set to false to invert the match (default: true)
  * @return array an array of the sub-components
  */
  function GetComponents( $type = null, $normal_match = true ) {
    deprecated('iCalendar::GetComponents' );
    return $this->component->GetComponents($type,$normal_match);
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Clear all components, or the components matching a particular type
  * @param string $type The type of component - omit for all components
  */
  function ClearComponents( $type = null ) {
    deprecated('iCalendar::ClearComponents' );
    $this->component->ClearComponents($type);
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Sets some or all sub-components of the component to the supplied new components
  *
  * @param array of iCalComponent $new_components The new components to replace the existing ones
  * @param string $type The type of components to be replaced.  Defaults to null, which means all components will be replaced.
  */
  function SetComponents( $new_component, $type = null ) {
    deprecated('iCalendar::SetComponents' );
    $this->component->SetComponents( $new_component, $type );
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Adds a new subcomponent
  *
  * @param iCalComponent $new_component The new component to append to the set
  */
  function AddComponent( $new_component ) {
    deprecated('iCalendar::AddComponent' );
    $this->component->AddComponent($new_component);
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Mask components, removing any that are not of the types in the list
  * @param array $keep An array of component types to be kept
  */
  function MaskComponents( $keep ) {
    deprecated('iCalendar::MaskComponents' );
    $this->component->MaskComponents($keep);
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns a PostgreSQL Date Format string suitable for returning HTTP (RFC2068) dates
  * Preferred is "Sun, 06 Nov 1994 08:49:37 GMT" so we do that.
  */
  static function HttpDateFormat() {
    return "'Dy, DD Mon IYYY HH24:MI:SS \"GMT\"'";
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns a PostgreSQL Date Format string suitable for returning iCal dates
  */
  static function SqlDateFormat() {
    return "'YYYYMMDD\"T\"HH24MISS'";
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns a PostgreSQL Date Format string suitable for returning dates which
  * have been cast to UTC
  */
  static function SqlUTCFormat() {
    return "'YYYYMMDD\"T\"HH24MISS\"Z\"'";
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns a PostgreSQL Date Format string suitable for returning iCal durations
  *  - this doesn't work for negative intervals, but events should not have such!
  */
  static function SqlDurationFormat() {
    return "'\"PT\"HH24\"H\"MI\"M\"'";
  }

  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns a suitably escaped RFC2445 content string.
  *
  * @param string $name The incoming name[;param] prefixing the string.
  * @param string $value The incoming string to be escaped.
  *
  * @deprecated This function is deprecated and will be removed eventually.
  * @todo Remove this function.
  */
  function RFC2445ContentEscape( $name, $value ) {
    deprecated('iCalendar::RFC2445ContentEscape' );
    $property = preg_replace( '/[;].*$/', '', $name );
    switch( $property ) {
        /** Content escaping does not apply to these properties culled from RFC2445 */
      case 'ATTACH':                case 'GEO':                       case 'PERCENT-COMPLETE':      case 'PRIORITY':
      case 'COMPLETED':             case 'DTEND':                     case 'DUE':                   case 'DTSTART':
      case 'DURATION':              case 'FREEBUSY':                  case 'TZOFFSETFROM':          case 'TZOFFSETTO':
      case 'TZURL':                 case 'ATTENDEE':                  case 'ORGANIZER':             case 'RECURRENCE-ID':
      case 'URL':                   case 'EXDATE':                    case 'EXRULE':                case 'RDATE':
      case 'RRULE':                 case 'REPEAT':                    case 'TRIGGER':               case 'CREATED':
      case 'DTSTAMP':               case 'LAST-MODIFIED':             case 'SEQUENCE':
        break;

        /** Content escaping applies by default to other properties */
      default:
        $value = str_replace( '\\', '\\\\', $value);
        $value = preg_replace( '/\r?\n/', '\\n', $value);
        $value = preg_replace( "/([,;:\"])/", '\\\\$1', $value);
    }
    $result = preg_replace( '/(.{72})/u', '$1'."\r\n ", $name.':'.$value ) ."\r\n";
    return $result;
  }

  /**
  * @DEPRECATED: This class will be removed soon.
   * Return all sub-components of the given type, which are part of the
   * component we pass in as an array of lines.
   *
   * @param array $component The component to be parsed
   * @param string $type The type of sub-components to be extracted
   * @param int $count The number of sub-components to extract (default: 9999)
   *
   * @return array The sub-component lines
   */
  function ExtractSubComponent( $component, $type, $count=9999 ) {
    deprecated('iCalendar::ExtractSubComponent' );
    $answer = array();
    $intags = false;
    $start = "BEGIN:$type";
    $finish = "END:$type";
    dbg_error_log( 'iCalendar', ":ExtractSubComponent: Looking for %d subsets of type %s", $count, $type );
    reset($component);
    foreach( $component AS $k => $v ) {
      if ( !$intags && $v == $start ) {
        $answer[] = $v;
        $intags = true;
      }
      else if ( $intags && $v == $finish ) {
        $answer[] = $v;
        $intags = false;
      }
      else if ( $intags ) {
        $answer[] = $v;
      }
    }
    return $answer;
  }


  /**
  * @DEPRECATED: This class will be removed soon.
   * Extract a particular property from the provided component.  In doing so we
   * assume that the content was unescaped when iCalComponent::ParseFrom()
   * called iCalComponent::UnwrapComponent().
   *
   * @param array $component An array of lines of this component
   * @param string $type The type of parameter
   *
   * @return array An array of iCalProperty objects
   */
  function ExtractProperty( $component, $type, $count=9999 ) {
    deprecated('iCalendar::ExtractProperty' );
    $answer = array();
    dbg_error_log( 'iCalendar', ":ExtractProperty: Looking for %d properties of type %s", $count, $type );
    reset($component);
    foreach( $component AS $k => $v ) {
      if ( preg_match( "/$type"."[;:]/i", $v ) ) {
        $answer[] = new iCalProp($v);
        dbg_error_log( 'iCalendar', ":ExtractProperty: Found property %s", $type );
        if ( --$count < 1 ) return $answer;
      }
    }
    return $answer;
  }


  /**
  * @DEPRECATED: This class will be removed soon.
   * Applies the filter conditions, possibly recursively, to the value which will be either
   * a single property, or an array of lines of the component under test.
   *
   * @todo Eventually we need to handle all of these possibilities, which will mean writing
   * several routines:
   *  - Get Property from Component
   *  - Get Parameter from Property
   *  - Test TimeRange
   * For the moment we will leave these, until there is a perceived need.
   *
   * @param array $filter An array of XMLElement defining the filter(s)
   * @param mixed $value Either a string which is the single property, or an array of lines, for the component.
   * @return boolean Whether the filter passed / failed.
   */
  function ApplyFilter( $filter, $value ) {
    deprecated('iCalendar::ApplyFilter' );
    foreach( $filter AS $k => $v ) {
      $tag = $v->GetTag();
      $value_type = gettype($value);
      $value_defined = (isset($value) && $value_type == 'string') || ($value_type == 'array' && count($value) > 0 );
      if ( $tag == 'urn:ietf:params:xml:ns:caldav:is-not-defined' && $value_defined ) {
        dbg_error_log( 'iCalendar', ":ApplyFilter: Value is set ('%s'), want unset, for filter %s", count($value), $tag );
        return false;
      }
      elseif ( $tag == 'urn:ietf:params:xml:ns:caldav:is-defined' && !$value_defined ) {
        dbg_error_log( 'iCalendar', ":ApplyFilter: Want value, but it is not set for filter %s", $tag );
        return false;
      }
      else {
        switch( $tag ) {
          case 'urn:ietf:params:xml:ns:caldav:time-range':
            /** todo:: While this is unimplemented here at present, most time-range tests should occur at the SQL level. */
            break;
          case 'urn:ietf:params:xml:ns:caldav:text-match':
            $search = $v->GetContent();
            // In this case $value will either be a string, or an array of iCalProp objects
            // since TEXT-MATCH does not apply to COMPONENT level - only property/parameter
            if ( gettype($value) != 'string' ) {
              if ( gettype($value) == 'array' ) {
                $match = false;
                foreach( $value AS $k1 => $v1 ) {
                  // $v1 could be an iCalProp object
                  if ( $match = $v1->TextMatch($search)) break;
                }
              }
              else {
                dbg_error_log( 'iCalendar', ":ApplyFilter: TEXT-MATCH will only work on strings or arrays of iCalProp.  %s unsupported", gettype($value) );
                return true;  // We return _true_ in this case, so the client sees the item
              }
            }
            else {
              $match = strstr( $value, $search[0] );
            }
            $negate = $v->GetAttribute("negate-condition");
            if ( isset($negate) && strtolower($negate) == "yes" && $match ) {
              dbg_error_log( 'iCalendar', ":ApplyFilter: TEXT-MATCH of %s'%s' against '%s'", (isset($negate) && strtolower($negate) == "yes"?'!':''), $search, $value );
              return false;
            }
            break;
          case 'urn:ietf:params:xml:ns:caldav:comp-filter':
            $subfilter = $v->GetContent();
            $component = $this->ExtractSubComponent($value,$v->GetAttribute("name"));
            if ( ! $this->ApplyFilter($subfilter,$component) ) return false;
            break;
          case 'urn:ietf:params:xml:ns:caldav:prop-filter':
            $subfilter = $v->GetContent();
            $properties = $this->ExtractProperty($value,$v->GetAttribute("name"));
            if ( ! $this->ApplyFilter($subfilter,$properties) ) return false;
            break;
          case 'urn:ietf:params:xml:ns:caldav:param-filter':
            $subfilter = $v->GetContent();
            $parameter = $this->ExtractParameter($value,$v->GetAttribute("NAME"));
            if ( ! $this->ApplyFilter($subfilter,$parameter) ) return false;
            break;
        }
      }
    }
    return true;
  }

  /**
  * @DEPRECATED: This class will be removed soon.
   * Test a PROP-FILTER or COMP-FILTER and return a true/false
   * COMP-FILTER (is-defined | is-not-defined | (time-range?, prop-filter*, comp-filter*))
   * PROP-FILTER (is-defined | is-not-defined | ((time-range | text-match)?, param-filter*))
   *
   * @param array $filter An array of XMLElement defining the filter
   *
   * @return boolean Whether or not this iCalendar passes the test
   */
  function TestFilter( $filters ) {
    deprecated('iCalendar::TestFilter' );

    foreach( $filters AS $k => $v ) {
      $tag = $v->GetTag();
      $name = $v->GetAttribute("name");
      $filter = $v->GetContent();
      if ( $tag == "urn:ietf:params:xml:ns:caldav:prop-filter" ) {
        $value = $this->ExtractProperty($this->lines,$name);
      }
      else {
        $value = $this->ExtractSubComponent($this->lines,$v->GetAttribute("name"));
      }
      if ( count($value) == 0 ) unset($value);
      if ( ! $this->ApplyFilter($filter,$value) ) return false;
    }
    return true;
  }

  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns the header we always use at the start of our iCalendar resources
  *
  * @todo Remove this function.
  */
  static function iCalHeader() {
    deprecated('iCalendar::iCalHeader' );
    return <<<EOTXT
BEGIN:VCALENDAR\r
PRODID:-//davical.org//NONSGML AWL Calendar//EN\r
VERSION:2.0\r

EOTXT;
  }



  /**
  * @DEPRECATED: This class will be removed soon.
  * Returns the footer we always use at the finish of our iCalendar resources
  *
  * @todo Remove this function.
  */
  static function iCalFooter() {
    deprecated('iCalendar::iCalFooter' );
    return "END:VCALENDAR\r\n";
  }


  /**
  * @DEPRECATED: This class will be removed soon.
  * Render the iCalendar object as a text string which is a single VEVENT (or other)
  *
  * @param boolean $as_calendar Whether or not to wrap the event in a VCALENDAR
  * @param string $type The type of iCalendar object (VEVENT, VTODO, VFREEBUSY etc.)
  * @param array $restrict_properties The names of the properties we want in our rendered result.
  */
  function Render( $as_calendar = true, $type = null, $restrict_properties = null ) {
    deprecated('iCalendar::Render' );
    if ( $as_calendar ) {
      return $this->component->Render();
    }
    else {
      $components = $this->component->GetComponents($type);
      $rendered = "";
      foreach( $components AS $k => $v ) {
        $rendered .= $v->Render($restrict_properties);
      }
      return $rendered;
    }
  }

}
