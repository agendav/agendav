/*
 * Copyright 2011 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

// Useful names
var ved = "div.view_event_details";
var ced = "#com_event_dialog";
var ccd = "#create_calendar_dialog";
var mcd = "#modify_calendar_dialog";
var dcd = "#delete_calendar_dialog";

$(document).ready(function() {

	// Refresh session every X seconds code at js_generator
	// Calls session_refresh()

	// Form elements
	apply_jQueryUI_styles();

	// Default datepicker options
	set_default_datepicker_options();

	// Default colorpicker options
	set_default_colorpicker_options();

	// Enable full calendar
	// TODO: configurable!
	$("#calendar_view").fullCalendar({
		selectable: true,
		editable: true,
		firstDay: 1,
		timeFormat: 'HH:mm',
		columnFormat: {
			month: 'ddd',    // lun
			week: 'ddd d', // lun 9
			day: 'dddd d MMMM'  // lunes 9 abril
		},
		titleFormat: {
			month: 'MMMM yyyy',                             // September 2009
			week: "d [ MMM][ yyyy]{ '&#8212;'d MMM yyyy}", // Sep 7 - 13 2009
			day: 'dddd, dd MMM, yyyy'                  // Tuesday, Sep 8, 2009
		},
		weekMode: 'variable',
		aspectRatio: 1.2,
		height: calendar_height(),
		windowResize: function(view) {
			$(this).fullCalendar('option', 'height', calendar_height());
		},
		header: {
			left:   'month,agendaWeek,agendaDay',
			center: 'title',
			right:  'today prev,next'
		},
		monthNames: _('labels', 'months_long'),
		monthNamesShort: _('labels', 'months_short'),
		dayNames: _('labels', 'daynames_long'),
		dayNamesShort: _('labels', 'daynames_short'),
		buttonText: {
			today: _('labels', 'today'),
			month: _('labels', 'month'),
			week: _('labels', 'week'),
			day: _('labels', 'day')
		},
		theme: true, // use jQuery UI themeing
		allDayText: _('labels', 'allday'),
		axisFormat: 'HH:mm',
		slotMinutes: 30,
		firstHour: 8,

		allDayDefault: false,

		loading: function(bool) {
			if (bool) {
				// Now loading
				$("#calendar_view").mask(_('labels', 'synchronizing'), 500);
			} else {
				// Finished loading
				$("#calendar_view").unmask();
			}
		},

		eventRender: function(event, element) {
			element.qtip({
				content: {
					text: event_bubble_content(event),
					title: {
						text: event.title,
						button: true
					}
				},
				position: {
				/*
					my: 'bottom center',
					at: 'top center',
					*/
					target: 'mouse',
					viewport: $("#calendar_view"),
					adjust: {
						x: 10, y: 10,
						mouse: false
					}
				},
				style: {
					classes: 'view_event_details',
					tip: false,
					widget: true
				},
				show: {
					event: false,
					solo: true
				},
				hide: {
					fixed: true,
					event: 'unfocus'
				},

				events: {
					show: function (event, api) {
						$(window).bind('keydown.tooltipevents', function(e) {
							if(e.keyCode === 27) {
								api.hide(e);
							}
						})

						// Icons
						var links = api.elements.tooltip.find('div.actions').find('button.addicon').button();
						add_button_icons(links);
					},

					hide: function (event, api) {
						$(window).unbind('keydown.tooltipevents');
					}
				}
			});
		},

		eventClick: function(event, jsEvent, view) {
			// Store current event details
			set_data('current_event', event);

			$(this).qtip('show', jsEvent);
		},

		// Add new event by dragging. Click also triggers this event,
		// if you define dayClick and select there is some kind of
		// collision between them.
		select: function(startDate, endDate, allDay, jsEvent, view) {
			var pass_allday = (view.name == 'month') ? false : allDay;
			var data = {
					start: startDate.getTime()/1000,
					end: endDate.getTime()/1000,
					allday: pass_allday,
					view: view.name,
					current_calendar: $("#calendar_list li.selected_calendar").data().calendar
			};
		
		// Useful for creating events in agenda view
		// TODO: enable and use a custom function to reflect calendar colour (by
		// default it uses blue events)
		//selectHelper: true,

			// Unselect every single day/slot
			$("#calendar_view").fullCalendar('unselect');
			event_field_form('new', data);
		},

		// Event resizing
		eventResize: function(event, dayDelta, minuteDelta, revertFunc,
			jsEvent, ui, view ) {

			// Generate on-the-fly form
			var formid = generate_on_the_fly_form(
				base_app_url + 'caldav2json/resize_or_drag_event',
				{
					uid: event.uid,
					calendar: event.calendar,
					etag: event.etag,
					view: view.name,
					dayDelta: dayDelta,
					minuteDelta: minuteDelta,
					allday: event.allDay,
					was_allday: event.was_allday,
					type: 'resize'
				});

			if (get_data('formcreation') == 'ok') {
				var thisform = $("#" + formid);

				proceed_send_ajax_form(thisform,
					function(data) {
						// Users just want to know if something fails
						update_single_event(event, data);
					},
					function(data) {
						show_error(_('js_messages', 'modification_failed'), data);
						revertFunc();
					},
					function() {
						revertFunc();
					});
				}

			// Remove generated form
			$(thisform).remove();
		},

		// Event dragging
		eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc,
									 jsEvent, ui, view) {

			// Generate on-the-fly form
			var formid = generate_on_the_fly_form(
				base_app_url + 'caldav2json/resize_or_drag_event',
				{
					uid: event.uid,
					calendar: event.calendar,
					etag: event.etag,
					view: view.name,
					dayDelta: dayDelta,
					minuteDelta: minuteDelta,
					allday: event.allDay,
					was_allday: event.orig_allday,
					type: 'drag'
				});

			if (get_data('formcreation') == 'ok') {
				var thisform = $("#" + formid);

				proceed_send_ajax_form(thisform,
					function(data) {
						// Users just want to know if something fails
						update_single_event(event, data);
					},
					function(data) {
						show_error(_('js_messages', 'modification_failed'), data);
						revertFunc();
					},
					function() {
						revertFunc();
					});
				}

			// Remove generated form
			$(thisform).remove();
		}

	});


	// Refresh link
	$("#calendar_view td.fc-header-right")
		.append('<span class="fc-header-space"></span><span class="fc-button-refresh">' +_('labels', 'refresh') + '</span>');
	$("#calendar_view span.fc-button-refresh")
		.button() 
		.click(function() {
			$("#calendar_view").fullCalendar('refetchEvents');
		});

	// Date picker above calendar
	$("#calendar_view span.fc-button-today").after('<span class="fc-header-space"></span><span class="fc-button-datepicker">'
		+'<img src="' + base_url + '/img/datepicker.gif" alt="' 
		+ _('labels', 'choose_date') +'" />'
		+'</span><input type="hidden" id="datepicker_fullcalendar" />');
	
	$("#datepicker_fullcalendar").datepicker({
		changeYear: true,
		closeText: _('labels', 'cancel'),
		onSelect: function(date, text) {
			var d = $("#datepicker_fullcalendar").datepicker('getDate');	
			$("#calendar_view").fullCalendar('gotoDate', d);
		}
	});

	$("#calendar_view span.fc-button-datepicker").click(function() {
		$("#datepicker_fullcalendar").datepicker('setDate', $("#calendar_view").fullCalendar('getDate'));
		$("#datepicker_fullcalendar").datepicker('show');
	});
	
	// Delete link
	// TODO: check for rrule/recurrence-id (EXDATE, etc)
	$(ved + ' button.link_delete_event').live('click', function() {
		var data = get_data('current_event');
		if (data === undefined) {
			show_error(_('labels', 'interface_error'), 
				_('labels', 'current_event_not_loaded'));
			return;
		}

		var ded = "#delete_event_dialog";

		load_generated_dialog('dialog_generator/delete_event',
			{},
			function() {
				// Show event fields
				$(ded + " span.calendar").html(get_calendar_displayname(data.calendar));
				$(ded + " p.title").html(data.title);

				var rrule = data.rrule;
				if (rrule === undefined) {
					$(ded + " div.rrule").hide();
				}

				var thisform = $("#delete_form");
				thisform.find("input.uid").val(data.uid);
				thisform.find("input.calendar").val(data.calendar);
				thisform.find("input.href").val(data.href);
				thisform.find("input.etag").val(data.etag);
				
			},
			_('labels', 'delete_event'),
			[ 
				{
					'text': _('labels', 'yes'),
					'class': 'addicon btn-icon-event-delete',
					'click': function() {
						var thisform = $("#delete_form");
						proceed_send_ajax_form(thisform,
							function(data) {
								show_success(_('labels', 'event_deleted'), '');
								$("#calendar_view").fullCalendar('removeEvents', get_data('current_event').id);
							},
							function(data) {
								show_error(_('labels', 'event_not_deleted'), data);
							},
							function() {}); 

						// Destroy dialog
						destroy_dialog("#delete_event_dialog");

					}
				},
				{
					'text': _('labels', 'cancel'),
					'class': 'addicon btn-icon-cancel',
					'click': function() { destroy_dialog("#delete_event_dialog"); }
				}
			],
			'delete_event_dialog', 400);
			
			// Close tooltip
			$(this).parents(ved).qtip('hide');
		return false;
	});

	$("#calendar_view").fullCalendar('renderEvent', 
		{
			title: 'Little portal',
			start: '1985-02-15T00:00:00Z',
			end: '1985-02-15T23:59:59Z',
			allDay: true,
			editable: false,
			color: '#E78AEF'
		},
		true);

	// Edit/Modify link
	// TODO: check for rrule/recurrence-id
	$(ved + ' button.link_edit_event').live('click', function() {
		// Data about this event
		var event_data = get_data('current_event');
		if (event_data === undefined) {
			show_error(_('labels', 'interface_error'), 
				_('labels', 'current_event_not_loaded'));
			return;
		}

		var data = {
			uid: event_data.uid,
			calendar: event_data.calendar,
			href: event_data.href,
			etag: event_data.etag,
			start: event_data.start.getTime()/1000,
			end: event_data.end.getTime()/1000,
			summary: event_data.title,
			location: event_data.location,
			allday: event_data.allDay,
			description: event_data.description,
			rrule: event_data.rrule,
			rrule_serialized: event_data.rrule_serialized,
			rrule_explained: event_data.rrule_explained,
			icalendar_class: event_data.icalendar_class,
			transp: event_data.transp,
			recurrence_id: event_data.recurrence_id,
			orig_start: event_data.orig_start,
			orig_end: event_data.orig_end
		};
		// Close tooltip
		$(this).parents(ved).qtip('hide');

		event_field_form('modify', data);

		return false;
	});

	/*************************************************************
	 * Calendar list events
	 *************************************************************/

	// Choosing a calendar
	$("li.available_calendar").live('click', function() {
		$("#calendar_list li.selected_calendar").removeClass("selected_calendar");
		$(this).addClass("selected_calendar");
	});

	// Editing a calendar
	$("li.available_calendar").live('dblclick', function(e) {
		e.preventDefault();
		calendar_modify_form(this);
	});

	// First time load: create calendar list
	update_calendar_list(true);

	// Refresh calendar list
	$("#calendar_list_refresh").click(function() {
		update_calendar_list(true);
	});

	// Create calendar
	$("#calendar_add").click(calendar_create_form);
	

	/*************************************************************
	 * End of calendar list events
	 *************************************************************/

	/*************************************************************
	 * Shortcuts
	 *************************************************************/

	$("#shortcut_add_event")
		.button({
			icons: {
				primary: 'ui-icon-plusthick'
			}
		})
		.bind('click', function() {
			var start = $("#calendar_view").fullCalendar('getDate').getTime()/1000;
			var data = {
					start: start,
					allday: false,
					view: 'month',
					current_calendar: $("#calendar_list li.selected_calendar").data().calendar
			};

			// Unselect every single day/slot
			$("#calendar_view").fullCalendar('unselect');
			event_field_form('new', data);
		});
});


