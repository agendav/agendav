var AgenDAVDateAndTime = AgenDAVDateAndTime || {};

// Time formats for fullcalendar axis and time
AgenDAVDateAndTime.fullCalendarFormat = {
    '24': 'H:mm',
    '12': 'h(:mm)a'
};

// Time and date moment.js formats
AgenDAVDateAndTime.momentFormat = {
    '24': 'HH:mm',
    '12': 'hh:mm A',
    'ymd': 'YYYY-MM-DD',
    'dmy': 'DD/MM/YYYY',
    'mdy': 'MM/DD/YYYY',
};

// Datepicker  formats
AgenDAVDateAndTime.datepickerFormat = {
    'ymd': 'yy-mm-dd',
    'dmy': 'dd/mm/yy',
    'mdy': 'mm/dd/yy',
};

/*
 * Extracts the time from a Date object, and returns it as an string
 */
AgenDAVDateAndTime.extractTime = function extractTime(dateobj) {
    return moment(dateobj).format(AgenDAVDateAndTime.momentFormat[AgenDAVUserPrefs.time_format]);
};

/*
 * Extracts the date from a Date object, and returns it as an string
 */
AgenDAVDateAndTime.extractDate = function extractDate(dateobj) {
    return moment(dateobj).format(AgenDAVDateAndTime.momentFormat[AgenDAVUserPrefs.date_format]);
};

/*
 * Approximates a date to the nearest quarter
 */
AgenDAVDateAndTime.approxNearest = function approxNearest(dt) {
    var now = moment();
    var minutes = Math.ceil(now.minutes()/15)*15;
    // Clone original moment object, and set new minutes
    var result = moment(dt)
        .hours(now.hours())
        .minutes(0)
        .seconds(0)
        .add(minutes, 'minutes');

    return result;
};

/**
 * Generates an end date given the actual start and end dates.
 *
 * This method just sets the right end date for events with end = start|undefined
 *
 * @param Object Event data
 */
AgenDAVDateAndTime.endDate = function endDate(event) {

    if (event.end === undefined || event.end === null || event.start.diff(event.end) === 0) {
        if (event.allDay === true) {
            return moment(event.start);
        }
        return moment(event.start).add(1, 'hours');
    }

    // No issues with the end date
    return moment(event.end);
};

/**
 * Parses a set of start and end moment objects and returns them formatted
 */
AgenDAVDateAndTime.formatEventDates = function formatEventDates(event_data) {
    var result = '';
    var start = moment(event_data.start);
    var end = moment(event_data.end);

    // After dropping/resizing an event, the 'end' property gets a null
    // value, usually on all day events if the event duration is just 1 day
    if (event_data.end === null) {
        var unit = (event_data.allDay ? 'days' : 'hours');
        end = moment(event_data.start);
        end.add(1, unit);
    }

    if (event_data.allDay === true) {
        end.subtract(1, 'days');
        result = start.format('LL');
        if (!start.isSame(end, 'day')) {
            result += " - " + end.format('LL');
        }

        return result;
    }

    result = start.format('LL') + " " + this.extractTime(start);

    var end_string = end.format('LL') + " " + this.extractTime(end);
    if (start.isSame(end, 'day')) {
        end_string = this.extractTime(end); // Just show the time
    }
    result += " - " + end_string;

    return result;
};


/**
 * Receives a datepicker input and an optional timepicker input, and returns
 * a string in ISO8601 format
 *
 * @param jQuery datepicker
 * @param jQuery timepicker
 * @param bool allday
 * @param string timezone
 * @return string
 */
AgenDAVDateAndTime.convertISO8601 = function convertISO8601(datepicker, timepicker, allday, timezone) {
    var result = datepicker.datepicker('getDate');

    // Events with no time set (all day, recurrences, etc)
    if (timepicker.length === 0 || allday === true) {
        // This should be an UTC date
        return moment(result).format('YYYY-MM-DDT00:00:00.000') + 'Z';
    }

    result = timepicker.timepicker('getTime', result);

    // Convert to user timezone
    return moment.tz(
                moment(result).format('YYYY-MM-DDTHH:mm:ss.000'),
                timezone
            ).toISOString();
};
