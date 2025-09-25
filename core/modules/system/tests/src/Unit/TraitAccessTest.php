<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit;

use Drupal\Tests\system\Traits\TestTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test whether traits are autoloaded during PHPUnit discovery time.
 */
#[Group('system')]
#[Group('Test')]
class TraitAccessTest extends UnitTestCase {

  use TestTrait;

  /**
   * Tests \Drupal\Tests\system\Traits\TestTrait::getStuff().
   */
  public function testSimpleStuff(): void {
    $stuff = $this->getStuff();
    $this->assertSame($stuff, 'stuff', "Same old stuff");
  }

}
