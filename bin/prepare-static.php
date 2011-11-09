#!/usr/bin/php
<?php
/*
 * Creates unified and minified JS/CSS files
 */

set_time_limit(0);
define('CLOSURE_COMPILER_URL',
		'http://closure-compiler.googlecode.com/files/compiler-latest.zip');

$web_dir = getcwd() . '/../web/';
$public_dir = $web_dir . 'public/';
$js_dir = $web_dir . 'public/js/';
$css_dir = $web_dir . 'public/css/';
$app_dir = $web_dir . 'application/';

// Is compiler.jar available?
if (!file_exists('compiler.jar')) {
	$zip = getcwd() . '/compiler-latest.zip';
	$ziphandle = fopen($zip, 'w');
	$options = array(
			CURLOPT_FILE => $ziphandle,
			CURLOPT_TIMEOUT => 28800, 
			CURLOPT_URL => CLOSURE_COMPILER_URL,
			);
	$ch = curl_init();
	curl_setopt_array($ch, $options);
	curl_exec($ch);

	exec('unzip ' . $zip . ' compiler.jar');
}

// Load JS file list
require_once($app_dir . 'hooks/Defs.php');

$defs = new Defs();
$defs->definitions();

$jsmin = $js_dir . 'jquery-base-' . AGENDAV_VERSION . '.js';
$jsfull = $js_dir . 'agendav-' . AGENDAV_VERSION . '.js';

$jsfullhandle = fopen($jsfull, 'w');
$jsminhandle = fopen($jsmin, 'w');
$i = 0;
foreach (Defs::$jsfiles as $js) {
	echo "Processing $js...";
	$contents = '';
	if (strpos($js, '.min.') !== FALSE) {
		echo " already minimized.\n";
		$contents = file_get_contents($js_dir . $js);
	} else {
		echo " using compiler.jar.\n";
		$cmd = 'java -jar compiler.jar --compilation_level '
				.'SIMPLE_OPTIMIZATIONS --js ' . $js_dir . $js;
		$cmdhandle = popen($cmd, 'r');
		while (!feof($cmdhandle)) {
			$contents .= fread($cmdhandle, 8192);
		}
	}

	// Write on two files
	if ($i < 2) {
		fwrite($jsminhandle, $contents);
	} else {
		fwrite($jsfullhandle, $contents);
	}
	$i++;
}
fclose($jsfullhandle);
fclose($jsminhandle);

// Minimal JS
