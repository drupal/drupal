<?php

namespace Drupal\Component\Assertion;

/**
 * Handler for runtime assertion failures.
 *
 * This class allows PHP 5.x to throw exceptions on runtime assertion fails
 * in the same manner as PHP 7, and sets the ASSERT_EXCEPTION flag to TRUE
 * for the PHP 7 runtime.
 *
 * @ingroup php_assert
 */
class Handle {

  /**
   * Registers uniform assertion handling.
   */
  public static function register() {
    // Since we're using exceptions, turn error warnings off.
    assert_options(ASSERT_WARNING, FALSE);

    if (version_compare(PHP_VERSION, '7.0.0-dev') < 0) {
      if (!class_exists('AssertionError', FALSE)) {
        require __DIR__ . '/global_namespace_php5.php';
      }
      // PHP 5 - create a handler to throw the exception directly.
      assert_options(ASSERT_CALLBACK, function($file = '', $line = 0, $code = '', $message = '') {
        if (empty($message)) {
          $message = $code;
        }
        throw new \AssertionError($message, 0, NULL, $file, $line);
      });
    }
    else {
      // PHP 7 - just turn exception throwing on.
      assert_options(ASSERT_EXCEPTION, TRUE);
    }
  }

}
