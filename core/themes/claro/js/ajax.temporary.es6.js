/**
 * @file
 * Provides theme function for Ajax-related markup.
 *
 * This file is a temporary addition to the theme to override
 * stable/drupal.ajax. This is to help surface potential issues that may arise
 * once the theme no longer depends on stable/drupal.ajax.
 * @todo delete this file in https://drupal.org/node/3111468
 */

(($, Drupal) => {
  /**
   * Provide a wrapper for the AJAX progress bar element.
   *
   * @param {jQuery} $element
   *   Progress bar element.
   * @return {string}
   *   The HTML markup for the progress bar.
   */
  Drupal.theme.ajaxProgressBar = $element =>
    $('<div class="ajax-progress ajax-progress-bar"></div>').append($element);
})(jQuery, Drupal);
