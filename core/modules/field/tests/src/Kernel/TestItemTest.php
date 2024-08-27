<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the test field type.
 *
 * @group field
 */
class TestItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_test'];

  /**
   * The name of the field to use in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a 'test_field' field and storage for validation.
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the test field type.
   */
  public function testTestItem(): void {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = rand(1, 10);
    $entity->field_test = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->{$this->fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $entity->{$this->fieldName}[0]);
    $this->assertEquals($value, $entity->{$this->fieldName}->value);
    $this->assertEquals($value, $entity->{$this->fieldName}[0]->value);

    // Verify changing the field value.
    $new_value = rand(1, 10);
    $entity->field_test->value = $new_value;
    $this->assertEquals($new_value, $entity->{$this->fieldName}->value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($new_value, $entity->{$this->fieldName}->value);

    // Test the schema for this field type.
    $expected_schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'medium',
        ],
      ],
      'unique keys' => [],
      'indexes' => [
        'value' => ['value'],
      ],
      'foreign keys' => [],
    ];
    $field_schema = BaseFieldDefinition::create('test_field')->getSchema();
    $this->assertEquals($expected_schema, $field_schema);
  }

}
