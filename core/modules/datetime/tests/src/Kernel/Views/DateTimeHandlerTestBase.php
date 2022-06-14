<?php

namespace Drupal\Tests\datetime\Kernel\Views;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Base class for testing datetime handlers.
 */
abstract class DateTimeHandlerTestBase extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['datetime_test', 'node', 'datetime', 'field'];

  /**
   * Name of the field.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected static $field_name = 'field_date';

  /**
   * Type of the field.
   *
   * @var string
   */
  protected static $field_type = 'datetime';

  /**
   * Nodes to test.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * Column map.
   *
   * @var string[]
   */
  protected array $map;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    // Add a date field to page nodes.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $node_type->save();
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => static::$field_name,
      'entity_type' => 'node',
      'type' => static::$field_type,
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ]);
    $fieldStorage->save();
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'page',
      'required' => TRUE,
    ]);
    $field->save();

    // Views needs to be aware of the new field.
    $this->container->get('views.views_data')->clear();

    // Set column map.
    $this->map = [
      'nid' => 'nid',
    ];

    // Load test views.
    ViewTestData::createTestViews(static::class, ['datetime_test']);
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
   * Returns UTC timestamp of user's TZ 'now'.
   *
   * The date field stores date_only values without conversion, considering them
   * already as UTC. This method returns the UTC equivalent of user's 'now' as a
   * unix timestamp, so they match using Y-m-d format.
   *
   * @return int
   *   Unix timestamp.
   */
  protected function getUTCEquivalentOfUserNowAsTimestamp() {
    $user_now = new DateTimePlus('now', new \DateTimeZone(date_default_timezone_get()));
    $utc_equivalent = new DateTimePlus($user_now->format('Y-m-d H:i:s'), new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

    return $utc_equivalent->getTimestamp();
  }

  /**
   * Returns an array formatted date_only values relative to timestamp.
   *
   * @param int $timestamp
   *   Unix Timestamp used as 'today'.
   *
   * @return array
   *   An array of DateTimeItemInterface::DATE_STORAGE_FORMAT date values. In
   *   order tomorrow, today and yesterday.
   */
  protected function getRelativeDateValuesFromTimestamp($timestamp) {
    return [
      // Tomorrow.
      \Drupal::service('date.formatter')->format($timestamp + 86400, 'custom', DateTimeItemInterface::DATE_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE),
      // Today.
      \Drupal::service('date.formatter')->format($timestamp, 'custom', DateTimeItemInterface::DATE_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE),
      // Yesterday.
      \Drupal::service('date.formatter')->format($timestamp - 86400, 'custom', DateTimeItemInterface::DATE_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE),
    ];
  }

}
