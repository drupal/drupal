/**
 * @file
 * Provides backwards compatibility layer for Ajax-related markup.
 */

((Drupal) => {
  /**
   * Override the default ajaxProgressBar for backwards compatibility.
   *
   * @param {jQuery} $element
   *   Progress bar element.
   * @return {string}
   *   The HTML markup for the progress bar.
   */
  Drupal.theme.ajaxProgressBar = ($element) =>
    $element.addClass('ajax-progress ajax-progress-bar');
})(Drupal);
