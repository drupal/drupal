/**
 * @file
 * This file allows calls to `once()` and `once.remove()` to also populate the
 * jQuery.once registry.
 *
 * It allows contributed code still using jQuery.once to behave as expected:
 * @example
 * once('core-once-call', 'body');
 *
 * // The following will work in a contrib module still using jQuery.once:
 * $('body').once('core-once-call'); // => returns empty object
 */

(($, once) => {
  // We'll replace the whole library so keep a version in cache for later.
  const drupalOnce = once;

  // When calling once(), also populate jQuery.once registry.
  function augmentedOnce(id, selector, context) {
    $(selector, context).once(id);
    return drupalOnce(id, selector, context);
  }

  // When calling once.remove(), also remove it from jQuery.once registry.
  function remove(id, selector, context) {
    $(selector, context).removeOnce(id);
    return drupalOnce.remove(id, selector, context);
  }

  // Expose the rest of @drupal/once API and replace @drupal/once library with
  // the version augmented with jQuery.once calls.
  window.once = Object.assign(augmentedOnce, drupalOnce, { remove });
})(jQuery, once);
