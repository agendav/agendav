<script language="JavaScript" type="text/javascript">
//<![CDATA[
$(document).ready(function() {
	$("input:submit").button();
	$('input[type="text"],input[type="password"],textarea').addClass("ui-widget-content ui-corner-all");
	$('input[name="user"]').focus();

});
//]]>
</script>
<div class="logintitle">
<?php
$img = array(
		'src' => 'img/US.gif',
		'alt' => 'Universidad de Sevilla',
		);
echo img($img);

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
  echo form_open('login/');
  ?>
   <table>
    <tr>
	 <td>
<?php

  echo form_label($this->i18n->_('labels', 'username'));
  echo '</td><td>';
  $usuario = array(
		  'name' => 'user',
		  'id' => 'login_user',
		  'value' => set_value('user'),
		  'maxlength' => '40',
		  'size' => '15',
		  'autofocus' => 'autofocus',
		  );
  echo form_input($usuario);
  echo '</td></tr><tr><td>';
  echo form_label($this->i18n->_('labels', 'password'));
  echo '</td><td>';
  $password = array(
		  'name' => 'passwd',
		  'id' => 'login_passwd',
		  'value' => '',
		  'maxlength' => '40',
		  'size' => '15',
		  );
  echo form_password($password);
  echo '</td></tr><tr><td></td><td>';
  echo form_submit('login', $this->i18n->_('labels', 'login'));
  echo '</td></tr></table>';
  echo form_close();

 ?>
 </div>
 </div>
</div>
