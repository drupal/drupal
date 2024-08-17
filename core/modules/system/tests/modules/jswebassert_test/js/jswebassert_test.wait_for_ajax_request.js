/**
 * @file
 *  Testing behavior for JSWebAssertTest.
 */

(function (Drupal, drupalSettings) {
  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Makes changes in the DOM to be able to test the completion of AJAX in assertWaitOnAjaxRequest.
   */
  Drupal.behaviors.jswebassert_test_wait_for_ajax_request = {
    attach(context) {
      const waitAjaxInput = document.querySelector(
        'input[name="test_assert_wait_on_ajax_input"]',
      );
      // Confirm the input exists before assigning a value to it.
      if (waitAjaxInput) {
        waitAjaxInput.value = 'jswebassert_test';
      }
    },
  };
})(Drupal, drupalSettings);
