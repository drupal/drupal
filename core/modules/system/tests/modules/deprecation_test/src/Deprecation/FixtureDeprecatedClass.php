<?php

declare(strict_types=1);

namespace Drupal\deprecation_test\Deprecation;

// phpcs:ignore Drupal.Semantics.FunctionTriggerError
@trigger_error(__NAMESPACE__ . '\FixtureDeprecatedClass is deprecated.', E_USER_DEPRECATED);

/**
 * Fixture class for use by DrupalStandardsListenerDeprecationTest.
 *
 * This class is arbitrarily deprecated in order to test the deprecation error
 * handling properties of DrupalStandardsListener.
 *
 * @see \Drupal\Tests\Core\Listeners\DrupalStandardsListenerDeprecationTest
 * @see \Drupal\Tests\Listeners\DrupalStandardsListener::endTest()
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
