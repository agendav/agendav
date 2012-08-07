/*
 * Copyright 2011-2012 Jorge López Pérez <jorge@adobo.org>
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
var ved = 'div.view_event_details';
var ced = '#com_event_dialog';
var dustbase = {};


$(document).ready(function() {
  // Load i18n strings
  var i18n = undefined;
  // TODO: language
  load_i18n_strings();

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



  // Login page: focus first input field
  if ($('body').hasClass('loginpage')) {
    $('input:submit').button();
    $('input[name="user"]').focus();
  } else if ($('body').hasClass('prefspage')) {
    $('#prefs_tabs').tabs();
    $('#prefs_buttons button').button();
    $('#return_button').on('click', function() {
      window.location = base_app_url;
      return false;
    });
    $('#save_button').on('click', function() {
      var thisform = $('#prefs_form');
      proceed_send_ajax_form(thisform,
        function(data) {
          show_success(
            t('messages', 'info_prefssaved'),
            '');
        },
        function(data) {
          show_error(t('messages', 'error_invalidinput'), data);
        },
        function(data) { });
    });
  } else if ($('body').hasClass('calendarpage')) {
    // Dust.js base context
    dustbase = dust.makeBase({
      default_calendar_color: default_calendar_color,
      base_url: base_url,
      base_app_url: base_app_url,
      enable_calendar_sharing: enable_calendar_sharing
    });

    // Default colorpicker options
    set_default_colorpicker_options();
  
    // Enable full calendar
    // TODO: configurable!
    $('#calendar_view').fullCalendar({
      selectable: true,
      editable: true,
      firstDay: prefs_firstday,
      timeFormat: {
        agenda: prefs_timeformat + '{ - ' + prefs_timeformat + '}',
        '': prefs_timeformat
      },
      columnFormat: {
        month: prefs_format_column_month,
        week: prefs_format_column_week,
        day: prefs_format_column_day,
        table: prefs_format_column_table
      },
      titleFormat: {
        month: prefs_format_title_month,
        week: prefs_format_title_week,
        day: prefs_format_title_day,
        table: prefs_format_title_table
      },
      currentTimeIndicator: true,
      weekMode: 'liquid',
      height: calendar_height(),
      windowResize: function(view) {
        $(this).fullCalendar('option', 'height', calendar_height());
      },
      header: {
        left:   'month,agendaWeek,agendaDay table',
        center: 'title',
        right:  'today prev,next'
      },

      listTexts: {
        until: t('labels', 'repeatuntil'),
        past: t('labels', 'pastevents'),
        today: t('labels', 'today'),
        tomorrow: t('labels', 'tomorrow'),
        thisWeek: t('labels', 'thisweek'),
        nextWeek: t('labels', 'nextweek'),
        thisMonth: t('labels', 'thismonth'),
        nextMonth: t('labels', 'nextmonth'),
        future: t('labels', 'future'),
        week: 'W'
      },
      // list/table options
      listSections: 'smart',
      listRange: 30,
      listPage: 7,

      monthNames: month_names_long(),
      monthNamesShort: month_names_short(),
      dayNames: day_names_long(),
      dayNamesShort: day_names_short(),
      buttonText: {
        today: t('labels', 'today'),
        month: t('labels', 'month'),
        week: t('labels', 'week'),
        day: t('labels', 'day'),
        table: t('labels', 'tableview')
      },
      theme: true, // use jQuery UI themeing
      allDayText: t('labels', 'allday'),
      axisFormat: prefs_timeformat,
      slotMinutes: 30,
      firstHour: 8,

      allDayDefault: false,

      loading: function(bool) {
        if (bool) {
          // Now loading
          $('#calendar_view').mask(t('messages', 'overlay_synchronizing'), 500);
        } else {
          // Finished loading
          $('#calendar_view').unmask();
        }
      },

      eventRender: event_render_callback,
      eventClick: event_click_callback,

      // Add new event by dragging. Click also triggers this event,
      // if you define dayClick and select there is some kind of
      // collision between them.
      select: slots_drag_callback,
      
      // Useful for creating events in agenda view
      selectHelper: select_helper,

      eventResize: event_resize_callback,
      eventDrop: event_drop_callback
    });


    // Refresh link
    $('<span class="fc-button-refresh">' 
      +'<i class="icon-refresh"></i> '
      +t('labels', 'refresh') + '</span>')
      .appendTo('#calendar_view td.fc-header-right')
      .button()
      .on('click', function() {
        update_calendar_list(true);
      })
      .before('<span class="fc-header-space">');

    // Date picker above calendar
    dust.render('datepicker_button', dustbase, function(err, out) {
      if (err != null) {
      show_error(t('messages', 'error_interfacefailure'),
        err.message);
      } else {
        $('#calendar_view span.fc-button-next')
          .after(out);
        $('#datepicker_fullcalendar')
        .datepicker({
          changeYear: true,
          closeText: t('labels', 'cancel'),
          onSelect: function(date, text) {
            var d = $('#datepicker_fullcalendar').datepicker('getDate');  
            $('#calendar_view').fullCalendar('gotoDate', d);
          }
        })
        .prev()
        .button()
        .on('click', function() {
          $('#datepicker_fullcalendar').datepicker('setDate', $('#calendar_view').fullCalendar('getDate'));
          $('#datepicker_fullcalendar').datepicker('show');
        });
      }
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
    $('div.calendar_list').on('click', 'img.cfg', function(e) {
      e.stopPropagation();
      var calentry = $(this).parent();
      calendar_modify_dialog($(calentry[0]).data());
    })
    .on('click', 'li.available_calendar', function(e) {
      // Make calendar transparent
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
          .attr('src', base_url + 'img/color_swatch.png');
      } else {
        $.map(shared_cals, function(e, i) {
          show_calendar($(e));
        });
        $(this)
          .removeClass('show_all')
          .addClass('hide_all')
          .attr('src', base_url + 'img/color_swatch_empty.png');
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
      .button({
        icons: {
          primary: 'ui-icon-plusthick'
        }
      })
      .on('click', function() {
        var start = fulldatetimestring($('#calendar_view').fullCalendar('getDate'));
        var data = {
            start: start,
            allday: false,
            view: 'month'
        };

        // Unselect every single day/slot
        $('#calendar_view').fullCalendar('unselect');
        event_field_form('new', data);
      });
    }

    // User menu
    $('#usermenu').qtip({
      content: $('#usermenu_content'),
      position: { my: 'top center', at: 'bottom center' },
      style: {
        tip: true,
        classes: 'ui-tooltip-bootstrap agendav-menu'
      },
      show: {
        event: 'click',
        delay: 0
      },
      hide: {
        event: 'unfocus'
      }
    });
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
  $('#popup').freeow(title, message,
    {
      classes: ['popup_error'],
      autoHide: false,
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
 * Gets data from body
 */
