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

  public static $modules = ['deprecation_test'];

  /**
   * @expectedDeprecation Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.
   */
  public function testDeprecatedClass() {
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  /**
   * @expectedDeprecation This is the deprecation message for deprecation_test_function().
   */
  public function testDeprecatedFunction() {
    $this->assertEquals('known_return_value', \deprecation_test_function());
  }

}