/**
 * Used to calculate calendar view height
 */
function calendar_height() {
	return $(window).height() - 24 - 25 - $("#footer").height();
}

/**
 * Used to show error messages
 */

function show_error(title, message) {
	$("#popup").freeow(title, message,
		{
			classes: ["popup_error"],
			autoHide: false,
			showStyle: {
				opacity: 1,
				left: 0
			},
			hideStyle: {
				opacity: 0,
				left: "400px"
			}
		});
}

/**
 * Used to show success messages
 */


function show_success(title, message) {
	$("#popup").freeow(title, message,
		{
			classes: ["popup_success"],
			autoHide: true,
      autoHideDelay: 2000,
			showStyle: {
				opacity: 1,
				left: 0
			},
			hideStyle: {
				opacity: 0,
				left: "400px"
			}
		});
}


/**
 * Gets data from body
 */
function get_data(name) {
	return $('body').data(name);
}

/**
 * Sets data on body
 */
function set_data(name, value) {
	$('body').data(name, value);
}


/**
 * Loads a form (via AJAX) to a specified div
 */
function load_generated_dialog(url, data, preDialogFunc, title, buttons, divname, width) {
	// Do it via POST
	var newid = generate_on_the_fly_form(
		base_app_url + 'caldav2json/edit_event', data);

	if (get_data('formcreation') == 'ok') {
		var thisform = $("#" + newid);
		var action = $(thisform).attr("action");
		var formdata = $(thisform).serialize();

		$.ajax({
			url: base_app_url + url,
			beforeSend: function(jqXHR, settings) {
				$("body").mask(_('labels', 'loading_dialog'), 500);
			},
			complete: function(jqXHR, textStatus) {
				$("body").unmask();
			},
			cache: false,
			type: 'POST',
			data: formdata,
			dataType: 'html',
			error: function(jqXHR, textStatus, errorThrown) {
				show_error('Error cargando formulario',
					'Por favor, inténtelo de nuevo. Mensaje: ' + textStatus);
			},
			success: function(data, textStatus, jqXHR) {
				$("body").append(data);
				apply_jQueryUI_styles();
				$("#" + divname).dialog({
					autoOpen: true,
					buttons: buttons,
					title: title,
          width: width,
					modal: true,
					open: function(event, ui) {
						preDialogFunc();
						var buttons = $(event.target).parent().find('.ui-dialog-buttonset').children();
						add_button_icons(buttons);
					},
					close: function(ev, ui) { $(this).remove(); }
				})
			} 
		});

		// Remove generated form
		$(thisform).remove();
	} else {
		// Error generating dialog on the fly?
		show_error(_('labels', 'interface_error'), 
				_('labels', 'oops'));
	}
}

