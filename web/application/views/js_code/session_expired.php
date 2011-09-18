<script language="JavaScript" type="text/javascript">
//<![CDATA[
$(".ui-dialog-content").dialog("close");

show_error('Sesión expirada', 'Por favor, vuelva a autenticarse...');
setTimeout ( "window.location = '"+base_url+"';", 2000);
//]]>
</script>

