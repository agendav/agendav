<?php
/**
* Some intelligence and standardisation around presenting a menu hierarchy.
*
* See the MenuSet class for examples as that is the primary interface.
* @see MenuSet
*
* @package awl
* @subpackage   MenuSet
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst IT Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

require_once("AWLUtilities.php");

/**
* Each menu option is an object.
* @package awl
*/
class MenuOption {
  /**#@+
  * @access private
  */
  /**
  * The label for the menu item
  * @var string
  */
  var $label;

  /**
  * The target URL for the menu
  * @var string
  */
  var $target;

  /**
  * The title for the item when moused over, which should be displayed as a tooltip.
  * @var string
  */
  var $title;

  /**
  * Whether the menu option is active
  * @var string
  */
  var $active;

  /**
  * For sorting menu options
  * @var string
  */
  var $sortkey;

  /**
  * Style to render the menu option with.
  * @var string
  */
  var $style;

  /**
  * The MenuSet that this menu is a parent of
  * @var string
  */
  var $submenu_set;
  /**#@-*/

  /**
  * A reference to this menu option itself
  * @var reference
  */
  var $self;

  /**#@+
  * @access public
  */
  /**
  * The rendered HTML fragment (once it has been).
  * @var string
  */
  var $rendered;
  /**#@-*/

  /**
  * The thing we click
  * @param string $label The label to display for this option.
  * @param string $target The URL to target for this option.
  * @param string $title Some tooltip help for the title tag.
  * @param string $style A base class name for this option.
  * @param int $sortkey An (optional) value to allow option ordering.
  */
  function MenuOption( $label, $target, $title="", $style="menu", $sortkey=1000 ) {
    $this->label  = $label;
    $this->target = $target;
    $this->title  = $title;
    $this->style  = $style;
    $this->attributes = array();
    $this->active = false;
    $this->sortkey = $sortkey;

    $this->rendered = "";
    $this->self  =& $this;
  }

  /**
  * Convert the menu option into an HTML string
  * @return string The HTML fragment for the menu option.
  */
  function Render( ) {
    $r = sprintf('<a href="%s" class="%s" title="%s"%s>%s</a>',
            $this->target, $this->style, htmlspecialchars($this->title), "%%attributes%%",
            htmlspecialchars($this->label), $this->style );

    // Now process the generic attributes
    $attribute_values = "";
    foreach( $this->attributes AS $k => $v ) {
      if ( substr($k, 0, 1) == '_' ) continue;
      $attribute_values .= ' '.$k.'="'.htmlspecialchars($v).'"';
    }
    $r = str_replace( '%%attributes%%', $attribute_values, $r );

    $this->rendered = $r;
    return "$r";
  }

  /**
  * Set arbitrary attributes of the menu option
  * @param string $attribute An arbitrary attribute to be set in the hyperlink.
  * @param string $value A value for this attribute.
  */
  function Set( $attribute, $value ) {
    $this->attributes[$attribute] = $value;
  }

  /**
  * Mark it as active, with a fancy style to distinguish that
  * @param string $style A style used to highlight that the option is active.
  */
  function Active( $style=false ) {
    $this->active = true;
    if ( $style ) $this->style = $style;
  }

  /**
  * This menu option is now promoted to the head of a tree
  */
  function AddSubmenu( &$submenu_set ) {
    $this->submenu_set = &$submenu_set;
  }

  /**
  * Whether this option is currently active.
  * @return boolean The value of the active flag.
  */
  function IsActive( ) {
    return ( $this->active );
  }

  /**
  * Whether this option is currently active.
  * @return boolean The value of the active flag.
  */
  function MaybeActive( $test_pattern, $active_style ) {
    if ( is_string($test_pattern) && preg_match($test_pattern,$_SERVER['REQUEST_URI']) ) {
      $this->Active($active_style);
    }
    return ( $this->active );
  }
}


