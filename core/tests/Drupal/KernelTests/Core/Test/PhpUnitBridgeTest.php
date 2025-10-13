<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test how kernel tests interact with deprecation errors.
 */
#[Group('Test')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class PhpUnitBridgeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deprecation_test'];

  /**
   * Tests class deprecation.
   */
  public function testDeprecatedClass(): void {
    $this->expectDeprecation('Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.');
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

  /**
   * Tests function deprecation.
   */
  public function testDeprecatedFunction(): void {
    $this->expectDeprecation('This is the deprecation message for deprecation_test_function().');
    $this->assertEquals('known_return_value', \deprecation_test_function());
  }

}
