<?php

namespace Drupal\system\Tests;

/**
 * Test cases for JS Messages tests.
 */
class JsMessageTestCases {

  /**
   * Gets the test types.
   *
   * @return string[]
   *   The test types.
   */
  public static function getTypes() {
    return ['status', 'error', 'warning'];
  }

  /**
   * Gets the test messages selectors.
   *
   * @return string[]
   *   The test test messages selectors.
   *
   * @see core/modules/system/tests/themes/test_messages/templates/status-messages.html.twig
   */
  public static function getMessagesSelectors() {
    return ['', '[data-drupal-messages-other]'];
  }

}
