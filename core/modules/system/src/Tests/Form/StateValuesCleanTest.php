<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\StateValuesCleanTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Tests proper removal of submitted form values using
 * \Drupal\Core\Form\FormState::cleanValues().
 *
 * @group Form
 */
class StateValuesCleanTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  /**
   * Tests \Drupal\Core\Form\FormState::cleanValues().
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

    // Verify values manually added for cleaning were removed.
    $this->assertFalse(isset($values['wine']), SafeMarkup::format('%element was removed.', ['%element' => 'wine']));

    // Verify that nested form value still exists.
    $this->assertTrue(isset($values['baz']['beer']), 'Nested form value still exists.');

    // Verify that actual form values equal resulting form values.
    $this->assertEqual($values, $result, 'Expected form values equal actual form values.');
  }
}
