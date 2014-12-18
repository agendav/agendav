<?php
if (isset($logo)) {
	echo $logo;
}
?>

 <div id="shortcuts" class="block">
   <button id="shortcut_add_event" class="btn btn-default btn-block">
        <i class="fa fa-plus"></i> <?php echo $this->i18n->_('labels', 'createevent')?>
   </button>
 </div>

 <div class="block calendar_list panel panel-default" id="own_calendar_list">
  <div class="panel-heading">
    <h3 class="panel-title"><?php echo $this->i18n->_('labels', 'calendars')?></h3>
  </div>
  <div class="panel-body">
   <ul class="fa-ul">
   </ul>
   <div class="buttons">
   <i title="<?php echo  $this->i18n->_('labels', 'create')?>" id="calendar_add" class="pseudobutton fa fa-plus"></i>
   </div>
  </div><!-- panel-body -->
 </div><!-- panel -->

 <div class="block calendar_list panel panel-default shared_calendars " id="shared_calendar_list">
  <div class="panel-heading">
    <h3 class="panel-title"><?php echo $this->i18n->_('labels', 'shared_calendars')?></h3>
  </div>
  <div class="panel-body">
   <ul class="fa-ul">
   </ul>
   <div class="buttons">
    <span id="toggle_all_shared_calendars" class="pseudobutton hide_all"
    title="<?php echo $this->i18n->_('labels', 'toggleallcalendars')?>"
    ><i class="fa fa-eye-slash fa-lg"></i></span>
   </div>
  </div><!-- panel-body -->
 </div><!-- panel -->

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
