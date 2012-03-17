<?php
$write_access_options = array(
		'0' => $this->i18n->_('labels', 'readonly'),
		'1' => $this->i18n->_('labels', 'readandwrite'),
		);

$img_share_delete = array(
		'src' => 'img/delete.png',
		'class' => 'share_calendar_manager_delete pseudobutton',
		'alt' => $this->i18n->_('labels', 'delete'),
		'title' => $this->i18n->_('labels', 'delete'),
		);
?>
<tr<?php echo (isset($sid)) ? ' id="sid-' . $sid . '"' : '';?>>
<td><div class="username share_data_username"><?php echo $user ?></div></td>
<td class="share_data_other">
<?php
echo form_dropdown('write_access', 
		$write_access_options, $write_access);
?></td>
<td>
<?php echo img($img_share_delete); ?>
</td>
</tr>
