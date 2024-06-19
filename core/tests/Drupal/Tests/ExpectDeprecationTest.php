<?php

declare(strict_types=1);

namespace Drupal\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * Ensures Drupal has test coverage of Symfony's deprecation testing.
 *
 * @group Test
 * @group legacy
 */
class ExpectDeprecationTest extends TestCase {
  use ExpectDeprecationTrait;

  /**
   * Tests expectDeprecation.
   */
  public function testExpectDeprecation(): void {
    $this->expectDeprecation('Test deprecation');
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * Tests expectDeprecation in isolated test.
   *
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testExpectDeprecationInIsolation(): void {
    $this->expectDeprecation('Test isolated deprecation');
    // phpcs:ignore Drupal.Semantics.FunctionTriggerError
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
  }

}
