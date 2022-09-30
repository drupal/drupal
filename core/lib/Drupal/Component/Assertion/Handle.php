<?php

namespace Drupal\Component\Assertion;

trigger_error(__NAMESPACE__ . '\Handle is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Instead, use assert_options(ASSERT_EXCEPTION, TRUE). See https://drupal.org/node/3105918', E_USER_DEPRECATED);

/**
 * Handler for runtime assertion failures.
 *
 * @ingroup php_assert
 *
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
 *   assert_options(ASSERT_EXCEPTION, TRUE).
 *
 * @see https://www.drupal.org/node/3105918
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
