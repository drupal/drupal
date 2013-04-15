<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsFieldTest.
 */

namespace Drupal\options\Tests;

use Drupal\field\FieldException;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests for the 'Options' field types.
 */
class OptionsFieldTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('options');

  public static function getInfo() {
    return array(
      'name' => 'Options field',
      'description' => 'Test the Options field type.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp();
    $this->installSchema('system', 'menu_router');


    $this->field_name = 'test_options';
    $this->field_definition = array(
      'field_name' => $this->field_name,
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(1 => 'One', 2 => 'Two', 3 => 'Three'),
      ),
    );
    $this->field = field_create_field($this->field_definition);

    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'widget' => array(
        'type' => 'options_buttons',
      ),
    );
    $this->instance = field_create_instance($this->instance);
  }

  /**
   * Test that allowed values can be updated.
   */
  function testUpdateAllowedValues() {
    $langcode = LANGUAGE_NOT_SPECIFIED;

    // All three options appear.
    $entity = entity_create('entity_test', array());
    $form = entity_get_form($entity);
    $this->assertTrue(!empty($form[$this->field_name][$langcode][1]), 'Option 1 exists');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][2]), 'Option 2 exists');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][3]), 'Option 3 exists');

    // Use one of the values in an actual entity, and check that this value
    // cannot be removed from the list.
    $entity = entity_create('entity_test', array());
    $entity->{$this->field_name}->value = 1;
    $entity->save();
    $this->field['settings']['allowed_values'] = array(2 => 'Two');
    try {
      field_update_field($this->field);
      $this->fail(t('Cannot update a list field to not include keys with existing data.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot update a list field to not include keys with existing data.'));
    }
    // Empty the value, so that we can actually remove the option.
    unset($entity->{$this->field_name});
    $entity->save();

    // Removed options do not appear.
    $this->field['settings']['allowed_values'] = array(2 => 'Two');
    field_update_field($this->field);
    $entity = entity_create('entity_test', array());
    $form = entity_get_form($entity);
    $this->assertTrue(empty($form[$this->field_name][$langcode][1]), 'Option 1 does not exist');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][2]), 'Option 2 exists');
    $this->assertTrue(empty($form[$this->field_name][$langcode][3]), 'Option 3 does not exist');

    // Completely new options appear.
    $this->field['settings']['allowed_values'] = array(10 => 'Update', 20 => 'Twenty');
    field_update_field($this->field);
    $form = entity_get_form($entity);
    $this->assertTrue(empty($form[$this->field_name][$langcode][1]), 'Option 1 does not exist');
    $this->assertTrue(empty($form[$this->field_name][$langcode][2]), 'Option 2 does not exist');
    $this->assertTrue(empty($form[$this->field_name][$langcode][3]), 'Option 3 does not exist');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][10]), 'Option 10 exists');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][20]), 'Option 20 exists');

    // Options are reset when a new field with the same name is created.
    field_delete_field($this->field_name);
    unset($this->field['id']);
    field_create_field($this->field_definition);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'widget' => array(
        'type' => 'options_buttons',
      ),
    );
    field_create_instance($this->instance);
    $entity = entity_create('entity_test', array());
    $form = entity_get_form($entity);
    $this->assertTrue(!empty($form[$this->field_name][$langcode][1]), 'Option 1 exists');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][2]), 'Option 2 exists');
    $this->assertTrue(!empty($form[$this->field_name][$langcode][3]), 'Option 3 exists');
  }
}
