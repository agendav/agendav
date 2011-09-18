<?php
/**
* A Class for handling vCalendar & vCard data.
*
* When parsed the underlying structure is roughly as follows:
*
*   vComponent( array(vComponent), array(vProperty) )
*
* @package awl
* @subpackage vComponent
* @author Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*
*/
require_once('XMLElement.php');

/**
* A Class for representing properties within a vComponent (VCALENDAR or VCARD)
*
* @package awl
*/
class vProperty {
  /**#@+
   * @access private
   */

  /**
   * The name of this property
   *
   * @var string
   */
  protected $name;

  /**
   * An array of parameters to this property, represented as key/value pairs.
   *
   * @var array
   */
  protected $parameters;

  /**
   * The value of this property.
   *
   * @var string
   */
  protected $content;

  /**
   * The original value that this was parsed from, if that's the way it happened.
   *
   * @var string
   */
  protected $rendered;

  /**#@-*/

  /**
   * The constructor parses the incoming string, which is formatted as per RFC2445 as a
   *   propname[;param1=pval1[; ... ]]:propvalue
   * however we allow ourselves to assume that the RFC2445 content unescaping has already
   * happened when vComponent::ParseFrom() called vComponent::UnwrapComponent().
   *
   * @param string $propstring The string from the vComponent which contains this property.
   */
  function __construct( $propstring = null ) {
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
   * happened when vComponent::ParseFrom() called vComponent::UnwrapComponent().
   *
   * @param string $propstring The string from the vComponent which contains this property.
   */
  function ParseFrom( $propstring ) {
    $this->rendered = (strlen($propstring) < 73 ? $propstring : null);  // Only pre-rendered if we didn't unescape it

    $unescaped = preg_replace( '{\\\\[nN]}', "\n", $propstring);

    // Split into two parts on : which is not preceded by a \
    list( $start, $values) = preg_split( '{(?<!\\\\):}', $unescaped, 2);
    $this->content = preg_replace( "/\\\\([,;:\"\\\\])/", '$1', $values);

    // Split on ; which is not preceded by a \
    $parameters = preg_split( '{(?<!\\\\);}', $start);

    $this->name = strtoupper(array_shift( $parameters ));
    $this->parameters = array();
    foreach( $parameters AS $k => $v ) {
      $pos = strpos($v,'=');
      $name = strtoupper(substr( $v, 0, $pos));
      $value = substr( $v, $pos + 1);
      $this->parameters[$name] = $value;
    }
//    dbg_error_log('vComponent', " vProperty::ParseFrom found '%s' = '%s' with %d parameters", $this->name, substr($this->content,0,200), count($this->parameters) );
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
      $this->name = strtoupper($newname);
      if ( isset($this->rendered) ) unset($this->rendered);
//      dbg_error_log('vComponent', " vProperty::Name(%s)", $this->name );
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
      $this->parameters = array();
      foreach( $newparams AS $k => $v ) {
        $this->parameters[strtoupper($k)] = $v;
      }
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
    $name = strtoupper($name);
    if ( isset($this->parameters[$name]) ) return $this->parameters[$name];
    return null;
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
    $this->parameters[strtoupper($name)] = $value;
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
      case 'RRULE':                 case 'REPEAT':                    case 'TRIGGER':               case 'RDATE':
      case 'COMPLETED':             case 'DTEND':                     case 'DUE':                   case 'DTSTART':
      case 'DTSTAMP':               case 'LAST-MODIFIED':             case 'CREATED':               case 'EXDATE':
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
    else if ( (strlen($property) + strlen($escaped)) > 72 && (strlen($property) <= 72) && (strlen($escaped) <= 72) ) {
      $this->rendered = $property . "\r\n " . $escaped;
    }
    else {
      $this->rendered = preg_replace( '/(.{72})/u', '$1'."\r\n ", $property.$escaped );
    }
    return $this->rendered;
  }

  
  public function __toString() {
    return $this->Render();
  }

  
  /**
   * Test a PROP-FILTER or PARAM-FILTER and return a true/false
   * PROP-FILTER (is-defined | is-not-defined | ((time-range | text-match)?, param-filter*))
   * PARAM-FILTER (is-defined | is-not-defined | ((time-range | text-match)?, param-filter*))
   *
   * @param array $filter An array of XMLElement defining the filter
   *
   * @return boolean Whether or not this vProperty passes the test
   */
  function TestFilter( $filters ) {
    foreach( $filters AS $k => $v ) {
      $tag = $v->GetTag();
      switch( $tag ) {
        case 'urn:ietf:params:xml:ns:caldav:is-defined':
        case 'urn:ietf:params:xml:ns:carddav:is-defined':
          break;

        case 'urn:ietf:params:xml:ns:caldav:is-not-defined':
        case 'urn:ietf:params:xml:ns:carddav:is-not-defined':
          return false;
          break;

        case 'urn:ietf:params:xml:ns:caldav:time-range':
          /** @todo: While this is unimplemented here at present, most time-range tests should occur at the SQL level. */
          break;

        case 'urn:ietf:params:xml:ns:carddav:text-match':
        case 'urn:ietf:params:xml:ns:caldav:text-match':
          $search = $v->GetContent();
          $match = $this->TextMatch($search);
          $negate = $v->GetAttribute("negate-condition");
          if ( isset($negate) && strtolower($negate) == "yes" && $match ) {
            return false;
          }
          if ( ! $match ) return false;
          break;

        case 'urn:ietf:params:xml:ns:carddav:param-filter':
        case 'urn:ietf:params:xml:ns:caldav:param-filter':
          $subfilter = $v->GetContent();
          $parameter = $this->GetParameterValue($v->GetAttribute("name"));
          if ( ! $this->TestParamFilter($subfilter,$parameter) ) return false;
          break;

        default:
          dbg_error_log( 'vComponent', ' vProperty::TestFilter: unhandled tag "%s"', $tag );
          break;
      }
    }
    return true;
  }


  function TestParamFilter( $filters, $parameter_value ) {
    foreach( $filters AS $k => $v ) {
      $subtag = $v->GetTag();
      switch( $subtag ) {
        case 'urn:ietf:params:xml:ns:caldav:is-defined':
        case 'urn:ietf:params:xml:ns:carddav:is-defined':
          break;

        case 'urn:ietf:params:xml:ns:caldav:is-not-defined':
        case 'urn:ietf:params:xml:ns:carddav:is-not-defined':
          return false;
          break;

        case 'urn:ietf:params:xml:ns:caldav:time-range':
          /** @todo: While this is unimplemented here at present, most time-range tests should occur at the SQL level. */
          break;

        case 'urn:ietf:params:xml:ns:carddav:text-match':
        case 'urn:ietf:params:xml:ns:caldav:text-match':
          $search = $v->GetContent();
          $match = false;
          if ( isset($parameter_value) ) $match = strstr( $this->content, $search );
          $negate = $v->GetAttribute("negate-condition");
          if ( isset($negate) && strtolower($negate) == "yes" && $match ) {
            return false;
          }
          if ( ! $match ) return false;
          break;

        default:
          dbg_error_log( 'vComponent', ' vProperty::TestParamFilter: unhandled tag "%s"', $tag );
          break;
      }
    }
    return true;
  }
}


/**
* A Class for representing components within an vComponent
*
* @package awl
*/
class vComponent {
  /**#@+
   * @access private
   */

