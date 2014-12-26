var AgenDAVDateAndTime = AgenDAVDateAndTime || {};

/*
 * Extracts the time from a Date object, and returns it as an string
 */
AgenDAVDateAndTime.extractTime = function extractTime(dateobj) {
    return moment(dateobj).format(AgenDAVConf.prefs_timeformat_moment);
};

/*
 * Extracts the date from a Date object, and returns it as an string
 */
AgenDAVDateAndTime.extractDate = function extractDate(dateobj) {
    return moment(dateobj).format(AgenDAVConf.prefs_dateformat_moment);
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
 * Generates a Moment object containing the ending date for an event,
 * based on start date and the original end Date object,
 * which could be undefined. In that case the generated ending object will
 * be calculated by adding an hour to the start date
 */
AgenDAVDateAndTime.endDate = function endDate(end, start) {
    var generated_end;

    if (end === undefined || end === null) {
        generated_end = moment(start).add(1, 'hours');
    } else {
        generated_end = moment(end);
        if (start.diff(generated_end) === 0) {
            generated_end = moment(start).add(1, 'hours');
        }
    }

    return generated_end;
};

/**
 * Parses a set of start and end moment objects and returns them formatted
 */
AgenDAVDateAndTime.formatEventDates = function formatEventDates(event_data) {
    var result = '';
    var start = moment(event_data.start);
    var end = moment(event_data.end);

    if (event_data.allDay === true) {
        end.subtract(1, 'days');
        result = start.format('LL');
        if (!start.isSame(end, 'day')) {
            result += " - " + end.format('LL');
        }

        return result;
    }

    result = start.format('LL') + " " + this.formatTime(start);

    var end_string = end.format('LL') + " " + this.formatTime(end);
    if (start.isSame(end, 'day')) {
        end_string = this.formatTime(end); // Just show the time
    }
    result += " - " + end_string;

    return result;
};


/**
 * Returns a moment object time formatted
 */
AgenDAVDateAndTime.formatTime = function formatTime(datetime) {
    return datetime.format(AgenDAVConf.prefs_timeformat_moment);
};

/**
 * Receives a datepicker input and an optional timepicker input, and returns
 * a string in ISO8601 format
 */
AgenDAVDateAndTime.convertISO8601 = function convertISO8601(datepicker, timepicker, allday) {
    var result = datepicker.datepicker('getDate');

    // Events with no time set (all day, recurrences, etc)
    if (timepicker.length === 0 || allday === true) {
        // This should be an UTC date
        return moment(result).format('YYYY-MM-DDTHH:mm:ss.000') + 'Z';
    }

    result = timepicker.timepicker('getTime', result);
    return moment(result).toISOString();
};
