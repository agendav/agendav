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
        if (start.diff(generated_end) == 0) {
            generated_end = moment(start).add(1, 'hours');
        }
    }

    return generated_end;
};
