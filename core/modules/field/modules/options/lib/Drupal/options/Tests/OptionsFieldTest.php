<?php

/**
 * @file
 * Definition of Drupal\options\Tests\OptionsFieldTest.
 */

namespace Drupal\options\Tests;

use Drupal\field\FieldException;
use Drupal\field\Tests\FieldTestBase;

/**
 * Tests for the 'Options' field types.
 */
class OptionsFieldTest extends FieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Options field',
      'description' => 'Test the Options field type.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp(array('options', 'field_test'));

    $this->field_name = 'test_options';
    $this->field = array(
      'field_name' => $this->field_name,
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(1 => 'One', 2 => 'Two', 3 => 'Three'),
      ),
    );
    $this->field = field_create_field($this->field);

    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
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
    $entity = field_test_create_stub_entity();
    $form = drupal_get_form('field_test_entity_form', $entity);
    $this->assertTrue(!empty($form[$this->field_name][$langcode][1]), t('Option 1 exists'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][2]), t('Option 2 exists'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][3]), t('Option 3 exists'));

    // Use one of the values in an actual entity, and check that this value
    // cannot be removed from the list.
    $entity = field_test_create_stub_entity();
    $entity->{$this->field_name}[$langcode][0] = array('value' => 1);
    field_test_entity_save($entity);
    $this->field['settings']['allowed_values'] = array(2 => 'Two');
    try {
      field_update_field($this->field);
      $this->fail(t('Cannot update a list field to not include keys with existing data.'));
    }
    catch (FieldException $e) {
      $this->pass(t('Cannot update a list field to not include keys with existing data.'));
    }
    // Empty the value, so that we can actually remove the option.
    $entity->{$this->field_name}[$langcode] = array();
    field_test_entity_save($entity);

    // Removed options do not appear.
    $this->field['settings']['allowed_values'] = array(2 => 'Two');
    field_update_field($this->field);
    $entity = field_test_create_stub_entity();
    $form = drupal_get_form('field_test_entity_form', $entity);
    $this->assertTrue(empty($form[$this->field_name][$langcode][1]), t('Option 1 does not exist'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][2]), t('Option 2 exists'));
    $this->assertTrue(empty($form[$this->field_name][$langcode][3]), t('Option 3 does not exist'));

    // Completely new options appear.
    $this->field['settings']['allowed_values'] = array(10 => 'Update', 20 => 'Twenty');
    field_update_field($this->field);
    $form = drupal_get_form('field_test_entity_form', $entity);
    $this->assertTrue(empty($form[$this->field_name][$langcode][1]), t('Option 1 does not exist'));
    $this->assertTrue(empty($form[$this->field_name][$langcode][2]), t('Option 2 does not exist'));
    $this->assertTrue(empty($form[$this->field_name][$langcode][3]), t('Option 3 does not exist'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][10]), t('Option 10 exists'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][20]), t('Option 20 exists'));

    // Options are reset when a new field with the same name is created.
    field_delete_field($this->field_name);
    unset($this->field['id']);
    $this->field['settings']['allowed_values'] = array(1 => 'One', 2 => 'Two', 3 => 'Three');
    $this->field = field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'widget' => array(
        'type' => 'options_buttons',
      ),
    );
    $this->instance = field_create_instance($this->instance);
    $entity = field_test_create_stub_entity();
    $form = drupal_get_form('field_test_entity_form', $entity);
    $this->assertTrue(!empty($form[$this->field_name][$langcode][1]), t('Option 1 exists'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][2]), t('Option 2 exists'));
    $this->assertTrue(!empty($form[$this->field_name][$langcode][3]), t('Option 3 exists'));
  }
}