var get_data = function get_data(name) {
  return $.data($('body')[0], name);
};

/**
 * Sets data on body
 */
var set_data = function set_data(name, value) {
  $.data($('body')[0], name, value);
};

/**
 * Removes data from body
 */
var remove_data = function remove_data(name) {
  $.removeData($('body')[0], name);
};


/**
 * Loads a form (via AJAX) to a specified div
 */
var load_generated_dialog = function load_generated_dialog(url, data, preDialogFunc, title, buttons, divname, width) {
  
  divname = '#' + divname;

  // Avoid double dialog opening
  if ($(divname).length != 0) {
    return false;
  }

  // Do it via POST
  var newid = generate_on_the_fly_form(
    base_app_url + 'event/modify', data);

  if (get_data('formcreation') == 'ok') {
    var thisform = $('#' + newid);
    var action = $(thisform).attr('action');
    var formdata = $(thisform).serialize();

    $('body').mask(t('messages', 'overlay_loading_dialog'), 500);

    var dialog_ajax_req = $.ajax({
      url: base_app_url + url,
      cache: false,
      type: 'POST',
      data: formdata,
      dataType: 'html'
    });

    dialog_ajax_req.then(function() {
        $('body').unmask();
    });

    dialog_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
      show_error(t('messages', 'error_loading_dialog'),
        t('messages', 'error_oops') + ': ' + textStatus);
    });
      
    dialog_ajax_req.done(function(data, textStatus, jqxHR) {
      $('body').append(data);
      $(divname).dialog({
        autoOpen: true,
        buttons: buttons,
        title: title,
        minWidth: width,
        modal: true,
        open: function(event, ui) {
          preDialogFunc();
          $(divname).dialog('option', 'position', 'center');
          var buttons = $(event.target).parent().find('.ui-dialog-buttonset').children();
          add_button_icons(buttons);
        },
        close: function(ev, ui) { $(this).remove(); }
      })
    });

    // Remove generated form
    $(thisform).remove();
  } else {
    // Error generating dialog on the fly?
    show_error(t('messages', 'error_interfacefailure'), 
        t('messages', 'error_oops'));
  }
};

/**
 * Sends a form via AJAX.
 * 
 * This way we respect CodeIgniter CSRF tokens
 */
var proceed_send_ajax_form = function proceed_send_ajax_form(formObj, successFunc, exceptionFunc,
    errorFunc) {
  var url = $(formObj).attr('action');
  var data = $(formObj).serialize();

  // Mask body
  $('body').mask(t('messages', 'overlay_sending_form'), 1000);

  var sendform_ajax_req = $.ajax({
    url: url,
    cache: false,
    type: 'POST',
    data: data,
    dataType: 'json'
  });

  sendform_ajax_req.then(function() {
    $('body').unmask();
  });

  sendform_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
    show_error(t('messages', 'error_interfacefailure'),
      t('messages', 'error_oops') + ':' + textStatus);
    set_data('lastoperation', 'failed');
    errorFunc();
  });

  sendform_ajax_req.done(function(data, textStatus, jqXHR) {
    // "ERROR", "EXCEPTION" or "SUCCESS"
    var result = data.result;
    var message = data.message;
    if (result == 'ERROR') {
      set_data('lastoperation', 'failed');
      show_error(
        t('messages', 'error_internal'),
        message);
      errorFunc();
    } else if (result == 'EXCEPTION') {
      set_data('lastoperation', 'failed');
      exceptionFunc(message);
    } else if (result == 'SUCCESS') {
      set_data('lastoperation', 'success');
      successFunc(message);
    } else {
      show_error(t('messages', 'error_internal'),
          t('messages', 'error_oops') + ':' + result);
    }
  });
};


/**
 * Generates a dialog
 */

