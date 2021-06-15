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
  const oldOnce = once;

  // Define a new once function that call both the @drupal/once library as well
  // as $.fn.once().
  const newOnce = (id, selector, context) => {
    $(selector, context).once(id);
    return oldOnce(id, selector, context);
  };

  // Replace the once.remove function with a function that calls
  // $.fn.removeOnce().
  newOnce.remove = (id, selector, context) => {
    $(selector, context).removeOnce(id);
    return oldOnce.remove(id, selector, context);
  };
  // Expose the rest of the once API.
  newOnce.filter = once.filter;
  newOnce.find = once.find;

  // Replace the once library with the version augmented with jQuery.once calls.
  window.once = newOnce;
})(jQuery, once);
