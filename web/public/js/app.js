/*
 * Copyright 2011-2015 Jorge López Pérez <jorge@adobo.org>
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
var dustbase = {};
var event_details_popup;


$(document).ready(function() {
  // translations is loaded in HTML body (TODO make it more elegant)
  setTranslations(translations);

  // Dust.js i18n helper
  dust.helpers.i18n = function i18n(chunk, context, bodies, params) {
    var i18n_params = {};
    var i18n_name = params.name;
    var i18n_type = params.type;

    delete params.name;
    delete params.type;

    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        var param_name = '%' + key;
        i18n_params[param_name] = dust.helpers.tap(params[key],
          chunk, context);
      }
    }
    return chunk.write(t(i18n_type, i18n_name, i18n_params));
  };

  set_default_datepicker_options();

  // Dust.js base context
  dustbase = dust.makeBase({
    default_calendar_color: AgenDAVConf.default_calendar_color,
    base_url: AgenDAVConf.base_url,
    base_app_url: AgenDAVConf.base_app_url,
    csrf_token_name: csrf_id,
    csrf_token_value: csrf_value,
    enable_calendar_sharing: AgenDAVConf.enable_calendar_sharing,
    // Sorry for this!
    numbers1to31: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31],
  });

  // Default colorpicker options
  set_default_colorpicker_options();

  // Enable full calendar
  // TODO: configurable!
  $('#calendar_view').fullCalendar({
    selectable: true,
    editable: true,
    timezone: AgenDAVUserPrefs.timezone,
    firstDay: AgenDAVUserPrefs.weekstart,
    timeFormat: AgenDAVDateAndTime.fullCalendarFormat[AgenDAVUserPrefs.time_format],
    weekMode: 'liquid',
    height: calendar_height(),
    windowResize: function(view) {
      var new_height = calendar_height();
      $(this).fullCalendar('option', 'height', new_height);
    },
    header: {
      right:   'month,agendaWeek,agendaDay',
      center: 'title',
      left:  'today prev,next'
    },

    theme: true, // use jQuery UI themeing
    axisFormat: AgenDAVDateAndTime.fullCalendarFormat[AgenDAVUserPrefs.time_format],
    slotMinutes: 30,
    firstHour: 8,

    // Default event durations. Used when dropping events
    defaultTimedEventDuration: '01:00:00',
    defaultAllDayEventDuration: { days: 1 },

    // Limit cell heignt
    eventLimit: true,

    allDayDefault: false,

    loading: function(bool) {
      loading(bool);
    },

    eventRender: event_render_callback,
    eventClick: event_click_callback,

    // Add new event by dragging. Click also triggers this event,
    // if you define dayClick and select there is some kind of
    // collision between them.
    select: slots_drag_callback,


    // Use default select helper. Useful for creating events in agenda view
    selectHelper: false,

    eventResize: event_resize_callback,
    eventDrop: event_drop_callback
  });

  // Event details popup
  event_details_popup = $('#event_details').qtip({
    id: 'event_details',
    prerender: false,
    content: {
      text: '.',
      title: {
        button: true
      }
    },
    position: {
      my: 'bottom center',
      at: 'top center',
      target: 'mouse',
      viewport: $('#calendar_view'),
      adjust: {
        mouse: false,
        scroll: false
      }
    },
    style: {
      classes: 'view_event_details qtip-bootstrap qtip-shadow',
      tip: true
    },
    show: {
      target: $('#calendar_view'),
      event: false,
      solo: $('#calendar_view'),
      effect: false
    },
    hide: {
      fixed: true,
      event: 'unfocus',
      effect: false
    },

    events: {
      show: function (event, api) {
        // Attach modify and delete events
        $(this)
          .find('.remove')
          .off('click')
          .on('click', function(e) {
            var event_id = $(this).data('event-id');

            event_delete(event_id);

            // Close tooltip
            event_details_popup.hide();
            e.preventDefault();
          })
        .end()
          .find('.modify')
          .off('click')
          .on('click', function(e) {
            var event_id = $(this).data('event-id');

            modify_event_handler(event_id);

            // Close tooltip
            event_details_popup.hide();
            e.preventDefault();
          });

        $(window).on('keydown.tooltipevents', function(e) {
          if(e.keyCode === $.ui.keyCode.ESCAPE) {
            api.hide(e);
          }
        });
      },

      hide: function (event, api) {
        $(window).off('keydown.tooltipevents');
      }
    }

  }).qtip('api');


  // Refresh link
  $('<button id="button-refresh" class="btn btn-default">' +
    '<i class="fa fa-refresh"></i> ' +
    t('labels', 'refresh') + '</button>')
    .appendTo('#calendar_view div.fc-right')
    .on('click', function() {
      update_calendar_list(true);
    })
    .before('<span class="fc-header-space">');

  // Date picker above calendar
  render_template('datepicker_button', {}, function(out) {
    $('#calendar_view .fc-center').append(out);
    $('#datepicker_fullcalendar') .datepicker({
      changeYear: true,
      closeText: t('labels', 'cancel'),
      onSelect: function(date, text) {
        var d = $('#datepicker_fullcalendar').datepicker('getDate');
        $('#calendar_view').fullCalendar('gotoDate', d);
      }
    })
    .prev()
      .on('click', function() {
        var current_date = $('#calendar_view').fullCalendar('getDate').toDate();
        $('#datepicker_fullcalendar').datepicker('setDate', current_date);
        $('#datepicker_fullcalendar').datepicker('show');
      });
  });

  $('#calendar_view').fullCalendar('renderEvent',
    {
      title: 'Little portal',
      start: '1985-02-15T00:00:00Z',
      end: '1985-02-15T23:59:59Z',
      allDay: true,
      editable: false,
      color: '#E78AEF'
    },
    true);


  /*************************************************************
   * Calendar list events
   *************************************************************/

  // Editing a calendar
  $('div.calendar_list').on('click', 'i.cfg', function(e) {
    e.stopPropagation();
    var calentry = $(this).parent();
    calendar_modify_dialog($(calentry[0]).data());
  })
  .on('click', 'li.available_calendar', function(e) {
    // Make calendar hidden
    toggle_calendar($(this));
  });

  // First time load: create calendar list
  update_calendar_list(true);

  $('#sidebar').on('click', '#toggle_all_shared_calendars', function(e) {
    var shared_cals = $('#shared_calendar_list').find('ul').children();
    if ($(this).hasClass('hide_all')) {
      $.map(shared_cals, function(e, i) {
        hide_calendar($(e));
      });
      $(this)
        .removeClass('hide_all')
        .addClass('show_all')
        .find('i')
          .removeClass('fa-eye-slash')
          .addClass('fa-eye');
    } else {
      $.map(shared_cals, function(e, i) {
        show_calendar($(e));
      });
      $(this)
        .removeClass('show_all')
        .addClass('hide_all')
        .find('i')
          .removeClass('fa-eye')
          .addClass('fa-eye-slash');
    }
  });

  // Create calendar
  $('#calendar_add')
    .on('click', calendar_create_dialog);

  /*************************************************************
   * End of calendar list events
   *************************************************************/

  /*************************************************************
   * Shortcuts
   *************************************************************/

  $('#shortcut_add_event')
    .on('click', function() {
      var start = $('#calendar_view').fullCalendar('getDate');
      var data = {
          start: start,
          allDay: false,
          view: 'month'
      };

      // Unselect every single day/slot
      $('#calendar_view').fullCalendar('unselect');
      open_event_edit_dialog(data);
    });

    // Printing
    setup_print_tweaks();

});


