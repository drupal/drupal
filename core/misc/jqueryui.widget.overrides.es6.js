/**
 * @file
 * Overrides the jQuery UI widget function.
 */
(($) => {
  // The jQuery UI `widget()` function will be overridden below, make a copy of
  // the original so it's available to use cases that don't require any of the
  // additional logic in the override.
  const oldWidget = $.widget;
  $.fn.extend({
    widget(...args) {
      let runDefaultWidget = true;
      if ($.ui.hasOwnProperty('autocomplete')) {
        if (args[1] === $.ui.autocomplete) {
          // @todo this is where the ability to extend the shimmed autocomplete
          //   widget needs to be.
        }
        runDefaultWidget = false;
      }

      // Run jQuery UI's default widget().
      if (runDefaultWidget) {
        return oldWidget(...args);
      }
    },
  });
})(jQuery);
