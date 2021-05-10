/**
 * @file
 * Overrides the jQuery UI widget function.
 */
(($) => {
  // The jQuery UI `widget()` function will be overridden below, make a copy of
  // the original so it's available to use cases that don't require any of the
  // additional logic in the override.
  const oldWidget = $.widget;
  const oldWidgetExtend = $.widget.extend;
  const oldWidgetBridge = $.widget.bridge;

  // eslint-disable-next-line func-names
  $.widget = function (...args) {
    let runDefaultWidget = true;
    if ($.ui.hasOwnProperty('autocomplete')) {
      if (args[1] === $.ui.autocomplete && typeof args[2] === 'object') {
        runDefaultWidget = false;

        // eslint-disable-next-line no-unused-vars
        if (args[0] === 'ui.autocomplete') {
          // eslint-disable-next-line prefer-destructuring
          Drupal.autocompleteShim.overrides = args[2];
        } else {
          // This creates a new custom widget that "extends" autocomplete.

          // eslint-disable-next-line no-unused-vars
          const widgetName = args[0].split('.').slice(-1).pop();
          const { options } = args[2].hasOwnProperty('options')
            ? args[2]
            : { options: {} };
          delete args[2].options;

          // eslint-disable-next-line prefer-destructuring
          options.widgetOverrides = args[2];
          const toExtend = {};
          // eslint-disable-next-line func-names
          toExtend[widgetName] = function (...widgetArgs) {
            if (typeof widgetArgs[0] === 'object') {
              this.autocomplete({ ...options, ...widgetArgs[0] });
              return this;
            }
            return this.autocomplete(...widgetArgs);
          };
          $.fn.extend(toExtend);
        }
      }
    }

    // Run jQuery UI's default widget().
    if (runDefaultWidget) {
      return oldWidget(...args);
    }
  };

  // Bring back widget prototype functions that were removed due to $.widget
  // being overwritten.
  $.widget.extend = oldWidgetExtend;
  $.widget.bridge = oldWidgetBridge;
})(jQuery);
