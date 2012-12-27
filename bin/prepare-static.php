#!/usr/bin/php
<?php
/*
 * Creates unified and minified JS/CSS files
 */

set_time_limit(0);
$compilers = array(
		'compiler.jar' =>
			'http://closure-compiler.googlecode.com/files/compiler-latest.zip',
		'yuicompressor-2.4.7/build/yuicompressor-2.4.7.jar' =>
			'http://yui.zenfs.com/releases/yuicompressor/yuicompressor-2.4.7.zip'
		);

$web_dir = getcwd() . '/../web/';
$public_dir = $web_dir . 'public/';
$js_dir = $web_dir . 'public/js/';
$css_dir = $web_dir . 'public/css/';
$app_dir = $web_dir . 'application/';

// Google Closure Compiler and YUI
foreach ($compilers as $path => $url) {
	$file = basename($path);
	if (!file_exists($file)) {
		$zip = '/tmp/tmp_'.$file.'.zip';
		$ziphandle = fopen($zip, 'w');
		$options = array(
				CURLOPT_FILE => $ziphandle,
				CURLOPT_TIMEOUT => 28800, 
				CURLOPT_URL => $url,
				);
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_exec($ch);

		// Use -j to extract here
		exec('unzip -j ' . $zip . ' ' . $path);
		fclose($ziphandle);
	}
}

// Load file list
define('BASEPATH', $app_dir);
require_once($app_dir . 'hooks/Defs.php');
require_once($app_dir . '../lib/AgenDAV/Version.php');

$defs = new Defs();
$defs->definitions();


// JS
$jsmin = $js_dir . 'jquery-base-' . \AgenDAV\Version::V . '.js';
$jsfull = $js_dir . 'agendav-' . \AgenDAV\Version::V . '.js';

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


// CSS
$cssmin = $css_dir . 'agendav-' . \AgenDAV\Version::V . '.css';
$cssprint = $css_dir . 'agendav-' . \AgenDAV\Version::V . '.print.css';
$tmp = array_keys($compilers);
$yuicompressor = basename($tmp[1]);

$csshandle = fopen($cssmin, 'w');
$cssprinthandle = fopen($cssprint, 'w');
$tasks = array('cssfiles', 'printcssfiles');
foreach ($tasks as $task) {
	foreach (Defs::$$task as $css) {
		echo "Processing $css...";
		$contents = '';
		if (strpos($css, '.min.') !== FALSE) {
			echo " already minimized.\n";
			$contents = file_get_contents($css_dir . $css);
		} else {
			echo " using ".$yuicompressor."\n";
			$cmd = 'java -jar '.$yuicompressor.' --type css'
					.' ' . $css_dir . $css;
			$cmdhandle = popen($cmd, 'r');
			while (!feof($cmdhandle)) {
				$contents .= fread($cmdhandle, 8192);
			}
		}

		// Write
		if ($task == 'cssfiles') {
			fwrite($csshandle, $contents);
		} else {
			fwrite($cssprinthandle, $contents);
		}
	}
}
fclose($csshandle);
fclose($cssprinthandle);

