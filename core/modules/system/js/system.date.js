/**
 * @file
 * Provides date format preview feature.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  var dateFormats = drupalSettings.dateFormats;

  /**
   * Display the preview for date format entered.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dateFormat = {
    attach: function (context) {
      var $context = $(context);
      var $source = $context.find('[data-drupal-date-formatter="source"]').once('dateFormat');
      var $target = $context.find('[data-drupal-date-formatter="preview"]').once('dateFormat');
      var $preview = $target.find('em');

      // All elements have to exist.
      if (!$source.length || !$target.length) {
        return;
      }

      /**
       * Event handler that replaces date characters with value.
       *
       * @param {jQuery.Event} e
       */
      function dateFormatHandler(e) {
        var baseValue = $(e.target).val() || '';
        var dateString = baseValue.replace(/\\?(.?)/gi, function (key, value) {
          return dateFormats[key] ? dateFormats[key] : value;
        });

        $preview.html(dateString);
        $target.toggleClass('js-hide', !dateString.length);
      }

      /**
       * On given event triggers the date character replacement.
       */
      $source.on('keyup.dateFormat change.dateFormat input.dateFormat', dateFormatHandler)
        // Initialize preview.
        .trigger('keyup');
    }
  };

})(jQuery, Drupal, drupalSettings);
