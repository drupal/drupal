/**
 * @file
 * Timezone detection.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Set the client's system time zone as default values of form fields.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.setTimezone = {
    attach: function (context, settings) {
      var $timezone = $(context).find('.timezone-detect').once('timezone');
      if ($timezone.length) {
        var dateString = Date();
        // In some client environments, date strings include a time zone
        // abbreviation, between 3 and 5 letters enclosed in parentheses,
        // which can be interpreted by PHP.
        var matches = dateString.match(/\(([A-Z]{3,5})\)/);
        var abbreviation = matches ? matches[1] : 0;

        // For all other client environments, the abbreviation is set to "0"
        // and the current offset from UTC and daylight saving time status are
        // used to guess the time zone.
        var dateNow = new Date();
        var offsetNow = dateNow.getTimezoneOffset() * -60;

        // Use January 1 and July 1 as test dates for determining daylight
        // saving time status by comparing their offsets.
        var dateJan = new Date(dateNow.getFullYear(), 0, 1, 12, 0, 0, 0);
        var dateJul = new Date(dateNow.getFullYear(), 6, 1, 12, 0, 0, 0);
        var offsetJan = dateJan.getTimezoneOffset() * -60;
        var offsetJul = dateJul.getTimezoneOffset() * -60;

        var isDaylightSavingTime;
        // If the offset from UTC is identical on January 1 and July 1,
        // assume daylight saving time is not used in this time zone.
        if (offsetJan === offsetJul) {
          isDaylightSavingTime = '';
        }
        // If the maximum annual offset is equivalent to the current offset,
        // assume daylight saving time is in effect.
        else if (Math.max(offsetJan, offsetJul) === offsetNow) {
          isDaylightSavingTime = 1;
        }
        // Otherwise, assume daylight saving time is not in effect.
        else {
          isDaylightSavingTime = 0;
        }

        // Submit request to the system/timezone callback and set the form
        // field to the response time zone. The client date is passed to the
        // callback for debugging purposes. Submit a synchronous request to
        // avoid database errors associated with concurrent requests
        // during install.
        var path = 'system/timezone/' + abbreviation + '/' + offsetNow + '/' + isDaylightSavingTime;
        $.ajax({
          async: false,
          url: Drupal.url(path),
          data: {date: dateString},
          dataType: 'json',
          success: function (data) {
            if (data) {
              $timezone.val(data);
            }
          }
        });
      }
    }
  };

})(jQuery, Drupal);
