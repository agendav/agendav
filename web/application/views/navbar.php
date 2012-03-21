<div class="navbar navbar-fixed-top">
 <div class="navbar-inner">
  <div class="container">
   <span class="brand"><?php echo $title ?></span>
<?php 
if (isset($logged_in)):
$img_logout = array(
        'src' => 'img/exit.png',
        'alt' => 'Logout',
        'title' => 'Logout',
		'id' => 'logoutbutton',
        );
?>
   <ul class="nav pull-right">
    <li><a><span class="username"><?php echo
	$this->auth->get_user() ?></span></a></li>
	<li class="divider-vertical"></li>
	<li><?php echo anchor('calendar/logout', img($img_logout)) ?></li>
   </ul>
<?php
endif;
?>
  </div>
 </div>
</div>
