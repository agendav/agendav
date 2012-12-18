var AgenDAVDateAndTime = AgenDAVDateAndTime || {};

/*
 * Extracts the time from a Date object, and returns it as an string
 */
AgenDAVDateAndTime.extractTime = function extractTime(dateobj) {
    if (AgenDAVConf.prefs_timeformat_option == '24') {
        return moment(dateobj).format('HH:mm');
    } else {
        return moment(dateobj).format('hh:mm A');
    }
};

/*
 * Extracts the date from a Date object, and returns it as an string
 */
AgenDAVDateAndTime.extractDate = function extractDate(dateobj) {
    if (AgenDAVConf.prefs_dateformat_option == 'ymd') {
        return moment(dateobj).format('YYYY-MM-DD');
    } else if (AgenDAVConf.prefs_dateformat_option == 'dmy') {
        return moment(dateobj).format('DD-MM-YYYY');
    } else {
        return moment(dateobj).format('MM-DD-YYYY');
    }
};

/*
 * Approximates a date to the nearest quarter
 */
AgenDAVDateAndTime.approxNearest = function approxNearest(dt) {
    var minutes = dt.minutes();
    var minutes_to_add = Math.round(minutes/15)*15;
    // Clone original moment object, and set new minutes
    var result = moment(dt).seconds(0).minutes(0).add('minutes', minutes_to_add);

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
        generated_end = moment(start).add('hours', 1);
    } else {
        generated_end = moment(end);
        if (start.diff(generated_end) == 0) {
            generated_end = moment(start).add('hours', 1);
        }
    }

    return generated_end;
};
