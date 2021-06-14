<?php

namespace Drupal\Component\Assertion;

/**
 * Handler for runtime assertion failures.
 *
 * @ingroup php_assert
 *
 * @todo Deprecate this class. https://www.drupal.org/node/3054072
 */
class Handle {

  /**
   * Ensures exceptions are thrown when an assertion fails.
   */
  public static function register() {
    // Since we're using exceptions, turn error warnings off.
    assert_options(ASSERT_WARNING, FALSE);

    // Turn exception throwing on.
    assert_options(ASSERT_EXCEPTION, TRUE);
  }

}
