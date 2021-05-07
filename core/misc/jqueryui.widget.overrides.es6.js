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
        runDefaultWidget = false;
        if (args[1] === $.ui.autocomplete) {
          // eslint-disable-next-line no-unused-vars
          const widgetOverrides = args[2];
          if (args[0] === 'ui.autocomplete') {
            // @todo extend all instances of the ui.autocomplete widget.
          } else {
            // @todo create a new custom widget.
            // eslint-disable-next-line no-unused-vars
            const widgetName = args[0].split('.').slice(-1).pop();
          }
        }
      }

      // Run jQuery UI's default widget().
      if (runDefaultWidget) {
        return oldWidget(...args);
      }
    },
  });
})(jQuery);
