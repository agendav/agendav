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

die();

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


// CSS