  /**
   * The type of this component, such as 'VEVENT', 'VTODO', 'VTIMEZONE', 'VCARD', etc.
   *
   * @var string
   */
  protected $type;

  /**
   * An array of properties, which are vProperty objects
   *
   * @var array
   */
  protected $properties;

  /**
   * An array of (sub-)components, which are vComponent objects
   *
   * @var array
   */
  protected $components;

  /**
   * The rendered result (or what was originally parsed, if there have been no changes)
   *
   * @var array
   */
  protected $rendered;

  /**#@-*/

  /**
  * A basic constructor
  */
  function __construct( $content = null ) {
    $this->type = "";
    $this->properties = array();
    $this->components = array();
    $this->rendered = "";
    if ( $content != null && (gettype($content) == 'string' || gettype($content) == 'array') ) {
      $this->ParseFrom($content);
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
//        dbg_error_log( 'vComponent', "::CollectParameterValues(%s) : Found '%s'", $parameter_name, $also);
        $values[$also] = 1;
      }
    }
    return $values;
  }


  /**
  * Parse the text $content into sets of vProperty & vComponent within this vComponent
  * @param string $content The raw RFC2445-compliant vComponent component, including BEGIN:TYPE & END:TYPE
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
//      dbg_error_log( 'vComponent',  "::ParseFrom: Parsing line: $line");

      if ( $type === false ) {
        if ( preg_match( '/^BEGIN:(.+)$/i', $line, $matches ) ) {
          // We have found the start of the main component
          $type = strtoupper($matches[1]);
          $finish = 'END:'.$type;
          $this->type = $type;
          dbg_error_log( 'vComponent', "::ParseFrom: Start component of type '%s'", $type);
        }
        else {
          dbg_error_log( 'vComponent', "::ParseFrom: Ignoring crap before start of component: $line");
          // unset($lines[$k]);  // The content has crap before the start
          if ( $line != "" ) $this->rendered = null;
        }
      }
      else if ( $type == null ) {
        dbg_error_log( 'vComponent', "::ParseFrom: Ignoring crap after end of component");
        if ( $line != "" ) $this->rendered = null;
      }
      else if ( strtoupper($line) == $finish ) {
        dbg_error_log( 'vComponent', "::ParseFrom: End of component");
        $type = null;  // We have reached the end of our component
      }
      else {
        if ( $subtype === false && preg_match( '/^BEGIN:(.+)$/i', $line, $matches ) ) {
          // We have found the start of a sub-component
          $subtype = strtoupper($matches[1]);
          $subfinish = "END:$subtype";
          $subcomponent = $line . "\r\n";
          dbg_error_log( 'vComponent', "::ParseFrom: Found a subcomponent '%s'", $subtype);
        }
        else if ( $subtype ) {
          // We are inside a sub-component
          $subcomponent .= $this->WrapComponent($line);
          if ( strtoupper($line) == $subfinish ) {
            dbg_error_log( 'vComponent', "::ParseFrom: End of subcomponent '%s'", $subtype);
            // We have found the end of a sub-component
            $this->components[] = new vComponent($subcomponent);
            $subtype = false;
          }
//          else
//            dbg_error_log( 'vComponent', "::ParseFrom: Inside a subcomponent '%s'", $subtype );
        }
        else {
//          dbg_error_log( 'vComponent', "::ParseFrom: Parse property of component");
          // It must be a normal property line within a component.
          $this->properties[] = new vProperty($line);
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
    $this->type = strtoupper($type);
    return $this->type;
  }


  /**
  * Return the first instance of a property of this name
  */
  function GetProperty( $type ) {
    foreach( $this->properties AS $k => $v ) {
      if ( is_object($v) && $v->Name() == $type ) {
        return $v;
      }
      else if ( !is_object($v) ) {
        debug_error_log("ERROR", 'vComponent::GetProperty(): Trying to get %s on %s which is not an object!', $type, $v );
      }
    }
    /** So we can call methods on the result of this, make sure we always return a vProperty of some kind */
    return null;
  }


