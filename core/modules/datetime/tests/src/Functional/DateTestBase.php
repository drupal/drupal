<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for testing Datetime field functionality.
 */
abstract class DateTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'entity_test', 'datetime', 'field_ui'];

  /**
   * An array of display options.
   *
   * An array of display options to pass to
   * EntityDisplayRepositoryInterface::getViewDisplay()
   *
   * @var array
   *
   * @see \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewDisplay()
   */
  protected $displayOptions;

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * An array of timezone extremes to test.
   *
   * @var string[]
   */
  protected static $timezones = [
    // UTC-12, no DST.
    'Pacific/Kwajalein',
    // UTC-11, no DST
    'Pacific/Midway',
    // UTC-7, no DST.
    'America/Phoenix',
    // UTC.
    'UTC',
    // UTC+5:30, no DST.
    'Asia/Kolkata',
    // UTC+12, no DST
    'Pacific/Funafuti',
    // UTC+13, no DST.
    'Pacific/Tongatapu',
  ];

  /**
   * Returns the type of field to be tested.
   *
   * @return string
   */
  abstract protected function getTestFieldType();

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'access content',
      'view test entity',
      'administer entity_test content',
      'administer entity_test form display',
      'administer content types',
      'bypass node access',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    $this->drupalLogin($web_user);

    // Create a field with settings to validate.
    $this->createField();

    $this->dateFormatter = $this->container->get('date.formatter');
  }

  /**
   * Creates a date test field.
   */
  protected function createField() {
    $field_name = $this->randomMachineName();
    $field_label = Unicode::ucfirst($this->randomMachineName());
    $type = $this->getTestFieldType();
    $widget_type = $formatter_type = $type . '_default';

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $type,
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATE],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'label' => $field_label,
      'bundle' => 'entity_test',
      'description' => 'Description for ' . $field_label,
      'required' => TRUE,
    ]);
    $this->field->save();

    EntityFormDisplay::load('entity_test.entity_test.default')
      ->setComponent($field_name, ['type' => $widget_type])
      ->save();

    $this->displayOptions = [
      'type' => $formatter_type,
      'label' => 'hidden',
      'settings' => ['format_type' => 'medium'],
    ];
    EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'full',
      'status' => TRUE,
    ])->setComponent($field_name, $this->displayOptions)
      ->save();
  }

  /**
   * Renders an entity_test and sets the output in the internal browser.
   *
   * @param int $id
   *   The entity_test ID to render.
   * @param string $view_mode
   *   (optional) The view mode to use for rendering. Defaults to 'full'.
   * @param bool $reset
   *   (optional) Whether to reset the entity_test controller cache. Defaults to
   *   TRUE to simplify testing.
   *
   * @return string
   *   The rendered HTML output.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      $this->container->get('entity_type.manager')->getStorage('entity_test')->resetCache([$id]);
    }
    $entity = EntityTest::load($id);
    $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $build = $display->build($entity);
    return (string) $this->container->get('renderer')->renderRoot($build);
  }

  /**
   * Sets the site timezone to a given timezone.
   *
   * @param string $timezone
   *   The timezone identifier to set.
   */
  protected function setSiteTimezone($timezone) {
    // Set an explicit site timezone, and disallow per-user timezones.
    $this->config('system.date')
      ->set('timezone.user.configurable', 0)
      ->set('timezone.default', $timezone)
      ->save();
  }

  /**
   * Massages test date values.
   *
   * If a date object is generated directly by a test, then it needs to be
   * adjusted to behave like the computed date from the item.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object directly generated by the test.
   */
  protected function massageTestDate($date) {
    if ($this->field->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      // Set the default time for date-only items.
      $date->setDefaultDateTime();
    }
  }

}
