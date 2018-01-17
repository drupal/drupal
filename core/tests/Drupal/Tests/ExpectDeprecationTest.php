<?php

namespace Drupal\Tests;

use Drupal\Tests\Traits\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\ExpectDeprecationTrait
 *
 * @group Test
 * @group legacy
 */
class ExpectDeprecationTest extends UnitTestCase {
  use ExpectDeprecationTrait;

  /**
   * @covers ::expectDeprecation
   */
  public function testExpectDeprecation() {
    $this->expectDeprecation('Test deprecation');
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * @covers ::expectDeprecation
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testExpectDeprecationInIsolation() {
    $this->expectDeprecation('Test isolated deprecation');
    $this->expectDeprecation('Test isolated deprecation2');
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
    @trigger_error('Test isolated deprecation2', E_USER_DEPRECATED);
  }

}
