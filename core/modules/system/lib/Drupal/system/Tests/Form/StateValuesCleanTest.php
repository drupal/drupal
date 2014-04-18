<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\StateValuesCleanTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Test $form_state clearance.
 */
class StateValuesCleanTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Form state values clearance',
      'description' => 'Test proper removal of submitted form values using form_state_values_clean().',
      'group' => 'Form API',
    );
  }

  /**
   * Tests form_state_values_clean().
   */
  function testFormStateValuesClean() {
    $values = Json::decode($this->drupalPostForm('form_test/form-state-values-clean', array(), t('Submit')));

    // Setup the expected result.
    $result = array(
      'beer' => 1000,
      'baz' => array('beer' => 2000),
    );

    // Verify that all internal Form API elements were removed.
    $this->assertFalse(isset($values['form_id']), format_string('%element was removed.', array('%element' => 'form_id')));
    $this->assertFalse(isset($values['form_token']), format_string('%element was removed.', array('%element' => 'form_token')));
    $this->assertFalse(isset($values['form_build_id']), format_string('%element was removed.', array('%element' => 'form_build_id')));
    $this->assertFalse(isset($values['op']), format_string('%element was removed.', array('%element' => 'op')));

    // Verify that all buttons were removed.
    $this->assertFalse(isset($values['foo']), format_string('%element was removed.', array('%element' => 'foo')));
    $this->assertFalse(isset($values['bar']), format_string('%element was removed.', array('%element' => 'bar')));
    $this->assertFalse(isset($values['baz']['foo']), format_string('%element was removed.', array('%element' => 'foo')));
    $this->assertFalse(isset($values['baz']['baz']), format_string('%element was removed.', array('%element' => 'baz')));

    // Verify that nested form value still exists.
    $this->assertTrue(isset($values['baz']['beer']), 'Nested form value still exists.');

    // Verify that actual form values equal resulting form values.
    $this->assertEqual($values, $result, 'Expected form values equal actual form values.');
  }
}
