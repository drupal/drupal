<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test how unit tests interact with deprecation errors in process isolation.
 */
#[Group('Test')]
#[IgnoreDeprecations]
#[PreserveGlobalState(FALSE)]
#[RunTestsInSeparateProcesses]
class PhpUnitBridgeIsolatedTest extends UnitTestCase {

  public function testDeprecatedClass(): void {
    $this->expectDeprecation('Drupal\deprecation_test\Deprecation\FixtureDeprecatedClass is deprecated.');
    $deprecated = new FixtureDeprecatedClass();
    $this->assertEquals('test', $deprecated->testFunction());
  }

}
