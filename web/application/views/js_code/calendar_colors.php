<script language="JavaScript" type="text/javascript">
//<![CDATA[
function set_default_colorpicker_options() {
	$.fn.colorPicker.defaultColors = [
<?php
$final_colors = array();
foreach ($colors as $bg) {
	$final_colors[] = "'" . $bg . "'";
}

echo implode(', ', $final_colors);
?>
		];
}
var default_calendar_color = <?php echo $final_colors[0]?>;
//]]>
</script>