/**
 * Used to calculate calendar view height
 */
var calendar_height = function calendar_height() {
  var offset = $('#calendar_view').offset();
  return $(window).height() - Math.ceil(offset.top) - 30;
};

/**
 * Used to show error messages
 */

var show_error = function show_error(title, message) {
  // Hide loading indicator
  loading(false);

  $('#popup').freeow(title, message,
    {
      classes: ['popup_error'],
      autoHide: true,
      showStyle: {
        opacity: 1,
        left: 0
      },
      hideStyle: {
        opacity: 0,
        left: '400px'
      }
    });
};

/**
 * Used to show success messages
 */


var show_success = function show_success(title, message) {
  $('#popup').freeow(title, message,
    {
      classes: ['popup_success'],
      autoHide: true,
      autoHideDelay: 2000,
      showStyle: {
        opacity: 1,
        left: 0
      },
      hideStyle: {
        opacity: 0,
        left: '400px'
      }
    });
};

/**
 * Sends a form via AJAX.
 *
 * Parameters:
 *
 *  form_object: form element object. Used to get the action URL and to perform
 *               checks on required fields
 *  data: data to be sent
 *  success: success callback
 *  exception: exception callback
 *  error: error callback
 *
 */
var send_form = function send_form(params) {
  var url;

  var formObj = params.form_object;
  var data = params.data;
  var successFunc = params.success || function() {};
  var exceptionFunc = params.exception || function() {};
  var errorFunc = params.error || function() {};

  if (formObj instanceof jQuery) {
    url = $(formObj).attr('action');
    if (!check_required_fields(formObj)) {
      loading(false);
      show_error(t('messages', 'error_empty_fields'), '');
      return;
    }
  } else {
    url = formObj.url;
    data = formObj.data;
  }

  // Mask body
  loading(true);

  var sendform_ajax_req = $.ajax({
    url: url,
    cache: false,
    type: 'POST',
    data: data,
    dataType: 'json'
  });

  sendform_ajax_req.then(function() {
    loading(false);
  });

  sendform_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
    // Not a JSON response
    if (jqXHR.getResponseHeader('content-type').indexOf('json') === -1) {
      console.log(jqXHR.responseText);
      show_error(t('messages', 'error_interfacefailure'), t('messages', 'error_oops'));
      errorFunc('');
      return;
    }

    // jQuery doesn't handle JSON for responses with 4xx or 5xx codes
    var data = $.parseJSON(jqXHR.responseText);

    if (data.result === 'EXCEPTION') {
      exceptionFunc(data.message);
    }

    if (data.result === 'ERROR') {
      errorFunc(data.message);
    }
  });

  sendform_ajax_req.done(function(data, textStatus, jqXHR) {
    if (data.result !== 'SUCCESS') {
      show_error(t('messages', 'error_internal'), '');
      errorFunc('');
      return;
    }

    successFunc(data.message);
  });
};


/**
 * Generates a dialog
 *
 * Parameters:
 *
 *  template: dust.js template name
 *  data: data to be passed to the template
 *  title: dialog title
 *  buttons: list of buttons
 *  divname: div where the dialog will be placed at
 *  width: dialog width
 *  pre_func: function to be called before showing the dialog
 */

var show_dialog = function show_dialog(params) {

  var template = params.template;
  var data = params.data;
  var title = params.title;
  var buttons = params.buttons;
  var divname = params.divname;
  var width = params.width;
  var pre_func = params.pre_func;

  render_template(template, data, function(out) {
    $('body').append(out);
    $('#' + divname).dialog({
      autoOpen: true,
      buttons: buttons,
      title: title,
      minWidth: width,
      modal: true,
      open: function(event, ui) {
        if (pre_func !== undefined) {
          pre_func();
        }
        $('#' + divname).dialog('option', 'position', 'center');
        var buttons = $(event.target).parent().find('.ui-dialog-buttonset').children();
        add_button_icons(buttons);
      },
      close: function(ev, ui) {
        $(this).remove();
      }
    });
  });
};

/**
 * Destroys a dialog
 */
var destroy_dialog = function destroy_dialog(name) {
  $(name).dialog('close');
  $(name).dialog('destroy');
  $(name).remove();
};

/**
 * Sets datepicker options
 */
var set_default_datepicker_options = function set_default_datepicker_options() {
  $.datepicker.setDefaults({constrainInput: true});
  $.datepicker.setDefaults({dateFormat: AgenDAVDateAndTime.datepickerFormat[AgenDAVUserPrefs.date_format]});
};

/**
 * Sets a minDate on passed elements, which already are datepickers
 */
var set_mindate = function set_mindate(mindate, datepickers) {
  var desired_date = mindate;

  if (moment.isMoment(mindate)) {
    desired_date = mindate.toDate();
  }

  $.each(datepickers, function (i, element) {
    element.datepicker('option', 'minDate', desired_date);
  });
};


/**
 * Opens a dialog for editing/creating an event.
 * Detects if this is a new event checking if an 'id' is present
 * TODO: check for rrule/recurrence-id
 *
 * @param Object data The event data
 */
