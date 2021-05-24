<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\FieldInputValueNormalizerTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Field\FieldInputValueNormalizerTrait
 * @group Field
 */
class FieldInputValueNormalizerTraitTest extends UnitTestCase {

  use FieldInputValueNormalizerTrait;

  /**
   * @dataProvider keyValueByDeltaTestCases
   * @covers ::normalizeValue
   */
  public function testKeyValueByDelta($input_value, $expected_value, $main_property_name = 'value') {
    $this->assertEquals($expected_value, $this->normalizeValue($input_value, $main_property_name));
  }

  /**
   * Provides test cases for ::testKeyValueByDelta.
   */
  public function keyValueByDeltaTestCases() {
    return [
      'Integer' => [
        1,
        [['value' => 1]],
      ],
      'Falsey integer' => [
        0,
        [['value' => 0]],
      ],
      'String' => [
        'foo',
        [['value' => 'foo']],
      ],
      'Empty string' => [
        '',
        [['value' => '']],
      ],
      'Null' => [
        NULL,
        [],
      ],
      'Empty field value' => [
        [],
        [],
      ],
      'Single delta' => [
        ['value' => 'foo'],
        [['value' => 'foo']],
      ],
      'Keyed delta' => [
        [['value' => 'foo']],
        [['value' => 'foo']],
      ],
      'Multiple keyed deltas' => [
        [['value' => 'foo'], ['value' => 'bar']],
        [['value' => 'foo'], ['value' => 'bar']],
      ],
      'No main property with keyed delta' => [
        [['foo' => 'bar']],
        [['foo' => 'bar']],
        NULL,
      ],
      'No main property with single delta' => [
        ['foo' => 'bar'],
        [['foo' => 'bar']],
        NULL,
      ],
      'No main property with empty array' => [
        [],
        [],
        NULL,
      ],
    ];
  }

  /**
   * @covers ::normalizeValue
   */
  public function testScalarWithNoMainProperty() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('A main property is required when normalizing scalar field values.');
    $value = 'foo';
    $this->normalizeValue($value, NULL);
  }

  /**
   * @covers ::normalizeValue
   */
  public function testKeyValueByDeltaUndefinedVariables() {
    $this->assertEquals([], $this->normalizeValue($undefined_variable, 'value'));
    $this->assertEquals([], $this->normalizeValue($undefined_variable['undefined_key'], 'value'));
  }

}
