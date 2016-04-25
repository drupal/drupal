<?php

/**
 * @file
 * Contains \Drupal\Tests\simpletest\Unit\AssertHelperTraitTest.
 */

namespace Drupal\Tests\simpletest\Unit;

use Drupal\Core\Render\Markup;
use Drupal\simpletest\AssertHelperTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\simpletest\AssertHelperTrait
 * @group simpletest
 */
class AssertHelperTraitTest extends UnitTestCase {

  /**
   * @covers ::castSafeStrings
   * @dataProvider providerCastSafeStrings
   */
  public function testCastSafeStrings($expected, $value) {
    $class = new AssertHelperTestClass();
    $this->assertSame($expected, $class->testMethod($value));
  }

  public function providerCastSafeStrings() {
    $safe_string = Markup::create('test safe string');
    return [
      ['test simple string', 'test simple string'],
      [['test simple array', 'test simple array'], ['test simple array', 'test simple array']],
      ['test safe string', $safe_string],
      [['test safe string', 'test safe string'], [$safe_string, $safe_string]],
      [['test safe string', 'mixed array', 'test safe string'], [$safe_string, 'mixed array', $safe_string]],
    ];
  }
}

class AssertHelperTestClass {
  use AssertHelperTrait;

  public function testMethod($value) {
    return $this->castSafeStrings($value);
  }
}