/**
 * Sends a form via AJAX.
 * 
 * This way we respect CodeIgniter CSRF tokens
 */
function proceed_send_ajax_form(formObj, successFunc, exceptionFunc,
		errorFunc) {
	var url = $(formObj).attr("action");
	var data = $(formObj).serialize();

	$.ajax({
		url: url,
		beforeSend: function(jqXHR, settings) {
			$("body").mask(_('labels', 'sending_form'), 1000);
		},
		complete: function(jqXHR, textStatus) {
			$("body").unmask();
		},
		cache: false,
		type: 'POST',
		data: data,
		dataType: 'json',
		error: function(jqXHR, textStatus, errorThrown) {
			show_error(_('labels', 'interface_error'),
				_('labels', 'oops') + ':' + textStatus);
			set_data('lastoperation', 'failed');
			errorFunc();
		},
		success: function(data, textStatus, jqXHR) {
			// "ERROR", "EXCEPTION" or "SUCCESS"
			var result = data.result;
			var message = data.message;
			if (result == "ERROR") {
				set_data('lastoperation', 'failed');
				show_error(
					_('js_messages', 'internal_error'),
					message);
				errorFunc();
			} else if (result == "EXCEPTION") {
				set_data('lastoperation', 'failed');
				exceptionFunc(message);
			} else if (result == "SUCCESS") {
				set_data('lastoperation', 'success');
				successFunc(message);
			} else {
				show_error(_('js_messages', 'internal_error'),
						_('labels', 'oops') + ':' + result);
			}
		}
		});
}

