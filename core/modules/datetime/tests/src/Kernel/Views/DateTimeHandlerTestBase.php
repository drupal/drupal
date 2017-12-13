<?php

namespace Drupal\Tests\datetime\Kernel\Views;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
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
  public static $modules = ['datetime_test', 'node', 'datetime', 'field'];

  /**
   * Name of the field.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected static $field_name = 'field_date';

  /**
   * Nodes to test.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    // Add a date field to page nodes.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'page'
    ]);
    $node_type->save();
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => static::$field_name,
      'entity_type' => 'node',
      'type' => 'datetime',
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
    ViewTestData::createTestViews(get_class($this), ['datetime_test']);
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

}
