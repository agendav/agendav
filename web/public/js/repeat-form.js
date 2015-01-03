/**
 * Functions used to manage the 'Repeat' tab interactions
 */

var AgenDAVRepeat = AgenDAVRepeat || {};

/**
 * Handles interaction with the form controls
 *
 * @param jQuery $form jQuery element containing the form
 */
AgenDAVRepeat.handleForm = function handleForm($form) {
  var $repeat_type = $form.find('select.repeat_type');
  var $repeat_ends = $form.find('select.repeat_ends');

  $form.on('change', 'input,select.secondary', function(e) {
    $repeat_type.trigger('change');
  });

  $repeat_type.on('change', function() {
    var frequency = $(this).val();

    if (frequency === 'none') {
      $form.find('.container_repeat_options').hide();
    } else {
      $form.find('.container_repeat_options').show();
    }

    $repeat_ends.trigger('change');
  });

  $repeat_ends.on('change', function() {
    var container_repeat_ends_options = $form.find('div.container_repeat_ends_options');
    var ends = $(this).val();

    if (ends === 'never') {
      container_repeat_ends_options.hide();
    }

    if (ends === 'after') {
      container_repeat_ends_options.show();
      $form.find('div.container_repeat_count').show();
      $form.find('div.container_repeat_until').hide();
      $form.find('input.repeat_until').val('');
    }

    if (ends === 'date') {
      container_repeat_ends_options.show();
      $form.find('div.container_repeat_count').hide();
      $form.find('div.container_repeat_until').show();
      $form.find('input.repeat_count').val('');
    }


    // Generate new RRULE value
    generate_iso8601_values($form); // Required to have a valid date

    // serialize* can't be called on a div
    var new_rrule = AgenDAVRepeat.generateRRule(
        $form.find('input,select').serializeArray()
    );
    $('#rrule').val(new_rrule.toString());
    $('#repeat_explanation').html(
        AgenDAVRepeat.explainRRule(new_rrule)
      );
  });

  // Trigger it for the first time
  $repeat_type.trigger('change');
};


/**
 * Generates a RRule based on the form contents
 *
 * @param Object data Form data from serializeArray()
 * @return RRule object
 */
AgenDAVRepeat.generateRRule = function generateRRule(data) {
  var options = {};
  var result;
  var ends;

  $.each(data, function(i, field) {
    var value = field.value;

    if (value === '') {
      // Skip this one
      return true;
    }

    if (field.name === 'frequency') {
      // Stop processing if repeat was not set
      if (value === 'none') {
        return false;
      }
      options.freq = AgenDAVRepeat.getRRuleJsFrequency(value);
    }

    if (field.name === 'repeat_interval') {
      options.interval = value;
    }

    if (field.name === 'ends') {
      ends = field.value;
    }

    if (field.name === 'repeat_count' && ends === 'after') {
      options.count = value;
    }

    if (field.name === 'repeat_until_date' && ends === 'date') {
      options.until = moment(value).toDate();
    }
  });

  result = new RRule(options);
  return result;
};

/**
 * Translates a frequency value into a rrule.js frequency constant
 *
 * @param string frequency Form value
 * @return RRule constant
 */
AgenDAVRepeat.getRRuleJsFrequency = function getRRuleJsFrequency(frequency) {
  if (frequency === 'daily') {
    return RRule.DAILY;
  }

  if (frequency === 'weekly') {
    return RRule.WEEKLY;
  }

  if (frequency === 'monthly') {
    return RRule.MONTHLY;
  }

  if (frequency === 'yearly') {
    return RRule.YEARLY;
  }
};

/**
 * Generates an human readable explanation of a RRULE
 *
 * @param RRule rrule
 */
AgenDAVRepeat.explainRRule = function explainRRule(rrule) {
  return rrule.toText(rrule_gettext, AgenDAVConf.i18n.rrule);
};
