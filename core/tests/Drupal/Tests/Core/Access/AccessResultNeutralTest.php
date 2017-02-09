<?php

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Access\AccessResultNeutral
 * @group Access
 */
class AccessResultNeutralTest extends UnitTestCase {

  /**
   * Tests the construction of an AccessResultForbidden object.
   *
   * @covers ::__construct
   * @covers ::getReason
   */
  public function testConstruction() {
    $a = new AccessResultNeutral();
    $this->assertNull($a->getReason());

    $reason = $this->getRandomGenerator()->string();
    $b = new AccessResultNeutral($reason);
    $this->assertEquals($reason, $b->getReason());
  }

  /**
   * Test setReason()
   *
   * @covers ::setReason
   */
  public function testSetReason() {
    $a = new AccessResultNeutral();

    $reason = $this->getRandomGenerator()->string();
    $return = $a->setReason($reason);

    $this->assertSame($reason, $a->getReason());
    $this->assertSame($a, $return);
  }

}
