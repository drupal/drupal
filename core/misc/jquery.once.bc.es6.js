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
  const deprecatedMessageSuffix = `is deprecated in Drupal 9.3.0 and will be removed in Drupal 10.0.0. Use the core/once library instead. See https://www.drupal.org/node/3158256`;

  // Trigger a deprecation error when using jQuery.once methods.
  const originalJQOnce = $.fn.once;
  const originalJQRemoveOnce = $.fn.removeOnce;
  // Do not deprecate findOnce because it is used internally by jQuery.once().

  $.fn.once = function jQueryOnce(id) {
    Drupal.deprecationError({
      message: `jQuery.once() ${deprecatedMessageSuffix}`,
    });
    return originalJQOnce.apply(this, [id]);
  };
  $.fn.removeOnce = function jQueryRemoveOnce(id) {
    Drupal.deprecationError({
      message: `jQuery.removeOnce() ${deprecatedMessageSuffix}`,
    });
    return originalJQRemoveOnce.apply(this, [id]);
  };

  // We'll replace the whole library so keep a version in cache for later.
  const drupalOnce = once;

  // When calling once(), also populate jQuery.once registry.
  function augmentedOnce(id, selector, context) {
    // Do not trigger deprecation warnings for the BC layer calls.
    originalJQOnce.apply($(selector, context), [id]);
    return drupalOnce(id, selector, context);
  }

  // When calling once.remove(), also remove it from jQuery.once registry.
  function remove(id, selector, context) {
    // Do not trigger deprecation warnings for the BC layer calls.
    originalJQRemoveOnce.apply($(selector, context), [id]);
    return drupalOnce.remove(id, selector, context);
  }

  // Expose the rest of @drupal/once API and replace @drupal/once library with
  // the version augmented with jQuery.once calls.
  window.once = Object.assign(augmentedOnce, drupalOnce, { remove });
})(jQuery, once);
