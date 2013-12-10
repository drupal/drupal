<?php

/**
 * @file
 * Contains \Drupal\ajax_test\Controller\AjaxTestController.
 */

namespace Drupal\ajax_test\Controller;

/**
 * Provides content for dialog tests.
 */
class AjaxTestController {

  /**
   * Returns example content for dialog testing.
   */
  public function dialogContents() {
    // Re-use the utility method that returns the example content.
    return ajax_test_dialog_contents();
  }

  /**
   * @todo Remove ajax_test_render().
   */
  public function render() {
    return ajax_test_render();
  }

  /**
   * @todo Remove ajax_test_order().
   */
  public function order() {
    return ajax_test_order();
  }

  /**
   * @todo Remove ajax_test_error().
   */
  public function renderError() {
    return ajax_test_error();
  }

  /**
   * @todo Remove ajax_test_dialog().
   */
  public function dialog() {
    return ajax_test_dialog();
  }

  /**
   * @todo Remove ajax_test_dialog_close().
   */
  public function dialogClose() {
    return ajax_test_dialog_close();
  }

}
