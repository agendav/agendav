<?php
$access_options = array(
		'r' => $this->i18n->_('labels', 'readonly'),
		'rw' => $this->i18n->_('labels', 'readandwrite'),
		);

$orig_sids = array();
?>
<table class="share_calendar_manager">
<thead>
 <th><?php echo $this->i18n->_('labels', 'username')?></th>
 <th>Access</th>
 <th></th>
</thead>
<tbody>
<?php
foreach ($shares as $user => $data) {
	$orig_sids[] = $data['sid'];
	$this->load->view('share_calendar_manager_row',
			array(
				'sid' => $data['sid'],
				'user' => $user,
				'write_access' => $data['write_access'],
				));
}
?>
<?php

$form_new_username_share = array(
		'name' => 'autocomplete_username',
		'class' => 'share_calendar_manager_username_new',
		'value' => '',
		'maxlength' => '255',
		'size' => '10',
		);

$img_share_add = array(
		'src' => 'img/add.png',
		'class' => 'share_calendar_manager_add pseudobutton',
		'alt' => $this->i18n->_('labels', 'add'),
		'title' => $this->i18n->_('labels', 'add'),
		);
?>
<tr class="share_calendar_manager_new">
 <td><div class="username"><?php
 echo form_input($form_new_username_share);
 ?></div></td>
 <td>
 <?php 
   echo form_dropdown('access', $access_options, 'r');
 ?>
 </td>
 <td>
 <?php echo img($img_share_add); ?>
 </td>
 </tr>
 </tbody>
</table>
<?php
foreach ($orig_sids as $sid) {
	echo form_hidden('orig_sids['.$sid.']', '1');
}