var show_dialog = function show_dialog(template, data, title, buttons,
  divname, width, pre_func) {

  dust.render(template, dustbase.push(data), function(err, out) {
    if (err != null) {
      show_error(t('messages', 'error_interfacefailure'),
        err.message);
    } else {
      $('body').append(out);
      $('#' + divname).dialog({
        autoOpen: true,
        buttons: buttons,
        title: title,
        minWidth: width,
        modal: true,
        open: function(event, ui) {
          pre_func();
          $(divname).dialog('option', 'position', 'center');
          var buttons = $(event.target).parent().find('.ui-dialog-buttonset').children();
          add_button_icons(buttons);
        },
        close: function(ev, ui) { $(this).remove(); }
      })
    }
  });
};

/**
 * Creates a form with a random id in the document, and returns it.
 * Defines each element in the second parameter as hidden fields
 */
var generate_on_the_fly_form = function generate_on_the_fly_form(action, data) {
  var random_id = '';
  var possible = 
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  for( var i=0; i < 10; i++ )
    random_id += possible.charAt(Math.floor(Math.random() *
          possible.length));
  
  // Now we have our random id
  var form_gen = base_app_url + 'dialog_generator/on_the_fly_form/' +
    random_id;
  var csrf_ajax_gen = $.ajax({
    url: form_gen,
    cache: false,
    type: 'POST',
    contentType: 'text',
    dataType: 'text',
    async: false // Let's wait
  });

  csrf_ajax_gen.fail(function(jqXHR, textStatus, errorThrown) {
    // This is generally caused by expired session
    session_expired();
    set_data('formcreation', 'failed');
  });

  csrf_ajax_gen.done(function(formdata, textStatus, jqXHR) {
    var hidden_fields = '';

    $.each(data, function (i, v) {
      hidden_fields += '<input type="hidden" name="'+i
        +'" value="'+v+'" />';
    });

    $(formdata)
      .append(hidden_fields)
      .attr('action' , action)
      .appendTo(document.body);

    set_data('formcreation', 'ok');
  });

  return random_id;
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
  // Localization (TODO: make this configurable!)
$.datepicker.regional['custom'] = {
  closeText: t('labels', 'close'),
  prevText: t('labels', 'previous'),
  nextText: t('labels', 'next'),
  currentText: t('labels', 'today'),
  monthNames: month_names_long(),
  monthNamesShort: month_names_short(),
  dayNames: day_names_long(),
  dayNamesShort: day_names_short(),
  dayNamesMin: day_names_short(),
  weekHeader: 'Sm',
  firstDay: prefs_firstday,
  isRTL: false,
  showMonthAfterYear: false,
  yearSuffix: ''};  

$.datepicker.setDefaults($.datepicker.regional['custom']);
$.datepicker.setDefaults({constrainInput: true});
$.datepicker.setDefaults({dateFormat: prefs_dateformat});
};

/**
 * Sets a minDate on end_date
 */
var set_end_minDate = function set_end_minDate() {
  var elems = ced + ' input.start_date';
  var eleme = ced + ' input.end_date';
  var elemru = ced + ' input.recurrence_until';

  var selected = $(elems).datepicker('getDate');

  selected.setTime(selected.getTime());

  $(eleme).datepicker('option', 'minDate', selected);
  $(elemru).datepicker('option', 'minDate', selected);

};

/**
 * Sets recurrence options to be enabled or disabled
 */
var update_recurrence_options = function update_recurrence_options(newval) {
  if (newval == 'none') {
    $(ced + ' input.recurrence_count').val('');
    $(ced + ' input.recurrence_until').val('');

    $(ced + ' input.recurrence_count').attr('disabled', 'disabled');
    $(ced + ' input.recurrence_count').addClass('ui-state-disabled');
    $(ced + ' label[for="recurrence_count"]').addClass('ui-state-disabled');

    $(ced + ' input.recurrence_until').attr('disabled', 'disabled');
    $(ced + ' input.recurrence_until').datepicker('disable');
    $(ced + ' input.recurrence_until').addClass('ui-state-disabled');
    $(ced + ' label[for="recurrence_until"]').addClass('ui-state-disabled');
  } else {
    enforce_exclusive_recurrence_field('recurrence_count', 'recurrence_until');
    enforce_exclusive_recurrence_field('recurrence_until', 'recurrence_count');

  }
};



/***************************
 * Event handling functions
 */

