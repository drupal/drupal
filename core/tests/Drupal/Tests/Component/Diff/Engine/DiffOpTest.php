<?php

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\DiffOp;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Error\Error;

/**
 * Test DiffOp base class.
 *
 * The only significant behavior here is that ::reverse() should throw an error
 * if not overridden. In versions of this code in other projects, reverse() is
 * marked as abstract, which enforces some of this behavior.
 *
 * @coversDefaultClass \Drupal\Component\Diff\Engine\DiffOp
 *
 * @group Diff
 */
class DiffOpTest extends TestCase {

  /**
   * DiffOp::reverse() always throws an error.
   *
   * @covers ::reverse
   */
  public function testReverse() {
    $this->expectException(Error::class);
    $op = new DiffOp();
    $result = $op->reverse();
  }

}