var open_event_edit_dialog = function open_event_edit_dialog(event) {
  var is_new = false;
  var title = t('labels', 'editevent');

  if (event.id === undefined) {
    is_new = true;
  }

  // Clone original object
  // TODO use a better approach (lodash.clone?)
  event = jQuery.extend(true, {}, event);

  // Event creation
  if (is_new) {
    title = t('labels', 'createevent');

    if (event.view == 'month') {
      event.start = AgenDAVDateAndTime.approxNearest(event.start);
      event.end = AgenDAVDateAndTime.approxNearest(event.end).add(1, 'hours');
    }
  }

  // Adapt end when editing all day events. Fullcalendar uses exclusive ends,
  // so the real end date for all day events has to be altered here
  if (!is_new && event.allDay === true) {
    var adapted_end = moment(event.end);
    adapted_end.subtract(1, 'days');
    event.end = adapted_end;
  }

  // end can be null if the iCalendar resource was defined with DTSTART <= DTEND
  event.end = AgenDAVDateAndTime.endDate(event);

  // All day events have start_time = end_time = 00:00. Set them to something
  // more sensible to have an initial value on each
  if (event.allDay === true) {
      event.start = AgenDAVDateAndTime.approxNearest(event.start);
      event.end = AgenDAVDateAndTime.approxNearest(event.end).add(1, 'hours');
  }

  // Use default calendar
  if (event.calendar === undefined) {
    event.calendar = AgenDAVUserPrefs.default_calendar;
  }

  // Recurrence instances: allow modifying recurrence rules or calendar only
  // on the base event
  if (event.rrule !== undefined && event.recurrence_id !== undefined) {
    event.fixed_calendar = true;
    event.fixed_repeat_rule = true;
  }

  $.extend(
    event,
    {
      applyid: 'event_edit_form',
      frm: {
        action: AgenDAVConf.base_app_url + 'events/save',
        method: 'post'
      },
      calendars: calendar_list(),

      // Dates and times
      start_date: AgenDAVDateAndTime.extractDate(event.start),
      start_time: AgenDAVDateAndTime.extractTime(event.start),
      end_date: AgenDAVDateAndTime.extractDate(event.end),
      end_time: AgenDAVDateAndTime.extractTime(event.end),

      // RRule constants for frequency
      // We can't do the same with weekdays, as RRule.MO - .SU don't have
      // just integer values
      yearly: RRule.YEARLY,
      monthly: RRule.MONTHLY,
      weekly: RRule.WEEKLY,
      daily: RRule.DAILY
    }
  );

  // Log to console for debugging purposes
  console.log(event);

  var button_save = {
    'text': t('labels', 'save'),
    'class': 'addicon btn-icon-event-edit',
    'click': function() {
      var event_fields = $('#event_edit_form').serializeObject();

      event_fields.timezone = AgenDAVUserPrefs.timezone;

      send_form({
        form_object: $('#event_edit_form'),
        data: event_fields,
        success: function(affected_calendars) {
          // Reload only affected calendars
          var total = affected_calendars.length;
          for (var i=0;i<total;i++) {
            reload_event_source(affected_calendars[i]);
          }

          destroy_dialog('#event_edit_dialog');
        },
        exception: function(error) {
          // Validation error
          show_error(t('messages', 'error_invalidinput'), error);
        }
      });
    }
  };

  var button_cancel = {
    'text': t('labels', 'cancel'),
    'class': 'addicon btn-icon-cancel',
    'click': function() { destroy_dialog('#event_edit_dialog'); }
  };

  var buttons = [ button_save, button_cancel ];

  show_dialog({
    template: 'event_edit_dialog',
    data: event,
    title: title,
    buttons: buttons,
    divname: 'event_edit_dialog',
    width: 550,
    pre_func: function() {
      $('#event_edit_dialog').find('input.summary').focus();
      handle_date_and_time('#event_edit_dialog', event);
      AgenDAVRepeat.handleForm($('#tabs-recurrence'));

      if (event.rrule !== undefined && event.rrule !== '') {
        AgenDAVRepeat.setRepeatRuleOnForm(event.rrule, $('#tabs-recurrence'));
      }

      // Reminders
      reminders_manager();
    }
  });
};


/*
 * Sets up date and time fields
 */

var handle_date_and_time = function handle_date_and_time(where, data) {

  var $start_time = $(where).find('input.start_time');
  var $end_time = $(where).find('input.end_time');
  var $start_date = $(where).find('input.start_date');
  var $end_date = $(where).find('input.end_date');
  var $repeat_until = $('#repeat_until');
  var $allday = $(where).find('input.allday');

  $start_time.timepicker(AgenDAVDateAndTime.timepickerSettings[AgenDAVUserPrefs.time_format]);
  $end_time.timepicker(AgenDAVDateAndTime.timepickerSettings[AgenDAVUserPrefs.time_format]);
  $start_date.datepicker(
      {
        onSelect: function(dateText, inst) {
          // End date can't be previous to start date
          set_mindate($(this).datepicker('getDate'),
            [ $end_date, $repeat_until ]
            );

        }
      });
  $end_date.datepicker();
  $repeat_until.datepicker();

  // First time datepicker is run we need to set minDate on end date
  set_mindate(data.start_date,
      [ $end_date, $repeat_until ]
      );

  // All day checkbox
  $(where).on('change', 'input.allday', function() {
    if ($(this).prop('checked')) {
      $start_time.prop('required', false);
      $end_time.prop('required', false);

      $start_time.hide();
      $end_time.hide();
    } else {
      $start_time.prop('required', true);
      $end_time.prop('required', true);

      $start_time.show();
      $end_time.show();
    }

    generate_iso8601_values($(where));
  });

  // Update status
  $allday.trigger('change');

  // Preserve start->end duration
  $(where)
    .on('change', 'input.start_time', function(event) {
      var start = AgenDAVDateAndTime.getMoment(
          $('#start').val(),
          AgenDAVUserPrefs.timezone
      );
      var duration = $end_time.data('duration');

      var new_end = start.add(duration, 'minutes');

      $end_date.val(AgenDAVDateAndTime.extractDate(new_end));
      $end_time.val(AgenDAVDateAndTime.extractTime(new_end));
      generate_iso8601_values($(where));
    })

    .on('change', 'input.end_time', function(event) {
      $end_time.data('duration', calculate_event_duration());
    });

    // Update start/end times
    $('input.date, input.time').on('change', function(event) {
      generate_iso8601_values($(where));
    });

    // Update repeat rule UNTIL time (start_time) or format (allday)
    $(where).on('change', 'input.start_time, input.allday', function(event) {
      AgenDAVRepeat.regenerate();
    });

    // First start/end generation
    generate_iso8601_values($(where));

    // Calculate initial event duration
    $end_time.data('duration', calculate_event_duration());
};

/**
 * Calculates the duration of an event
 */
var calculate_event_duration = function calculate_event_duration() {
  var start = AgenDAVDateAndTime.getMoment(
      $('#start').val(),
      AgenDAVUserPrefs.timezone
  );
  var end = AgenDAVDateAndTime.getMoment(
      $('#end').val(),
      AgenDAVUserPrefs.timezone
  );

  var result = end.diff(start, 'minutes');

  if (result < 0) {
    result *= -1;
  }

  return result;
};

