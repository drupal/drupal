<?php

declare(strict_types=1);

namespace Drupal\deprecation_test;

/**
 * Defines a controller that calls a deprecated method.
 */
class DeprecatedController {

  /**
   * Controller callback.
   *
   * @return array
   *   Render array.
   */
  public function deprecatedMethod() {
    return [
      '#markup' => static::testDeprecation(),
    ];
  }

  /**
   * A deprecated function.
   *
   * @return string
   *   A known return value of 'known_return_value'.
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. This is
   *   the deprecation message for testDeprecation().
   *
   * @see https://www.drupal.org/project/drupal/issues/2870194
   */
  public static function testDeprecation() {
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('This is the deprecation message for testDeprecation().', E_USER_DEPRECATED);
    return 'known_return_value';
  }

}
