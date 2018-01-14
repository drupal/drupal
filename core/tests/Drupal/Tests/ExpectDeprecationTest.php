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

}
