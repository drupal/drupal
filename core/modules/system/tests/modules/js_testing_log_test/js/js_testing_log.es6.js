/**
 * @file
 *  Support code for testing JavaScript error handling in functional tests.
 */
(function (Drupal) {
  if (typeof console !== 'undefined' && console.warn) {
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

    const originalThrowFunction = Drupal.throwError;
    Drupal.throwError = (error) => {
      const errors = JSON.parse(
        sessionStorage.getItem('js_testing_log_test.errors') ||
          JSON.stringify([]),
      );
      errors.push(error.stack);
      sessionStorage.setItem(
        'js_testing_log_test.errors',
        JSON.stringify(errors),
      );
      originalThrowFunction(error);
    };
  }
})(Drupal);
