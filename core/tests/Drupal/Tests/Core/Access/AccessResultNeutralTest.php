<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Access\AccessResultNeutral.
 */
#[CoversClass(AccessResultNeutral::class)]
#[Group('Access')]
class AccessResultNeutralTest extends UnitTestCase {

  /**
   * Tests the construction of an AccessResultForbidden object.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::getReason
   */
  public function testConstruction(): void {
    $a = new AccessResultNeutral();
    $this->assertEquals('', $a->getReason());

    $reason = $this->getRandomGenerator()->string();
    $b = new AccessResultNeutral($reason);
    $this->assertEquals($reason, $b->getReason());
  }

  /**
   * Tests setReason()
   *
   * @legacy-covers ::setReason
   */
  public function testSetReason(): void {
    $a = new AccessResultNeutral();

    $reason = $this->getRandomGenerator()->string();
    $return = $a->setReason($reason);

    $this->assertSame($reason, $a->getReason());
    $this->assertSame($a, $return);
  }

}
