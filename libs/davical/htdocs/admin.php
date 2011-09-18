<?php
require_once('./always.php');
require_once('classEditor.php');
require_once('classBrowser.php');
include("DAViCalSession.php");
$session->LoginRequired();

require_once('AwlQuery.php');

param_to_global('action', '{(edit|browse)}', 'action');
param_to_global('component', '{[a-z0-9-_]+}', 't');
param_to_global('id', '{[a-z0-9-_]+}', 'id');

$c->stylesheets[] = 'css/'.$action.'.css';
if ( $c->enable_row_linking ) {
  $c->scripts[] = 'js/browse.js';
}

require_once('interactive-page.php');

$page_elements = array();
$code_file = sprintf( 'ui/%s-%s.php', $component, $action );
if ( ! @include_once( $code_file ) ) {
  $c->messages[] = sprintf('No page found to %s %s%s%s', $action, ($action == 'browse' ? '' : 'a '), $component, ($action == 'browse' ? 's' : ''));
  include('page-header.php');
  include('page-footer.php');
  exit(0);
}

include('page-header.php');

/**
* Page elements could be an array of viewers, browsers or something else
* that supports the Render() method... or a non-object which we assume is
* just a string of text that we echo.
*/
$heading_level = null;
foreach( $page_elements AS $k => $page_element ) {
  if ( is_object($page_element) ) {
    echo $page_element->Render($heading_level);
    $heading_level = 'h2';
  }
  else {
    echo $page_element;
  }
}

if (function_exists("post_render_function")) {
  post_render_function();
}

include('page-footer.php');
