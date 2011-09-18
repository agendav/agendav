<?php

if ( !isset($c->page_title) ) {
  $c->page_title = translate('DAViCal CalDAV Server');
}

function make_help_link($matches)
{
  global $c;

  // as usual: $matches[0] is the complete match
  // $matches[1] the match for the first subpattern
  // enclosed in '##...##' and so on
  // Use like: $s = preg_replace_callback('/##([^#]+)##', 'make_help_link', $s);
//  $help_topic = preg_replace( '/^##(.+)##$/', '$1', $matches[1]);
  $help_topic = $matches[1];
  $display_url = $help_topic;
  if ( $GLOBALS['session']->AllowedTo('Admin') || $GLOBALS['session']->AllowedTo('Support') ) {
    if ( strlen($display_url) > 30 ) {
      $display_url = substr( $display_url, 0, 28 ) . '...' ;
    }
  }
  else {
    $display_url = 'help';
  }
  return ' <a class="help" href="'.$c->base_url.'/help.php?h='.$help_topic.'" title="'.translate('Show help on').' &39;'.$help_topic.'&39;" target="_new">['.$display_url.']</a> ';
}


if ( !function_exists('send_page_header') ) {
function send_page_header() {
  global $session, $c, $main_menu, $related_menu;

  header( 'Content-type: text/html; charset="utf-8"' );

  echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
  echo <<<EOHDR
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title>$c->page_title</title>

EOHDR;

  foreach ( $c->stylesheets AS $stylesheet ) {
    echo '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'">';
  }
  if ( isset($c->local_styles) ) {
    // Always load local styles last, so they can override prior ones...
    foreach ( $c->local_styles AS $stylesheet ) {
      echo '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'">';
    }
  }

  if ( isset($c->print_styles) ) {
    // Finally, load print styles last, so they can override all of the above...
    foreach ( $c->print_styles AS $stylesheet ) {
      echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$stylesheet\" media=\"print\">\n";
    }
  }

  echo "</head>\n<body>\n";
  echo "<div id=\"pageheader\">\n";

  if ( isset($main_menu) ) echo $main_menu->RenderAsCSS();
  if ( isset($related_menu) && $related_menu->Size() > 0 ) {
    echo $related_menu->Render( true );
  }

  echo "</div>\n";

  if ( isset($c->messages) && is_array($c->messages) && count($c->messages) > 0 ) {
    echo "<div id=\"messages\"><ul class=\"messages\">\n";
    foreach( $c->messages AS $i => $msg ) {
      // ##HelpTextKey## gets converted to a "/help.phph=HelpTextKey" link
      $msg = preg_replace_callback("/##([^#]+)##/", "make_help_link", translate($msg));
      echo "<li class=\"messages\">$msg</li>\n";
    }
    echo "</ul>\n</div>\n";
  }

}
}

send_page_header();

