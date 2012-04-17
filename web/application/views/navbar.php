<div class="navbar navbar-fixed-top">
 <div class="navbar-inner">
  <div class="container-fluid">
   <span class="brand"><?php echo $title ?></span>
<?php 
if (isset($logged_in)):
$img_logout = array(
        'src' => 'img/exit.png',
        'alt' => 'Logout',
        'title' => 'Logout',
		'id' => 'logoutbutton',
        );

$img_settings = array(
        'src' => 'img/setting_tools.png',
        'alt' => $this->i18n->_('labels', 'preferences'),
        'title' => $this->i18n->_('labels', 'preferences'),
		'id' => 'prefsbutton',
        );
?>
   <ul class="nav pull-right">
    <li><a><span class="username"><?php echo
	$this->auth->get_user() ?></span></a></li>
	<li class="divider-vertical"></li>
	<li><?php echo anchor('prefs', img($img_settings)) ?></li>
	<li class="divider-vertical"></li>
	<li><?php echo anchor('calendar/logout', img($img_logout)) ?></li>
   </ul>
<?php
endif;
?>
  </div>
 </div>
</div>
