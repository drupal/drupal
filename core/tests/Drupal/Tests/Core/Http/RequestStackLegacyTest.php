<?php

namespace Drupal\Tests\Core\Http;

use Drupal\Core\Http\RequestStack;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Http\RequestStack
 * @group legacy
 */
class RequestStackLegacyTest extends UnitTestCase {

  /**
   * Tests deprecation message in our subclassed RequestStack.
   *
   * @covers ::getMasterRequest
   */
  public function testGetMasterRequestDeprecation() {
    $stack = new RequestStack();

    $this->expectDeprecation('Drupal\Core\Http\RequestStack::getMasterRequest() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use getMainRequest() instead. See https://www.drupal.org/node/3253744');
    $this->assertNull($stack->getMasterRequest());
  }

}
