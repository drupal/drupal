/**
 * @file
 * System behaviors.
 */

(function ($, Drupal, drupalSettings) {
  // Cache IDs in an array for ease of use.
  const ids = [];

  /**
   * Attaches field copy behavior from input fields to other input fields.
   *
   * When a field is filled out, apply its value to other fields that will
   * likely use the same value. In the installer this is used to populate the
   * administrator email address with the same value as the site email address.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the field copy behavior to an input field.
   */
  Drupal.behaviors.copyFieldValue = {
    attach(context) {
      // List of fields IDs on which to bind the event listener.
      // Create an array of IDs to use with jQuery.
      Object.keys(drupalSettings.copyFieldValue || {}).forEach((element) => {
        ids.push(element);
      });

      if (ids.length) {
        // Listen to value:copy events on all dependent fields.
        // We have to use body and not document because of the way jQuery events
        // bubble up the DOM tree.
        $(once('copy-field-values', 'body')).on(
          'value:copy',
          this.valueTargetCopyHandler,
        );
        // Listen on all source elements.
        $(once('copy-field-values', `#${ids.join(', #')}`)).on(
          'blur',
          this.valueSourceBlurHandler,
        );
      }
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload' && ids.length) {
        $(once.remove('copy-field-values', 'body')).off('value:copy');
        $(once.remove('copy-field-values', `#${ids.join(', #')}`)).off('blur');
      }
    },

    /**
     * Event handler that fill the target element with the specified value.
     *
     * @param {jQuery.Event} e
     *   Event object.
     * @param {string} value
     *   Custom value from jQuery trigger.
     */
    valueTargetCopyHandler(e, value) {
      const $target = $(e.target);
      if ($target.val() === '') {
        $target.val(value);
      }
    },

    /**
     * Handler for a Blur event on a source field.
     *
     * This event handler will trigger a 'value:copy' event on all dependent
     * fields.
     *
     * @param {jQuery.Event} e
     *   The event triggered.
     */
    valueSourceBlurHandler(e) {
      const value = $(e.target).val();
      const targetIds = drupalSettings.copyFieldValue[e.target.id];
      $(`#${targetIds.join(', #')}`).trigger('value:copy', value);
    },
  };
})(jQuery, Drupal, drupalSettings);
