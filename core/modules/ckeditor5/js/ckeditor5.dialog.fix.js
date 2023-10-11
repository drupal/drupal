/**
 * @file
 * This file overrides the way jQuery UI focus trap works.
 *
 * When a focus event is fired while a CKEditor 5 instance is focused, do not
 * trap the focus and let CKEditor 5 manage that focus.
 */

(($) => {
  $.widget('ui.dialog', $.ui.dialog, {
    // Override core override of jQuery UI's `_allowInteraction()` so that
    // CKEditor 5 in modals can work as expected.
    // @see https://api.jqueryui.com/dialog/#method-_allowInteraction
    _allowInteraction(event) {
      // Fixes "Uncaught TypeError: event.target.classList is undefined"
      // in Firefox (only).
      // @see https://www.drupal.org/project/drupal/issues/3351600
      if (event.target.classList === undefined) {
        return this._super(event);
      }
      return event.target.classList.contains('ck') || this._super(event);
    },
  });
})(jQuery);
