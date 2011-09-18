<?php
/**
* A class to assist with construction of XML documents
*
* @package   awl
* @subpackage   XMLElement
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://www.gnu.org/licenses/lgpl-3.0.txt  GNU LGPL version 3 or later
*/

require_once('AWLUtilities.php');

/**
* A class for XML elements which may have attributes, or contain
* other XML sub-elements
*
* @package   awl
*/
class XMLElement {
  protected $tagname;
  protected $xmlns;
  protected $attributes;
  protected $content;
  protected $_parent;

  /**
  * Constructor - nothing fancy as yet.
  *
  * @param string $tagname The tag name of the new element
  * @param mixed $content Either a string of content, or an array of sub-elements
  * @param array $attributes An array of attribute name/value pairs
  * @param array $xmlns An XML namespace specifier
  */
  function __construct( $tagname, $content=false, $attributes=false, $xmlns=null ) {
    $this->tagname=$tagname;
    if ( gettype($content) == "object" ) {
      // Subtree to be parented here
      $this->content = array(&$content);
    }
    else {
      // Array or text
      $this->content = $content;
    }
    $this->attributes = $attributes;
    if ( isset($this->attributes['xmlns']) ) {  // Oversimplification to be removed
      $this->xmlns = $this->attributes['xmlns'];
    }
    if ( isset($xmlns) ) {
      $this->xmlns = $xmlns;
    }
  }


  /**
  * Count the number of elements
  * @return int The number of elements
  */
  function CountElements( ) {
    if ( $this->content === false ) return 0;
    if ( is_array($this->content) ) return count($this->content);
    if ( $this->content == '' ) return 0;
    return 1;
  }

  /**
  * Set an element attribute to a value
  *
  * @param string The attribute name
  * @param string The attribute value
  */
  function SetAttribute($k,$v) {
    if ( gettype($this->attributes) != "array" ) $this->attributes = array();
    $this->attributes[$k] = $v;
    if ( strtolower($k) == 'xmlns' ) {
      $this->xmlns = $v;
    }
  }

  /**
  * Set the whole content to a value
  *
  * @param mixed The element content, which may be text, or an array of sub-elements
  */
  function SetContent($v) {
    $this->content = $v;
  }

  /**
  * Accessor for the tag name
  *
  * @return string The tag name of the element
  */
  function GetTag() {
    return $this->tagname;
  }

  /**
  * Accessor for the full-namespaced tag name
  *
  * @return string The tag name of the element, prefixed by the namespace
  */
  function GetNSTag() {
    return $this->xmlns . ':' . $this->tagname;
  }

  /**
  * Accessor for a single attribute
  * @param string $attr The name of the attribute.
  * @return string The value of that attribute of the element
  */
  function GetAttribute( $attr ) {
    if ( $attr == 'xmlns' ) return $this->xmlns;
    if ( isset($this->attributes[$attr]) ) return $this->attributes[$attr];
    return null;
  }

  /**
  * Accessor for the attributes
  *
  * @return array The attributes of this element
  */
  function GetAttributes() {
    return $this->attributes;
  }

  /**
  * Accessor for the content
  *
  * @return array The content of this element
  */
  function GetContent() {
    return $this->content;
  }

  /**
  * Return an array of elements matching the specified tag, or all elements if no tag is supplied.
  * Unlike GetContent() this will always return an array.
  *
  * @return array The XMLElements within the tree which match this tag
  */
  function GetElements( $tag=null, $recursive=false ) {
    $elements = array();
    if ( gettype($this->content) == "array" ) {
      foreach( $this->content AS $k => $v ) {
        if ( !isset($tag) || (isset($v->tagname) && $v->tagname == $tag) ) {
          $elements[] = $v;
        }
        if ( $recursive ) {
          $elements = $elements + $v->GetElements($tag,true);
        }
      }
    }
    else if ( !isset($tag) || (isset($v->content->tagname) && $this->content->tagname == $tag) ) {
      $elements[] = $this->content;
    }
    return $elements;
  }


  /**
  * Return an array of elements matching the specified path
  *
  * @return array The XMLElements within the tree which match this tag
  */
  function GetPath( $path ) {
    $elements = array();
    // printf( "Querying within '%s' for path '%s'\n", $this->tagname, $path );
    if ( !preg_match( '#(/)?([^/]+)(/?.*)$#', $path, $matches ) ) return $elements;
    // printf( "Matches: %s -- %s -- %s\n", $matches[1], $matches[2], $matches[3] );
    if ( $matches[2] == '*' || strtolower($matches[2]) == strtolower($this->tagname) ) {
      if ( $matches[3] == '' ) {
        /**
        * That is the full path
        */
        $elements[] = $this;
      }
      else if ( gettype($this->content) == "array" ) {
        /**
        * There is more to the path, so we recurse into that sub-part
        */
        foreach( $this->content AS $k => $v ) {
          $elements = array_merge( $elements, $v->GetPath($matches[3]) );
        }
      }
    }

    if ( $matches[1] != '/' && gettype($this->content) == "array" ) {
      /**
      * If our input $path was not rooted, we recurse further
      */
      foreach( $this->content AS $k => $v ) {
        $elements = array_merge( $elements, $v->GetPath($path) );
      }
    }
    // printf( "Found %d within '%s' for path '%s'\n", count($elements), $this->tagname, $path );
    return $elements;
  }