/**
 * Creates a form with a random id in the document, and returns it.
 * Defines each element in the second parameter as hidden fields
 */
function generate_on_the_fly_form(action, data) {
	var random_id = "";
	var possible = 
		"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	for( var i=0; i < 10; i++ )
		random_id += possible.charAt(Math.floor(Math.random() *
					possible.length));
	
	// Now we have our random id
	var form_gen = base_app_url + 'dialog_generator/on_the_fly_form/' +
		random_id;
	$.ajax({
		url: form_gen,
		cache: false,
		type: 'POST',
		contentType: 'text',
		dataType: 'text',
		async: false, // Let's wait
		error: function(jqXHR, textStatus, errorThrown) {
			show_error(_('js_messages', 'error_generating_form'),
				textStatus);
			set_data('formcreation', 'failed');
		},
		success: function(formdata, textStatus, jqXHR) {
			$("body").append(formdata);
			$("#" + random_id).attr('action', action);
			$.each(data, function (i, v) {
				$("#" + random_id).append('<input type="hidden" name="'+i
					+'" value="'+v+'" />');
			});
			set_data('formcreation', 'ok');
		}
	});

	return random_id;
}

/**
 * Destroys a dialog
 */
function destroy_dialog(name) {
	$(name).dialog('close');
	$(name).dialog('destroy');
	$(name).remove();

}


/**
 * Applies jQuery UI themeing classes
 */
function apply_jQueryUI_styles() {
	$('input[type="text"],input[type="password"],textarea').addClass("ui-widget-content ui-corner-all");
	$('input[disabled="disabled"]').addClass("ui-state-disabled");
}

/**
 * Sets datepicker options
 */
function set_default_datepicker_options() {
	// Localization (TODO: make this configurable!)
$.datepicker.regional['custom'] = {
	closeText: _('labels', 'close'),
	prevText: _('labels', 'previous'),
	nextText: _('labels', 'next'),
	currentText: _('labels', 'today'),
	monthNames: _('labels', 'months_long_datepicker'),
	monthNamesShort: _('labels', 'months_short_datepicker'),
	dayNames: _('labels', 'daynames_long_datepicker'),
	dayNamesShort: _('labels', 'daynames_short_datepicker'),
	dayNamesMin: _('labels', 'daynames_short_datepicker'),
	weekHeader: 'Sm',
	dateFormat: 'dd/mm/yy',
	firstDay: 1, // XXX TODO
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: ''};	

$.datepicker.setDefaults($.datepicker.regional['custom']);
$.datepicker.setDefaults({constrainInput: true});
}

