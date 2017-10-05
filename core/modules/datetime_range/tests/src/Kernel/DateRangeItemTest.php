<?php

namespace Drupal\Tests\datetime_range\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Test datetime range field type via API.
 *
 * @group datetime
 */
class DateRangeItemTest extends FieldKernelTestBase {

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
  public static $modules = [
    'datetime',
    'datetime_range',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add a datetime range field.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => Unicode::strtolower($this->randomMachineName()),
      'entity_type' => 'entity_test',
      'type' => 'daterange',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATE],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    $display_options = [
      'type' => 'daterange_default',
      'label' => 'hidden',
      'settings' => [
        'format_type' => 'fallback',
        'separator' => 'UNTRANSLATED',
      ],
    ];
    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldStorage->getName(), $display_options)
      ->save();
  }

  /**
   * Tests the field configured for date-only.
   */
  public function testDateOnly() {
    $this->fieldStorage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATE);
    $field_name = $this->fieldStorage->getName();
    // Create an entity.
    $entity = EntityTest::create([
      'name' => $this->randomString(),
      $field_name => [
        'value' => '2016-09-21',
        'end_value' => '2016-09-21',
      ],
    ]);

    // Dates are saved without a time value. When they are converted back into
    // a \Drupal\datetime\DateTimeComputed object they should all have the same
    // time.
    $start_date = $entity->{$field_name}->start_date;
    sleep(1);
    $end_date = $entity->{$field_name}->end_date;
    $this->assertEquals($start_date->getTimestamp(), $end_date->getTimestamp());
    $this->assertEquals('12:00:00', $start_date->format('H:i:s'));
    $this->assertEquals('12:00:00', $end_date->format('H:i:s'));
  }

}
