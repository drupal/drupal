<?php

/**
 * @file
 * Definition of Drupal\views\Test\Field\FieldTestBase.
 */

/**
 * @TODO
 *   - Test on a generic entity not on a node.
 *
 * What has to be tested:
 *   - Take sure that every wanted field is added to the according entity type.
 *   - Take sure the joins are done correct.
 *   - Use basic fields and take sure that the full wanted object is build.
 *   - Use relationships between different entity types, for example node and the node author(user).
 */

namespace Drupal\views\Tests\Field;

use Drupal\views\Tests\ViewTestBase;

/**
 * Provides some helper methods for testing fieldapi integration into views.
 */
abstract class FieldTestBase extends ViewTestBase {

  /**
   * Stores the field definitions used by the test.
   * @var array
   */
  public $fields;
  /**
   * Stores the instances of the fields. They have
   * the same keys as the fields.
   * @var array
   */
  public $instances;

  function setUpFields($amount = 3) {
    // Create three fields.
    $field_names = array();
    for ($i = 0; $i < $amount; $i++) {
      $field_names[$i] = 'field_name_' . $i;
      $field = array('field_name' => $field_names[$i], 'type' => 'text');

      $this->fields[$i] = $field = field_create_field($field);
    }
    return $field_names;
  }

  function setUpInstances($bundle = 'page') {
    foreach ($this->fields as $key => $field) {
      $instance = array(
        'field_name' => $field['field_name'],
        'entity_type' => 'node',
        'bundle' => 'page',
      );
      $this->instances[$key] = field_create_instance($instance);
    }
  }

  /**
   * Clear all views caches and static caches which are required for the patch.
   */
  function clearViewsCaches() {
    // Reset views data cache.
    drupal_static_reset('_views_fetch_data_cache');
    drupal_static_reset('_views_fetch_data_recursion_protected');
    drupal_static_reset('_views_fetch_data_fully_loaded');
  }

}
