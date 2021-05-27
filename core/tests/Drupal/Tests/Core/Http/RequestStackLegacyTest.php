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

    $this->expectDeprecation('Drupal\Core\Http\RequestStack::getMasterRequest() is deprecated, use getMainRequest() instead.');
    $this->assertNull($stack->getMasterRequest());
  }

}