// Triggers a dialog for editing/creating events
var event_field_form = function event_field_form(type, data) {

  var url_dialog = 'dialog_generator/';
  var title;
  var action_verb;

  if (type == 'new') {
    url_dialog += 'create_event';
    title = t('labels', 'createevent');
  } else {
    url_dialog += 'edit_event';
    title = t('labels', 'editevent');
  }

  load_generated_dialog(url_dialog,
    data,
    function() {
      var common_timepicker_opts = {
        show24Hours: (prefs_timeformat_option == '24' ? true : false),
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
      $(ced + '_tabs').tabs();


      $(ced + ' input.start_time').timePicker(common_timepicker_opts);
      $(ced + ' input.end_time').timePicker(common_timepicker_opts);
      $(ced + ' input.start_date').datepicker(start_datepicker_opts);
      $(ced + ' input.end_date').datepicker();
      $(ced + ' input.recurrence_until').datepicker();

      // Untouched value
      $(ced + ' input.end_time').data('untouched', true);

      // First time datepicker is run we need to set minDate on end date
      set_end_minDate();

      // And recurrence options have to be enabled/disabled
      update_recurrence_options($(ced + ' select.recurrence_type').val());

      // All day checkbox
      $(ced).on('change', 'input.allday', function() {
        // TODO: timepickers should update their values
        var current = $(ced + " input.start_date").datepicker('getDate');
        set_end_minDate();

        if ($(this).is(':checked')) {
          $(ced + ' input.start_time').hide();
          $(ced + ' input.end_time').hide();
        } else {
          $(ced + ' input.end_date').removeAttr('disabled');
          $(ced + ' input.end_date').removeClass('ui-state-disabled');
          $(ced + ' input.end_date').datepicker('setDate', current);

          $(ced + ' input.start_time').show();
          $(ced + ' input.end_time').show();
        }
      });

      // Recurrence type
      $(ced).on('change', 'select.recurrence_type', function() {
        var newval = $(this).val();

        update_recurrence_options($(this).val());
      });

      // Avoid having a value in both recurrence options (count / until)
      $(ced)
      .on('keyup', 'input.recurrence_count', function() {
        enforce_exclusive_recurrence_field('recurrence_count', 'recurrence_until');
      })
      .on('keyup change', 'input.recurrence_until', function() {
        enforce_exclusive_recurrence_field('recurrence_until', 'recurrence_count');
      });

      // Timepicker: keep 1h between start-end if on the same day
      // and end_time hasn't been changed by hand
      var origStart = $.timePicker(ced + ' input.start_time').getTime();
      var origDur = $.timePicker(ced + ' input.end_time').getTime() - origStart.getTime();


      $(ced).on('change', 'input.start_time', function() {
        if ($(ced + ' input.end_time').data('untouched')) { 

          var start = $.timePicker(ced + ' input.start_time').getTime();

          var dur = $.timePicker(ced + ' input.end_time').getTime() 
            - origStart.getTime();
          $.timePicker(ced + ' input.end_time').setTime(new Date(start.getTime() + dur));
          origStart = start;
        }
      });

      $(ced).on('change', 'input.end_time', function() {
        var durn = $.timePicker(this).getTime() 
          - $.timePicker(ced + ' input.start_time').getTime();
        if (durn != origDur) {
          $(this).data('untouched', false);
        }
      });

      // Focus first field on creation
      if (type == 'new') {
        $('input[name="summary"]').focus();
      }

      // Show 'Reminders' tab contents
      dust.render('reminder_table', dustbase, function(err, out) {
        if (err != null) {
          show_error(t('messages', 'error_interfacefailure'),
            err.message);
        } else {
          $('#tabs-reminders').html(out);
        }
      });

      
    },
    title,
    [
      {
        'text': t('labels', 'save'),
        'class': 'addicon btn-icon-event-edit',
        'click': function() {
          var thisform = $('#com_form');
          proceed_send_ajax_form(thisform,
            function(data) {
              // Reload only affected calendars
              $.each(data, function(k, cal) {
                reload_event_source(cal);
              });

              destroy_dialog(ced);
            },
            function(data) {
              // Problem with form data
              show_error(t('messages', 'error_invalidinput'), data);
            },
            function(data) {
              // Do nothing
            });

        }
      },
      {
        'text': t('labels', 'cancel'),
        'class': 'addicon btn-icon-cancel',
        'click': function() { destroy_dialog(ced); }
      }
    ],
    'com_event_dialog', 500);
};

/*
 * Updates a single event fetching it from server
 */
var update_single_event = function update_single_event(event, new_data) {
  $.each(new_data, function (i, v) {
      event[i] = v;
      });

  $('#calendar_view').fullCalendar('updateEvent', event);
};

// Triggers a dialog for creating calendars
var calendar_create_dialog = function calendar_create_dialog() {

  var form_url = base_app_url + 'calendar/create';
  var title = t('labels', 'newcalendar');

  var data = {
    applyid: 'calendar_create_form',
    frm: {
      action: form_url,
      method: 'post',
      csrf: get_csrf_token()
    }
  };

  show_dialog('calendar_create_dialog',
    data,
    title,
    [
      {
        'text': t('labels', 'create'),
        'class': 'addicon btn-icon-calendar-add',
        'click': function() {
          var thisform = $('#calendar_create_form');
          proceed_send_ajax_form(thisform,
            function(data) {
              destroy_dialog('#calendar_create_dialog');
              update_calendar_list(false);
            },
            function(data) {
              // Problem with form data
              show_error(t('messages', 'error_invalidinput'), data);
            },
            function(data) {
              // Do nothing
            });
          }
      },
      {
        'text': t('labels', 'cancel'),
        'class': 'addicon btn-icon-cancel',
        'click': function() { destroy_dialog('#calendar_create_dialog'); }
      }
    ],
    'calendar_create_dialog',
    400,
    function() {
      $('input.pick_color').colorPicker();
    });
};

// Triggers a dialog for editing calendars
var calendar_modify_dialog = function calendar_modify_dialog(calendar_obj) {

  var form_url = base_app_url + 'calendar/modify';
  var title = t('labels', 'modifycalendar');

  var data = calendar_obj;
  $.extend(data, { 
    applyid: 'calendar_modify_form',
    frm: {
      action: form_url,
      method: 'post',
      csrf: get_csrf_token()
    }
  });

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
        var thisform = $('#calendar_modify_form');

        proceed_send_ajax_form(thisform,
          function(data) {
            destroy_dialog('#calendar_modify_dialog');
            // TODO remove specific calendar and update only its events
            update_calendar_list(false);
          },
          function(data) {
            // Problem with form data
            show_error(t('messages', 'error_invalidinput'), data);
          },
          function(data) {
            // Do nothing
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


  show_dialog('calendar_modify_dialog',
    data,
    title,
    buttons_and_actions,
    'calendar_modify_dialog',
    500,
    function() {
      $('input.pick_color').colorPicker();
      $('#calendar_modify_dialog_tabs').tabs();

      if (enable_calendar_sharing === true && data.shared !== true) {
        share_manager();
      }
    });
};


/**
 * Shows the 'Delete calendar' dialog
 */
var calendar_delete_dialog = function calendar_delete_dialog(calendar_obj) {
  destroy_dialog('#calendar_modify_dialog');
  var form_url = base_app_url + 'calendar/delete';
  var title = t('labels', 'deletecalendar');

  var data = calendar_obj;
  $.extend(data, {
    applyid: 'calendar_delete_form',
    frm: {
      action: form_url,
      method: 'post',
      csrf: get_csrf_token()
    }
  });

  show_dialog('calendar_delete_dialog',
    data,
    title,
    [ 
    {
      'text': t('labels', 'yes'),
      'class': 'addicon btn-icon-calendar-delete',
      'click': function() {
        var thisform = $('#calendar_delete_form');
        proceed_send_ajax_form(thisform,
            function(removed_calendar) {
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
            function(data) {
              show_error(t('messages', 'error_caldelete'), data);
            },
            function() {}); 

        // Destroy dialog
        destroy_dialog('#calendar_delete_dialog');
      }
    },
    {
      'text': t('labels', 'cancel'),
      'class': 'addicon btn-icon-cancel',
      'click': function() { destroy_dialog('#calendar_delete_dialog'); }
    }
  ],
  'calendar_delete_dialog',
  500,
  function() { });
}

/*
 * Updates the calendar list and generates eventSources for fullcalendar
 */

var update_calendar_list = function update_calendar_list(maskbody) {
  if (maskbody) {
    $('body').mask(t('messages', 'overlay_loading_calendar_list'), 500);
  }

  var updcalendar_ajax_req = $.ajax({
    url: base_app_url + 'calendar/all',
    cache: false,
    dataType: 'json',
    async: false // Let's wait
  });

  updcalendar_ajax_req.then(function() {
    if (maskbody) {
      $('body').unmask();
    }
  });

  updcalendar_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
    show_error(t('messages', 'error_loading_calendar_list'),
      t('messages', 'error_oops') + textStatus);
  });

  updcalendar_ajax_req.done(function(data, textStatus, jqXHR) {
    // Remove old eventSources and remove every list item
    $('.calendar_list li.available_calendar').each(function(index) {
      var data = $(this).data();
      $('#calendar_view').fullCalendar('removeEventSource',
        data.eventsource);
      $(this).remove();
    });

    var count = 0,
      count_shared = 0,
      own_calendars = document.createDocumentFragment(),
      shared_calendars = document.createDocumentFragment(),
      collected_event_sources = [];

    $.each(data, function(key, value) {
      count++;

      var li = generate_calendar_entry(value);

      if (value.shared == true) {
        count_shared++;
        shared_calendars.appendChild(li[0]);
      } else {
        own_calendars.appendChild(li[0]);
      }

      collected_event_sources.push($(li).data().eventsource);
    });

    // No calendars?
    if (count == 0) {
      // Some CalDAV servers (e.g. DAViCal) create first calendar on first
      // login. Let's reload calendar list again
      var last_calendar_count = get_data('last_calendar_count');
      if (last_calendar_count === undefined ||
        last_calendar_count != '0') {
        set_data('last_calendar_count', 0);
        setTimeout(function() {
          update_calendar_list(false);
        }, 1);
      } else {
        // Calendar list received empty twice
        show_error(t('messages','notice_no_calendars'), '');
        $('#shortcut_add_event').button('disable');
      }
    } else {
      set_data('last_calendar_count', count);

      $('#own_calendar_list ul')[0]
        .appendChild(own_calendars);

      // Hide unused block
      if (count_shared == 0) {
        $('#shared_calendar_list').hide();
      } else {
        $('#shared_calendar_list ul')[0]
          .appendChild(shared_calendars);
        $('#shared_calendar_list').show();
      }

      // Adjust text length
      adjust_calendar_names_width();

      // Add event sources
      while (count--) {
        $('#calendar_view').fullCalendar('addEventSource',
          collected_event_sources[count]);
      }

      $('#shortcut_add_event').button('enable');

    }
  });
};

/**
 * Function used to query the server for events
 */
var generate_event_source = function generate_event_source(calendar) {
  var ajax_options = {
      // If #calendar is not used, Fullcalendar will be confused when
      // calling removeEventSource, and will remove all calendars
      url: base_app_url + 'event/all#' + calendar,
      cache: false,
      // TODO make timezone configurable
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
      },

      startParamUTC: true,
      endParamUTC: true
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
    url: base_app_url + 'js_generator/keepalive',
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
  // Default color
  if (data.color === undefined || data.color === false || data.color == null) {
    data.color = '#' + default_calendar_color;
  } else {
    // Remove alpha channel from color
    data.color = data.color.substring(0, 7);
  }

  // Foreground color
  var fg = fg_for_bg(data.color);
  // Border color
  //var border = $.color.parse(data.color).scale('rgb', 0.8).toString();
  var border = $.color.parse(data.color);
  border = border.scale('rgb', (fg == '#000000' ? 0.8 : 1.8)).toString();

  var color_square = $('<div></div>')
    .addClass('calendar_color')
    .css('background-color', data.color)
    .css('border-color', border);

  var li = $('<li></li>')
    .addClass('available_calendar')
    .attr('title', data.displayname)
    .html('<span class="text">' + data.displayname + '</span>')
    .prepend(color_square);

  var eventsource = generate_event_source(data.calendar);
  eventsource.ignoreTimezone = true; // Ignore UTC offsets
  eventsource.color = data.color;
  eventsource.textColor = fg;
  eventsource.borderColor = border;


  // Shared calendars
  if (data.shared !== undefined && data.shared == true) {
    li.attr("title", li.attr("title") + " (@" + data.user_from + ")");

    if (data.write_access == '0') {
      eventsource.editable = false;
      li.find('span.text').prepend('<i class="icon-lock"></i>');
    }
  }

  // Default calendar
  if (data.default_calendar === true) {
    li.addClass('default_calendar');
  }

  data.eventsource = eventsource;

  // Associate data + eventsource to new list item
  li.data(data);

  // Disable text selection on this (useful for dblclick)
  li.disableSelection();

  li.append('<img class="cfg pseudobutton" src="'+base_url+'img/gear_in.png" />');

  return li;
};

/**
 * Gets calendar data from its internal name
 */
var get_calendar_data = function get_calendar_data(c) {
  var data = undefined;

  $('.calendar_list li.available_calendar').each(function(index) {
    var thiscal = $(this).data();
    if (thiscal.calendar == c) {
      data = thiscal;
      return false; // stop looking for calendar
    }
  });

  return data;
};

/**
 * Gets calendar display name from its internal name
 */
var get_calendar_displayname = function get_calendar_displayname(c) {
  var data = get_calendar_data(c);

  if (data === undefined || data.displayname === undefined) {
    return '(?)';
  } else {
    return data.displayname;
  }
};

/*
 * Reloads an event source
 */
var reload_event_source = function reload_event_source(cal) {
  var eventsource = undefined;

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

/*
 * Enforces the use of only one recurrence fields
 */
var enforce_exclusive_recurrence_field = function enforce_exclusive_recurrence_field(current, other) {
  if ($(ced + ' input.' + current).val() == '') {
    $(ced + ' input.' + other).removeAttr('disabled');
    $(ced + ' input.' + other).removeClass('ui-state-disabled');
    $(ced + ' label[for="' + other + '"]').removeClass('ui-state-disabled');
    if (other == 'recurrence_until') {
      $(ced + ' input.' + other).datepicker('enable');
    }
  } else {
    $(ced + ' input.' + other).attr('disabled', 'disabled');
    $(ced + ' input.' + other).addClass('ui-state-disabled');
    $(ced + ' input.' + other).val('');
    $(ced + ' label[for="' + other + '"]').addClass('ui-state-disabled');
    if (other == 'recurrence_until') {
      $(ced + ' input.' + other).datepicker('disable');
    }
  }
};

/*
 * Round a Date timestamp
 */
var timestamp = function timestamp(d) {
  return Math.round(d.getTime()/1000);
};

/*
 * Returns a full date+time string which is easily parseable
 */
var fulldatetimestring = function fulldatetimestring(d) {
  if (d != undefined) {
    return $.fullCalendar.formatDate(d, 'yyyyMMddHHmmss');
  } else {
    return undefined;
  }
};

/**
 * Returns a foreground color for a given background
 */
var fg_for_bg = function fg_for_bg(color) {
  var colr = parseInt(color.substr(1), 16);

  var is_dark = (colr >>> 16) // R
    + ((colr >>> 8) & 0x00ff) // G 
    + (colr & 0x0000ff) // B
    < 500;

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
    window.location = base_url;
  }, 2000);
};

/**
 * Adjusts calendar text span width to parent container
 */
var adjust_calendar_names_width = function adjust_calendar_names_width() {
  $('.calendar_list li').each(function() {
    var li = $(this);
    var li_width = li.width();
    var shared = li.find('span.shared');

    var adjust = li_width - li.find('span.text').position().left + (li.outerWidth()-li_width);
    if (shared.length == 1) {
      adjust -= shared.outerWidth();
    }

    li.find('span.text').width(adjust);
  });
};

/**
 * Handles events on share calendar dialog
 */
var share_manager = function share_manager() {
  var manager = $('#calendar_share_table');
  var new_entry_form = $('#calendar_share_add');

  share_manager_no_entries_placeholder();

  manager.on('click', 
    '.calendar_share_delete', function(event) {
      $(this).parent().parent()
        .fadeOut('fast', function() { 
          $(this).remove();
          share_manager_no_entries_placeholder();
        });
    });

  // Autocomplete caching
  var user_autocomplete_cache = {}, lastXhr;

  new_entry_form.find('#calendar_share_add_username')
    .autocomplete({
      minLength: 3,
      source: function(request, response) {
        var term = request.term;

        if (term in user_autocomplete_cache) {
          response(user_autocomplete_cache[term]);
          return;
        }

        lastXhr = $.getJSON(base_app_url + 'caldav2json/principal_search', 
          request, function(data, status, xhr) {
          user_autocomplete_cache[term] = data;
          if (xhr === lastXhr) {
            response(data);
          }
        });
      },
      focus: function( event, ui ) {
        $(this).val(ui.item.username);
        return false;
      },
      select: function( event, ui ) {
        $(this).val(ui.item.username);
        return false;
      }
    })
    .data('autocomplete')._renderItem = function(ul, item) {
      return $('<li></li>')
        .data('item.autocomplete', item)
        .append('<a>' + item.displayname 
        + '<span style="font-style: italic">'
        + ' &lt;' + item.email + '&gt;</span></a>')
        .appendTo(ul);
    };

  new_entry_form.on('click', 
    '#calendar_share_add_button', function(event) {
    var new_user = $('#calendar_share_add_username').val();
    var access = $('#calendar_share_add_write_access').val();
    if (new_user != '') {
      // Check if new_user is already on list
      var already_added = false;
      manager.find('span.username')
        .each(function(index) {
          if (!already_added && $(this).text() == new_user) {
            already_added = true;
            $(this).parent().parent().effect('highlight', {}, 'slow');
          }
        });

      if (!already_added) {
        var new_row_data = {
          username: new_user,
          write_access: access
        };

        dust.render('calendar_share_row',
            dustbase.push(new_row_data), 
            function(err, out) {
              if (err != null) {
                show_error(t('messages', 'error_interfacefailure'),
                  err.message);
              } else {
                manager.find('tbody').append(out);

                // Reset form
                $('#calendar_share_add_username').val('');
                $('#calendar_share_add_write_access').val('0');

                share_manager_no_entries_placeholder();
              }
        });
      }
    }

  });
};

/**
 * Shows the placeholder for empty share lists
 */
var share_manager_no_entries_placeholder = function share_manager_no_entries_placeholder() {
  var manager = $('#calendar_share_table');
  if (manager.find('tbody tr').length == 1) {
    $('#calendar_share_no_rows').show();
  } else {
    $('#calendar_share_no_rows').hide();
  }
};


/**
 * Event render
 */
var event_render_callback = function event_render_callback(event, element) {
  var data = $.extend({},
    event,
    { formatted_calendar: get_calendar_displayname(event.calendar) });

  var caldata = get_calendar_data(event.calendar);
  if (caldata !== undefined && caldata.shared === true &&
    caldata.write_access == '0') {
    $.extend(data, { disable_actions: true });
  }

  dust.render('event_details_popup', dustbase.push(data), function(err, out) {
    if (err != null) {
      show_error(t('messages', 'error_interfacefailure'),
        err.message);
    } else {
      element.qtip({
        content: {
          text: out,
          title: {
            text: event.title,
            button: true
          }
        },
        position: {
          my: 'bottom center',
          at: 'top center',
          viewport: $('#calendar_view')
        },
        style: {
          classes: 'view_event_details ui-tooltip-bootstrap',
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
            .find('button.link_delete_event')
            .off('click')
            .on('click', function() {
              delete_event_handler();
            })
            .end()
            .find('button.link_modify_event')
            .off('click')
            .on('click', function() {
              modify_event_handler();
            });

            $(window).on('keydown.tooltipevents', function(e) {
              if(e.keyCode === $.ui.keyCode.ESCAPE) {
                api.hide(e);
              }
            })

            // Icons
            var links = api.elements.tooltip.find('div.actions').find('button.addicon').button();
            add_button_icons(links);
          },

          hide: function (event, api) {
            // Clicked on event?
            var has_clicked_event;

            if (event.originalEvent != undefined) {
              var click_target = $(event.originalEvent.target).parents();
              has_clicked_event = (click_target.length > 1 && click_target.andSelf().filter('.fc-event').length == 1);
            } else {
              has_clicked_event = false;
            }

            set_data('tooltip_hide_clicked_event', has_clicked_event);

            var current = get_data('current_event');
            set_data('recently_hidden_event', current);

            $(window).off('keydown.tooltipevents');
          }
        }
      });
    }
  });
};

