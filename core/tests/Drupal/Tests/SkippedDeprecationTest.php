<?php

namespace Drupal\Tests;

/**
 * @group Test
 */
class SkippedDeprecationTest extends UnitTestCase {

  /**
   * Tests skipping deprecations in unit tests.
   *
   * @see \Drupal\Tests\Listeners\DeprecationListenerTrait::getSkippedDeprecations()
   */
  public function testSkippingDeprecations() {
    @trigger_error('\Drupal\Tests\SkippedDeprecationTest deprecation', E_USER_DEPRECATED);
    $this->addToAssertionCount(1);
  }

  /**
   * Tests skipping deprecations in unit tests multiple times.
   *
   * @see \Drupal\Tests\Listeners\DeprecationListenerTrait::getSkippedDeprecations()
   */
  public function testSkippingDeprecationsAgain() {
    @trigger_error('\Drupal\Tests\SkippedDeprecationTest deprecation', E_USER_DEPRECATED);
    $this->addToAssertionCount(1);
  }

  /**
   * Tests skipping E_DEPRECATED deprecations in unit tests.
   *
   * @see \Drupal\Tests\Listeners\DeprecationListenerTrait::getSkippedDeprecations()
   */
  public function testSkippingPhpDeprecations() {
    include_once __DIR__ . '/../../fixtures/deprecated_code.php';
    $this->addToAssertionCount(1);
  }

}
