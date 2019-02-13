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

}
