<?php
if (isset($logo)) {
	echo $logo;
}
?>

 <div id="shortcuts" class="block">
   <div id="shortcut_add_event"><?php echo $this->i18n->_('labels',
		   'createevent')?></div>
 </div>

 <div class="calendar_list ui-widget block" id="own_calendar_list">
  <div class="ui-widget-header ui-corner-all">
  <i class="icon-calendar"></i>
  <?php echo
  $this->i18n->_('labels', 'calendars')?></div>
  <div class="ui-widget-content">
   <ul>
   </ul>
   <div class="buttons">
    <?php echo img(array(
				'id' => 'calendar_add',
				'class' => 'pseudobutton',
				'src' => 'img/calendar_add.png',
				'alt' => $this->i18n->_('labels', 'create'),
				'title' => $this->i18n->_('labels', 'create'),
				)); ?>
   </div>
  </div><!-- block contents -->
 </div><!-- block -->

 <div class="calendar_list ui-widget block" id="shared_calendar_list">
  <div class="ui-widget-header ui-corner-all">
  <i class="icon-group"></i>
  <?php echo $this->i18n->_('labels', 'shared_calendars')?>
    <?php echo img(array(
				'id' => 'toggle_all_shared_calendars',
				'class' => 'hide_all pseudobutton',
				'src' => 'img/color_swatch_empty.png',
				'alt' => $this->i18n->_('labels', 'toggleallcalendars'),
				'title' => $this->i18n->_('labels', 'toggleallcalendars'),
				)); ?>
  </div>
  <div class="ui-widget-content">
   <ul>
   </ul>
  </div>
 </div><!-- block -->

 <div id="footer">
<?php
 $img = array(
		 'src' => 'img/agendav_small.png',
		 'alt' => 'AgenDAV',
		 );
 echo img($img);
?>
  <p><?php echo $this->config->item('footer')?></p>
 </div> <!-- footer -->
