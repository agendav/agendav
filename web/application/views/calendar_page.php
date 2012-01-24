<div id="page_contents">
	<div id="calendar_view">
	</div>
<div id="left_frame">

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

 <div id="shortcuts" class="block">
   <div id="shortcut_add_event"><?php echo $this->i18n->_('labels',
		   'createevent')?></div>
 </div>

 <div class="calendar_list ui-widget block" id="own_calendar_list">
  <div class="ui-widget-header ui-corner-all"><?php echo
  $this->i18n->_('labels', 'calendars')?></div>
  <div class="ui-widget-content">
   <ul>
   </ul>
  <div class="links">
<?php
// Links
$img_add = array(
        'src' => 'img/calendar_add.png',
        'alt' => $this->i18n->_('labels', 'create'),
        'title' => $this->i18n->_('labels', 'create'),
        );

$img_refresh = array(
        'src' => 'img/arrow_refresh.png',
        'alt' => $this->i18n->_('labels', 'refresh'),
        'title' => $this->i18n->_('labels', 'refresh'),
        );

$items = array(
		'calendar_add' => img($img_add),
		'calendar_list_refresh' => img($img_refresh),
		);

foreach ($items as $id => $item) {
	echo '<span class="item" id="'.$id.'">' . $item . '</span>';
}

?> 
  </div>
  </div><!-- block contents -->
 </div><!-- block -->

 <div class="calendar_list ui-widget block" id="shared_calendar_list">
  <div class="ui-widget-header ui-corner-all">
  <?php echo $this->i18n->_('labels', 'calendars')?>
  <span class="shared"></span>
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
</div>
</div> <!-- page_contents -->

<div id="popup" class="freeow freeow-top-right">
</div>
