<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Access\AccessResultForbidden.
 */
#[CoversClass(AccessResultForbidden::class)]
#[Group('Access')]
class AccessResultForbiddenTest extends UnitTestCase {

  /**
   * Tests the construction of an AccessResultForbidden object.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::getReason
   */
  public function testConstruction(): void {

    $a = new AccessResultForbidden();
    $this->assertEquals(NULL, $a->getReason());

    $reason = $this->getRandomGenerator()->string();
    $b = new AccessResultForbidden($reason);
    $this->assertEquals($reason, $b->getReason());
  }

  /**
   * Tests setReason()
   *
   * @legacy-covers ::setReason
   */
  public function testSetReason(): void {
    $a = new AccessResultForbidden();

    $reason = $this->getRandomGenerator()->string();
    $return = $a->setReason($reason);

    $this->assertSame($reason, $a->getReason());
    $this->assertSame($a, $return);
  }

}
