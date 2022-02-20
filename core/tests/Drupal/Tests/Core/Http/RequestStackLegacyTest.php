<?php

namespace Drupal\Tests\Core\Http;

use Drupal\Core\Http\RequestStack;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Http\RequestStack
 * @group legacy
 *
 * @todo Remove this in Drupal 11 https://www.drupal.org/node/3265121
 */
class RequestStackLegacyTest extends UnitTestCase {

  /**
   * Tests deprecation message in our subclassed RequestStack.
   *
   * @covers ::getMainRequest
   */
  public function testGetMainRequestDeprecation() {
    $stack = new RequestStack();

    $this->expectDeprecation('The Drupal\Core\Http\RequestStack is deprecated in drupal:10.0.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3265357');
    $this->assertNull($stack->getMainRequest());
  }

}
