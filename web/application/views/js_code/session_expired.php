<script language="JavaScript" type="text/javascript">
//<![CDATA[
$(".ui-dialog-content").dialog("close");

show_error(<?php echo $this->i18n->_('messages', 'session_expired')?>, 
		<?php echo $this->i18n->_('messages', 'login_again')?>);
setTimeout ( "window.location = '"+base_url+"';", 2000);
//]]>
</script>

