<?php
// TODO i18n
$access_options = array(
		'r' => $this->i18n->_('labels', 'readonly'),
		'rw' => $this->i18n->_('labels', 'readandwrite'),
		);

$img_share_delete = array(
		'src' => 'img/delete.png',
		'class' => 'share_calendar_manager_delete pseudobutton',
		'alt' => $this->i18n->_('labels', 'delete'),
		'title' => $this->i18n->_('labels', 'delete'),
		);
?>
<tr<?php echo (isset($sid)) ? ' class="sid-' . $sid . '"' : '';?>>
<td><div class="username share_data_username"><?php echo $user ?></div></td>
<td class="share_data_other">
<?php
echo form_dropdown('access', $access_options, 
		($write_access == '1' ? 'rw' : 'r'));
?></td>
<td>
<?php echo img($img_share_delete); ?>
</td>
</tr>
