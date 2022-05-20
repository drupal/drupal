<?php

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
  public function testExpectDeprecation() {
    $this->expectDeprecation('Test deprecation');
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * Tests expectDeprecation in isolated test.
   *
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testExpectDeprecationInIsolation() {
    $this->expectDeprecation('Test isolated deprecation');
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
  }

}
