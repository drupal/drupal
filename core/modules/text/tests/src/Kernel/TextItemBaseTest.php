<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * Tests TextItemBase.
 *
 * @coversDefaultClass \Drupal\text\Plugin\Field\FieldType\TextItemBase
 * @group text
 */
class TextItemBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'text', 'entity_test', 'field', 'user'];

  /**
   * Tests creation of sample values.
   *
   * @covers ::generateSampleValue
   * @dataProvider providerTextFieldSampleValue
   */
  public function testTextFieldSampleValue($max_length): void {
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
  public static function providerTextFieldSampleValue() {
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

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $format = FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
    ]);
    $format->save();
    $fieldName = $this->randomMachineName();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'type' => 'text',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'allowed_formats' => [$format->id()],
      ],
    ]);
    $field->save();

    $field->calculateDependencies();
    $this->assertEquals([
      'module' => [
        'entity_test',
        'text',
      ],
      'config' => [
        "field.storage.entity_test.$fieldName",
        'filter.format.test_format',
      ],
    ], $field->getDependencies());
  }

}
