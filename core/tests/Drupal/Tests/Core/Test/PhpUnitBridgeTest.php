<?php

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;

/**
 * Test how unit tests interact with deprecation errors.
 *
 * @group Test
 * @group legacy
 */
class PhpUnitBridgeTest extends UnitTestCase {

  /**
   * @expectedDeprecation Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.
   */
  public function testDeprecatedClass() {
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  public function testDeprecatedFunction() {
    $this->markTestIncomplete('Modules are not loaded for unit tests, so deprecated_test_function() will not be available.');
    $this->assertEquals('known_return_value', \deprecation_test_function());
  }

}
