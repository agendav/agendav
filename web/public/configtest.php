<?php
/*
 * Copyright 2012 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */


/*
 * Set this to TRUE to enable config test (disabled by default)
 * Remember to disable it again when finished
 */
define('ENABLE_SETUP_TESTS', FALSE);

/*
 * Checks:
 *
 * - PHP >= 5.3.0
 * - magic_quotes_gpc and magic_quotes_runtime disabled
 * - php-mbstring available
 * - Correctly configured config.php, database.php and caldav.php 
 * - PHP MySQL extension available (or MySQLi)
 * - Working database connection
 * - Existing log directory and writable by current user
 */

if (ENABLE_SETUP_TESTS === FALSE) {
	echo "Access to setup tests is disabled";
	exit;
}


// Abort tests on error (e.g. syntax errors)
set_error_handler('error_abort_tests');

$tests = array();
$keep_checking = TRUE;

// PHP version
$cmp = version_compare(phpversion(), '5.3.0');

$tests[] = array('PHP version', phpversion(), 
		($cmp >= 0) ? 'OK' : 'PHP 5.3.0 or later required');


// magic_quotes_gpc
$res_magic_quotes_gpc = (get_magic_quotes_gpc() == 0);
$tests[] = array('magic_quotes_gpc', $res_magic_quotes_gpc ? 'Disabled' :
		'Enabled', $res_magic_quotes_gpc ? 'OK' : 
		'Disable it inside <tt>php.ini</tt>');

// magic_quotes_runtime
$res_magic_quotes_runtime = (get_magic_quotes_runtime() == 0);
$tests[] = array('magic_quotes_runtime', 
		$res_magic_quotes_runtime ? 'Disabled' : 'Enabled',
		$res_magic_quotes_runtime ? 'OK' : 
		'Disable it inside <tt>php.ini</tt>');

// PHP mbstring
if (extension_loaded('mbstring')) {
	$tests[] = array('mbstring extension', 'Available', 'OK');
} else {
	$tests[] = array('mbstring extension', 'Not installed', 
			'mbstring extension is needed by AgenDAV');
}


// Configuration files: config.php, database.php and caldav.php
$cwd = dirname(__FILE__);
$configdir = preg_replace('/public$/', 'application/config', $cwd);

$test_subj = 'File <tt>config.php</tt>';

if (!file_exists($configdir . '/config.php')) {
	$tests[] = array($test_subj,
			'Not present or readable', 
			'Create it using template <tt>config.php.template</tt>');

	$keep_checking = FALSE;
} else {
	$tests[] = array($test_subj, 'Exists', 'OK');
}

$test_subj = 'File <tt>database.php</tt>';
if ($keep_checking && !file_exists($configdir . '/database.php')) {
	$tests[] = array($test_subj, 
			'Not present or readable', 
			'Create it using template <tt>database.php.template</tt>');
	$keep_checking = FALSE;

} elseif ($keep_checking) {
	$tests[] = array($test_subj, 'Exists', 'OK');
}

$test_subj = 'File <tt>caldav.php</tt>';
if ($keep_checking && !file_exists($configdir . '/caldav.php')) {
	$tests[] = array($test_subj, 
			'Not present or readable', 
			'Create it using template <tt>caldav.php.template</tt>');
	$keep_checking = FALSE;

} elseif ($keep_checking) {
	$tests[] = array($test_subj, 'Exists', 'OK');
}

// Fool CodeIgniter and load configuration files
define('BASEPATH', '/tmp');
include($configdir .'/config.php');
include($configdir .'/database.php');

if ($keep_checking) {
	// PHP + MySQL
	switch ($db['default']['dbdriver']) {
		case 'mysql':
			$check_sql_ext = 'mysql';
			break;
		case 'mysqli':
			$check_sql_ext = 'mysqli';
			break;
		default:
			$tests[] = array('SQL driver', 'Unsupported ' .
					$db['default']['dbdriver'], 
					'AgenDAV requires a MySQL database');
			$keep_checking = FALSE;
	}

	if ($keep_checking) {
		if (!extension_loaded($check_sql_ext)) {
			$tests[] = array('PHP + MySQL', 'Not available', 
					'Configured DB driver inside database.php (<tt>'
						. $db['default']['dbdriver'] . '</tt>) is not'
						. ' available to PHP');
	} else {
		$tests[] = array('PHP + MySQL', 'Yes', 'OK');
	}
}



// Database connection

$db = $db['default'];
$link = @mysql_connect($db['hostname'], $db['username'], 
		$db['password']);

$test_subj = 'Database connection';
if (!$link) {
	$tests[] = array($test_subj, 'Could not connect: <tt>' .
			mysql_error() .'</tt>', 'Check database connection '
			.' parameters (file <tt>database.php</tt>');
} else {
	$ret = @mysql_select_db($db['database'],$link);
	if ($ret === FALSE) {
		$tests[] = array($test_subj, 'Connection succeeded, but'
				.' could not use database <tt>'.$db['database'].'</tt>');
	} else {
		$tests[] = array($test_subj, 'Working', 'OK');
	}
	@mysql_close($link);
}

// Log directory
$test_subj = 'Log directory';
if (!is_writable($config['log_path'])) {
	$tests[] = array($test_subj, 
		'Does not exist or is not writable by web server',
		'Check directory <tt>'.$config['log_path'].'</tt>');
} else {
	$tests[] = array($test_subj, 
			'All right', 'OK');
}



// ---- Tests end -----
} // $keep_checking

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">

<head>
<title>AgenDAV configuration test</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
  <h1>Configuration test</h1>

  <table>
   <thead>
    <tr>
	 <th>Test</th>
	 <th>Result</th>
	 <th>Comments</th>
	</tr>
   </thead>
   <tbody>
   <?php
   foreach ($tests as $test) {
	   ?>
		   <tr>
		   <td><?php echo $test[0]?></td>
		   <td style="color: #ffffff; background-color: <?php 
		   	echo $test[2] == 'OK' ?
		   	'#00bb00' : '#bb0000' ?>"><?php echo $test[1]?></td>
		   <td><?php echo $test[2] ?></td>
		   </tr>
	   <?php
   }
   ?>
   </tbody>
  </table>

</body>
</html>
<?php


// Abort tests on error
function error_abort_tests($errno, $errstr, $errfile, $errline) {
	echo "<p>There is an error on " . $errfile . " @ line " . $errline;
	echo ':</p>';
	echo '<pre>' . $errstr . '</pre>';
	exit;
}
?>
