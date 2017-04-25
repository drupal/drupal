<?php

namespace Drupal\Tests\field\Kernel\Timestamp;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the timestamp fields.
 *
 * @group field
 */
class TimestampItemTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_timestamp',
      'type' => 'timestamp',
      'entity_type' => 'entity_test',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $this->field->save();
  }

  /**
   * Tests using entity fields of the datetime field type.
   */
  public function testDateTime() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = 1488914208;
    $entity->field_timestamp = $value;
    $entity->name->value = $this->randomMachineName();
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_timestamp instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_timestamp[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEquals($entity->field_timestamp->value, $value);
    $this->assertEquals($entity->field_timestamp[0]->value, $value);

    // Verify changing the date value.
    $new_value = 1488914000;
    $entity->field_timestamp->value = $new_value;
    $this->assertEquals($entity->field_timestamp->value, $new_value);

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEquals($entity->field_timestamp->value, $new_value);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_timestamp->generateSampleItems();
    $this->entityValidateAndSave($entity);

    // Ensure there is sample value a generated for the field.
    $this->assertNotNull($entity->field_timestamp->value);
  }

}
