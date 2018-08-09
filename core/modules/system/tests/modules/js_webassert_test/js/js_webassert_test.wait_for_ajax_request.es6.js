/**
 * @file
 *  Testing behavior for JSWebAssertTest.
 */

(function($, Drupal, drupalSettings) {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Makes changes in the DOM to be able to test the completion of AJAX in assertWaitOnAjaxRequest.
   */
  Drupal.behaviors.js_webassert_test_wait_for_ajax_request = {
    attach(context) {
      $('input[name="test_assert_wait_on_ajax_input"]').val(
        'js_webassert_test',
      );
    },
  };
})(jQuery, Drupal, drupalSettings);
