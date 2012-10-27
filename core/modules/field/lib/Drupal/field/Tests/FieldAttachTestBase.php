<?php

/**
 * @file
 * Definition of Drupal\field\Tests\FieldAttachTestBase.
 */

namespace Drupal\field\Tests;

abstract class FieldAttachTestBase extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  function setUp() {
    parent::setUp();

    $this->createFieldWithInstance();
  }

  /**
   * Create a field and an instance of it.
   *
   * @param string $suffix
   *   (optional) A string that should only contain characters that are valid in
   *   PHP variable names as well.
   */
  function createFieldWithInstance($suffix = '') {
    $field_name = 'field_name' . $suffix;
    $field = 'field' . $suffix;
    $field_id = 'field_id' . $suffix;
    $instance = 'instance' . $suffix;

    $this->$field_name = drupal_strtolower($this->randomName() . '_field_name' . $suffix);
    $this->$field = array('field_name' => $this->$field_name, 'type' => 'test_field', 'cardinality' => 4);
    $this->$field = field_create_field($this->$field);
    $this->$field_id = $this->{$field}['id'];
    $this->$instance = array(
      'field_name' => $this->$field_name,
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
    field_create_instance($this->$instance);
  }
}