// Triggers a dialog for creating calendars
var calendar_create_dialog = function calendar_create_dialog() {

  var form_url = AgenDAVConf.base_app_url + 'calendars';
  var title = t('labels', 'newcalendar');

  var data = {
    applyid: 'calendar_create_form',
    frm: {
      action: form_url,
      method: 'post'
    }
  };

  var buttons = [
  {
    'text': t('labels', 'create'),
    'class': 'addicon btn-icon-calendar-add',
    'click': function() {
      var calendar_data = $('#calendar_create_form').serialize();

      send_form({
        form_object: $('#calendar_create_form'),
        data: calendar_data,
        success: function(data) {
          update_calendar_list(false);
          destroy_dialog('#calendar_create_dialog');
        },
        exception: function(data) {
          show_error(t('messages', 'error_invalidinput'), data);
        }
      });
    }
  },
  {
    'text': t('labels', 'cancel'),
    'class': 'addicon btn-icon-cancel',
    'click': function() { destroy_dialog('#calendar_create_dialog'); }
  }
  ];

  show_dialog({
    template: 'calendar_create_dialog',
    data: data,
    title: title,
    buttons: buttons,
    divname: 'calendar_create_dialog',
    width: 400,
    pre_func: function() {
      $('input.pick_color').colorPicker();
    }
  });
};

// Triggers a dialog for editing calendars
var calendar_modify_dialog = function calendar_modify_dialog(calendar_obj) {

  var form_url = AgenDAVConf.base_app_url + 'calendars/save';
  var title = t('labels', 'modifycalendar');

  var data = calendar_obj;
  $.extend(data, {
    applyid: 'calendar_modify_form',
    frm: {
      action: form_url,
      method: 'post'
    }
  });

  if (AgenDAVConf.show_public_caldav_url === true) {
    data.public_url = AgenDAVConf.caldav_public_base_url + data.url;
  }

  // Buttons for modification dialog
  var buttons_and_actions =
    [
      {
        'text': t('labels', 'deletecalendar'),
        'class': 'addicon btn-icon-calendar-delete',
        'click': function() {
          calendar_delete_dialog(calendar_obj);
        }
      },
      {
        'text': t('labels', 'save'),
        'class': 'addicon btn-icon-calendar-edit',
        'click': function() {

          var calendar_data = $('#calendar_modify_form').serialize();

          send_form({
            form_object: $('#calendar_modify_form'),
            data: calendar_data,
            success: function(data) {
              destroy_dialog('#calendar_modify_dialog');
              // TODO remove specific calendar and update only its events
              update_calendar_list(false);
            },
            exception: function(data) {
              // Problem with form data
              show_error(t('messages', 'error_invalidinput'), data);
            }
          });
        }
      },
      {
        'text': t('labels', 'cancel'),
        'class': 'addicon btn-icon-cancel',
        'click': function() { destroy_dialog('#calendar_modify_dialog'); }
      }
    ];

  // On shared calendars, don't show 'Remove calendar'
  if (data.shared === true) {
    buttons_and_actions.splice(0, 1);
  }

  show_dialog({
    template: 'calendar_modify_dialog',
    data: data,
    title: title,
    buttons: buttons_and_actions,
    divname: 'calendar_modify_dialog',
    width: 500,
    pre_func: function() {
      $('input.pick_color').colorPicker();

      if (AgenDAVConf.enable_calendar_sharing === true && data.shared !== true) {
        shares_manager();
      }
    }
  });
};


/**
 * Shows the 'Delete calendar' dialog
 */
var calendar_delete_dialog = function calendar_delete_dialog(calendar_obj) {
  destroy_dialog('#calendar_modify_dialog');
  var form_url = AgenDAVConf.base_app_url + 'calendars/delete';
  var title = t('labels', 'deletecalendar');

  var data = calendar_obj;
  $.extend(data, {
    applyid: 'calendar_delete_form',
    frm: {
      action: form_url,
      method: 'post'
    }
  });

  var buttons = [
  {
    'text': t('labels', 'yes'),
    'class': 'addicon btn-icon-calendar-delete',
    'click': function() {
      var fake_form = {
        url: AgenDAVConf.base_app_url + 'calendars/delete',
        data: $('#calendar_delete_form').serializeObject()
      };

      destroy_dialog('#calendar_delete_dialog');

      send_form({
        form_object: fake_form,
        success: function(removed_calendar) {
          // Just remove deleted calendar
          $('.calendar_list li.available_calendar').each(function(index) {
            var thiscal = $(this).data();
            if (thiscal.calendar == removed_calendar) {
              $('#calendar_view').fullCalendar('removeEventSource', thiscal.eventsource);
              $(this).remove();
              return false; // stop looking for calendar
            }
          });
        },
        exception: function(data) {
          show_error(t('messages', 'error_caldelete'), data);
        }
      });
    }
  },
  {
    'text': t('labels', 'cancel'),
    'class': 'addicon btn-icon-cancel',
    'click': function() { destroy_dialog('#calendar_delete_dialog'); }
  }
  ];

  show_dialog({
    template: 'calendar_delete_dialog',
    data: data,
    title: title,
    buttons: buttons,
    divname: 'calendar_delete_dialog',
    width: 500
  });

};

/*
 * Updates the calendar list and generates eventSources for fullcalendar
 */

var update_calendar_list = function update_calendar_list(maskbody) {
  if (maskbody) {
    loading(true);
  }

  var updcalendar_ajax_req = $.ajax({
    url: AgenDAVConf.base_app_url + 'calendars',
    cache: false,
    dataType: 'json'
  });

  updcalendar_ajax_req.then(function() {
    if (maskbody) {
      loading(false);
    }
  });

  updcalendar_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
    var message = errorThrown;

    if (jqXHR.responseJSON !== undefined) {
      message = jqXHR.responseJSON.message;
    }

    show_error(t('messages', 'error_loading_calendar_list'), message);
  });

  updcalendar_ajax_req.done(function(data, textStatus, jqXHR) {
    var was_hidden = {};

    // Remove old eventSources and remove every list item
    $('.calendar_list li.available_calendar').each(function(index) {
      var data = $(this).data();
      $('#calendar_view').fullCalendar('removeEventSource',
        data.eventsource);

      if ($(this).hasClass('hidden_calendar')) {
        was_hidden[data.calendar] = true;
      }

      $(this).remove();
    });

    var count = 0,
      count_shared = 0,
      own_calendars = document.createDocumentFragment(),
      shared_calendars = document.createDocumentFragment(),
      collected_event_sources = [];

    var calendars = data.calendars;

    $.each(calendars, function(key, calendar) {
      // This is a hidden calendar
      if (AgenDAVUserPrefs.hidden_calendars[calendar.calendar] !== undefined) {
        return true; // Equivalent to 'continue' inside a $.each
      }
      count++;

      // Some values need to be generated
      if (calendar.color === undefined || calendar.color === null) {
        calendar.color = AgenDAVConf.default_calendar_color;
      } else {
        calendar.color = calendar.color.substr(0,7);
      }
      calendar.fg = fg_for_bg(calendar.color);
      calendar.bordercolor = $.color.parse(calendar.color).scale('rgb',
        (calendar.fg == '#000000' ? 0.8 : 1.8)).toString();

      var li = generate_calendar_entry(calendar);

      if (calendar.calendar === AgenDAVUserPrefs.default_calendar) {
        li.addClass('default_calendar');
      }

      if (was_hidden[calendar.calendar]) {
        li.addClass('hidden_calendar');
      } else {
        collected_event_sources.push($(li).data().eventsource);
      }

      if (calendar.shared === true) {
        count_shared++;
        shared_calendars.appendChild(li[0]);
      } else {
        own_calendars.appendChild(li[0]);
      }

    });

    // No calendars?
    if (count === 0) {
      // Some CalDAV servers (e.g. DAViCal) create first calendar on first
      // login. Let's reload calendar list again
      var last_calendar_count = $('#calendar_view').data('calendar-count');

      if (last_calendar_count === undefined) {
        $('#calendar_view').data('calendar-count', 0);
        setTimeout(function() {
          update_calendar_list(false);
        }, 1);
        return;
      }

      // Calendar list received empty twice
      show_error(t('messages','notice_no_calendars'), '');
      $('#shortcut_add_event').attr('disabled', 'disabled');
      return;
    }

    $('#calendar_view').data('calendar-count', count);

    $('#own_calendar_list ul')[0]
      .appendChild(own_calendars);

    // Hide unused block
    if (count_shared === 0) {
      $('#shared_calendar_list').hide();
    } else {
      $('#shared_calendar_list ul')[0]
        .appendChild(shared_calendars);
      $('#shared_calendar_list').show();
    }

    // Add event sources
    while (count--) {
      $('#calendar_view').fullCalendar('addEventSource',
        collected_event_sources[count]);
    }

    $('#shortcut_add_event').removeAttr('disabled');

  });
};

