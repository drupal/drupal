<?php

namespace Drupal\KernelTests\Core\Test;

use Drupal\KernelTests\KernelTestBase;
use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;

/**
 * Test how kernel tests interact with deprecation errors.
 *
 * @group Test
 * @group legacy
 */
class PhpUnitBridgeTest extends KernelTestBase {

  protected static $modules = ['deprecation_test'];

  public function testDeprecatedClass() {
    $this->expectDeprecation('Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.');
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  public function testDeprecatedFunction() {
    $this->expectDeprecation('This is the deprecation message for deprecation_test_function().');
    $this->assertEquals('known_return_value', \deprecation_test_function());
  }

}
