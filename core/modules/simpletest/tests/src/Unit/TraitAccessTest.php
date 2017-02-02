<?php

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\simpletest\Traits\TestTrait;

/**
 * Test whether traits are autoloaded during PHPUnit discovery time.
 *
 * @group simpletest
 */
class TraitAccessTest extends UnitTestCase {

  use TestTrait;

  /**
   * @coversNothing
   */
  public function testSimpleStuff() {
    $stuff = $this->getStuff();
    $this->assertSame($stuff, 'stuff', "Same old stuff");
  }

}
