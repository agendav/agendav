<div class="page-header">
 <h1><?php echo $this->i18n->_('labels', 'preferences')?></h1>
</div>

<?php
$img_return = array(
		'src' => 'img/button-icons/arrow_left.png',
		'alt' => $this->i18n->_('labels', 'return'),
		'title' => $this->i18n->_('labels', 'return'),
		);

$img_save = array(
		'src' => 'img/button-icons/ok.png',
		'alt' => $this->i18n->_('labels', 'save'),
		'title' => $this->i18n->_('labels', 'save'),
		);

?>
<div id="prefs_buttons">
<button id="return_button"><?php echo img($img_return) 
	. ' ' . $this->i18n->_('labels', 'return')?></button>
<button id="save_button"><?php echo img($img_save) 
	. ' ' . $this->i18n->_('labels', 'save')?></button>
</div>

<div id="prefs_tabs">
<ul>
 <li><a href="#tabs-general"><?php echo $this->i18n->_('labels',
         'general')?></a></li>
 <li><a href="#tabs-calendars"><?php echo $this->i18n->_('labels',
		 'calendars')?></a></li>
</ul>

<?php echo form_open('prefs/save', array('id' => 'prefs_form')); ?>

<div id="tabs-general">
    <?php $this->load->view('preferences_general', array(
        'language' => $language,
        'prefs_firstday' => $prefs_firstday,
        'timezone' => $timezone)); ?>
</div>

<div id="tabs-calendars">
<?php $this->load->view('preferences_calendars', array(
			'calendar_list' => $calendar_list,
			'calendar_ids_and_dn' => $calendar_ids_and_dn,
			'default_calendar' => $default_calendar,
			'hidden_calendars' => $hidden_calendars)); ?>
</div>

<?php echo form_close(); ?>

</div>