/**
 * Sets a minDate on end_date
 */
function set_end_minDate() {
	var elems = ced + ' input.start_date';
	var eleme = ced + ' input.end_date';
	var elemru = ced + ' input.recurrence_until';

	var selected = $(elems).datepicker('getDate');

	if ($(ced + " input.allday").is(":checked")) {
		selected.setTime(selected.getTime() + 86400000); // +1d
	}

	$(eleme).datepicker("option", "minDate", selected);
	$(elemru).datepicker("option", "minDate", selected);

}

/**
 * Sets recurrency options to be enabled or disabled
 */
function update_recurrency_options(newval) {
	if (newval == "none") {
		$(ced + " input.recurrence_count").val("");
		$(ced + " input.recurrence_until").val("");

		$(ced + " input.recurrence_count").attr('disabled', 'disabled');
		$(ced + " input.recurrence_count").addClass('ui-state-disabled');
		$(ced + ' label[for="recurrence_count"]').addClass('ui-state-disabled');

		$(ced + " input.recurrence_until").attr('disabled', 'disabled');
		$(ced + " input.recurrence_until").datepicker('disable');
		$(ced + " input.recurrence_until").addClass('ui-state-disabled');
		$(ced + ' label[for="recurrence_until"]').addClass('ui-state-disabled');
	} else {
		enforce_exclusive_recurrence_field('recurrence_count', 'recurrence_until');
		enforce_exclusive_recurrence_field('recurrence_until', 'recurrence_count');

	}
}



/***************************
 * Event handling functions
 */

// Triggers a dialog for editing/creating events
function event_field_form(type, data) {

	var url_dialog = 'dialog_generator/';
	var title;
	var action_verb;

	if (type == 'new') {
		url_dialog += 'create_event';
		title = _('labels', 'create_event');
		action_verb = 'creado'; // XXX remove
	} else {
		url_dialog += 'edit_event';
		title = _('labels', 'edit_event');
		action_verb = 'modificado'; // XXX remove
	}

	load_generated_dialog(url_dialog,
		data,
		function() {
			// TODO make this configurable
			var common_timepicker_opts = {
				show24Hours: true,
				separator: ':',
				step: 30
			};

			var start_datepicker_opts = {
				onSelect: function(dateText, inst) {
					// End date can't be previous to start date
					set_end_minDate();
				}
			};

			// Tabs
			$(ced + "_tabs").tabs();


			$(ced + " input.start_time").timePicker(common_timepicker_opts);
			$(ced + " input.end_time").timePicker(common_timepicker_opts);
			$(ced + " input.start_date").datepicker(start_datepicker_opts);
			$(ced + " input.end_date").datepicker();
			$(ced + " input.recurrence_until").datepicker();

			// Untouched value
			$(ced + " input.end_time").data('untouched', true);

			// First time datepicker is run we need to set minDate on end date
			set_end_minDate();

			// And recurrency options have to be enabled/disabled
			update_recurrency_options($(ced + " select.recurrence_type").val());

			// All day checkbox
			$(ced + " input.allday").change(function() {
				// TODO: timepickers should update their values
				var current = $(ced + " input.start_date").datepicker('getDate');
				set_end_minDate();

				if ($(this).is(":checked")) {

					$(ced + " input.start_time").attr('disabled', 'disabled');;
					$(ced + " input.start_time").addClass('ui-state-disabled');
					$(ced + " input.end_time").attr('disabled', 'disabled');;
					$(ced + " input.end_time").addClass('ui-state-disabled');
				} else {
					$(ced + " input.end_date").removeAttr('disabled');
					$(ced + " input.end_date").removeClass('ui-state-disabled');
					$(ced + " input.end_date").datepicker('setDate', current);

					$(ced + " input.start_time").removeAttr('disabled');
					$(ced + " input.start_time").removeClass('ui-state-disabled');
					$(ced + " input.start_time").timepicker('enable');
					$(ced + " input.end_time").removeAttr('disabled');
					$(ced + " input.end_time").removeClass('ui-state-disabled');
					$(ced + " input.end_time").timepicker('enable');
				}
			});

			// Recurrence type
			$(ced + " select.recurrence_type").change(function() {
				var newval = $(this).val();

				update_recurrency_options($(this).val());
			});

			// Avoid having a value in both recurrence options (count / until)
			$(ced + " input.recurrence_count").change(function() {
				enforce_exclusive_recurrence_field('recurrence_count', 'recurrence_until');
			});
			$(ced + " input.recurrence_until").change(function() {
				enforce_exclusive_recurrence_field('recurrence_until', 'recurrence_count');
			});

			// Timepicker: keep 1h between start-end if on the same day
			// and end_time hasn't been changed by hand
			var origStart = $.timePicker(ced + " input.start_time").getTime();
			var origDur = $.timePicker(ced + " input.end_time").getTime() - origStart.getTime();


			$(ced + " input.start_time").change(function() {
				if ($(ced + " input.end_time").data('untouched')) { 

					var start = $.timePicker(ced + " input.start_time").getTime();

					var dur = $.timePicker(ced + " input.end_time").getTime() 
						- origStart.getTime();
					$.timePicker(ced + " input.end_time").setTime(new Date(start.getTime() + dur));
					origStart = start;
				}
			});

			$(ced + " input.end_time").change(function() {
				var durn = $.timePicker(this).getTime() 
					- $.timePicker(ced + " input.start_time").getTime();
				if (durn != origDur) {
					$(this).data('untouched', false);
				}
			});

			// Focus first field on creation
			if (type == 'new') {
				$('input[name="summary"]').focus();
			}
			
		},
		title,
		[
			{
				'text': _('labels', 'save'),
				'class': 'addicon btn-icon-event-edit',
				'click': function() {
					var thisform = $("#com_form");
					proceed_send_ajax_form(thisform,
						function(data) {
							// TODO remove this
							show_success('Evento '+action_verb, 'El evento fue '+action_verb
								+' correctamente');

							// Reload only affected calendars
							$.each(data, function(k, cal) {
								reload_event_source(cal);
							});

							//$("#calendar_view").fullCalendar('refetchEvents');
							destroy_dialog(ced);
						},
						function(data) {
							// Problem with form data
							show_error(_('js_messages', 'invalid_data'), data);
						},
						function(data) {
							// Do nothing
						});

				}
			},
			{
				'text': _('labels', 'cancel'),
				'class': 'addicon btn-icon-cancel',
				'click': function() { destroy_dialog(ced); }
			}
		],
		'com_event_dialog', 500);
}

