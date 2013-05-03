<?php

/**
 * @file
 * Contains \Drupal\ajax_test\AjaxTestController.
 */

namespace Drupal\ajax_test;

/**
 * Provides content for dialog tests.
 */
class AjaxTestController {

  /**
   * Returns example content for dialog testing.
   */
  public function dialogContents() {
    // Re-use the utility method that returns the example content.
    drupal_set_title(t('AJAX Dialog contents'));
    return ajax_test_dialog_contents();
  }

}
