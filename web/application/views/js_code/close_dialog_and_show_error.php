<div id="<?php echo $dialog ?>"></div>

<script language="JavaScript" type="text/javascript">
//<![CDATA[

show_error('<?php echo addslashes($title) ?>', 
		'<?php echo addslashes($content) ?>');
destroy_dialog("#<?php echo $dialog?>");

//]]>
</script>
