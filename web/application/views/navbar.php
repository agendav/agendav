<div class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <div class="navbar-header">
       <span class="navbar-brand"><?php echo $title ?></span>
   </div>
   <p class="navbar-text navbar-right" id="loading">
   <?php echo img(array('src' => 'img/loading.gif')); ?>
   </p>
   <ul class="nav navbar-nav navbar-right" id="usermenu">
    <li><?php echo anchor(
      'prefs',
      '<i title="'.$this->i18n->_('labels', 'preferences').'" class="fa fa-lg fa-wrench"></i>',
      array('class' => 'prefs')
    )?></li>
    <li><?php echo anchor(
      'main/logout',
      '<i title="'.$this->i18n->_('labels', 'logout').'" class="fa fa-lg fa-power-off"></i>',
      array('class' => 'logout')
    )?></li>
   </ul>
  </div>
</div>
