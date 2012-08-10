<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsDynamicValuesTest.
 */

namespace Drupal\options\Tests;

use Drupal\field\Tests\FieldTestBase;

/**
 * Sets up a Options field for testing allowed values functions.
 */
class OptionsDynamicValuesTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('options', 'field_test', 'options_test');

  function setUp() {
    parent::setUp();

    $this->field_name = 'test_options';
    $this->field = array(
      'field_name' => $this->field_name,
      'type' => 'list_text',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values_function' => 'options_test_dynamic_values_callback',
      ),
    );
    $this->field = field_create_field($this->field);

    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'required' => TRUE,
      'widget' => array(
        'type' => 'options_select',
      ),
    );
    $this->instance = field_create_instance($this->instance);
    $this->test = array(
      'id' => mt_rand(1, 10),
      // Make sure this does not equal the ID so that
      // options_test_dynamic_values_callback() always returns 4 values.
      'vid' => mt_rand(20, 30),
      'bundle' => 'test_bundle',
      'label' => $this->randomName(),
    );
    $this->entity = call_user_func_array('field_test_create_entity', $this->test);
  }
}
