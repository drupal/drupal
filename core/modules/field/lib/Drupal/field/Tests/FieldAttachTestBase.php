<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAttachTestBase.
 */

namespace Drupal\field\Tests;

class FieldAttachTestBase extends FieldTestBase {
  function setUp() {
    // Since this is a base class for many test cases, support the same
    // flexibility that Drupal\simpletest\WebTestBase::setUp() has for the
    // modules to be passed in as either an array or a variable number of string
    // arguments.
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    if (!in_array('field_test', $modules)) {
      $modules[] = 'field_test';
    }
    parent::setUp($modules);

    $this->field_name = drupal_strtolower($this->randomName() . '_field_name');
    $this->field = array('field_name' => $this->field_name, 'type' => 'test_field', 'cardinality' => 4);
    $this->field = field_create_field($this->field);
    $this->field_id = $this->field['id'];
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => $this->randomName() . '_label',
      'description' => $this->randomName() . '_description',
      'weight' => mt_rand(0, 127),
      'settings' => array(
        'test_instance_setting' => $this->randomName(),
      ),
      'widget' => array(
        'type' => 'test_field_widget',
        'label' => 'Test Field',
        'settings' => array(
          'test_widget_setting' => $this->randomName(),
        )
      )
    );
    field_create_instance($this->instance);
  }
}
