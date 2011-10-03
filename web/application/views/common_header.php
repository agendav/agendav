<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $title?></title>

<link rel="shortcut icon" type="image/x-icon"
href="<?php echo base_url() . 'favicon.ico';?>" />

 
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<?php
if (isset($css)) {
	foreach ($css as $cssfile) {
		echo link_tag($cssfile);
	}
}
?>
    <!--[if lte ie 7]>
      <style type="text/css" media="screen">
        /* Move these to your IE6/7 specific stylesheet if possible */
        .uniForm, .uniForm .ctrlHolder, .uniForm .buttonHolder, .uniForm .ctrlHolder ul{ zoom:1; }
      </style>
    <![endif]-->


<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js_generator/i18n/' . AGENDAV_VERSION)?>"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo
site_url('js_generator/prefs')?>"></script>

<?php
if (isset($js)) {
	foreach ($js as $jsfile) {
		echo script_tag('js/' . $jsfile);
	}
}
?>

<?php
$base = base_url();
$relative = preg_replace('/^http[s]:\/\/[^\/]+/', '', $base);
?>
 
<script language="JavaScript" type="text/javascript">
//<![CDATA[
var base_url = '<?php echo $base; ?>';
var base_app_url = '<?php echo site_url(); ?>/';
var relative_url = '<?php echo $relative; ?>';
//]]>
</script>
</head>
<body class="ui-form">
<div id="topbar">
 <div id="wrap_topbar">
  <div class="title">
   <?php echo $title; ?>
  </div>
<?php 
if (isset($logged_in)):
$img_logout = array(
        'src' => 'img/exit.png',
        'alt' => 'Logout',
        'title' => 'Logout',
		'id' => 'logoutbutton',
        );
?>
<div class="current_username"><?php echo $this->auth->get_user(); ?></div> | <?php echo anchor('calendar/logout', img($img_logout))?>
<?php
endif;
?>
 </div><!--wrap_topbar-->
</div>
