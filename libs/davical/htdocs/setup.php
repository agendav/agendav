<?php
/** @TODO: work out something more than true/false returns for dependency checks */

/**
 * We put many of these checks before we even try to load always.php so that we
 * can try and do some diagnostic work to ensure it will load OK.
 */
function check_pgsql() {
  return function_exists('pg_connect');
}

function check_pdo() {
  return class_exists('PDO');
}

function check_pdo_pgsql() {
  global $loaded_extensions;

  if ( !check_pdo() ) return false;
  return isset($loaded_extensions['pdo_pgsql']);
}

function check_gettext() {
  global $phpinfo, $loaded_extensions;

  if ( !function_exists('gettext') ) return false;
  return isset($loaded_extensions['gettext']);
}

function check_suhosin_server_strip() {
  global $loaded_extensions;

  if ( !isset($loaded_extensions['suhosin']) ) return true;
  if ( ini_get('suhosin.server.strip') == "0" ) {
    return true;
  }

  return false;
}

function check_magic_quotes_gpc() {
  global $loaded_extensions;

  if ( ini_get('magic_quotes_gpc') == "0" ) {
    return true;
  }

  return false;
}

function check_magic_quotes_runtime() {
  global $loaded_extensions;

  if ( ini_get('magic_quotes_runtime') == "0" ) {
    return true;
  }

  return false;
}

function do_error( $errormessage ) {
  printf("<p class='error'>%s</p>", $errormessage );  
}
$loaded_extensions = array_flip(get_loaded_extensions());
if ( !check_pgsql() )     do_error("PHP 'pgsql' functions are not available" );
if ( !check_pdo() )       do_error("PHP 'PDO' module is not available" );
if ( !check_pdo_pgsql() ) do_error("The PDO drivers for PostgreSQL are not available" );


include("./always.php");
include("DAViCalSession.php");

ob_start( );
phpinfo();
$phpinfo = ob_get_contents( );
ob_end_clean( );

$phpinfo = preg_replace( '{^.*?<body>}s', '', $phpinfo);
$phpinfo = preg_replace( '{</body>.*?$}s', '', $phpinfo);


if ( check_pgsql() ) {
  $session->LoginRequired( (isset($c->restrict_setup_to_admin) && $c->restrict_setup_to_admin ? 'Admin' : null ) );
}


include("interactive-page.php");
include("page-header.php");

require_once("AwlQuery.php");

function check_schema_version() {
  global $c;
  if ( $c->want_dbversion[0] == $c->schema_major
    && $c->want_dbversion[1] == $c->schema_minor
    && $c->want_dbversion[2] == $c->schema_patch ) {
    return true;
  }
  return false;
}

function check_davical_version() {
  global $c;
  $url = 'http://www.davical.org/current_davical_version?v='.$c->version_string;
  $version_file = @fopen($url, 'r');
  if ( ! $version_file ) return translate("Could not retrieve") . " '$url'";
  $current_version = trim(fread( $version_file,12));
  fclose($version_file);
  return ( $c->version_string == $current_version ? true : $current_version );
}


function check_awl_version() {
  global $c;
  
  if ( function_exists('awl_version') ) {
    return ( $c->want_awl_version == awl_version() ? true : sprintf( "want %f", $c->awl_library_version) );
  }
  return false;
}


function build_site_statistics() {
  $principals  = translate('No. of Principals');
  $collections = translate('No. of Collections');
  $resources   = translate('No. of Resources');
  $table = <<<EOTABLE
<table class="statistics">
<tr><th>$principals</th><th>$collections</th><th>$resources</th></tr>
<tr>%s</tr>
</table>
EOTABLE;

  if ( !check_pdo_pgsql() ) {
    return sprintf( $table, '<td colspan="3">'.translate('Site Statistics require the database to be available!').'</td>');
  }
  $sql = 'SELECT
(SELECT count(1) FROM principal) AS principals,
(SELECT count(1) FROM collection) AS collections,
(SELECT count(1) FROM caldav_data) AS resources';
  $qry = new AwlQuery($sql);
  if ( $qry->Exec('setup',__LINE__,__FILE__) && $s = $qry->Fetch() ) {
    $row = sprintf('<td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>',
                                       $s->principals, $s->collections, $s->resources );
    return sprintf( $table, $row );
  }
  return sprintf( $table, '<td colspan="3">'.translate('Site Statistics require the database to be available!').'</td>');
}


$dependencies = array(
  translate('Current DAViCal version '). $c->version_string => 'check_davical_version',
  translate('DAViCal DB Schema version '). implode('.',$c->want_dbversion) => 'check_schema_version',
  translate('AWL Library version '). $c->want_awl_version => 'check_awl_version',
  translate('PHP PDO module available') => 'check_pdo',
  translate('PDO PostgreSQL drivers') => 'check_pdo_pgsql',
  translate('PHP PostgreSQL available') => 'check_pgsql',
  translate('GNU gettext support') => 'check_gettext',
  translate('Suhosin "server.strip" disabled') => 'check_suhosin_server_strip',
  translate('PHP Magic Quotes GPC off') => 'check_magic_quotes_gpc',
  translate('PHP Magic Quotes runtime off') => 'check_magic_quotes_runtime' /*,
  'YAML' => 'php5-syck' */
);

