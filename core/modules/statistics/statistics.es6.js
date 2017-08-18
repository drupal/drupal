/**
 * @file
 * Statistics functionality.
 */

(function ($, Drupal, drupalSettings) {
  $(document).ready(() => {
    $.ajax({
      type: 'POST',
      cache: false,
      url: drupalSettings.statistics.url,
      data: drupalSettings.statistics.data,
    });
  });
}(jQuery, Drupal, drupalSettings));