/**
 * Event click
 */
var event_click_callback = function event_click_callback(event, 
    jsEvent, view) {
  var recently_hidden_event = get_data('recently_hidden_event');
  var hide_clicked_event = get_data('tooltip_hide_clicked_event');

  remove_data('current_event');

  if (recently_hidden_event != event ||
      (hide_clicked_event === false && 
       recently_hidden_event == event)) {
    set_data('current_event', event);
    $(this).qtip('show', jsEvent);
  }

  remove_data('recently_hidden_event');
  remove_data('tooltip_hide_clicked_event');
};

/**
 * Calendar slots dragging
 */
var slots_drag_callback = function slots_drag_callback(startDate, endDate, allDay, jsEvent, view) {
  var pass_allday = (view.name == 'month') ? false : allDay;
  var data = {
      start: fulldatetimestring(startDate),
      end: fulldatetimestring(endDate),
      allday: pass_allday,
      view: view.name
  };

  // Unselect every single day/slot
  $('#calendar_view').fullCalendar('unselect');
  event_field_form('new', data);
};

/**
 * Select helper
 */

var select_helper = function select_helper(start,end) {
  return $('<div style="border: 1px solid black; background-color: #f0f0f0;" class="selecthelper"/>')
    .text(
        $.fullCalendar.formatDates(start, end,
          prefs_timeformat + '{ - ' + prefs_timeformat + '}'));
};

