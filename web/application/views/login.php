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

<?php
if (isset($logo)) {
	echo $logo;
}
?>
<div class="loginform ui-corner-all">

  <?php
  echo form_open('login/', array('class' => 'form-horizontal'));
  ?>
<?php

  $user = array(
		  'name' => 'user',
		  'id' => 'login_user',
		  'value' => set_value('user'),
		  'maxlength' => '40',
		  'size' => '15',
		  'class' => 'form-control',
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
		  'maxlength' => '100',
		  'class' => 'form-control',
		  'size' => '15',
		  );
  echo formelement(
		  $this->i18n->_('labels', 'password'),
		  form_password($password));
  echo form_submit('login', $this->i18n->_('labels', 'login'), 'class="btn btn-success"');
  echo form_close();
 ?>
</div>
