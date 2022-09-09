/**
 * @file
 * Adds a jQuery polyfill for event.which.
 *
 * In jQuery 3.6, the event.which polyfill was removed, which is not needed for
 * any supported browsers, but is still necessary to trigger keyboard events in
 * FunctionalJavaScript tests.
 *
 * @todo: This polyfill may be removed if MinkSelenium2Driver updates its use of
 * syn.js, https://github.com/minkphp/MinkSelenium2Driver/pull/333
 *
 * @see https://github.com/jquery/jquery/issues/4755
 * @see https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/which
 */

(($) => {
  // This is the polyfill implementation from jQuery 3.5.1, modified only to
  // meet Drupal coding standards.
  jQuery.event.addProp('which', function (event) {
    const keyEventRegex = /^key/;
    const mouseEventRegex = /^(?:mouse|pointer|contextmenu|drag|drop)|click/;
    const button = event.button;

    // Add which for key events
    if (event.which == null && keyEventRegex.test(event.type)) {
      return event.charCode != null ? event.charCode : event.keyCode;
    }

    // Add which for click: 1 === left; 2 === middle; 3 === right
    if (
      !event.which &&
      button !== undefined &&
      mouseEventRegex.test(event.type)
    ) {
      if (button && 1) {
        return 1;
      }

      if (button && 2) {
        return 3;
      }

      if (button && 4) {
        return 2;
      }

      return 0;
    }

    return event.which;
  });
})(jQuery);
