/**
 * @file
 *  Testing behavior for JSWebAssertTest.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Makes changes in the DOM to be able to test the completion of AJAX in assertWaitOnAjaxRequest.
   */
  Drupal.behaviors.js_webassert_test_wait_for_element = {
    attach(context) {
      $('#js_webassert_test_element_invisible').show();
    },
  };
})(jQuery, Drupal, drupalSettings);
