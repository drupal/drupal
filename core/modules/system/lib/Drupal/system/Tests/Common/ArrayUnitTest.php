<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\ArrayUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests the various drupal_array_* helper functions.
 */
class ArrayUnitTest extends UnitTestBase {

  /**
   * Form array to check.
   */
  protected $form;

  /**
   * Array of parents for the nested element.
   */
  protected $parents;

  public static function getInfo() {
    return array(
      'name' => 'drupal_array_*() tests',
      'description' => 'Tests the various drupal_array_* helper functions.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a form structure with a nested element.
    $this->form['fieldset']['element'] = array(
     '#value' => 'Nested element',
    );

    // Set up parent array.
    $this->parents = array('fieldset', 'element');
  }

  /**
   * Tests getting nested array values.
   */
  function testGet() {
    // Verify getting a value of a nested element.
    $value = drupal_array_get_nested_value($this->form, $this->parents);
    $this->assertEqual($value['#value'], 'Nested element', 'Nested element value found.');

    // Verify changing a value of a nested element by reference.
    $value = &drupal_array_get_nested_value($this->form, $this->parents);
    $value['#value'] = 'New value';
    $value = drupal_array_get_nested_value($this->form, $this->parents);
    $this->assertEqual($value['#value'], 'New value', 'Nested element value was changed by reference.');
    $this->assertEqual($this->form['fieldset']['element']['#value'], 'New value', 'Nested element value was changed by reference.');

    // Verify that an existing key is reported back.
    $key_exists = NULL;
    drupal_array_get_nested_value($this->form, $this->parents, $key_exists);
    $this->assertIdentical($key_exists, TRUE, 'Existing key found.');

    // Verify that a non-existing key is reported back and throws no errors.
    $key_exists = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    drupal_array_get_nested_value($this->form, $parents, $key_exists);
    $this->assertIdentical($key_exists, FALSE, 'Non-existing key not found.');
  }

  /**
   * Tests setting nested array values.
   */
  function testSet() {
    $new_value = array(
      '#value' => 'New value',
      '#required' => TRUE,
    );

    // Verify setting the value of a nested element.
    drupal_array_set_nested_value($this->form, $this->parents, $new_value);
    $this->assertEqual($this->form['fieldset']['element']['#value'], 'New value', 'Changed nested element value found.');
    $this->assertIdentical($this->form['fieldset']['element']['#required'], TRUE, 'New nested element value found.');
  }

  /**
   * Tests unsetting nested array values.
   */
  function testUnset() {
    // Verify unsetting a non-existing nested element throws no errors and the
    // non-existing key is properly reported.
    $key_existed = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    drupal_array_unset_nested_value($this->form, $parents, $key_existed);
    $this->assertTrue(isset($this->form['fieldset']['element']['#value']), 'Outermost nested element key still exists.');
    $this->assertIdentical($key_existed, FALSE, 'Non-existing key not found.');

    // Verify unsetting a nested element.
    $key_existed = NULL;
    drupal_array_unset_nested_value($this->form, $this->parents, $key_existed);
    $this->assertFalse(isset($this->form['fieldset']['element']), 'Removed nested element not found.');
    $this->assertIdentical($key_existed, TRUE, 'Existing key was found.');
  }

  /**
   * Tests existence of array key.
   */
  function testKeyExists() {
    // Verify that existing key is found.
    $this->assertIdentical(drupal_array_nested_key_exists($this->form, $this->parents), TRUE, 'Nested key found.');

    // Verify that non-existing keys are not found.
    $parents = $this->parents;
    $parents[] = 'foo';
    $this->assertIdentical(drupal_array_nested_key_exists($this->form, $parents), FALSE, 'Non-existing nested key not found.');
  }
}
