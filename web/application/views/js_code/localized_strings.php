<?php
$i18n = $this->i18n->dump();

?>
//<![CDATA[
function _(mtype, s, params) {
	var ret = '[' + mtype + ':' + s + ']';

	if (typeof(i18n)!= 'undefined' && (mtype == 'messages' 
			|| mtype == 'labels')) {
		if (mtype == 'labels' && i18n.labels[s]) {
			ret = i18n.labels[s];
		} else if (mtype == 'messages' && i18n.messages[s]) {
			ret = i18n.messages[s];
		}
	}

	for (var i in params) {
		ret = ret.replace(i, params[i]);
	}

	return ret;
}

var i18n = JSON.parse(<?php echo var_export(json_encode($i18n)) ?>);
//]]>
<?php
// vim: set ft=javascript
