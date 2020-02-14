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
   * @covers ::addExpectedDeprecationMessage
   */
  public function testExpectDeprecation() {
    $this->addExpectedDeprecationMessage('Test deprecation');
    @trigger_error('Test deprecation', E_USER_DEPRECATED);
  }

  /**
   * @covers ::addExpectedDeprecationMessage
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testExpectDeprecationInIsolation() {
    $this->addExpectedDeprecationMessage('Test isolated deprecation');
    $this->addExpectedDeprecationMessage('Test isolated deprecation2');
    @trigger_error('Test isolated deprecation', E_USER_DEPRECATED);
    @trigger_error('Test isolated deprecation2', E_USER_DEPRECATED);
  }

}
