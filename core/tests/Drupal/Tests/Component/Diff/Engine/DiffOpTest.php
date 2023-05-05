<?php

namespace Drupal\Tests\Component\Diff\Engine;

use Drupal\Component\Diff\Engine\DiffOp;
use Drupal\Tests\Traits\PhpUnitWarnings;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

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
 * @group legacy
 */
class DiffOpTest extends TestCase {

  use ExpectDeprecationTrait;
  use PhpUnitWarnings;

  /**
   * DiffOp::reverse() always throws an error.
   *
   * @covers ::reverse
   */
  public function testReverse() {
    $this->expectDeprecation('Drupal\Component\Diff\Engine\DiffOp::reverse() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942');
    $this->expectError();
    $op = new DiffOp();
    $result = $op->reverse();
  }

}
