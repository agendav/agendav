<div class="page-header">
<h1><?php echo $this->config->item('site_title')?></h1>
</div>


<?php
if (!empty($errors)):
?>
<div class="ui-widget loginerrors">
 <div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"> 
  <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span> 
   <?php echo $errors?>
  </p>
  </div>
</div>

<?php
endif;
?>

<div class="loginform">
<?php
if (isset($logo)) {
	echo $logo;
}
?>
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
