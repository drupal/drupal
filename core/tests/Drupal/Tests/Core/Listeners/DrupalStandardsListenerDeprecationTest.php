<?php

namespace Drupal\Tests\Core\Listeners;

use Drupal\Tests\UnitTestCase;

/**
 * Test deprecation error handling by DrupalStandardsListener.
 *
 * DrupalStandardsListener has a dependency on composer/composer, so we can't
 * test it directly. However, we can create a test which is annotated as
 * covering a deprecated class. This way we can know whether the standards
 * listener process ignores deprecation errors.
 *
 * Note that this test is annotated as covering
 * \Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass::testFunction(),
 * but the reason the test exists is to cover
 * \Drupal\Tests\Listeners\DrupalStandardsListener::endTest(). We never
 * actually instantiate
 * \Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass because that
 * would trigger another deprecation error.
 *
 * @group Listeners
 *
 * @coversDefaultClass \Drupal\deprecation_test\Deprecation\DrupalStandardsListenerDeprecatedClass
 */
class DrupalStandardsListenerDeprecationTest extends UnitTestCase {

  /**
   * Exercise DrupalStandardsListener's coverage validation.
   *
   * @covers ::testFunction
   */
  public function testDeprecation() {
    // Meaningless assertion so this test is not risky.
    $this->assertTrue(TRUE);
  }

}