/**
* _CompareMenuSequence is used in sorting the menu options into the sequence order
*
* @param objectref $a The first menu option
* @param objectref $b The second menu option
* @return int ( $a == b ? 0 ( $a > b ? 1 : -1 ))
*/
function _CompareMenuSequence( $a, $b ) {
  dbg_error_log("MenuSet", ":_CompareMenuSequence: Comparing %d with %d", $a->sortkey, $b->sortkey);
  return ($a->sortkey - $b->sortkey);
}



/**
* A MenuSet is a hierarchy of MenuOptions, some of which might be
* MenuSet objects themselves.
*
* The menu options are presented in HTML span tags, and the menus
* themselves are presented inside HTML div tags.  All layout and
* styling is expected to be provide by CSS.
*
* A non-trivial example would look something like this:
*<code>
*require("MenuSet.php");
*$main_menu = new MenuSet('menu', 'menu', 'menu_active');
*  ...
*$other_menu = new MenuSet('submenu', 'submenu', 'submenu_active');
*$other_menu->AddOption("Extra Other","/extraother.php","Submenu option to do extra things.");
*$other_menu->AddOption("Super Other","/superother.php","Submenu option to do super things.");
*$other_menu->AddOption("Meta Other","/metaother.php","Submenu option to do meta things.");
*  ...
*$main_menu->AddOption("Do This","/dothis.php","Option to do this thing.");
*$main_menu->AddOption("Do That","/dothat.php","Option to do all of that.");
*$main_menu->AddSubMenu( $other_menu, "Do The Other","/dotheother.php","Submenu to do all of the other things.", true);
*  ...
*if ( isset($main_menu) && is_object($main_menu) ) {
*  $main_menu->AddOption("Home","/","Go back to the home page");
*  echo $main_menu->Render();
*}
*</code>
* In a hierarchical menu tree, like the example above, only one sub-menu will be
* shown, which will be the first one that is found to have active menu options.
*
* The menu display will generally recognise the current URL and mark as active the
* menu option that matches it, but in some cases it might be desirable to force one
* or another option to be marked as active using the appropriate parameter to the
* AddOption or AddSubMenu call.
* @package awl
*/
class MenuSet {
  /**#@+
  * @access private
  */
  /**
  * CSS style to use for the div around the options
  * @var string
  */
  var $div_id;

  /**
  * CSS style to use for normal menu option
  * @var string
  */
  var $main_class;

  /**
  * CSS style to use for active menu option
  * @var string
  */
  var $active_class;

  /**
  * An array of MenuOption objects
  * @var array
  */
  var $options;

  /**
  * Any menu option that happens to parent this set
  * @var reference
  */
  var $parent;

  /**
  * The sortkey used by any previous option
  * @var last_sortkey
  */
  var $last_sortkey;

  /**
  * Will be set to true or false when we link active sub-menus, but will be
  * unset until we do that.
  * @var reference
  */
  var $has_active_options;
  /**#@-*/

  /**
  * Start a new MenuSet with no options.
  * @param string $div_id An ID for the HTML div that the menu will be presented in.
  * @param string $main_class A CSS class for most menu options.
  * @param string $active_class A CSS class for active menu options.
  */
  function MenuSet( $div_id, $main_class = '', $active_class = 'active' ) {
    $this->options = array();
    $this->main_class = $main_class;
    $this->active_class = $active_class;
    $this->div_id = $div_id;
  }

