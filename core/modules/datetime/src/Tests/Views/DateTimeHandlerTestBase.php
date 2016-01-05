<?php

/**
 * @file
 * Contains \Drupal\datetime\Tests\Views\DateTimeHandlerTestBase.
 */

namespace Drupal\datetime\Tests\Views;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\views\Tests\Handler\HandlerTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for testing datetime handlers.
 */
abstract class DateTimeHandlerTestBase extends HandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['datetime_test', 'node', 'datetime'];

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
  protected function setUp() {
    parent::setUp();

    // Add a date field to page nodes.
    $node_type = entity_create('node_type', [
      'type' => 'page',
      'name' => 'page'
    ]);
    $node_type->save();
    $fieldStorage = entity_create('field_storage_config', [
      'field_name' => static::$field_name,
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ]);
    $fieldStorage->save();
    $field = entity_create('field_config', [
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

}
