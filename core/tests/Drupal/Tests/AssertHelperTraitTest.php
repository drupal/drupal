<?php

namespace Drupal\Tests;

use Drupal\Core\Render\Markup;

/**
 * @coversDefaultClass \Drupal\Tests\AssertHelperTrait
 * @group simpletest
 * @group Tests
 * @group legacy
 */
class AssertHelperTraitTest extends UnitTestCase {

  /**
   * @covers ::castSafeStrings
   * @dataProvider providerCastSafeStrings
   */
  public function testCastSafeStrings($expected, $value) {
    $this->expectDeprecation('AssertHelperTrait::castSafeStrings() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. There is no replacement; assertEquals() will automatically cast MarkupInterface to strings when needed. See https://www.drupal.org/node/3123638');
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