$dependencies_table = '';
$dep_tpl = '<tr class="%s">
  <td>%s</td>
  <td>%s</td>
</tr>
';
foreach( $dependencies AS $k => $v ) {
  $ok = $v();
  $dependencies_table .= sprintf( $dep_tpl, ($ok === true ? 'dep_ok' : 'dep_fail'), $k,  (is_string($ok) ? $ok : ($ok ? translate('OK') : translate('Failed'))) );
}

$want_dbversion = implode('.',$c->want_dbversion);

$heading_setup = translate('Setup');
$paragraph_setup = translate('This page primarily checks the environment needed for DAViCal to work correctly.  Suggestions or patches to make it do more useful stuff will be gratefully received.');

$heading_versions = translate('Current Versions');
if ( check_schema_version() != true )
{
  $paragraph_versions = translate('You are currently running DAViCal version %s. The database schema should be at version %s and it is at version %d.%d.%d.');
  $paragraph_versions = sprintf( $paragraph_versions, $c->version_string, $want_dbversion, $c->schema_major, $c->schema_minor, $c->schema_patch);
} else {
  $paragraph_versions = translate('You are currently running DAViCal version %s. The database schema is at version %d.%d.%d.');
  $paragraph_versions = sprintf( $paragraph_versions, $c->version_string, $c->schema_major, $c->schema_minor, $c->schema_patch);
}

$heading_dependencies = translate('Dependencies');
$th_dependency = translate('Dependency');
$th_status     = translate('Status');

$heading_site_statistics = translate('Site Statistics');
$site_statistics_table = build_site_statistics();

$heading_config_clients = translate('Configuring Calendar Clients for DAViCal');
$heading_config_davical = translate('Configuring DAViCal');
$davical_configuration_errors = '';
if ( $config_warnings != '' ) {
  $davical_configuration_errors = '<div class="error"><h3 class="error">' . translate('Your configuration produced PHP errors which should be corrected') . '</h3>
<pre>
'.$config_warnings.'
</pre></div>
';
}

  echo <<<EOBODY
<style>
tr.dep_ok {
  background-color:#80ff80;
}
tr.dep_fail {
  background-color:#ffc0c0;
}
table, table.dependencies {
  border: 1px grey solid;
  border-collapse: collapse;
  padding: 0.1em;
  margin: 0 1em 1.5em;
}
table tr td, table tr th, table.dependencies tr td, table.dependencies tr th {
  border: 1px grey solid;
  padding: 0.1em 0.2em;
}
p {
  padding: 0.3em 0.2em 0.7em;
}
</style>

<h1>$heading_setup</h1>
<p>$paragraph_setup

<h2>$heading_versions</h2>
<p>$paragraph_versions
<br>&nbsp;
</p>

<h2>$heading_dependencies</h2>
<p>
<table class="dependencies">
<tr>
<th>$th_dependency</th>
<th>$th_status</th>
</tr>
$dependencies_table
</table>
<br>&nbsp;
</p>

<script language="javascript">
function toggle_visible() {
  var argv = toggle_visible.arguments;
  var argc = argv.length;

  var fld_checkbox =  document.getElementById(argv[0]);

  if ( argc < 2 ) {
    return;
  }

  for (var i = 1; i < argc; i++) {
    var block_id = argv[i].substr(1);
    var block_logical = argv[i].substr(0,1);
    var b = document.getElementById(block_id);
    if ( block_logical == '!' )
      b.style.display = (fld_checkbox.checked ? 'none' : '');
    else
      b.style.display = (!fld_checkbox.checked ? 'none' : '');
  }
}
</script><p><label>Show phpinfo() output:<input type="checkbox" value="1" id="fld_show_phpinfo" onclick="toggle_visible('fld_show_phpinfo','=phpinfo')"></label></p>
<div style="display:none" id="phpinfo">$phpinfo</div>

<h2>$heading_site_statistics</h2>
<p>$site_statistics_table</p>

<h2>$heading_config_clients</h2>
<p>The <a href="http://www.davical.org/clients.php">client setup page on the DAViCal website</a> has information on how
to configure Evolution, Sunbird, Lightning and Mulberry to use remotely hosted calendars.</p>
<p>The administrative interface has no facility for viewing or modifying calendar data.</p>

<h2>$heading_config_davical</h2>
<p>If you can read this then things must be mostly working already.</p>
$davical_configuration_errors
<p>The <a href="http://www.davical.org/installation.php">installation page on the DAViCal website</a> has
some further information on how to install and configure this application.</p>
EOBODY;

include("page-footer.php");
