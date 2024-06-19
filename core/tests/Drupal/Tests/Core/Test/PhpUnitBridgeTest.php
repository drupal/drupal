<?php

declare(strict_types=1);

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
  public function testDeprecatedClass(): void {
    $this->expectDeprecation('Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.');
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  public function testDeprecatedFunction(): void {
    $this->markTestIncomplete('Modules are not loaded for unit tests, so deprecated_test_function() will not be available.');
    $this->assertEquals('known_return_value', \deprecation_test_function());
  }

}