  /**
  * Get all properties, or the properties matching a particular type, or matching an
  * array associating property names with true values: array( 'PROPERTY' => true, 'PROPERTY2' => true )
  */
  function GetProperties( $type = null ) {
    $properties = array();
    $testtypes = (gettype($type) == 'string' ? array( $type => true ) : $type );
    foreach( $this->properties AS $k => $v ) {
      if ( $type == null || (isset($testtypes[$v->Name()]) && $testtypes[$v->Name()]) ) {
        $properties[] = $v;
      }
    }
    return $properties;
  }


  /**
  * Clear all properties, or the properties matching a particular type
  * @param string|array $type The type of property - omit for all properties - or an
  * array associating property names with true values: array( 'PROPERTY' => true, 'PROPERTY2' => true )
  */
  function ClearProperties( $type = null ) {
    if ( $type != null ) {
      $testtypes = (gettype($type) == 'string' ? array( $type => true ) : $type );
      // First remove all the existing ones of that type
      foreach( $this->properties AS $k => $v ) {
        if ( isset($testtypes[$v->Name()]) && $testtypes[$v->Name()] ) {
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
  * @param vProperty $new_property The new property to append to the set, or a string with the name
  * @param string $value The value of the new property (default: param 1 is an vProperty with everything
  * @param array $parameters The key/value parameter pairs (default: none, or param 1 is an vProperty with everything)
  */
  function AddProperty( $new_property, $value = null, $parameters = null ) {
    if ( isset($this->rendered) ) unset($this->rendered);
    if ( isset($value) && gettype($new_property) == 'string' ) {
      $new_prop = new vProperty();
      $new_prop->Name($new_property);
      $new_prop->Value($value);
      if ( $parameters != null ) $new_prop->Parameters($parameters);
      dbg_error_log('vComponent'," Adding new property '%s'", $new_prop->Render() );
      $this->properties[] = $new_prop;
    }
    else if ( gettype($new_property) ) {
      $this->properties[] = $new_property;
    }
  }


  /**
   * Return number of components
   */
  function ComponentCount() {
    return count($this->components);
  }


  /**
  * Get all sub-components, or at least get those matching a type, or failling to match,
  * should the second parameter be set to false. Component types may be a string or an array
  * associating property names with true values: array( 'TYPE' => true, 'TYPE2' => true )
  *
  * @param mixed $type The type(s) to match (default: All)
  * @param boolean $normal_match Set to false to invert the match (default: true)
  * @return array an array of the sub-components
  */
  function GetComponents( $type = null, $normal_match = true ) {
    $components = $this->components;
    if ( $type != null ) {
      $testtypes = (gettype($type) == 'string' ? array( $type => true ) : $type );
      foreach( $components AS $k => $v ) {
//        printf( "Type: %s, %s, %s\n", $v->GetType(),
//                 ($normal_match && isset($testtypes[$v->GetType()]) && $testtypes[$v->GetType()] ? 'true':'false'),
//                 ( !$normal_match && (!isset($testtypes[$v->GetType()]) || !$testtypes[$v->GetType()]) ? 'true':'false')
//               );
        if ( !($normal_match && isset($testtypes[$v->GetType()]) && $testtypes[$v->GetType()] )
            && !( !$normal_match && (!isset($testtypes[$v->GetType()]) || !$testtypes[$v->GetType()])) ) {
          unset($components[$k]);
        }
      }
      $components = array_values($components);
    }
//    print_r($components);
    return $components;
  }


  /**
  * Clear all components, or the components matching a particular type
  * @param string $type The type of component - omit for all components
  */
  function ClearComponents( $type = null ) {
    if ( $type != null ) {
      $testtypes = (gettype($type) == 'string' ? array( $type => true ) : $type );
      // First remove all the existing ones of that type
      foreach( $this->components AS $k => $v ) {
        if ( isset($testtypes[$v->GetType()]) && $testtypes[$v->GetType()] ) {
          unset($this->components[$k]);
          if ( isset($this->rendered) ) unset($this->rendered);
        }
        else {
          if ( ! $this->components[$k]->ClearComponents($testtypes) ) {
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
  * @param array of vComponent $new_components The new components to replace the existing ones
  * @param string $type The type of components to be replaced.  Defaults to null, which means all components will be replaced.
  */
  function SetComponents( $new_component, $type = null ) {
    if ( isset($this->rendered) ) unset($this->rendered);
    $this->ClearComponents($type);
    foreach( $new_component AS $k => $v ) {
      $this->components[] = $v;
    }
  }


  /**
  * Adds a new subcomponent
  *
  * @param vComponent $new_component The new component to append to the set
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
      if ( !isset($keep[$v->GetType()]) || !$keep[$v->GetType()] ) {
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
    if ( !isset($component_list) || $component_list[$this->GetType()] ) {
      foreach( $this->properties AS $k => $v ) {
        if ( !isset($keep[$v->Name()]) || !$keep[$v->Name()] ) {
          unset($this->properties[$k]);
          if ( isset($this->rendered) ) unset($this->rendered);
        }
      }
    }
    foreach( $this->components AS $k => $v ) {
      $v->MaskProperties($keep, $component_list);
    }
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


    
  public function __toString() {
    return $this->Render();
  }

  
  /**
  * Return an array of properties matching the specified path
  *
  * @return array An array of vProperty within the tree which match the path given, in the form
  *  [/]COMPONENT[/...]/PROPERTY in a syntax kind of similar to our poor man's XML queries. We
  *  also allow COMPONENT and PROPERTY to be !COMPONENT and !PROPERTY for ++fun.
  *
  * @note At some point post PHP4 this could be re-done with an iterator, which should be more efficient for common use cases.
  */
  function GetPropertiesByPath( $path ) {
    $properties = array();
    dbg_error_log( 'vComponent', "GetPropertiesByPath: Querying within '%s' for path '%s'", $this->type, $path );
    if ( !preg_match( '#(/?)(!?)([^/]+)(/?.*)$#', $path, $matches ) ) return $properties;

    $anchored = ($matches[1] == '/');
    $inverted = ($matches[2] == '!');
    $ourtest = $matches[3];
    $therest = $matches[4];
    dbg_error_log( 'vComponent', "GetPropertiesByPath: Matches: %s -- %s -- %s -- %s\n", $matches[1], $matches[2], $matches[3], $matches[4] );
    if ( $ourtest == '*' || (($ourtest == $this->type) !== $inverted) && $therest != '' ) {
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

    if ( ! $anchored ) {
      /**
      * Our input $path was not rooted, so we recurse further
      */
      foreach( $this->components AS $k => $v ) {
        $properties = array_merge( $properties, $v->GetPropertiesByPath($path) );
      }
    }
    dbg_error_log('vComponent', "GetPropertiesByPath: Found %d within '%s' for path '%s'\n", count($properties), $this->type, $path );
    return $properties;
  }



  /**
   * Test a PROP-FILTER or COMP-FILTER and return a true/false
   * COMP-FILTER (is-defined | is-not-defined | (time-range?, prop-filter*, comp-filter*))
   * PROP-FILTER (is-defined | is-not-defined | ((time-range | text-match)?, param-filter*))
   *
   * @param array $filter An array of XMLElement defining the filter
   *
   * @return boolean Whether or not this vComponent passes the test
   */
  function TestFilter( $filters ) {
    foreach( $filters AS $k => $v ) {
      $tag = $v->GetTag();
      switch( $tag ) {
        case 'urn:ietf:params:xml:ns:caldav:is-defined':
        case 'urn:ietf:params:xml:ns:carddav:is-defined':
          break;

        case 'urn:ietf:params:xml:ns:caldav:is-not-defined':
        case 'urn:ietf:params:xml:ns:carddav:is-not-defined':
          return false;
          break;

        case 'urn:ietf:params:xml:ns:caldav:comp-filter':
          $subfilter = $v->GetContent();
          $subcomponents = $this->GetComponents($v->GetAttribute("name"));
          if ( count($subcomponents) > 0 ) {
            foreach( $subcomponents AS $kk => $subcomponent ) {
              if ( ! $subcomponent->TestFilter($subfilter) ) return false;
            }
          }
          else {
            if ( $subfilter[0] == 'urn:ietf:params:xml:ns:caldav:is-defined'
                 || $subfilter[0] == 'urn:ietf:params:xml:ns:carddav:is-defined' ) {
              return false;
            }
          }
          break;

        case 'urn:ietf:params:xml:ns:carddav:prop-filter':
        case 'urn:ietf:params:xml:ns:caldav:prop-filter':
          $subfilter = $v->GetContent();
          $properties = $this->GetProperties($v->GetAttribute("name"));
          if ( count($properties) > 0 ) {
            foreach( $properties AS $kk => $property ) {
              if ( !$property->TestFilter($subfilter) ) return false;
            }
          }
          else {
            if ( $subfilter[0] == 'urn:ietf:params:xml:ns:caldav:is-defined'
                 || $subfilter[0] == 'urn:ietf:params:xml:ns:carddav:is-defined' ) {
              return false;
            }
          }
          break;
      }
    }
    return true;
  }

}

