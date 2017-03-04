<?php

namespace Drupal\Tests\datetime\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the date field type.
 *
 * @group datetime
 */
class DateTimeItemTest extends FieldKernelTestBase {

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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['datetime'];

  protected function setUp() {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_datetime',
      'type' => 'datetime',
      'entity_type' => 'entity_test',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        'default_value' => 'blank',
      ],
    ]);
    $this->field->save();
  }

  /**
   * Tests using entity fields of the datetime field type.
   */
  public function testDateTime() {
    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00';
    $entity->field_datetime = $value;
    $entity->name->value = $this->randomMachineName();
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_datetime instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_datetime[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_datetime->value, $value);
    $this->assertEqual($entity->field_datetime[0]->value, $value);

    // Verify changing the date value.
    $new_value = '2016-11-04T00:21:00';
    $entity->field_datetime->value = $new_value;
    $this->assertEqual($entity->field_datetime->value, $new_value);

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime->value, $new_value);

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->field_datetime->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests using entity fields of the date field type.
   */
  public function testDateOnly() {
    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
    $this->fieldStorage->save();

    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '2014-01-01';
    $entity->field_datetime = $value;
    $entity->name->value = $this->randomMachineName();
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_datetime instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_datetime[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_datetime->value, $value);
    $this->assertEqual($entity->field_datetime[0]->value, $value);

    // Verify changing the date value.
    $new_value = '2016-11-04';
    $entity->field_datetime->value = $new_value;
    $this->assertEqual($entity->field_datetime->value, $new_value);

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime->value, $new_value);

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->field_datetime->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests DateTimeItem::setValue().
   */
  public function testSetValue() {
    // Test a date+time field.
    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();

    // Test DateTimeItem::setValue() using string.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00';
    $entity->get('field_datetime')->set(0, $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, 'DateTimeItem::setValue() works with string value.');

    // Test DateTimeItem::setValue() using property array.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00';
    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, 'DateTimeItem::setValue() works with array value.');

    // Test a date-only field.
    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
    $this->fieldStorage->save();

    // Test DateTimeItem::setValue() using string.
    $entity = EntityTest::create();
    $value = '2014-01-01';
    $entity->get('field_datetime')->set(0, $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, 'DateTimeItem::setValue() works with string value.');

    // Test DateTimeItem::setValue() using property array.
    $entity = EntityTest::create();
    $value = '2014-01-01';
    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, 'DateTimeItem::setValue() works with array value.');
  }

  /**
   * Tests setting the value of the DateTimeItem directly.
   */
  public function testSetValueProperty() {
    // Test Date::setValue() with a date+time field.
    // Test a date+time field.
    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00';

    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, '"Value" property can be set directly.');

    // Test Date::setValue() with a date-only field.
    // Test a date+time field.
    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
    $this->fieldStorage->save();
    $entity = EntityTest::create();
    $value = '2014-01-01';

    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, '"Value" property can be set directly.');
  }

}
