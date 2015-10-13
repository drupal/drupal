/**
 * @file
 * Statistics functionality.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  $(document).ready(function () {
    $.ajax({
      type: 'POST',
      cache: false,
      url: drupalSettings.statistics.url,
      data: drupalSettings.statistics.data
    });
  });
})(jQuery, Drupal, drupalSettings);