/**
 * Event resizing
 */

var event_resize_callback = function event_resize_callback(event, dayDelta, minuteDelta, revertFunc,
  jsEvent, ui, view ) {

  // Generate on-the-fly form
  var formid = generate_on_the_fly_form(
    base_app_url + 'event/alter',
    {
      uid: event.uid,
      calendar: event.calendar,
      etag: event.etag,
      view: view.name,
      dayDelta: dayDelta,
      minuteDelta: minuteDelta,
      allday: event.allDay,
      was_allday: event.was_allday,
      timezone: event.timezone,
      type: 'resize'
    });

  if (get_data('formcreation') == 'ok') {
    var thisform = $('#' + formid);

    proceed_send_ajax_form(thisform,
      function(data) {
        // Users just want to know if something fails
        update_single_event(event, data);
      },
      function(data) {
        show_error(t('messages', 'error_modfailed'), data);
        revertFunc();
      },
      function() {
        revertFunc();
      });
    }

  // Remove generated form
  $(thisform).remove();
};

/**
 * Event drag and drop
 */

var event_drop_callback = function event_drop_callback(event, dayDelta, minuteDelta, allDay,
      revertFunc, jsEvent, ui, view) {

  // Generate on-the-fly form
  var formid = generate_on_the_fly_form(
    base_app_url + 'event/alter',
    {
      uid: event.uid,
      calendar: event.calendar,
      etag: event.etag,
      view: view.name,
      dayDelta: dayDelta,
      minuteDelta: minuteDelta,
      allday: event.allDay,
      was_allday: event.orig_allday,
      timezone: event.timezone,
      type: 'drag'
    });

  if (get_data('formcreation') == 'ok') {
    var thisform = $('#' + formid);

    proceed_send_ajax_form(thisform,
      function(data) {
        // Users just want to know if something fails
        update_single_event(event, data);
      },
      function(data) {
        show_error(t('messages', 'error_modfailed'), data);
        revertFunc();
      },
      function() {
        revertFunc();
      });
    }

  // Remove generated form
  $(thisform).remove();
};

