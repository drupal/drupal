/**
 * @file
 *  Testing tools for JavaScript errors.
 */
(function ({ throwError, behaviors }) {
  behaviors.testErrors = {
    attach: () => {
      throwError(new Error('A manually thrown error.'));
    },
  };
})(Drupal);
