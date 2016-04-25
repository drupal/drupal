<?php

namespace Drupal\system\Tests\Form;

use Drupal\Core\Form\FormState;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the programmatic form submission behavior.
 *
 * @group Form
 */
class ProgrammaticTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  /**
   * Test the programmatic form submission workflow.
   */
  function testSubmissionWorkflow() {
    // Backup the current batch status and reset it to avoid conflicts while
    // processing the dummy form submit handler.
    $current_batch = $batch =& batch_get();
    $batch = array();

    // Test that a programmatic form submission is rejected when a required
    // textfield is omitted and correctly processed when it is provided.
    $this->submitForm(array(), FALSE);
    $this->submitForm(array('textfield' => 'test 1'), TRUE);
    $this->submitForm(array(), FALSE);
    $this->submitForm(array('textfield' => 'test 2'), TRUE);

    // Test that a programmatic form submission can turn on and off checkboxes
    // which are, by default, checked.
    $this->submitForm(array('textfield' => 'dummy value', 'checkboxes' => array(1 => 1, 2 => 2)), TRUE);
    $this->submitForm(array('textfield' => 'dummy value', 'checkboxes' => array(1 => 1, 2 => NULL)), TRUE);
    $this->submitForm(array('textfield' => 'dummy value', 'checkboxes' => array(1 => NULL, 2 => 2)), TRUE);
    $this->submitForm(array('textfield' => 'dummy value', 'checkboxes' => array(1 => NULL, 2 => NULL)), TRUE);

    // Test that a programmatic form submission can correctly click a button
    // that limits validation errors based on user input. Since we do not
    // submit any values for "textfield" here and the textfield is required, we
    // only expect form validation to pass when validation is limited to a
    // different field.
    $this->submitForm(array('op' => 'Submit with limited validation', 'field_to_validate' => 'all'), FALSE);
    $this->submitForm(array('op' => 'Submit with limited validation', 'field_to_validate' => 'textfield'), FALSE);
    $this->submitForm(array('op' => 'Submit with limited validation', 'field_to_validate' => 'field_to_validate'), TRUE);

    // Restore the current batch status.
    $batch = $current_batch;
  }

  /**
   * Helper function used to programmatically submit the form defined in
   * form_test.module with the given values.
   *
   * @param $values
   *   An array of field values to be submitted.
   * @param $valid_input
   *   A boolean indicating whether or not the form submission is expected to
   *   be valid.
   */
  private function submitForm($values, $valid_input) {
    // Programmatically submit the given values.
    $form_state = (new FormState())->setValues($values);
    \Drupal::formBuilder()->submitForm('\Drupal\form_test\Form\FormTestProgrammaticForm', $form_state);

    // Check that the form returns an error when expected, and vice versa.
    $errors = $form_state->getErrors();
    $valid_form = empty($errors);
    $args = array(
      '%values' => print_r($values, TRUE),
      '%errors' => $valid_form ? t('None') : implode(' ', $errors),
    );
    $this->assertTrue($valid_input == $valid_form, format_string('Input values: %values<br />Validation handler errors: %errors', $args));

    // We check submitted values only if we have a valid input.
    if ($valid_input) {
      // Fetching the values that were set in the submission handler.
      $stored_values = $form_state->get('programmatic_form_submit');
      foreach ($values as $key => $value) {
        $this->assertEqual($stored_values[$key], $value, format_string('Submission handler correctly executed: %stored_key is %stored_value', array('%stored_key' => $key, '%stored_value' => print_r($value, TRUE))));
      }
    }
  }

  /**
   * Test the programmed_bypass_access_check flag.
   */
  public function testProgrammaticAccessBypass() {
    $form_state = (new FormState())->setValues([
      'textfield' => 'dummy value',
      'field_restricted' => 'dummy value'
    ]);

    // Programmatically submit the form with a value for the restricted field.
    // Since programmed_bypass_access_check is set to TRUE by default, the
    // field is accessible and can be set.
    \Drupal::formBuilder()->submitForm('\Drupal\form_test\Form\FormTestProgrammaticForm', $form_state);
    $values = $form_state->get('programmatic_form_submit');
    $this->assertEqual($values['field_restricted'], 'dummy value', 'The value for the restricted field is stored correctly.');

    // Programmatically submit the form with a value for the restricted field
    // with programmed_bypass_access_check set to FALSE. Since access
    // restrictions apply, the restricted field is inaccessible, and the value
    // should not be stored.
    $form_state->setProgrammedBypassAccessCheck(FALSE);
    \Drupal::formBuilder()->submitForm('\Drupal\form_test\Form\FormTestProgrammaticForm', $form_state);
    $values = $form_state->get('programmatic_form_submit');
    $this->assertNotEqual($values['field_restricted'], 'dummy value', 'The value for the restricted field is not stored.');

  }
}
