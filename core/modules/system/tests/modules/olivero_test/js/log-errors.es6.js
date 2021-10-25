/**
 * @file
 * Log all errors.
 */

Drupal.errorLog = [];

window.addEventListener('error', (e) => {
  Drupal.errorLog.push(e);
});
