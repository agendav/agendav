<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $title?></title>

<link rel="shortcut icon" type="image/x-icon"
href="<?php echo base_url() . 'favicon.ico';?>" />

 
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<?php
if (ENVIRONMENT == 'development') {
	$css = Defs::$cssfiles;
	$printcss = Defs::$printcssfiles;
} else {
	$css = array(
			'agendav-' . AGENDAV_VERSION . '.css',
			);
	$printcss = array(
			'agendav-' . AGENDAV_VERSION . '.print.css',
			);
}

foreach ($css as $cssfile) {
	echo link_tag('css/' . $cssfile);
}

foreach ($printcss as $pcss) {
	echo link_tag(array(
				'href' => 'css/' . $pcss,
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'media' => 'print',
				)
			);
}
?>
    <!--[if lte ie 7]>
      <style type="text/css" media="screen">
        /* Move these to your IE6/7 specific stylesheet if possible */
        .uniForm, .uniForm .ctrlHolder, .uniForm .buttonHolder, .uniForm .ctrlHolder ul{ zoom:1; }
      </style>
    <![endif]-->
</head>
<?php
// Body classes
$final_body_class = array('ui-form');
if (isset($body_class)) {
	$final_body_class = array_merge($final_body_class, (array)$body_class);
}
?>
<body class="<?php echo implode(' ', $final_body_class)?>">
