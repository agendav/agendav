/**
 * Loads localized strings
 */
function load_i18n_strings() {
  AgenDAVConf.i18n = {};

	var i18n_ajax_req = $.ajax({
		async: false,
		url: base_app_url + 'strings/load/' + agendav_version,
		dataType: 'json',
		method: 'GET',
		ifModified: false // TODO set to true + cache
	});

	i18n_ajax_req.done(function(data, textStatus, jqXHR) {
		AgenDAVConf.i18n = data;

		// Localized names
		set_default_datepicker_options();
	});
		
	i18n_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
		show_error('Error loading translation',
			'Please, contact your system administrator');
	});
}

/**
 * Function that translates a given label/message
 */
function t(mtype, s, params) {
	var ret = '[' + mtype + ':' + s + ']';

	if (typeof(AgenDAVConf.i18n)!= 'undefined' && (mtype == 'messages' 
			|| mtype == 'labels')) {
		if (mtype == 'labels' && AgenDAVConf.i18n.labels[s]) {
			ret = AgenDAVConf.i18n.labels[s];
		} else if (mtype == 'messages' && AgenDAVConf.i18n.messages[s]) {
			ret = AgenDAVConf.i18n.messages[s];
		}
	}

	for (var i in params) {
		ret = ret.replace(i, params[i]);
	}

	return ret;
}

/**
 * Returns an array of labels using the parameter 'arr' as the index for
 * the desired labels
 */
function labels_as_array(arr) {
	if (!$.isArray(arr)) {
		return [];
	}

	var result = [];
	var total_arr = arr.length
	
	for (var i=0; i<total_arr; i++) {
		result.push(AgenDAVConf.i18n.labels[arr[i]]);
	}

	return result;
}

function month_names_long() {
	return labels_as_array([ 'january', 'february', 'march', 'april', 'may',
			'june', 'july', 'august', 'september', 'october',
			'november', 'december' ]);
}

function month_names_short() {
	return labels_as_array([ 'january_short', 'february_short', 'march_short',
			'april_short', 'may_short', 'june_short', 
			'july_short', 'august', 'september_short',
			'october_short', 'november_short', 'december_short' ]);
}

function day_names_long() {
	return labels_as_array([ 'sunday', 'monday', 'tuesday', 
		'wednesday', 'thursday', 'friday', 'saturday' ]);
}

function day_names_short() {
	return labels_as_array([ 'sunday_short', 'monday_short', 'tuesday_short', 
		'wednesday_short', 'thursday_short', 'friday_short', 'saturday_short' ]);
}

// vim: sw=2 tabstop=2
