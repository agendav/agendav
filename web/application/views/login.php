<div class="logintitle">
<?php
if (isset($logo)):
	$img = array(
        'src' => 'img/' . $logo,
        'alt' => $title,
        'title' => $title,
			);
	?>
 <div id="logo" class="block">
 <?php echo img($img); ?>
 </div>
 <?php
 endif;
?>
<h1><?php echo $this->config->item('site_title')?></h1>
</div>


<?php
$validation_errors = validation_errors();

$final_errors = $validation_errors . (isset($custom_errors) ? $custom_errors
		: '');

if (!empty($final_errors)):
?>
<div class="ui-widget loginerrors">
 <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
  <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
   <?php echo $final_errors?>
  </p>
  </div>
</div>

<?php
endif;
?>

<div class="loginform">
  <div class="ui-widget-content ui-corner-all">

  <div class="loginform_fields">
  <?php
  echo form_open('login/', array('class' => 'uniForm'));
  ?>
<?php

  $user = array(
		  'name' => 'user',
		  'id' => 'login_user',
		  'value' => set_value('user'),
		  'maxlength' => '40',
		  'size' => '15',
		  'autofocus' => 'autofocus',
		  );
  echo formelement(
		  $this->i18n->_('labels', 'username'),
		  form_input($user));
  ?>
  <?php
  $password = array(
		  'name' => 'passwd',
		  'id' => 'login_passwd',
		  'value' => '',
		  'maxlength' => '40',
		  'size' => '15',
		  );
  echo formelement(
		  $this->i18n->_('labels', 'password'),
		  form_password($password));
  ?>
	<div class="buttonHolder">
  <?php
  echo form_submit('login', $this->i18n->_('labels', 'login'));
  echo form_close();

 ?>
  </div>
 </div>
 </div>
</div>
