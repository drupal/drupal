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
   * Tests class-level deprecation.
   */
  public function testDeprecatedClass() {
    $this->expectDeprecation('Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.');
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  public function testDeprecatedFunction() {
    $this->markTestIncomplete('Modules are not loaded for unit tests, so deprecated_test_function() will not be available.');
    $this->assertEquals('known_return_value', \deprecation_test_function());
  }

  /**
   * Tests the @requires annotation in conjunction with DrupalListener.
   *
   * This test method will be skipped and should not cause the test suite to
   * fail.
   *
   * @requires extension will_hopefully_never_exist
   * @see \Drupal\Tests\Listeners\DrupalListener
   */
  public function testWillNeverRun(): void {
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

}
