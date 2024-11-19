/**
 * @file
 *  Support code for testing JavaScript error handling in functional tests.
 */
(function () {
  if (console?.warn) {
    const originalWarnFunction = console.warn;
    console.warn = (warning) => {
      const warnings = JSON.parse(
        sessionStorage.getItem('js_testing_log_test.warnings') ||
          JSON.stringify([]),
      );
      warnings.push(warning);
      sessionStorage.setItem(
        'js_testing_log_test.warnings',
        JSON.stringify(warnings),
      );
      originalWarnFunction(warning);
    };
  }

  window.addEventListener('error', (evt) => {
    const errors = JSON.parse(
      sessionStorage.getItem('js_testing_log_test.errors') ||
        JSON.stringify([]),
    );
    errors.push(evt.error.stack);
    sessionStorage.setItem(
      'js_testing_log_test.errors',
      JSON.stringify(errors),
    );
  });
})();