// Delete link
// TODO: check for rrule/recurrence-id (EXDATE, etc)
var delete_event_handler = function delete_event_handler() {
  var data = get_data('current_event'),
      ded = '#delete_event_dialog';

  if (data === undefined) {
    show_error(t('messages', 'error_interfacefailure'),
      t('messages', 'error_current_event_not_loaded'));
    return;
  }

  load_generated_dialog('dialog_generator/delete_event',
    {},
    function() {
      // Show event fields
      $(ded + ' span.calendar').html(get_calendar_displayname(data.calendar));
      $(ded + ' p.title').html(data.title);

      var rrule = data.rrule;
      if (rrule === undefined) {
        $(ded + ' div.rrule').hide();
      }

      var thisform = $('#delete_form');
      thisform.find('input.uid').val(data.uid);
      thisform.find('input.calendar').val(data.calendar);
      thisform.find('input.href').val(data.href);
      thisform.find('input.etag').val(data.etag);
    },
    t('labels', 'deleteevent'),
    [
      {
        'text': t('labels', 'yes'),
        'class': 'addicon btn-icon-event-delete',
        'click': function() {
          var thisform = $('#delete_form');
          proceed_send_ajax_form(thisform,
            function(data) {
              $('#calendar_view').fullCalendar('removeEvents', get_data('current_event').id);
            },
            function(data) {
              show_error(t('messages', 'error_event_not_deleted'), data);
            },
            function() {});

          // Destroy dialog
          destroy_dialog('#delete_event_dialog');

        }
      },
      {
        'text': t('labels', 'cancel'),
        'class': 'addicon btn-icon-cancel',
        'click': function() { destroy_dialog('#delete_event_dialog'); }
      }
    ],
    'delete_event_dialog', 400);

    // Close tooltip
    $(ved).qtip('hide');
  return false;
};