/**
 * Function used to query the server for events
 */
var generate_event_source = function generate_event_source(calendar) {
  var ajax_options = {
      // If #calendar is not used, Fullcalendar will be confused when
      // calling removeEventSource, and will remove all calendars
      url: AgenDAVConf.base_app_url + 'events#' + calendar,
      cache: false,
      data: {
        calendar: calendar
      },
      error: function (jqXHR, textStatus, errorThrown) {
        if (jqXHR.status !== undefined && jqXHR.status == 401) {
          session_expired();
        } else {
          show_error(t('messages', 'error_interfacefailure'),
          t('messages',
            'error_loadevents', { '%cal' : calendar }));
        }
      }
  };

  return ajax_options;
};

/**
 * Keeps session alive
 *
 * n = refresh interval in miliseconds
 */
var session_refresh = function session_refresh(n) {
  var sessrefresh_ajax_req = $.ajax({
    url: AgenDAVConf.base_app_url + 'js_generator/keepalive',
    cache: false,
    method: 'GET',
    dataType: 'html'
  });

  sessrefresh_ajax_req.done(function(data, textStatus, jqXHR) {
    if (data !== '') {
      // When data is not empty, it's usually JavaScript code
      // TODO think about using dataType: script here
      $('body').append(data);
    } else {
      setTimeout(function() {
        session_refresh(n);
      }, n);
    }
  });

  sessrefresh_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
    session_expired();
  });
};

/**
 * Adds button icons
 */
var add_button_icons = function add_button_icons(buttons) {
  buttons.filter('button.addicon')
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
};

/**
 * Generates a new calendar entry
 */
var generate_calendar_entry = function generate_calendar_entry(data) {
  var eventsource = generate_event_source(data.calendar);
  eventsource.color = data.color;
  eventsource.textColor = data.fg;
  eventsource.borderColor = data.bordercolor;

  // Shared calendars
  if (data.shared !== undefined && data.shared === true && data.rw == '0') {
    eventsource.editable = false;
  }

  data.eventsource = eventsource;

  var $out;

  render_template('calendar_list_entry', data, function(out) {
    $out = $(out);

    // Associate data + eventsource to new list item
    $out.data(data);

    // Disable text selection on this (useful for dblclick)
    $out.disableSelection();
  });

  return $out;
};

/**
 * Gets calendar data from its internal name
 */
var get_calendar_data = function get_calendar_data(calendar_url) {
  var matches = $('.calendar_list').find('[data-calendar-url="'+calendar_url+'"]');

  if (matches.length == 1) {
    return $(matches[0]).data();
  }
};

/**
 * Gets calendar display name from its internal name
 */
var get_calendar_displayname = function get_calendar_displayname(calendar_url) {
  var data = get_calendar_data(calendar_url);

  if (data === undefined || data.displayname === undefined) {
    return '(?)';
  } else {
    return data.displayname;
  }
};


/**
 * Gets an event data from FullCalendar
 *
 * @param string id
 * @return Object|undefined
 */
var get_event_data = function get_event_data(id) {
  var data = $('#calendar_view').fullCalendar('clientEvents', id);

  if (data.length === 0) {
    return undefined;
  }

  return data[0];
};

/**
 * Gets the base event for a given instance. Looks for it on currently
 * loaded events, and queries the server for it in case it is not found. This
 * is frequent on recurrent events, as the first event might have started some
 * days/months/years back
 *
 * @param Object instance Original event instance data
 * @param callback success Function that will be called if the event is found.
 * @param callback fail Function that will be called if the event is not found.
 * @return Object|undefined
 */
var load_base_event_for = function load_base_event_for(instance, success, fail) {
  var parts = instance.id.split('@');
  var base_id = parts[0] + '@';

  var base_event = $('#calendar_view').fullCalendar('clientEvents', base_id);

  // Found!
  if (base_event.length !== 0) {
    success(base_event[0]);
    return;
  }

  // Query the server
  var search = $.getJSON(
      AgenDAVConf.base_app_url + 'event',
      {
        calendar: instance.calendar,
        timezone: AgenDAVUserPrefs.timezone,
        uid: instance.uid
      }
  );

  // Add the base event to the calendar, event if it is not rendered. We need
  // Fullcalendar to apply some transformations to the data provided by
  // the backend
  search.done(function(event_data) {
    $('#calendar_view').fullCalendar('renderEvent', event_data);
    success(get_event_data(base_id));
  });

  search.fail(fail);
};

/*
 * Reloads an event source
 */
var reload_event_source = function reload_event_source(cal) {
  var eventsource;

  $('.calendar_list li.available_calendar').each(function(index) {
    var thiscal = $(this).data();
    if (thiscal.calendar == cal) {
      eventsource = thiscal.eventsource;
      return false; // stop looking for calendar
    }
  });

  if (eventsource !== undefined) {
    $('#calendar_view').fullCalendar('removeEventSource', eventsource);
    $('#calendar_view').fullCalendar('addEventSource', eventsource);
  } else {
    show_error(t('messages', 'error_interfacefailure'),
        t('messages', 'error_calendarnotfound', {'%calendar' : cal }));
  }

};