/*
 * Updates a single event fetching it from server
 */
function update_single_event(event, new_data) {
	$.each(new_data, function (i, v) {
			event[i] = v;
			});

	$("#calendar_view").fullCalendar('updateEvent', event);
}

// Triggers a dialog for creating calendars
function calendar_create_form() {

	var url_dialog = 'dialog_generator/create_calendar';
	var title = _('labels', 'new_calendar');


	load_generated_dialog(url_dialog,
		{},
		function() {
			$("input.pick_color").colorPicker();
		},
		title,
		[
			{
				'text': _('labels', 'create_calendar'),
				'class': 'addicon btn-icon-calendar-add',
				'click': function() {
					var thisform = $("#calendar_create_form");
					proceed_send_ajax_form(thisform,
						function(data) {
							// TODO remove?
							show_success('Calendario creado', 'Ya puede acceder a él');
							destroy_dialog(ccd);
							update_calendar_list(false);
						},
						function(data) {
							// Problem with form data
							show_error(_('js_messages', 'invalid_data'), data);
						},
						function(data) {
							// Do nothing
						});
					}
			},
			{
				'text': _('labels', 'cancel'),
				'class': 'addicon btn-icon-cancel',
				'click': function() { destroy_dialog(ccd); }
			}
		],
		'create_calendar_dialog', 500);
}
//
// Triggers a dialog for editing calendars
function calendar_modify_form(calendar_obj) {

	var url_dialog = 'dialog_generator/modify_calendar';
	var title = _('labels', 'modify_calendar');
	var data = $(calendar_obj).data();

	var calendar_data = {
		calendar: data.calendar,
		color: data.color,
		displayname: data.displayname,
		sid: data.sid,
		shared: data.shared,
		user_from: data.user_from,
		url: data.url
	};

	// Buttons for modification dialog
	var buttons_and_actions = 
		[
			{
				'text': _('labels', '_delete_calendar'),
				'class': 'addicon btn-icon-calendar-delete',
				'click': function() { 
					destroy_dialog(mcd);
					load_generated_dialog('dialog_generator/delete_calendar',
						{
							calendar: $(calendar_obj).data().calendar,
							displayname: $(calendar_obj).data().displayname
						},
						function() {},
						_('labels', 'delete'),
						[ 
						{
							'text': _('labels', '_yes'),
							'class': 'addicon btn-icon-calendar-delete',
							'click': function() {
								var thisform = $("#delete_calendar_form");
								proceed_send_ajax_form(thisform,
										function(data) {
										show_success(_('js_messages', 'calendar_deleted'), 
											'');

											// Remove from calendar and UI
											var es = $(calendar_obj).data().eventsource;
											$("#calendar_view").fullCalendar('removeEventSource',
												$(calendar_obj).data().eventsource);

											// Select another calendar before removing this one
											if ($(calendar_obj).hasClass("selected_calendar")) {
												$("#calendar_list li.available_calendar:first").click();
											}

											$(calendar_obj).remove();
										},
										function(data) {
											show_error(_('labels', 'error_delete_calendar'), data);
										},
										function() {}); 

								// Destroy dialog
								destroy_dialog(dcd);

							}
						},
						{
							'text': _('labels', 'cancel'),
							'class': 'addicon btn-icon-cancel',
							'click': function() { destroy_dialog(dcd); }
						}
					],
					'delete_calendar_dialog', 500);
				}
			},
			{
				'text': _('labels', 'modify'),
				'class': 'addicon btn-icon-calendar-edit',
				'click': function() {
				var thisform = $("#modify_calendar_form");
				proceed_send_ajax_form(thisform,
					function(data) {
						// TODO remove?
						show_success(_('js_messages', 'calendar_modified'), '');
						destroy_dialog(mcd);
						// TODO remove specific calendar and update only its events
						update_calendar_list(false);
					},
					function(data) {
						// Problem with form data
						show_error(_('js_messages', 'invalid_data'), data);
					},
					function(data) {
						// Do nothing
					});
				}
			},
			{
				'text': _('labels', 'cancel'),
				'class': 'addicon btn-icon-cancel',
				'click': function() { destroy_dialog(mcd); }
			}
		];
	
	// On shared calendars, don't show 'Remove calendar'
	if (data.shared === true) {
		buttons_and_actions.splice(0, 1);
	}


	load_generated_dialog(url_dialog,
		calendar_data,
		function() {
			$("input.pick_color").colorPicker();
			$(mcd + "_tabs").tabs();
			$("input.share_with").tagit({
				'caseSensitive': false,
				'removeConfirmation': true
			});
		},
		title,
		buttons_and_actions,
		'modify_calendar_dialog', 500);
}