// Edit/Modify link
var modify_event_handler = function modify_event_handler() {
  // TODO: check for rrule/recurrence-id
  // Data about this event
  var event_data = get_data('current_event');
  if (event_data === undefined) {
    show_error(t('messages', 'error_interfacefailure'),
      t('messages', 'error_current_event_not_loaded'));
    return;
  }

  var data = {
    uid: event_data.uid,
    calendar: event_data.calendar,
    href: event_data.href,
    etag: event_data.etag,
    start: fulldatetimestring(event_data.start),
    end: fulldatetimestring(event_data.end),
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
    orig_start: fulldatetimestring($.fullCalendar.parseDate(event_data.orig_start)),
    orig_end: fulldatetimestring($.fullCalendar.parseDate(event_data.orig_end))
  };
  // Close tooltip
  $(ved).qtip('hide');

  event_field_form('modify', data);

  return false;
};

// Shows a calendar
var show_calendar = function show_calendar(calendar_obj) {
  $('#calendar_view').fullCalendar('addEventSource', calendar_obj.data().eventsource);
  calendar_obj.removeClass('transparent');
};

// Hides a calendar
var hide_calendar = function hide_calendar(calendar_obj) {
  $('#calendar_view').fullCalendar('removeEventSource', calendar_obj.data().eventsource);
  calendar_obj.addClass('transparent');
};

// Toggles calendar visibility
var toggle_calendar = function toggle_calendar(calendar_obj) {
  if (calendar_obj.hasClass('transparent')) {
    show_calendar(calendar_obj);
  } else {
    hide_calendar(calendar_obj);
  }
};

// Gets csrf token value
var get_csrf_token = function get_csrf_token() {
  return $.cookie('csrf_cookie_name');
}

// vim: sw=2 tabstop=2