/**
 * Returns a foreground color for a given background
 */
var fg_for_bg = function fg_for_bg(color) {
  var colr = parseInt(color.substr(1), 16);

  var is_dark = (colr >>> 16) + // R
    ((colr >>> 8) & 0x00ff) + // G
    (colr & 0x0000ff) < 500; // B

  return (is_dark) ? '#ffffff' : '#000000';
};


/**
 * This method is called when a session has expired
 */
var session_expired = function session_expired() {
  $('.ui-dialog-content').dialog('close');

  show_error(t('messages', 'error_sessexpired'),
      t('messages', 'error_loginagain'));
  setTimeout(function() {
    window.location = AgenDAVConf.base_url;
  }, 2000);
};

/**
 * Handles events on share calendar dialog
 */
var shares_manager = function shares_manager() {

  var shares_list = $('#shares');

  shares_manager_no_entries_placeholder();

  shares_list.on('click', '.remove', function(event) {
    $(this).closest('.share').remove();
    shares_manager_no_entries_placeholder();
  });

  $('#new_share').on('click', function(event) {
    var filter = $('#calendar_share_filter');

    // No 'add share' form is already being shown. Just render it
    if (filter.length === 0) {
      render_template('calendar_share_row', {new: "1"}, function(out) {
        $('#shares').append(out);
        shares_manager_no_entries_placeholder();
        shares_manager_enable_autocomplete();
        $('#calendar_share_filter').focus();
      });
      return;
    }

    // Form already shown. Just give it focus
    filter.focus();


  });

};

/**
 * Shows the placeholder for empty share lists
 */
var shares_manager_no_entries_placeholder = function shares_manager_no_entries_placeholder() {
  var shares = $('#shares');
  if (shares.find('.share').length === 0) {
    $('#no_shares').show();
  } else {
    $('#no_shares').hide();
  }
};

/**
 * Enables autocompletion on the 'username' field when adding a new share
 */
var shares_manager_enable_autocomplete = function shares_manager_enable_autocomplete() {
  // Autocomplete caching
  var user_autocomplete_cache = {}, lastXhr;

  $('#calendar_share_filter').autocomplete({
    minLength: 3,
    source: function(request, response) {
      var term = request.term;

      if (term in user_autocomplete_cache) {
        response(user_autocomplete_cache[term]);
        return;
      }

      lastXhr = $.getJSON(AgenDAVConf.base_app_url + 'principals',
        request, function(data, status, xhr) {
          user_autocomplete_cache[term] = data;
          if (xhr === lastXhr) {
            response(data);
          }
        }
      );
    },
    select: function( event, ui ) {
              // Turn user input into a new share row
              var permissions = $('#calendar_share_add_rw').val();
              render_template('calendar_share_row', {with: ui.item.url, displayname: ui.item.displayname, rw: permissions}, function(out) {
                $('#calendar_share_add_row').before(out);
                $('#calendar_share_filter').val('').focus();
              });
              return false;
            }
  });

  $('#calendar_share_filter').data('ui-autocomplete')._renderItem = function(ul, item) {
    var text = '<a><i class="fa fa-user"></i> ' + item.displayname;

    if (item.email !== null) {
      text += ' <span style="font-style: italic">&lt;' + item.email + '&gt;</span>';
    }

    text += '</a>';

    return $('<li></li>')
      .data('item.autocomplete', item)
      .append(text)
      .appendTo(ul);
  };
};


/*
 * Reminders manager
 */

var reminders_manager = function reminders_manager() {

  var tab_reminders = $('#tabs-reminders');
  var manager = $('#reminders');

  reminders_manager_no_entries_placeholder();

  manager.on('click', '.remove', function(event) {
      $(this).closest('.reminder').remove();
      reminders_manager_no_entries_placeholder();
  });

  manager.parent().on('click', '#new_reminder', function(event) {
    render_template('reminder_row', {}, function(out) {
      $('#reminders').append(out);
      reminders_manager_no_entries_placeholder();
    });
  });
};

/*
 * Shows/hides reminders placeholder when no reminders are set up
 */

var reminders_manager_no_entries_placeholder = function reminders_manager_no_entries_placeholder() {
  var manager = $('#reminders');
  if (manager.find('.reminder').length === 0) {
    $('#no_reminders').show();
  } else {
    $('#no_reminders').hide();
  }
};



/**
 * Event render
 */
var event_render_callback = function event_render_callback(event, element) {
  // Icons
  var icons = [];

  if (event.rrule !== undefined) {
    icons.push('fa-repeat');
  }
  if (event.reminders !== undefined && event.reminders.length > 0) {
    icons.push('fa-bell-o');
  }

  // Prepend icons
  if (icons.length !== 0) {
    var icon_html = $('<span class="fc-event-icons"></span>');
    $.each(icons, function(n, i) {
      icon_html.append('<i class="fa ' + i + '"></i>');
    });

    element.find('.fc-title').after(icon_html);
  }

};

/**
 * Event click
 */
var event_click_callback = function event_click_callback(event,
    jsEvent, view) {

  var caldata = get_calendar_data(event.calendar);

  if (caldata === undefined) {
    show_error(t('messages', 'error_interfacefailure'),
        t('messages', 'error_calendarnotfound', {'%calendar' : event.calendar }));
    return;
  }

  var event_data = $.extend({},
    event,
    { caldata: caldata }
  );

  if (caldata.shared === true && caldata.rw == '0') {
    event_data.disable_actions = true;
  }

  if (event_data.rrule !== undefined) {
    var rrule = RRule.fromString(event_data.rrule);
    event_data.rrule_explained = AgenDAVRepeat.explainRRule(rrule);
  }

  event_data.readable_dates = AgenDAVDateAndTime.formatEventDates(event_data);

  // Event details popup
  render_template('event_details_popup', event_data, function(out) {
    event_details_popup.set({
      'content.text': out,
      'content.title': event_data.title,
    })
    .reposition(jsEvent)
    .show(jsEvent);
  });


};

/**
 * Calendar slots dragging
 */
var slots_drag_callback = function slots_drag_callback(start, end, jsEvent, view) {
  var pass_allday = false;

  // In month view, start and end are passed as date-only moment objects
  if (view.name != 'month' && !start.hasTime()) {
    pass_allday = true;
  }

  if (view.name == 'month' || pass_allday === true) {
    end.subtract(1, 'day');
  }

  var data = {
    start: start,
    end: end,
    allDay: pass_allday,
    view: view.name
  };

  // Unselect every single day/slot
  $('#calendar_view').fullCalendar('unselect');
  open_event_edit_dialog(data);
};

/**
 * Event resizing
 */

