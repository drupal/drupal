<?php

namespace Drupal\Tests\datetime\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('datetime');

  protected function setUp() {
    parent::setUp();

    // Create a field with settings to validate.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_datetime',
      'type' => 'datetime',
      'entity_type' => 'entity_test',
      'settings' => array('datetime_type' => 'date'),
    ));
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'settings' => array(
        'default_value' => 'blank',
      ),
    ]);
    $field->save();
  }

  /**
   * Tests using entity fields of the date field type.
   */
  public function testDateTimeItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00Z';
    $entity->field_datetime = $value;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertTrue($entity->field_datetime instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_datetime[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_datetime->value, $value);
    $this->assertEqual($entity->field_datetime[0]->value, $value);

    // Verify changing the date value.
    $new_value = $this->randomMachineName();
    $entity->field_datetime->value = $new_value;
    $this->assertEqual($entity->field_datetime->value, $new_value);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = entity_load('entity_test', $id);
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
    // Test DateTimeItem::setValue() using string.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00Z';
    $entity->get('field_datetime')->set(0, $value);
    $entity->save();
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, 'DateTimeItem::setValue() works with string value.');

    // Test DateTimeItem::setValue() using property array.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00Z';
    $entity->set('field_datetime', $value);
    $entity->save();
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, 'DateTimeItem::setValue() works with array value.');
  }

  /**
   * Tests setting the value of the DateTimeItem directly.
   */
  public function testSetValueProperty() {
    // Test Date::setValue().
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00Z';

    $entity->set('field_datetime', $value);
    $entity->save();
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id);
    $this->assertEqual($entity->field_datetime[0]->value, $value, '"Value" property can be set directly.');
  }

}
