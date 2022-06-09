/**
 * @file
 * This file overrides the way jQuery UI focus trap works.
 *
 * When a focus event is fired while a CKEditor 5 instance is focused, do not
 * trap the focus and let CKEditor 5 manage that focus.
 */

(($) => {
  // Get core version of the _focusTabbable method.
  const oldFocusTabbable = $.ui.dialog._proto._focusTabbable;

  $.widget('ui.dialog', $.ui.dialog, {
    // Override core override of jQuery UI's `_focusTabbable()` so that
    // CKEditor 5 in modals can work as expected.
    _focusTabbable() {
      // When the focused element is a CKEditor 5 instance, disable jQuery UI
      // focus trap and delegate focus trap to CKEditor 5.
      const hasFocus = this._focusedElement
        ? this._focusedElement.get(0)
        : null;
      // In case the element is a CKEditor 5 instance, do not change focus
      // management.
      if (!(hasFocus && hasFocus.ckeditorInstance)) {
        oldFocusTabbable.call(this);
      }
    },
  });
})(jQuery);
