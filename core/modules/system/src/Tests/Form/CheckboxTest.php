<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\CheckboxTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form API checkbox handling of various combinations of #default_value
 * and #return_value.
 *
 * @group Form
 */
class CheckboxTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  function testFormCheckbox() {
    // Ensure that the checked state is determined and rendered correctly for
    // tricky combinations of default and return values.
    foreach (array(FALSE, NULL, TRUE, 0, '0', '', 1, '1', 'foobar', '1foobar') as $default_value) {
      // Only values that can be used for array indices are supported for
      // #return_value, with the exception of integer 0, which is not supported.
      // @see \Drupal\Core\Render\Element\Checkbox::processCheckbox().
      foreach (array('0', '', 1, '1', 'foobar', '1foobar') as $return_value) {
        $form_array = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestCheckboxTypeJugglingForm', $default_value, $return_value);
        $form = drupal_render($form_array);
        if ($default_value === TRUE) {
          $checked = TRUE;
        }
        elseif ($return_value === '0') {
          $checked = ($default_value === '0');
        }
        elseif ($return_value === '') {
          $checked = ($default_value === '');
        }
        elseif ($return_value === 1 || $return_value === '1') {
          $checked = ($default_value === 1 || $default_value === '1');
        }
        elseif ($return_value === 'foobar') {
          $checked = ($default_value === 'foobar');
        }
        elseif ($return_value === '1foobar') {
          $checked = ($default_value === '1foobar');
        }
        $checked_in_html = strpos($form, 'checked') !== FALSE;
        $message = format_string('#default_value is %default_value #return_value is %return_value.', array('%default_value' => var_export($default_value, TRUE), '%return_value' => var_export($return_value, TRUE)));
        $this->assertIdentical($checked, $checked_in_html, $message);
      }
    }

    // Ensure that $form_state->getValues() is populated correctly for a checkboxes
    // group that includes a 0-indexed array of options.
    $results = json_decode($this->drupalPostForm('form-test/checkboxes-zero', array(), 'Save'));
    $this->assertIdentical($results->checkbox_off, array(0, 0, 0), 'All three in checkbox_off are zeroes: off.');
    $this->assertIdentical($results->checkbox_zero_default, array('0', 0, 0), 'The first choice is on in checkbox_zero_default');
    $this->assertIdentical($results->checkbox_string_zero_default, array('0', 0, 0), 'The first choice is on in checkbox_string_zero_default');
    $edit = array('checkbox_off[0]' => '0');
    $results = json_decode($this->drupalPostForm('form-test/checkboxes-zero', $edit, 'Save'));
    $this->assertIdentical($results->checkbox_off, array('0', 0, 0), 'The first choice is on in checkbox_off but the rest is not');

    // Ensure that each checkbox is rendered correctly for a checkboxes group
    // that includes a 0-indexed array of options.
    $this->drupalPostForm('form-test/checkboxes-zero/0', array(), 'Save');
    $checkboxes = $this->xpath('//input[@type="checkbox"]');
    foreach ($checkboxes as $checkbox) {
      $checked = isset($checkbox['checked']);
      $name = (string) $checkbox['name'];
      $this->assertIdentical($checked, $name == 'checkbox_zero_default[0]' || $name == 'checkbox_string_zero_default[0]', format_string('Checkbox %name correctly checked', array('%name' => $name)));
    }
    $edit = array('checkbox_off[0]' => '0');
    $this->drupalPostForm('form-test/checkboxes-zero/0', $edit, 'Save');
    $checkboxes = $this->xpath('//input[@type="checkbox"]');
    foreach ($checkboxes as $checkbox) {
      $checked = isset($checkbox['checked']);
      $name = (string) $checkbox['name'];
      $this->assertIdentical($checked, $name == 'checkbox_off[0]' || $name == 'checkbox_zero_default[0]' || $name == 'checkbox_string_zero_default[0]', format_string('Checkbox %name correctly checked', array('%name' => $name)));
    }
  }
}