  /**
  * Add a sub-element
  *
  * @param object An XMLElement to be appended to the array of sub-elements
  */
  function AddSubTag(&$v) {
    if ( gettype($this->content) != "array" ) $this->content = array();
    $this->content[] =& $v;
    return count($this->content);
  }

  /**
  * Add a new sub-element
  *
  * @param string The tag name of the new element
  * @param mixed Either a string of content, or an array of sub-elements
  * @param array An array of attribute name/value pairs
  *
  * @return objectref A reference to the new XMLElement
  */
  function &NewElement( $tagname, $content=false, $attributes=false, $xmlns=null ) {
    if ( gettype($this->content) != "array" ) $this->content = array();
    $element = new XMLElement($tagname,$content,$attributes,$xmlns);
    $this->content[] =& $element;
    return $element;
  }


  /**
  * Render just the internal content
  *
  * @return string The content of this element, as a string without this element wrapping it.
  */
  function RenderContent($indent=0, $nslist=null ) {
    $r = "";
    if ( is_array($this->content) ) {
      /**
      * Render the sub-elements with a deeper indent level
      */
      $r .= "\n";
      foreach( $this->content AS $k => $v ) {
        if ( is_object($v) ) {
          $r .= $v->Render($indent+1, "", $nslist);
        }
      }
      $r .= substr("                        ",0,$indent);
    }
    else {
      /**
      * Render the content, with special characters escaped
      *
      */
      $r .= htmlspecialchars($this->content, ENT_NOQUOTES );
    }
    return $r;
  }


  /**
  * Render the document tree into (nicely formatted) XML
  *
  * @param int The indenting level for the pretty formatting of the element
  */
  function Render($indent=0, $xmldef="", $nslist=null) {
    $r = ( $xmldef == "" ? "" : $xmldef."\n");

    $attr = "";
    $tagname = $this->tagname;
    if ( gettype($this->attributes) == "array" ) {
      /**
      * Render the element attribute values
      */
      foreach( $this->attributes AS $k => $v ) {
        if ( preg_match('#^xmlns(:?(.+))?$#', $k, $matches ) ) {
          if ( !isset($nslist) ) $nslist = array();
          $prefix = (isset($matches[2]) ? $matches[2] : '');
          if ( isset($nslist[$v]) && $nslist[$v] == $prefix ) continue; // No need to include in list as it's in a wrapping element
          $nslist[$v] = $prefix;
          if ( !isset($this->xmlns) ) $this->xmlns = $v;
        }
        $attr .= sprintf( ' %s="%s"', $k, htmlspecialchars($v) );
      }
    }
    if ( isset($this->xmlns) && isset($nslist[$this->xmlns]) && $nslist[$this->xmlns] != '' ) {
      $tagname = $nslist[$this->xmlns] . ':' . $tagname;
    }

    $r .= substr("                        ",0,$indent) . '<' . $tagname . $attr;

    if ( (is_array($this->content) && count($this->content) > 0) || (!is_array($this->content) && strlen($this->content) > 0) ) {
      $r .= ">";
      $r .= $this->RenderContent($indent,$nslist);
      $r .= '</' . $tagname.">\n";
    }
    else {
      $r .= "/>\n";
    }
    return $r;
  }


  function __tostring() {
    return $this->Render();
  }
}


/**
* Rebuild an XML tree in our own style from the parsed XML tags using
* a tail-recursive approach.
*
* @param array $xmltags An array of XML tags we get from using the PHP XML parser
* @param intref &$start_from A pointer to our current integer offset into $xmltags
* @return mixed Either a single XMLElement, or an array of XMLElement objects.
*/
function BuildXMLTree( $xmltags, &$start_from ) {
  $content = array();

  if ( !isset($start_from) ) $start_from = 0;

  for( $i=0; $i < 50000 && isset($xmltags[$start_from]); $i++) {
    $tagdata = $xmltags[$start_from++];
    if ( !isset($tagdata) || !isset($tagdata['tag']) || !isset($tagdata['type']) ) break;
    if ( $tagdata['type'] == "close" ) break;
    $attributes = ( isset($tagdata['attributes']) ? $tagdata['attributes'] : false );
    if ( $tagdata['type'] == "open" ) {
      $subtree = BuildXMLTree( $xmltags, $start_from );
      $content[] = new XMLElement($tagdata['tag'], $subtree, $attributes );
    }
    else if ( $tagdata['type'] == "complete" ) {
      $value = ( isset($tagdata['value']) ? $tagdata['value'] : false );
      $content[] = new XMLElement($tagdata['tag'], $value, $attributes );
    }
  }

  /**
  * If there is only one element, return it directly, otherwise return the
  * array of them
  */
  if ( count($content) == 1 ) {
    return $content[0];
  }
  return $content;
}

