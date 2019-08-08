/**
 * @file
 * Provides date format preview feature.
 */

(function($, Drupal, drupalSettings) {
  const dateFormats = drupalSettings.dateFormats;

  /**
   * Display the preview for date format entered.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach behavior for previewing date formats on input elements.
   */
  Drupal.behaviors.dateFormat = {
    attach(context) {
      const $context = $(context);
      const $source = $context
        .find('[data-drupal-date-formatter="source"]')
        .once('dateFormat');
      const $target = $context
        .find('[data-drupal-date-formatter="preview"]')
        .once('dateFormat');
      const $preview = $target.find('em');

      // All elements have to exist.
      if (!$source.length || !$target.length) {
        return;
      }

      /**
       * Event handler that replaces date characters with value.
       *
       * @param {jQuery.Event} e
       *   The jQuery event triggered.
       */
      function dateFormatHandler(e) {
        const baseValue = $(e.target).val() || '';
        const dateString = baseValue.replace(
          /\\?(.?)/gi,
          (key, value) => (dateFormats[key] ? dateFormats[key] : value),
        );

        $preview.text(dateString);
        $target.toggleClass('js-hide', !dateString.length);
      }

      /**
       * On given event triggers the date character replacement.
       */
      $source
        .on(
          'keyup.dateFormat change.dateFormat input.dateFormat',
          dateFormatHandler,
        )
        // Initialize preview.
        .trigger('keyup');
    },
  };
})(jQuery, Drupal, drupalSettings);
