<?php
/**
 * @file
 * Contains \Drupal\Component\Assertion\Handle.
 *
 * For PHP 5 this contains \AssertionError as well.
 */

namespace {

if (!class_exists('AssertionError', FALSE)) {

  /**
   * Emulates PHP 7 AssertionError as closely as possible.
   *
   * We force this class to exist at the root namespace for PHP 5.
   * This class exists natively in PHP 7. Note that in PHP 7 it extends from
   * Error, not Exception, but that isn't possible for PHP 5 - all exceptions
   * must extend from exception.
   */
  class AssertionError extends Exception {

    /**
     * {@inheritdoc}
     */
    public function __construct($message = '', $code = 0, Exception $previous = NULL, $file = '', $line = 0) {
      parent::__construct($message, $code, $previous);
      // Preserve the filename and line number of the assertion failure.
      $this->file = $file;
      $this->line = $line;
    }

  }
}

}

namespace Drupal\Component\Assertion {

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

}