var event_resize_callback = function event_resize_callback(event, delta, revertFunc, jsEvent, ui, view ) {
  var allDay = !event.start.hasTime();

  event_alter('resize', event, delta, allDay, revertFunc, jsEvent, ui, view);
};

/**
 * Event drag and drop
 */

var event_drop_callback = function event_drop_callback(event, delta, revertFunc, jsEvent, ui, view) {
  var allDay = !event.start.hasTime();

  event_alter('drop', event, delta, allDay, revertFunc, jsEvent, ui, view);
};


/**
 * Event alter via drag&drop or resizing it
 */
var event_alter = function event_alter(alterType, event, delta, allDay, revertFunc, jsEvent, ui, view) {
  var fake_form = {
    url: AgenDAVConf.base_app_url + 'events/' + alterType,
    data: {
      uid: event.uid,
      calendar: event.calendar,
      etag: event.etag,
      delta: delta.asMinutes(),
      allday: allDay,
      was_allday: event.orig_allday,
      timezone: AgenDAVUserPrefs.timezone,
    }
  };

  // Pass RECURRENCE-ID if event is recurrent
  if (event.rrule !== undefined) {
      fake_form.data.recurrence_id = event.recurrence_id;
  }

  fake_form.data[csrf_id] = get_csrf_token();

  send_form({
    form_object: fake_form,
    success: function(data) {
      var is_recurrent = (event.rrule !== undefined);

      // Update ETag for all instances
      updateEvents(event.id, is_recurrent, { etag: data.etag });

      // Set orig_allday just for this instance
      updateEvents(event.id, false, { orig_allday: event.allDay });

      // Update is_exception and has_exceptions for recurrent events
      if (is_recurrent) {
        updateEvents(event.id, true, { has_exceptions: true });
        updateEvents(event.id, false, { is_exception: true });
      }
    },
    exception: function(data) {
      show_error(t('messages', 'error_modfailed'), data);
      revertFunc();
    },
    error: function() {
      revertFunc();
    }
  });
};

/**
 * Deletes an event
 * Called when user clicks on 'Delete' from the event details popup
 *
 * @param string event_id Event internal id
 */
var event_delete = function event_delete(event_id) {
  var data = get_event_data(event_id);

  if (data === undefined) {
    show_error(t('messages', 'error_interfacefailure'),
      t('messages', 'error_current_event_not_loaded'));
    return;
  }

  // Non recurrent event. Just remove it
  if (data.rrule === undefined) {
    event_delete_proceed(data);
    return;
  }

  // This is a recurrent event. Ask user if he/she wants to remove
  // all instances or just this one
  event_delete_recurrent_dialog(data);
};


/**
 * Proceed to delete an event/event instance
 *
 * @param Object data Event data
 * @param string recurrence_id Optional RECURRENCE-ID
 */
var event_delete_proceed = function event_delete_proceed(data, recurrence_id) {
  var remove_all_instances = false;
  if (typeof recurrence_id === "undefined") {
    recurrence_id = null;
    remove_all_instances = true;
  }

  var remove_params = {
    calendar: data.calendar,
    uid: data.uid,
    href: data.href,
    etag: data.etag,
    recurrence_id: recurrence_id
  };
  remove_params[csrf_id] = get_csrf_token();

  send_form({
    form_object: {
      url: AgenDAVConf.base_app_url + 'events/delete',
      data: remove_params
    },
    success: function(rdata) {
      removeEvents(data.id, remove_all_instances);
    },
    exception: function(rdata) {
      show_error(t('messages', 'error_event_not_deleted'), rdata);
    }
  });
};

/**
 * Shows a dialog to let the user choose between removing all instances of a
 * recurrent event or just the current instance
 *
 * @param Object data Event data
 */
var event_delete_recurrent_dialog = function event_delete_recurrent_dialog(data) {
  var button_only_this_repetition = {
    'text': t('labels', 'delete_only_this_repetition'),
    'class': 'addicon btn-icon-event-delete',
    'click': function() {
      event_delete_proceed(data, data.recurrence_id);
      destroy_dialog('#event_delete_dialog');
    }
  };

  var button_all_repetitions = {
    'text': t('labels', 'delete_all_repetitions'),
    'class': 'addicon btn-icon-event-delete',
    'click': function() {
      event_delete_proceed(data);
      destroy_dialog('#event_delete_dialog');
    }
  };

  // First instance! Disable the 'remove this instance' button
  if (data.first_instance !== undefined) {
    button_only_this_repetition.disabled = true;
  }

  var buttons = [ button_only_this_repetition, button_all_repetitions ];

  data.applyid = 'event_delete_form';

  show_dialog({
    template: 'event_delete_recurrent_dialog',
    data: data,
    title: t('labels', 'deleteevent'),
    buttons: buttons,
    divname: 'event_delete_dialog',
    width: 400
  });

};

// Edit/Modify link
var modify_event_handler = function modify_event_handler(event_id) {
  var current_event = get_event_data(event_id);
  if (current_event === undefined) {
    show_error(t('messages', 'error_interfacefailure'),
      t('messages', 'error_current_event_not_loaded'));
    return;
  }

  // Is this a recurrent event? The first instance of a recurrent event has
  // the same treatment
  if (current_event.rrule === undefined || current_event.first_instance) {
    open_event_edit_dialog(current_event);
    return;
  }

  // Ask user if he wants to edit base instance or just this instance
  open_event_modify_recurrent_dialog(current_event);
};


/**
 * Opens a dialog asking the user what he/she wants to do: edit the
 * base event for a recurrent event, or just this instance
 *
 * @param Object event
 */
var open_event_modify_recurrent_dialog = function open_event_modify_recurrent_dialog(event) {
  var button_only_this_repetition = {
    'text': t('labels', 'edit_only_this_repetition'),
    'class': 'addicon btn-icon-event-edit',
    'click': function() {
      destroy_dialog('#event_edit_recurrent_dialog');
      open_event_edit_dialog(event);
    }
  };

  var button_all_repetitions = {
    'text': t('labels', 'edit_all_repetitions'),
    'class': 'addicon btn-icon-event-edit',
    'click': function() {
      destroy_dialog('#event_edit_recurrent_dialog');

      load_base_event_for(
          event,
          function(base) {
            open_event_edit_dialog(base);
          },
          function(jqXHR, textStatus) {
            show_error(t('messages', 'error_interfacefailure'), textStatus);
          }
      );
    }
  };

  var buttons = [ button_only_this_repetition, button_all_repetitions ];

  show_dialog({
    template: 'event_edit_recurrent_dialog',
    data: event,
    title: t('labels', 'editevent'),
    buttons: buttons,
    divname: 'event_edit_recurrent_dialog',
    width: 400
  });

};

// Shows a calendar
var show_calendar = function show_calendar(calendar_obj) {
  $('#calendar_view').fullCalendar('addEventSource', calendar_obj.data().eventsource);
  calendar_obj.removeClass('hidden_calendar');
};

