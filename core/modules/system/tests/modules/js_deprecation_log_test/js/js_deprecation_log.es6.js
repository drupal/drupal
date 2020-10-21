/**
 * @file
 *  Testing tools for deprecating JavaScript functions and class properties.
 */
(function () {
  if (typeof console !== 'undefined' && console.warn) {
    const originalWarnFunction = console.warn;
    console.warn = (warning) => {
      const warnings = JSON.parse(
        sessionStorage.getItem('js_deprecation_log_test.warnings') ||
          JSON.stringify([]),
      );
      warnings.push(warning);
      sessionStorage.setItem(
        'js_deprecation_log_test.warnings',
        JSON.stringify(warnings),
      );
      originalWarnFunction(warning);
    };
  }
})();
