<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Test how unit tests interact with deprecation errors.
 */
#[Group('Test')]
#[IgnoreDeprecations]
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