// Hides a calendar
var hide_calendar = function hide_calendar(calendar_obj) {
  $('#calendar_view').fullCalendar('removeEventSource', calendar_obj.data().eventsource);
  calendar_obj.addClass('hidden_calendar');
};

// Toggles calendar visibility
var toggle_calendar = function toggle_calendar(calendar_obj) {
  if (calendar_obj.hasClass('hidden_calendar')) {
    show_calendar(calendar_obj);
  } else {
    hide_calendar(calendar_obj);
  }
};

// Gets csrf token value
var get_csrf_token = function get_csrf_token() {
  return csrf_value;
};

// Loading indicator
var loading = function loading(status) {
  var $loading = $('#loading');
  var $refresh = $('#button-refresh');
  if (status === false) {
    $refresh.removeAttr('disabled');
    $loading.hide();
  } else {
    $refresh.attr('disabled', 'disabled');
    $loading.show();
  }
};

// Printing helpers

var beforePrint = function beforePrint() {
  // Prepare calendar for printing
  $('#calendar_view').addClass('printing');
  $('#calendar_view').fullCalendar('render');
};

var afterPrint = function afterPrint() {
  $('#calendar_view').removeClass('printing');
  $('#calendar_view').fullCalendar('render');
};


// Apply printing helpers to document
var setup_print_tweaks = function setup_print_tweaks() {
  if (window.matchMedia) {
    var mediaQueryList = window.matchMedia('print');
    mediaQueryList.addListener(function(mql) {
        if (mql.matches) {
          beforePrint();
        } else {
          afterPrint();
        }
    });
  }

  window.onbeforeprint = beforePrint;
  window.onafterprint = afterPrint;
};

// Get calendar list
var calendar_list = function calendar_list() {
  var calendars = $('div.calendar_list li.available_calendar');
  var total = calendars.length;
  var result = [];

  for (var i=0;i<total;i++) {
    result.push($(calendars[i]).data());
  }

  return result;
};


// Renders a template
//
var render_template = function render(template_name, template_data, callback) {
  dust.render(template_name, dustbase.push(template_data), function(err, out) {
    if (err !== null) {
      show_error(t('messages', 'error_interfacefailure'), err.message);
      return;
    }

    callback(out);
  });
};

// Check required fields
// Returns false if any of the required fields has no value
var check_required_fields = function check_required_fields(form) {
  var result = true;
  form.find('input:required').each(function() {
    if ($(this).val() === '') {
      $(this).parent().addClass('has-error');
      $(this).focus();
      result = false;
      return true; // Skip to next iteration
    }

    $(this).parent().removeClass('has-error');
  });

  return result;
};

/*
 * Automatically converts all datepickers + timepickers into
 * ISO8601 strings.
 *
 * Looks for .generate-iso8601 wrapping divs, which will have:
 *
 * - An input.date
 * - An input.time
 * - An input.generated, which will get the ISO8601 string value
 *
 * The wrapping div can also have a data-only-date-if-checked attribute,
 * which will contain a selector pointing to a checkbox. If it is checked,
 * then the time part will be ignored and the generated Date will be
 * specified using UTC
 */
var generate_iso8601_values = function generate_iso8601_values(element) {
  var matches = $(element).find('div.generate-iso8601');

  $.each(matches, function(index, div) {
    var datepicker = $(div).find('input.date');
    var timepicker = $(div).find('input.time');

    // Skip this match if the date field is not filled
    if (datepicker.val() === '') {
      $(div).find('input.generated').val('');
      return true;
    }

    var ignore_time = false;
    var ignore_time_data = $(div).data('only-date-if-checked');

    if (ignore_time_data !== undefined) {
      ignore_time = $(ignore_time_data + ":checked").length === 1;
    }


    $(div).find('input.generated').val(
        AgenDAVDateAndTime.convertISO8601(
          datepicker,
          timepicker,
          ignore_time,
          AgenDAVUserPrefs.timezone
        )
    );

  });

};

/**
 * Loads localized strings
 *
 * @param data Array of translations
 */
var setTranslations = function setTranslations(data) {
  AgenDAVConf.i18n = data;

  // Localized names
  set_default_datepicker_options();

  // Set RRule language options
  AgenDAVRepeat.language = AgenDAVRepeat.generateLanguage();
};

/**
 * Function that translates a given label/message
 *
 * @param string domain Message domain (labels, messages, etc)
 * @param string key Message to translate
 * @param Object params Optional parameters
 * @return string
 */
var t = function t(domain, key, params) {
  var full_key = domain + '.' + key;
  var result = AgenDAVConf.i18n[full_key];

  if (result === undefined) {
    return full_key;
  }

  for (var i in params) {
    result = result.replace(i, params[i]);
  }

  return result;
};

/**
 * Callback used to translate RRULE explanations
 * t() is not used because rrule.js expects a 'gettext' type
 * callback to be received
 *
 * @param string key Message identifier
 * @return string
 */
var rrule_gettext = function rrule_gettext(key) {
  return t('rrule', key);
};


/**
 * Updates Fullcalendar events several attributes (ETag, etc) that match the
 * passed id
 *
 * @param string id Event id
 * @param bool is_recurrent true if this event repeats
 * @param Object new_properties List of properties to be updated
 */
var updateEvents = function updateEvents(id, is_recurrent, new_properties) {
  var filter = generateIdFilter(id, is_recurrent);

  var events = $('#calendar_view').fullCalendar('clientEvents', filter);

  for (var i=0;i<events.length;i++) {
    for (var property in new_properties) {
      events[i][property] = new_properties[property];
    }
    $('#calendar_view').fullCalendar('updateEvent', events[i]);
  }

  return events.length;
};

/**
 * Removes Fullcalendar events that match the passed id. Useful for
 * recurrent events
 *
 * @param string id Event id
 * @param bool wildcard true to remove all instances of a recurrent event
 */
var removeEvents = function removeEvents(id, wildcard) {
  // TODO recurrence-ids
  var filter = generateIdFilter(id, wildcard);

  $('#calendar_view').fullCalendar('removeEvents', filter);
};

/**
 * Generates a filter to search for events, based on a given id
 * and an optional wildcard setting, useful for recurrent events
 *
 * @param string id
 * @param bool wildcard
 */
var generateIdFilter = function generateIdFilter(id, wildcard) {
  var result = id;

  // Look for events with id 'passed_id@*'
  if (wildcard === true) {
    var parts = id.split('@');
    var match_id = parts[0];

    result = function(event) {
      if (event.id === undefined) {
        return false;
      }

      return event.id === id || (event.id.substring(0, match_id.length + 1) == match_id + '@');
    };
  }

  return result;
};


// vim: sw=2 tabstop=2
