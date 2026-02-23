<?php

declare(strict_types=1);

namespace Drupal\deprecation_test\Deprecation;

// phpcs:ignore Drupal.Semantics.FunctionTriggerError
@trigger_error(__NAMESPACE__ . '\FixtureDeprecatedClass is deprecated.', E_USER_DEPRECATED);

/**
 * Fixture for Drupal\FunctionalTests\Core\Container\ServiceDeprecationTest.
 *
 * This class is arbitrarily deprecated in order to test container service
 * deprecations.
 */
class FixtureDeprecatedClass {

  /**
   * Returns a known value.
   *
   * @return string
   *   A known return value.
   */
  public function testFunction() {
    return 'test';
  }

}
