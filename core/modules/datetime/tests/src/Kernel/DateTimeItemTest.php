<?php

namespace Drupal\Tests\datetime\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\AssertionFailedError;

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
  protected static $modules = ['datetime'];

  protected function setUp(): void {
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
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_datetime);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_datetime[0]);
    $this->assertEqual($value, $entity->field_datetime->value);
    $this->assertEqual($value, $entity->field_datetime[0]->value);
    $this->assertEqual(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime[0]->getProperties()['value']->getDateTime()->getTimeZone()->getName());
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

    // Verify changing the date value.
    $new_value = '2016-11-04T00:21:00';
    $entity->field_datetime->value = $new_value;
    $this->assertEqual($new_value, $entity->field_datetime->value);
    $this->assertEqual(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime[0]->getProperties()['value']->getDateTime()->getTimeZone()->getName());
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEqual($new_value, $entity->field_datetime->value);
    $this->assertEqual(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime[0]->getProperties()['value']->getDateTime()->getTimeZone()->getName());
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

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
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_datetime);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_datetime[0]);
    $this->assertEqual($value, $entity->field_datetime->value);
    $this->assertEqual($value, $entity->field_datetime[0]->value);
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));
    $entity->field_datetime->date->setDefaultDateTime();
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));

    // Verify changing the date value.
    $new_value = '2016-11-04';
    $entity->field_datetime->value = $new_value;
    $this->assertEqual($new_value, $entity->field_datetime->value);
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));
    $entity->field_datetime->date->setDefaultDateTime();
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEqual($new_value, $entity->field_datetime->value);
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));
    $entity->field_datetime->date->setDefaultDateTime();
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->field_datetime->generateSampleItems();
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));
    $entity->field_datetime->date->setDefaultDateTime();
    $this->assertEquals('12:00:00', $entity->field_datetime->date->format('H:i:s'));
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
    $this->assertEqual($value, $entity->field_datetime[0]->value, 'DateTimeItem::setValue() works with string value.');
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

    // Test DateTimeItem::setValue() using property array.
    $entity = EntityTest::create();
    $value = '2014-01-01T20:00:00';
    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($value, $entity->field_datetime[0]->value, 'DateTimeItem::setValue() works with array value.');
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

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
    $this->assertEqual($value, $entity->field_datetime[0]->value, 'DateTimeItem::setValue() works with string value.');
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

    // Test DateTimeItem::setValue() using property array.
    $entity = EntityTest::create();
    $value = '2014-01-01';
    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
    // Load the entity and ensure the field was saved correctly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEqual($value, $entity->field_datetime[0]->value, 'DateTimeItem::setValue() works with array value.');
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());
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
    $this->assertEqual($value, $entity->field_datetime[0]->value, '"Value" property can be set directly.');
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());

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
    $this->assertEqual($value, $entity->field_datetime[0]->value, '"Value" property can be set directly.');
    $this->assertEquals(DateTimeItemInterface::STORAGE_TIMEZONE, $entity->field_datetime->date->getTimeZone()->getName());
  }

  /**
   * Tests the constraint validations for fields with date and time.
   *
   * @dataProvider datetimeValidationProvider
   */
  public function testDatetimeValidation($value) {
    $this->expectException(AssertionFailedError::class);

    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATETIME);
    $this->fieldStorage->save();
    $entity = EntityTest::create();

    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
  }

  /**
   * Provider for testDatetimeValidation().
   */
  public function datetimeValidationProvider() {
    return [
      // Valid ISO 8601 dates, but unsupported by DateTimeItem.
      ['2014-01-01T20:00:00Z'],
      ['2014-01-01T20:00:00+04:00'],
      ['2014-01-01T20:00:00+0400'],
      ['2014-01-01T20:00:00+04'],
      ['2014-01-01T20:00:00.123'],
      ['2014-01-01T200000'],
      ['2014-01-01T2000'],
      ['2014-01-01T20'],
      ['20140101T20:00:00'],
      ['2014-01T20:00:00'],
      ['2014-001T20:00:00'],
      ['2014001T20:00:00'],
      // Valid date strings, but unsupported by DateTimeItem.
      ['2016-11-03 20:52:00'],
      ['Thu, 03 Nov 2014 20:52:00 -0400'],
      ['Thursday, November 3, 2016 - 20:52'],
      ['Thu, 11/03/2016 - 20:52'],
      ['11/03/2016 - 20:52'],
      // Invalid date strings.
      ['YYYY-01-01T20:00:00'],
      ['2014-MM-01T20:00:00'],
      ['2014-01-DDT20:00:00'],
      ['2014-01-01Thh:00:00'],
      ['2014-01-01T20:mm:00'],
      ['2014-01-01T20:00:ss'],
      // Invalid dates.
      ['2014-13-13T20:00:00'],
      ['2014-01-55T20:00:00'],
      ['2014-01-01T25:00:00'],
      ['2014-01-01T00:70:00'],
      ['2014-01-01T00:00:70'],
      // Proper format for different field setting.
      ['2014-01-01'],
      // Wrong input type.
      [['2014', '01', '01', '00', '00', '00']],
    ];
  }

  /**
   * Tests the constraint validations for fields with date only.
   *
   * @dataProvider dateonlyValidationProvider
   */
  public function testDateonlyValidation($value) {
    $this->expectException(AssertionFailedError::class);

    $this->fieldStorage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
    $this->fieldStorage->save();
    $entity = EntityTest::create();

    $entity->set('field_datetime', $value);
    $this->entityValidateAndSave($entity);
  }

  /**
   * Provider for testDatetimeValidation().
   */
  public function dateonlyValidationProvider() {
    return [
      // Valid date strings, but unsupported by DateTimeItem.
      ['Thu, 03 Nov 2014'],
      ['Thursday, November 3, 2016'],
      ['Thu, 11/03/2016'],
      ['11/03/2016'],
      // Invalid date strings.
      ['YYYY-01-01'],
      ['2014-MM-01'],
      ['2014-01-DD'],
      // Invalid dates.
      ['2014-13-01'],
      ['2014-01-55'],
      // Proper format for different field setting.
      ['2014-01-01T20:00:00'],
      // Wrong input type.
      [['2014', '01', '01']],
    ];
  }

}
