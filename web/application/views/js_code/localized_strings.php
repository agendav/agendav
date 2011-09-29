<?php
$labels = $this->i18n->dump('labels');
$js_messages = $this->i18n->dump('js_messages');

$i18n = array('labels' => $labels, 'js_messages' => $js_messages);

?>
//<![CDATA[
function _(mtype, s, params) {
	var ret = '[' + mtype + ':' + s + ']';

	if (typeof(i18n)!= 'undefined' && (mtype == 'js_messages' 
			|| mtype == 'labels')) {
		if (mtype == 'labels' && i18n.labels[s]) {
			ret = i18n.labels[s];
		} else if (mtype == 'js_messages' && i18n.js_messages[s]) {
			ret = i18n.js_messages[s];
		}
	}

	for (var i in params) {
		ret.replace(i, params[i]);
	}

	return ret;
}

var i18n = JSON.parse(<?php echo var_export(json_encode($i18n)) ?>);
//]]>
<?php
// vim: set ft=javascript