  /**
  * Add an option, which is a link.
  * The call will attempt to work out whether the option should be marked as
  * active, and will sometimes get it wrong.
  * @param string $label A Label for the new menu option
  * @param string $target The URL to target for this option.
  * @param string $title Some tooltip help for the title tag.
  * @param string $active Whether this option should be marked as Active.
  * @param int $sortkey An (optional) value to allow option ordering.
  * @param external open this link in a new window/tab.
  * @return mixed A reference to the MenuOption that was added, or false if none were added.
  */
  function &AddOption( $label, $target, $title="", $active=false, $sortkey=null, $external=false ) {
    if ( !isset($sortkey) ) {
      $sortkey = (isset($this->last_sortkey) ? $this->last_sortkey + 100 : 1000);
    }
    $this->last_sortkey = $sortkey;
    if ( version_compare(phpversion(), '5.0') < 0) {
      $new_option = new MenuOption( $label, $target, $title, $this->main_class, $sortkey );
    }
    else {
      $new_option = new MenuOption( $label, $target, $title, $this->main_class, $sortkey );
    }
    if ( ($old_option = $this->_OptionExists( $label )) === false ) {
      $this->options[] = &$new_option ;
    }
    else {
      dbg_error_log("MenuSet",":AddOption: Replacing existing option # $old_option ($label)");
      $this->options[$old_option] = &$new_option;  // Overwrite the existing option
    }
    if ( is_bool($active) && $active == false && $_SERVER['REQUEST_URI'] == $target ) {
      // If $active is not set, then we look for an exact match to the current URL
      $new_option->Active( $this->active_class );
    }
    else if ( is_bool($active) && $active ) {
      // When active is specified as a boolean, the recognition has been done externally
      $new_option->Active( $this->active_class );
    }
    else if ( is_string($active) && preg_match($active,$_SERVER['REQUEST_URI']) ) {
      // If $active is a string, then we match the current URL to that as a Perl regex
      $new_option->Active( $this->active_class );
    }

    if ( $external == true ) $new_option->Set('target', '_blank');

    return $new_option ;
  }

  /**
  * Add an option, which is a submenu
  * @param object &$submenu_set A reference to a menu tree
  * @param string $label A Label for the new menu option
  * @param string $target The URL to target for this option.
  * @param string $title Some tooltip help for the title tag.
  * @param string $active Whether this option should be marked as Active.
  * @param int $sortkey An (optional) value to allow option ordering.
  * @return mixed A reference to the MenuOption that was added, or false if none were added.
  */
  function &AddSubMenu( &$submenu_set, $label, $target, $title="", $active=false, $sortkey=2000 ) {
    $new_option =& $this->AddOption( $label, $target, $title, $active, $sortkey );
    $submenu_set->parent = &$new_option ;
    $new_option->AddSubmenu( $submenu_set );
    return $new_option ;
  }

  /**
  * Does the menu have any options that are active.
  * Most likely used so that we can then set the parent menu as active.
  * @param string $label A Label for the new menu option
  * @return boolean Whether the menu has options that are active.
  */
  function _HasActive( ) {
    if ( isset($this->has_active_options) ) {
      return $this->has_active_options;
    }
    foreach( $this->options AS $k => $v ) {
      if ( $v->IsActive() ) {
        $rc = true;
        return $rc;
      }
    }
    $rc = false;
    return $rc;
  }

  /**
  * Find out how many options the menu has.
  * @return int The number of options in the menu.
  */
  function Size( ) {
    return count($this->options);
  }

  /**
  * See if a menu already has this option
  * @return boolean Whether the option already exists in the menu.
  */
  function _OptionExists( $newlabel ) {
    $rc = false;
    foreach( $this->options AS $k => $v ) {
      if ( $newlabel == $v->label ) return $k;
    }
    return $rc;
  }

  /**
  * Mark each MenuOption as active that has an active sub-menu entry.
  *
  * Currently needs to be called manually before rendering but
  * really should probably be called as part of the render now,
  * and then this could be a private routine.
  */
  function LinkActiveSubMenus( ) {
    $this->has_active_options = false;
    foreach( $this->options AS $k => $v ) {
      if ( isset($v->submenu_set) && $v->submenu_set->_HasActive() ) {
        // Note that we need to do it this way, since $v is a copy, not a reference
        $this->options[$k]->Active( $this->active_class );
        $this->has_active_options = true;
      }
    }
  }