/*
 * Updates the calendar list and generates eventSources for fullcalendar
 */

function update_calendar_list(maskbody) {
	$.ajax({
		url: base_app_url + 'caldav2json/calendar_list',
		cache: false,
		dataType: 'json',
		async: false, // Let's wait
		beforeSend: function(jqXHR, settings) {
			if (maskbody) {
				$("body").mask(_('labels', 'loading_calendar_list'), 500);
			}
		},
		complete: function(jqXHR, textStatus) {
			if (maskbody) {
				$("body").unmask();
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			show_error(_('labels', 'error_loading_calendar_list'),
				_('labels', 'oops') + textStatus);
		},
		success: function(data, textStatus, jqXHR) {
			// Remove old eventSources and remove every list item
			$("#calendar_list li.available_calendar").each(function(index) {
				var data = $(this).data();
				$("#calendar_view").fullCalendar('removeEventSource',
					data.eventsource);
				$(this).remove();
			});

			// Calendar count
			var count = 0;

			$.each(data, function(key, value) {
				count++;

				var li = generate_calendar_entry(value);

				$("#calendar_list ul").append(li);

				$("#calendar_view").fullCalendar('addEventSource', $(li).data().eventsource);
			});

			// No calendars?
			if (count == 0) {
				// Some CalDAV servers (e.g. DAViCal) create first calendar on first
				// login. Let's reload calendar list again
				var last_calendar_count = get_data('last_calendar_count');
				if (last_calendar_count === undefined ||
					last_calendar_count != '0') {
					set_data('last_calendar_count', 0);
					setTimeout("update_calendar_list(false)", 1);
				} else {
					// Calendar list received empty twice
					show_error(_('labels','no_calendars'), '');
				}
			} else {
				set_data('last_calendar_count', count);
				// Select the first one by default
				$("#calendar_list li.available_calendar:first").click();
			}
		}
	});
}

/**
 * Function used to query the server for events
 */
function generate_event_source(calendar) {
	var ajax_options = {
			url: base_app_url + 'caldav2json/events/' + calendar,
			cache: false,
			data: {
				tzoffset: new Date().getTimezoneOffset()
				},
			error: function() {
				show_error(_('label', 'interface_error'), 
				_('js_messages', 
					'error_loading_events', { '%cal' : calendar }));
			}
	};

	return ajax_options;
}

/**
 * Allows using selectors with ':', from docs.jquery.com
 */
function jq(myid) { 
	return myid.replace(/(:|\.)/g,'\\$1');
}

/**
 * Refreshs session.
 * 
 * n = refresh interval in miliseconds
 */
function session_refresh(n) {
	// Dumb AJAX call
	$.ajax({
		url: base_app_url + 'js_generator/dumb',
		cache: false,
		method: 'GET',
		success: function(data, textStatus, jqXHR) {
		},
		error: function(jqXHR, textStatus, errorThrown) {
			show_error(_('labels', 'interface_error'), 
			 _('js_messages', 'error_refreshing_session'));
		}
	});
	setTimeout("session_refresh(" + n + ")", n);
}

/**
 * Adds button icons
 */
function add_button_icons(buttons) {
	buttons.filter("button.addicon")
		.removeClass('addicon')
		.removeClass('ui-button-text-only')
		.addClass('ui-button-text-icon-primary')
		.each(function(k, v) {
			var classes = $(v).attr('class').split(' ');
			$.each(classes, function(i, j) {
				if (j.match(/^btn-icon-/)) {
					$(v).prepend('<span class="ui-button-icon-primary ui-icon '+ j +'"></span>');
					$(v).removeClass(j);
					return false;
				}
			});
		});
}

/**
 * Generates a new calendar entry
 */
function generate_calendar_entry(data) {
	// Default color
	if (data.color === undefined || data.color === false) {
		data.color = '#' + default_calendar_color;
	} else {
		// Remove alpha channel from color
		data.color = data.color.substring(0, 7);
	}

	// Foreground color
	var fg = calendar_colors[data.color];
	if (fg === undefined) {
		// Good luck!
		fg = '#000000';
	}

	var li = $("<li></li>")
		.addClass("calendar_color")
		.addClass("available_calendar")
		.attr("id", "calendar_" + data.calendar)
		.attr("title", data.displayname)
		.css('background-color', data.color)
		.html(data.shown_displayname);

	// Shared calendars
	if (data.shared !== undefined && data.shared == true) {
		li.append('<span class="shared"></span>');
		li.attr("title", li.attr("title") + " (compartido por " + data.user_from + ")");
	}

	var eventsource = generate_event_source(data.calendar);
	eventsource.color = data.color;
	eventsource.textColor = fg;
	data.eventsource = eventsource;

	// Associate data + eventsource to new list item
	li.data(data);

	// Disable text selection on this (useful for dblclick)
	li.disableSelection();

	return li;
}

/**
 * Gets calendar display name from its internal name
 */
function get_calendar_displayname(c) {
	var calelem = $(jq("#calendar_" + c));
	if (calelem.length == 1) {
		calname = $(calelem).data().displayname;
	} else {
		calname = event.calendar + " (?)";
	}

	return calname;
}

/*
 * Reloads an event source by removing it and reenabling it
 */
function reload_event_source(cal) {
	var calelem = $(jq("#calendar_" + cal));

	if (calelem.length == 1) {
		var eventsource = $(calelem).data().eventsource;
		$("#calendar_view").fullCalendar('removeEventSource', eventsource);
		$("#calendar_view").fullCalendar('addEventSource', eventsource);
	} else {
		show_error(_('labels', 'interface_error'),
			_('labels', 'invalid_calendar', {'%calendar' : cal }));
	}
}

/*
 * Enforces the use of only one recurrence fields
 */
function enforce_exclusive_recurrence_field(current, other) {
	if ($(ced + " input." + current).val() == '') {
		$(ced + " input." + other).removeAttr('disabled');
		$(ced + " input." + other).removeClass('ui-state-disabled');
		$(ced + ' label[for="' + other + '"]').removeClass('ui-state-disabled');
		if (other == 'recurrence_until') {
			$(ced + " input." + other).datepicker('enable');
		}
	} else {
		$(ced + " input." + other).attr('disabled', 'disabled');
		$(ced + " input." + other).addClass('ui-state-disabled');
		$(ced + " input." + other).val("");
		$(ced + ' label[for="' + other + '"]').addClass('ui-state-disabled');
		if (other == 'recurrence_until') {
			$(ced + " input." + other).datepicker('disable');
		}
	}
}

function event_bubble_content(event) {
	var tmpl = $("#view_event_details_template").clone();

	// Calendar
	tmpl.find('span.calendar_value').html(get_calendar_displayname(event.calendar));

	// Location
	if (event.location !== undefined) {
		tmpl.find('span.location_value').html(event.location);
	} else {
		tmpl.find('p.location').hide();
	}

	// Dates
	tmpl
		.find('span.start_value').html(event.formatted_start + ' - ').end()
		.find('span.end_value').html(event.formatted_end + '');

	// Description
	if (event.formatted_description !== undefined) {
		tmpl.find('p.description_value').html(event.formatted_description);
	} else {
		tmpl.find('p.description').hide();
	}

	// Recurrency rule
	if (event.rrule !== undefined) {
		if (event.rrule_explained !== undefined) {
			tmpl
				.find('div.unparseable_rrule').hide().end()
				.find('span.rrule_explained_value').html(event.rrule_explained);

		} else {
			tmpl
				.find('div.parseable_rrule').hide().end()
				.find('span.rrule_raw_value').html(event.rrule).end();
		}
	} else {
		tmpl
			.find('div.unparseable_rrule').hide().end()
			.find('div.parseable_rrule').hide();
	}

	return tmpl.html();
}

// vim: sw=2 tabstop=2
