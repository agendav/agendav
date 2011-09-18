<script language="JavaScript" type="text/javascript">
//<![CDATA[
var calendar_colors = new Array();
<?php
$bg_colors = array();
foreach ($colors as $bg => $fg) {
	echo 'calendar_colors[\''.$bg.'\'] = \''.$fg.'\'; ';
	$bg_colors[] = '\'' . $bg . '\'';
}

?>
var default_calendar_color = <?php echo $bg_colors[0] ?>;

function set_default_colorpicker_options() {
	$.fn.colorPicker.defaultColors = [
<?php
echo implode(', ', $bg_colors);
?>
		];
}
//]]>
</script>
