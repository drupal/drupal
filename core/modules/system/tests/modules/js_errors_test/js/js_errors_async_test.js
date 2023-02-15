/**
 * @file
 *  Test script for JavaScript errors thrown in async context.
 */
(function () {
  window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      throw new Error('An error thrown in async context.');
    });
  });
})();
