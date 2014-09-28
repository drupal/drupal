<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\FieldTestBase.
 */

/**
 * @TODO
 *   - Test on a generic entity not on a node.
 *
 * What has to be tested:
 *   - Make sure that every wanted field is added to the according entity type.
 *   - Make sure the joins are done correctly.
 *   - Use basic fields and make sure that the full wanted object is built.
 *   - Use relationships between different entity types, for example node and
 *     the node author(user).
 */

namespace Drupal\field\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Provides some helper methods for testing fieldapi integration into views.
 */
abstract class FieldTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_test_views');

  /**
   * Stores the field definitions used by the test.
   *
   * @var array
   */
  public $fieldStorages;

  /**
   * Stores the fields of the field storage. They have the same keys as the
   * field storages.
   *
   * @var array
   */
  public $fields;

  protected function setUp() {
    parent::setUp();

    // Ensure the page node type exists.
    entity_create('node_type', array(
      'type' => 'page',
      'name' => 'page',
    ))->save();

    ViewTestData::createTestViews(get_class($this), array('field_test_views'));
  }

  function setUpFieldStorages($amount = 3, $type = 'string') {
    // Create three fields.
    $field_names = array();
    for ($i = 0; $i < $amount; $i++) {
      $field_names[$i] = 'field_name_' . $i;
      $this->fieldStorages[$i] = entity_create('field_storage_config', array(
        'field_name' => $field_names[$i],
        'entity_type' => 'node',
        'type' => $type,
      ));
      $this->fieldStorages[$i]->save();
    }
    return $field_names;
  }

  function setUpFields($bundle = 'page') {
    foreach ($this->fieldStorages as $key => $field_storage) {
      $this->fields[$key] = entity_create('field_config', array(
        'field_storage' => $field_storage,
        'bundle' => $bundle,
      ));
      $this->fields[$key]->save();
    }
  }

}
