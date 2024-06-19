<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Template;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\AttributeHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Template\AttributeHelper
 * @group Template
 */
class AttributeHelperTest extends UnitTestCase {

  /**
   * Provides tests data for testAttributeExists.
   *
   * @return array
   *   An array of test data each containing an array of attributes, the name
   *   of the attribute to check existence of, and the expected result.
   */
  public static function providerTestAttributeExists() {
    return [
      [['class' => ['example-class']], 'class', TRUE],
      [[], 'class', FALSE],
      [['class' => ['example-class']], 'id', FALSE],
      [['class' => ['example-class'], 'id' => 'foo'], 'id', TRUE],
      [['id' => 'foo'], 'class', FALSE],
    ];
  }

  /**
   * @covers ::attributeExists
   * @dataProvider providerTestAttributeExists
   */
  public function testAttributeExists(array $test_data, $test_attribute, $expected): void {
    $this->assertSame($expected, AttributeHelper::attributeExists($test_attribute, $test_data));
    $attributes = new Attribute($test_data);
    $this->assertSame($expected, AttributeHelper::attributeExists($test_attribute, $attributes));
  }

  /**
   * Provides tests data for testMergeCollections.
   *
   * @return array
   *   An array of test data each containing an initial attribute collection, an
   *   Attribute object or array to be merged, and the expected result.
   */
  public static function providerTestMergeCollections() {
    return [
      [[], ['class' => ['class1']], ['class' => ['class1']]],
      [[], new Attribute(['class' => ['class1']]), ['class' => ['class1']]],
      [['class' => ['example-class']], ['class' => ['class1']], ['class' => ['example-class', 'class1']]],
      [['class' => ['example-class']], new Attribute(['class' => ['class1']]), ['class' => ['example-class', 'class1']]],
      [['class' => ['example-class']], ['id' => 'foo', 'href' => 'bar'], ['class' => ['example-class'], 'id' => 'foo', 'href' => 'bar']],
      [['class' => ['example-class']], new Attribute(['id' => 'foo', 'href' => 'bar']), ['class' => ['example-class'], 'id' => 'foo', 'href' => 'bar']],
    ];
  }

  /**
   * @covers ::mergeCollections
   * @dataProvider providerTestMergeCollections
   */
  public function testMergeCollections($original, $merge, $expected): void {
    $this->assertEquals($expected, AttributeHelper::mergeCollections($original, $merge));
    $this->assertEquals(new Attribute($expected), AttributeHelper::mergeCollections(new Attribute($original), $merge));
  }

  /**
   * @covers ::mergeCollections
   */
  public function testMergeCollectionsArgumentException(): void {
    $attributes = new Attribute(['class' => ['example-class']]);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid collection argument');
    AttributeHelper::mergeCollections($attributes, 'not an array');
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid collection argument');
    AttributeHelper::mergeCollections('not an array', $attributes);
  }

}
