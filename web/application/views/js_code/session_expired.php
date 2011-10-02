<script language="JavaScript" type="text/javascript">
//<![CDATA[
$(".ui-dialog-content").dialog("close");

show_error(<?php echo $this->i18n->_('messages', 'error_sessexpired')?>, 
		<?php echo $this->i18n->_('messages', 'error_loginagain')?>);
setTimeout ( "window.location = '"+base_url+"';", 2000);
//]]>
</script>