  /**
  * Mark each MenuOption as active that has an active sub-menu entry.
  *
  * Currently needs to be called manually before rendering but
  * really should probably be called as part of the render now,
  * and then this could be a private routine.
  */
  function MakeSomethingActive( $test_pattern ) {
    if ( $this->has_active_options ) return;  // Already true.
    foreach( $this->options AS $k => $v ) {
      if ( isset($v->submenu_set) && $v->submenu_set->_HasActive() ) {
        // Note that we need to do it this way, since $v is a copy, not a reference
        $this->options[$k]->Active( $this->active_class );
        $this->has_active_options = true;
        return $this->has_active_options;
      }
    }

    foreach( $this->options AS $k => $v ) {
      if ( isset($v->submenu_set) && $v->submenu_set->MakeSomethingActive($test_pattern) ) {
        // Note that we need to do it this way, since $v is a copy, not a reference
        $this->options[$k]->Active( $this->active_class );
        $this->has_active_options = true;
        return $this->has_active_options;
      }
      else {
        if ( $this->options[$k]->MaybeActive( $test_pattern, $this->active_class ) ) {
          $this->has_active_options = true;
          return $this->has_active_options;
        }
      }
    }
    return false;
  }

  /**
  * _CompareSequence is used in sorting the menu options into the sequence order
  *
  * @param objectref $a The first menu option
  * @param objectref $b The second menu option
  * @return int ( $a == b ? 0 ( $a > b ? 1 : -1 ))
  */
  function _CompareSequence( $a, $b ) {
    dbg_error_log("MenuSet",":_CompareSequence: Comparing %d with %d", $a->sortkey, $b->sortkey);
    return ($a->sortkey - $b->sortkey);
  }


  /**
  * Render the menu tree to an HTML fragment.
  *
  * @param boolean $submenus_inline Indicate whether to render the sub-menus within
  *   the menus, or render them entirely separately after we finish rendering the
  *   top level ones.
  * @return string The HTML fragment.
  */
  function Render( $submenus_inline = false ) {
    if ( !isset($this->has_active_options) ) {
      $this->LinkActiveSubMenus();
    }
    $options = $this->options;
    usort($options,"_CompareMenuSequence");
    $render_sub_menus = false;
    $r = "<div id=\"$this->div_id\">\n";
    foreach( $options AS $k => $v ) {
      $r .= $v->Render();
      if ( $v->IsActive() && isset($v->submenu_set) && $v->submenu_set->Size() > 0 ) {
        $render_sub_menus = $v->submenu_set;
        if ( $submenus_inline )
          $r .= $render_sub_menus->Render();
      }
    }
    $r .="</div>\n";
    if ( !$submenus_inline && $render_sub_menus != false ) {
      $r .= $render_sub_menus->Render();
    }
    return $r;
  }


  /**
  * Render the menu tree to an HTML fragment.
  *
  * @param boolean $submenus_inline Indicate whether to render the sub-menus within
  *   the menus, or render them entirely separately after we finish rendering the
  *   top level ones.
  * @return string The HTML fragment.
  */
  function RenderAsCSS( $depth = 0, $skip_empty = true ) {
    $this->LinkActiveSubMenus();

    if ( $depth > 0 )
      $class = "submenu" . $depth;
    else
      $class = "menu";

    $options = $this->options;
    usort($options,"_CompareMenuSequence");

    $r = "<div id=\"$this->div_id\" class=\"$class\">\n<ul>\n";
    foreach( $options AS $k => $v ) {
      if ( $skip_empty && isset($v->submenu_set) && $v->submenu_set->Size() < 1 ) continue;
      $r .= "<li>".$v->Render();
      if ( isset($v->submenu_set) && $v->submenu_set->Size() > 0 ) {
        $r .= $v->submenu_set->RenderAsCSS($depth+1);
      }
      $r .= "</li>\n";
    }
    $r .="</ul></div>\n";
    return $r;
  }
}
