<?php

namespace Drupal\Tests\text\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * Tests TextItemBase.
 *
 * @group text
 */
class TextItemBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'text'];

  /**
   * Tests creation of sample values.
   *
   * @covers ::generateSampleValue
   * @dataProvider providerTextFieldSampleValue
   */
  public function testTextFieldSampleValue($max_length) {
    // Create a text field.
    $field_definition = BaseFieldDefinition::create('text')
      ->setTargetEntityTypeId('foo');

    // Ensure testing of max_lengths from 1 to 3 because generateSampleValue
    // creates a sentence with a maximum number of words set to 1/3 of the
    // max_length of the field.
    $field_definition->setSetting('max_length', $max_length);
    $sample_value = TextItemBase::generateSampleValue($field_definition);
    $this->assertEquals($max_length, strlen($sample_value['value']));
  }

  /**
   * Data provider for testTextFieldSampleValue.
   */
  public function providerTextFieldSampleValue() {
    return [
      [
        1,
      ],
      [
        2,
      ],
      [
        3,
      ],
      [
        4,
      ],
    ];
  }

}
