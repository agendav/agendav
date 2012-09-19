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
      csrf_token_name: AgenDAVConf.prefs_csrf_token_name,
      enable_calendar_sharing: enable_calendar_sharing
    });

    // Default colorpicker options
    set_default_colorpicker_options();

    // Enable full calendar
    // TODO: configurable!
    $('#calendar_view').fullCalendar({
      selectable: true,
      editable: true,
      firstDay: AgenDAVConf.prefs_firstday,
      timeFormat: {
        agenda: AgenDAVConf.prefs_timeformat + '{ - ' 
          + AgenDAVConf.prefs_timeformat + '}',
        '': AgenDAVConf.prefs_timeformat
      },
      columnFormat: {
        month: AgenDAVConf.prefs_format_column_month,
        week: AgenDAVConf.prefs_format_column_week,
        day: AgenDAVConf.prefs_format_column_day,
        table: AgenDAVConf.prefs_format_column_table
      },
      titleFormat: {
        month: AgenDAVConf.prefs_format_title_month,
        week: AgenDAVConf.prefs_format_title_week,
        day: AgenDAVConf.prefs_format_title_day,
        table: AgenDAVConf.prefs_format_title_table
      },
      currentTimeIndicator: true,
      weekMode: 'liquid',
      height: calendar_height(),
      windowResize: function(view) {
        var new_height = calendar_height();
        $(this).fullCalendar('option', 'height', new_height);
      },
      overflowRender: function(data, element) {
        element.html(
          t('messages', 'more_events', { '%count':  data.count })
        );
        element.on('click', function(event) {
          $('#calendar_view').fullCalendar('gotoDate', data.date);
          $('#calendar_view').fullCalendar('changeView', 'agendaDay');
        });
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
      axisFormat: AgenDAVConf.prefs_timeformat,
      slotMinutes: 30,
      firstHour: 8,

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
    $('div.calendar_list').on('click', 'i.cfg', function(e) {
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
          .find('i')
            .removeClass('icon-eye-close')
            .addClass('icon-eye-open');
      } else {
        $.map(shared_cals, function(e, i) {
          show_calendar($(e));
        });
        $(this)
          .removeClass('show_all')
          .addClass('hide_all')
          .find('i')
            .removeClass('icon-eye-open')
            .addClass('icon-eye-close');
      }
    });

    // Help tooltips
    $('#sidebar div.buttons').find('img[title],span[title],a[title]').qtip({
      position: {
        my: 'top left',
        at: 'bottom left'
      },
      show: {
        delay: 600
      },
      style: {
        classes: 'ui-tooltip-bootstrap',
        tip: true
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

    // Printing
    
    setup_print_tweaks();
    



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
        effect: false,
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
  // Hide loading indicator
  loading(false);

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

    var dialog_ajax_req = $.ajax({
      url: base_app_url + url,
      cache: false,
      type: 'POST',
      data: formdata,
      dataType: 'html'
    });

    dialog_ajax_req.then(function() {
        loading(false);
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
  var url, data;

  if (formObj instanceof jQuery) {
    url = $(formObj).attr('action');
    data = $(formObj).serialize();
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
  firstDay: AgenDAVConf.prefs_firstday,
  isRTL: false,
  showMonthAfterYear: false,
  yearSuffix: ''};  

$.datepicker.setDefaults($.datepicker.regional['custom']);
$.datepicker.setDefaults({constrainInput: true});
$.datepicker.setDefaults({dateFormat: AgenDAVConf.prefs_dateformat});
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
      var start_datepicker_opts = {
        onSelect: function(dateText, inst) {
          // End date can't be previous to start date
          set_end_minDate();
        }
      };

      // Tabs
      $(ced + '_tabs').tabs();


      $(ced + ' input.start_time').timePicker(AgenDAVConf.timepicker_base);
      $(ced + ' input.end_time').timePicker(AgenDAVConf.timepicker_base);
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
      dust.render('reminders_table', dustbase.push(data), function(err, out) {
        if (err != null) {
          show_error(t('messages', 'error_interfacefailure'),
            err.message);
        } else {
          $('#tabs-reminders').html(out);
          reminders_manager();
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
    'com_event_dialog', 550);
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
};

/*
 * Updates the calendar list and generates eventSources for fullcalendar
 */

var update_calendar_list = function update_calendar_list(maskbody) {
  if (maskbody) {
    loading(true);
  }

  var updcalendar_ajax_req = $.ajax({
    url: base_app_url + 'calendar/all',
    cache: false,
    dataType: 'json',
    async: false // Let's wait
  });

  updcalendar_ajax_req.then(function() {
    if (maskbody) {
      loading(false);
    }
  });

  updcalendar_ajax_req.fail(function(jqXHR, textStatus, errorThrown) {
    show_error(t('messages', 'error_loading_calendar_list'),
      t('messages', 'error_oops') + textStatus);
  });

  updcalendar_ajax_req.done(function(data, textStatus, jqXHR) {
    var was_transparent = {};

    // Remove old eventSources and remove every list item
    $('.calendar_list li.available_calendar').each(function(index) {
      var data = $(this).data();
      $('#calendar_view').fullCalendar('removeEventSource',
        data.eventsource);

      if ($(this).hasClass('transparent')) {
        was_transparent[data.calendar] = true;
      }

      $(this).remove();
    });

    var count = 0,
      count_shared = 0,
      own_calendars = document.createDocumentFragment(),
      shared_calendars = document.createDocumentFragment(),
      collected_event_sources = [];

    $.each(data, function(key, calendar) {
      count++;

      // Some values need to be generated
      if (calendar.color === undefined || calendar.color === false || calendar.color == null) {
        calendar.color = default_calendar_color;
      } else {
        calendar.color = calendar.color.substr(0,7);
      }
      calendar.fg = fg_for_bg(calendar.color);
      calendar.bordercolor = $.color.parse(calendar.color).scale('rgb',
        (calendar.fg == '#000000' ? 0.8 : 1.8)).toString();

      var li = generate_calendar_entry(calendar);

      if (was_transparent[calendar.calendar]) {
        li.addClass('transparent');
      } else {
        collected_event_sources.push($(li).data().eventsource);
      }

      if (calendar.shared == true) {
        count_shared++;
        shared_calendars.appendChild(li[0]);
      } else {
        own_calendars.appendChild(li[0]);
      }

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
  var eventsource = generate_event_source(data.calendar);
  eventsource.ignoreTimezone = true; // Ignore UTC offsets
  eventsource.color = data.color;
  eventsource.textColor = data.fg;
  eventsource.borderColor = data.bordercolor;

  // Shared calendars
  if (data.shared !== undefined && data.shared == true && data.write_access == '0') {
    eventsource.editable = false;
  }

  data.eventsource = eventsource;

  var $out;

  dust.render('calendar_list_entry', dustbase.push(data), function(err, out) {
    if (err != null) {
      show_error(t('messages', 'error_interfacefailure'),
        err.message);
    } else {
      $out = $(out);

      // Associate data + eventsource to new list item
      $out.data(data);

      // Disable text selection on this (useful for dblclick)
      $out.disableSelection();

      $out.find('span[title],i[title]').qtip({
        position: {
          my: 'top left',
          at: 'bottom left'
        },
        show: {
          delay: 600
        },
        style: {
          classes: 'ui-tooltip-bootstrap',
          tip: true
        }
      });
    }
  });

  return $out;
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
        .append('<a><i class="icon-user"></i> ' + item.displayname
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


/*
 * Reminders manager
 */

var reminders_manager = function reminders_manager() {

  var tab_reminders = $('#tabs-reminders');
  var manager = $('#reminders_table');

  initialize_date_and_time_pickers(tab_reminders);

  reminders_manager_no_entries_placeholder();

  manager.on('click', 
    '.reminder_delete', function(event) {
      $(this).parent().parent()
        .fadeOut('fast', function() { 
          $(this).remove();
          reminders_manager_no_entries_placeholder();
        });
    });

  manager.parent().on('click', 'img.reminder_add_button', function(event) {
    var formdata = $(this).closest('tbody').serializeObject();
    // Basic validations
    var proceed = false;
    var regexp_num = /^[0-9]+$/;

    if (formdata.is_absolute === false) {
      if (formdata.qty !== '' && regexp_num.test(formdata.qty) &&
        formdata.interval !== '' && formdata.before !== '') {

        proceed = true;
      }
    } else {
      if (formdata.tdate !== '' && formdata.ttime !== '') {
        proceed = true;
      }
    }

    if (proceed === true) {
      var $new_reminder_row = $(this).closest('tr');

      dust.render('reminder_row',
        dustbase.push(formdata), function(err, out) {
        if (err != null) {
          show_error(t('messages', 'error_interfacefailure'),
            err.message);
        } else {
          manager.find('tbody').append(out);

          $new_reminder_row.find('input').val('');
          $new_reminder_row.find('select').val('');

          initialize_date_and_time_pickers(tab_reminders);
          reminders_manager_no_entries_placeholder();
        }
      });

    }
  });
};

/*
 * Shows/hides reminders placeholder when no reminders are set up
 */

var reminders_manager_no_entries_placeholder = function reminders_manager_no_entries_placeholder() {
  var manager = $('#reminders_table');
  if (manager.find('tbody tr').length == 1) {
    $('#reminders_no_rows').show();
  } else {
    $('#reminders_no_rows').hide();
  }
}



/**
 * Event render
 */
var event_render_callback = function event_render_callback(event, element) {
  var caldata = get_calendar_data(event.calendar);
  var data = $.extend({},
    event,
    { caldata: caldata });

  if (caldata !== undefined && caldata.shared === true &&
    caldata.write_access == '0') {
    $.extend(data, { disable_actions: true });
  }

  // Icons
  var icons = [];

  if (event.rrule != undefined) {
    icons.push('icon-repeat');
  }
  if (event.reminders.length > 0) {
    icons.push('icon-bell');
  }

  // Prepend icons
  if (icons.length != 0) {
    var icon_html = $('<span class="fc-event-icons"></span>');
    $.each(icons, function(n, i) {
      icon_html.append('<i class="' + i + '"></i>');
    });

    if (!element.hasClass('fc-event-row')) {
      element.find('.fc-event-title').after(icon_html);
    }
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
              event_delete_dialog();
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
            remove_data('current_event');
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
  var current_event = get_data('current_event');

  if (current_event == event) {
    $(ved).qtip('hide');
    remove_data('current_event');
  } else {
    set_data('current_event', event);
    $(this).qtip('show', jsEvent);
  }

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
          AgenDAVConf.prefs_timeformat + '{ - ' + AgenDAVConf.prefs_timeformat + '}'));
};

/**
 * Event resizing
 */

var event_resize_callback = function event_resize_callback(event, dayDelta, minuteDelta, revertFunc,
  jsEvent, ui, view ) {

      event_alter('resize', event, dayDelta, minuteDelta, event.allDay, revertFunc, jsEvent, ui, view);
};

/**
 * Event drag and drop
 */

var event_drop_callback = function event_drop_callback(event, dayDelta, minuteDelta, allDay,
      revertFunc, jsEvent, ui, view) {

      event_alter('drag', event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view);
};


/**
 * Event alter via drag&drop or resizing it
 */
var event_alter = function event_alter(alterType, event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view) {
  var params = {
    url: base_app_url + 'event/alter',
    data: {
      uid: event.uid,
      calendar: event.calendar,
      etag: event.etag,
      view: view.name,
      dayDelta: dayDelta,
      minuteDelta: minuteDelta,
      allday: allDay,
      was_allday: event.allDay,
      timezone: event.timezone,
      type: alterType
    }
  };

  params.data[AgenDAVConf.prefs_csrf_token_name] = get_csrf_token();

  proceed_send_ajax_form(params,
      function(data) {
        update_single_event(event, data);
      },
      function(data) {
        show_error(t('messages', 'error_modfailed'), data);
        revertFunc();
      },
      function() {
        revertFunc();
      });
};

// Delete link
// TODO: check for rrule/recurrence-id (EXDATE, etc)
var event_delete_dialog = function event_delete_dialog() {
  var form_url = base_app_url + 'event/delete';
  var title = t('labels', 'deleteevent');

  var data = get_data('current_event');

  if (data === undefined) {
    show_error(t('messages', 'error_interfacefailure'),
      t('messages', 'error_current_event_not_loaded'));
    return;
  }

  $.extend(data, {
    applyid: 'event_delete_form',
    frm: {
      action: form_url,
      method: 'post',
      csrf: get_csrf_token()
    }
  });

  show_dialog('event_delete_dialog',
    data,
    title,
    [ 
      {
        'text': t('labels', 'yes'),
        'class': 'addicon btn-icon-event-delete',
        'click': function() {
          var thisform = $('#event_delete_form');
          proceed_send_ajax_form(thisform,
            function(rdata) {
              $('#calendar_view').fullCalendar('removeEvents', data.id);
            },
            function(rdata) {
              show_error(t('messages', 'error_event_not_deleted'), data);
            },
            function() {});

          // Destroy dialog
          destroy_dialog('#event_delete_dialog');
        }
      },
      {
        'text': t('labels', 'cancel'),
        'class': 'addicon btn-icon-cancel',
        'click': function() { destroy_dialog('#event_delete_dialog'); }
      }
    ],
    'event_delete_dialog',
    400,
    function() {});

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
    reminders: event_data.reminders,
    visible_reminders: event_data.visible_reminders,
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

// Initializes datepickers and timepickers
var initialize_date_and_time_pickers = function initialize_date_and_time_pickers(obj) {
  obj.find('.needs-datepicker').datepicker();
  obj.find('.needs-timepicker').timePicker(AgenDAVConf.timepicker_base);
};


// Gets csrf token value
var get_csrf_token = function get_csrf_token() {
  return $.cookie(AgenDAVConf.prefs_csrf_cookie_name);
}

// Loading indicator
var loading = function loading(status) {
  if (status === false) {
    $('#loading').hide();
  } else {
    $('#loading').show();
  }
}

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


// vim: sw=2 tabstop=2
