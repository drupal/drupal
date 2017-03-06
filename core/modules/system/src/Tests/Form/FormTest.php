<?php

namespace Drupal\system\Tests\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\form_test\Form\FormTestDisabledElementsForm;
use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests various form element validation mechanisms.
 *
 * @group Form
 */
class FormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'form_test', 'file', 'datetime'];

  protected function setUp() {
    parent::setUp();

    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    ]);
    $filtered_html_format->save();

    $filtered_html_permission = $filtered_html_format->getPermissionName();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [$filtered_html_permission]);
  }

  /**
   * Check several empty values for required forms elements.
   *
   * Carriage returns, tabs, spaces, and unchecked checkbox elements are not
   * valid content for a required field.
   *
   * If the form field is found in $form_state->getErrors() then the test pass.
   */
  public function testRequiredFields() {
    // Originates from https://www.drupal.org/node/117748.
    // Sets of empty strings and arrays.
    $empty_strings = ['""' => "", '"\n"' => "\n", '" "' => " ", '"\t"' => "\t", '" \n\t "' => " \n\t ", '"\n\n\n\n\n"' => "\n\n\n\n\n"];
    $empty_arrays = ['array()' => []];
    $empty_checkbox = [NULL];

    $elements['textfield']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'textfield'];
    $elements['textfield']['empty_values'] = $empty_strings;

    $elements['telephone']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'tel'];
    $elements['telephone']['empty_values'] = $empty_strings;

    $elements['url']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'url'];
    $elements['url']['empty_values'] = $empty_strings;

    $elements['search']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'search'];
    $elements['search']['empty_values'] = $empty_strings;

    $elements['password']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'password'];
    $elements['password']['empty_values'] = $empty_strings;

    $elements['password_confirm']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'password_confirm'];
    // Provide empty values for both password fields.
    foreach ($empty_strings as $key => $value) {
      $elements['password_confirm']['empty_values'][$key] = ['pass1' => $value, 'pass2' => $value];
    }

    $elements['textarea']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'textarea'];
    $elements['textarea']['empty_values'] = $empty_strings;

    $elements['radios']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'radios', '#options' => ['' => t('None'), $this->randomMachineName(), $this->randomMachineName(), $this->randomMachineName()]];
    $elements['radios']['empty_values'] = $empty_arrays;

    $elements['checkbox']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'checkbox', '#required' => TRUE];
    $elements['checkbox']['empty_values'] = $empty_checkbox;

    $elements['checkboxes']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'checkboxes', '#options' => [$this->randomMachineName(), $this->randomMachineName(), $this->randomMachineName()]];
    $elements['checkboxes']['empty_values'] = $empty_arrays;

    $elements['select']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'select', '#options' => ['' => t('None'), $this->randomMachineName(), $this->randomMachineName(), $this->randomMachineName()]];
    $elements['select']['empty_values'] = $empty_strings;

    $elements['file']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'file'];
    $elements['file']['empty_values'] = $empty_strings;

    // Regular expression to find the expected marker on required elements.
    $required_marker_preg = '@<.*?class=".*?js-form-required.*form-required.*?">@';
    // Go through all the elements and all the empty values for them.
    foreach ($elements as $type => $data) {
      foreach ($data['empty_values'] as $key => $empty) {
        foreach ([TRUE, FALSE] as $required) {
          $form_id = $this->randomMachineName();
          $form = [];
          $form_state = new FormState();
          $form['op'] = ['#type' => 'submit', '#value' => t('Submit')];
          $element = $data['element']['#title'];
          $form[$element] = $data['element'];
          $form[$element]['#required'] = $required;
          $user_input[$element] = $empty;
          $user_input['form_id'] = $form_id;
          $form_state->setUserInput($user_input);
          $form_state->setFormObject(new StubForm($form_id, $form));
          $form_state->setMethod('POST');
          // The form token CSRF protection should not interfere with this test,
          // so we bypass it by setting the token to FALSE.
          $form['#token'] = FALSE;
          \Drupal::formBuilder()->prepareForm($form_id, $form, $form_state);
          \Drupal::formBuilder()->processForm($form_id, $form, $form_state);
          $errors = $form_state->getErrors();
          // Form elements of type 'radios' throw all sorts of PHP notices
          // when you try to render them like this, so we ignore those for
          // testing the required marker.
          // @todo Fix this work-around (https://www.drupal.org/node/588438).
          $form_output = ($type == 'radios') ? '' : \Drupal::service('renderer')->renderRoot($form);
          if ($required) {
            // Make sure we have a form error for this element.
            $this->assertTrue(isset($errors[$element]), "Check empty($key) '$type' field '$element'");
            if (!empty($form_output)) {
              // Make sure the form element is marked as required.
              $this->assertTrue(preg_match($required_marker_preg, $form_output), "Required '$type' field is marked as required");
            }
          }
          else {
            if (!empty($form_output)) {
              // Make sure the form element is *not* marked as required.
              $this->assertFalse(preg_match($required_marker_preg, $form_output), "Optional '$type' field is not marked as required");
            }
            if ($type == 'select') {
              // Select elements are going to have validation errors with empty
              // input, since those are illegal choices. Just make sure the
              // error is not "field is required".
              $this->assertTrue((empty($errors[$element]) || strpos('field is required', (string) $errors[$element]) === FALSE), "Optional '$type' field '$element' is not treated as a required element");
            }
            else {
              // Make sure there is *no* form error for this element.
              $this->assertTrue(empty($errors[$element]), "Optional '$type' field '$element' has no errors with empty input");
            }
          }
        }
      }
    }
    // Clear the expected form error messages so they don't appear as exceptions.
    drupal_get_messages();
  }

  /**
   * Tests validation for required checkbox, select, and radio elements.
   *
   * Submits a test form containing several types of form elements. The form
   * is submitted twice, first without values for required fields and then
   * with values. Each submission is checked for relevant error messages.
   *
   * @see \Drupal\form_test\Form\FormTestValidateRequiredForm
   */
  public function testRequiredCheckboxesRadio() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestValidateRequiredForm');

    // Attempt to submit the form with no required fields set.
    $edit = [];
    $this->drupalPostForm('form-test/validate-required', $edit, 'Submit');

    // The only error messages that should appear are the relevant 'required'
    // messages for each field.
    $expected = [];
    foreach (['textfield', 'checkboxes', 'select', 'radios'] as $key) {
      if (isset($form[$key]['#required_error'])) {
        $expected[] = $form[$key]['#required_error'];
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $expected[] = $form[$key]['#form_test_required_error'];
      }
      else {
        $expected[] = t('@name field is required.', ['@name' => $form[$key]['#title']]);
      }
    }

    // Check the page for error messages.
    $errors = $this->xpath('//div[contains(@class, "error")]//li');
    foreach ($errors as $error) {
      $expected_key = array_search($error[0], $expected);
      // If the error message is not one of the expected messages, fail.
      if ($expected_key === FALSE) {
        $this->fail(format_string("Unexpected error message: @error", ['@error' => $error[0]]));
      }
      // Remove the expected message from the list once it is found.
      else {
        unset($expected[$expected_key]);
      }
    }

    // Fail if any expected messages were not found.
    foreach ($expected as $not_found) {
      $this->fail(format_string("Found error message: @error", ['@error' => $not_found]));
    }

    // Verify that input elements are still empty.
    $this->assertFieldByName('textfield', '');
    $this->assertNoFieldChecked('edit-checkboxes-foo');
    $this->assertNoFieldChecked('edit-checkboxes-bar');
    $this->assertOptionSelected('edit-select', '');
    $this->assertNoFieldChecked('edit-radios-foo');
    $this->assertNoFieldChecked('edit-radios-bar');
    $this->assertNoFieldChecked('edit-radios-optional-foo');
    $this->assertNoFieldChecked('edit-radios-optional-bar');
    $this->assertNoFieldChecked('edit-radios-optional-default-value-false-foo');
    $this->assertNoFieldChecked('edit-radios-optional-default-value-false-bar');

    // Submit again with required fields set and verify that there are no
    // error messages.
    $edit = [
      'textfield' => $this->randomString(),
      'checkboxes[foo]' => TRUE,
      'select' => 'foo',
      'radios' => 'bar',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $this->assertNoFieldByXpath('//div[contains(@class, "error")]', FALSE, 'No error message is displayed when all required fields are filled.');
    $this->assertRaw("The form_test_validate_required_form form was submitted successfully.", 'Validation form submitted successfully.');
  }

  /**
   * Tests that input is retained for safe elements even with an invalid token.
   *
   * Submits a test form containing several types of form elements.
   */
  public function testInputWithInvalidToken() {
    // We need to be logged in to have CSRF tokens.
    $account = $this->createUser();
    $this->drupalLogin($account);
    // Submit again with required fields set but an invalid form token and
    // verify that all the values are retained.
    $edit = [
      'textfield' => $this->randomString(),
      'checkboxes[bar]' => TRUE,
      'select' => 'bar',
      'radios' => 'foo',
      'form_token' => 'invalid token',
    ];
    $this->drupalPostForm(Url::fromRoute('form_test.validate_required'), $edit, 'Submit');
    $this->assertFieldByXpath('//div[contains(@class, "error")]', NULL, 'Error message is displayed with invalid token even when required fields are filled.');
    $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
    // Verify that input elements retained the posted values.
    $this->assertFieldByName('textfield', $edit['textfield']);
    $this->assertNoFieldChecked('edit-checkboxes-foo');
    $this->assertFieldChecked('edit-checkboxes-bar');
    $this->assertOptionSelected('edit-select', 'bar');
    $this->assertFieldChecked('edit-radios-foo');

    // Check another form that has a textarea input.
    $edit = [
      'textfield' => $this->randomString(),
      'textarea' => $this->randomString() . "\n",
      'form_token' => 'invalid token',
    ];
    $this->drupalPostForm(Url::fromRoute('form_test.required'), $edit, 'Submit');
    $this->assertFieldByXpath('//div[contains(@class, "error")]', NULL, 'Error message is displayed with invalid token even when required fields are filled.');
    $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
    $this->assertFieldByName('textfield', $edit['textfield']);
    $this->assertFieldByName('textarea', $edit['textarea']);

    // Check another form that has a number input.
    $edit = [
      'integer_step' => mt_rand(1, 100),
      'form_token' => 'invalid token',
    ];
    $this->drupalPostForm(Url::fromRoute('form_test.number'), $edit, 'Submit');
    $this->assertFieldByXpath('//div[contains(@class, "error")]', NULL, 'Error message is displayed with invalid token even when required fields are filled.');
    $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
    $this->assertFieldByName('integer_step', $edit['integer_step']);

    // Check a form with a Url field
    $edit = [
      'url' => $this->randomString(),
      'form_token' => 'invalid token',
    ];
    $this->drupalPostForm(Url::fromRoute('form_test.url'), $edit, 'Submit');
    $this->assertFieldByXpath('//div[contains(@class, "error")]', NULL, 'Error message is displayed with invalid token even when required fields are filled.');
    $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
    $this->assertFieldByName('url', $edit['url']);
  }

  /**
   * CSRF tokens for GET forms should not be added by default.
   */
  public function testGetFormsCsrfToken() {
    // We need to be logged in to have CSRF tokens.
    $account = $this->createUser();
    $this->drupalLogin($account);

    $this->drupalGet(Url::fromRoute('form_test.get_form'));
    $this->assertNoRaw('form_token');
  }

  /**
   * Tests validation for required textfield element without title.
   *
   * Submits a test form containing a textfield form element without title.
   * The form is submitted twice, first without value for the required field
   * and then with value. Each submission is checked for relevant error
   * messages.
   *
   * @see \Drupal\form_test\Form\FormTestValidateRequiredNoTitleForm
   */
  public function testRequiredTextfieldNoTitle() {
    // Attempt to submit the form with no required field set.
    $edit = [];
    $this->drupalPostForm('form-test/validate-required-no-title', $edit, 'Submit');
    $this->assertNoRaw("The form_test_validate_required_form_no_title form was submitted successfully.", 'Validation form submitted successfully.');

    // Check the page for the error class on the textfield.
    $this->assertFieldByXPath('//input[contains(@class, "error")]', FALSE, 'Error input form element class found.');

    // Check the page for the aria-invalid attribute on the textfield.
    $this->assertFieldByXPath('//input[contains(@aria-invalid, "true")]', FALSE, 'Aria invalid attribute found.');

    // Submit again with required fields set and verify that there are no
    // error messages.
    $edit = [
      'textfield' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $this->assertNoFieldByXpath('//input[contains(@class, "error")]', FALSE, 'No error input form element class found.');
    $this->assertRaw("The form_test_validate_required_form_no_title form was submitted successfully.", 'Validation form submitted successfully.');
  }

  /**
   * Test default value handling for checkboxes.
   *
   * @see _form_test_checkbox()
   */
  public function testCheckboxProcessing() {
    // First, try to submit without the required checkbox.
    $edit = [];
    $this->drupalPostForm('form-test/checkbox', $edit, t('Submit'));
    $this->assertRaw(t('@name field is required.', ['@name' => 'required_checkbox']), 'A required checkbox is actually mandatory');

    // Now try to submit the form correctly.
    $values = Json::decode($this->drupalPostForm(NULL, ['required_checkbox' => 1], t('Submit')));
    $expected_values = [
      'disabled_checkbox_on' => 'disabled_checkbox_on',
      'disabled_checkbox_off' => '',
      'checkbox_on' => 'checkbox_on',
      'checkbox_off' => '',
      'zero_checkbox_on' => '0',
      'zero_checkbox_off' => '',
    ];
    foreach ($expected_values as $widget => $expected_value) {
      $this->assertEqual($values[$widget], $expected_value, format_string('Checkbox %widget returns expected value (expected: %expected, got: %value)', [
        '%widget' => var_export($widget, TRUE),
        '%expected' => var_export($expected_value, TRUE),
        '%value' => var_export($values[$widget], TRUE),
      ]));
    }
  }

  /**
   * Tests validation of #type 'select' elements.
   */
  public function testSelect() {
    $form = \Drupal::formBuilder()->getForm('Drupal\form_test\Form\FormTestSelectForm');
    $this->drupalGet('form-test/select');

    // Verify that the options are escaped as expected.
    $this->assertEscaped('<strong>four</strong>');
    $this->assertNoRaw('<strong>four</strong>');

    // Posting without any values should throw validation errors.
    $this->drupalPostForm(NULL, [], 'Submit');
    $no_errors = [
        'select',
        'select_required',
        'select_optional',
        'empty_value',
        'empty_value_one',
        'no_default_optional',
        'no_default_empty_option_optional',
        'no_default_empty_value_optional',
        'multiple',
        'multiple_no_default',
    ];
    foreach ($no_errors as $key) {
      $this->assertNoText(t('@name field is required.', ['@name' => $form[$key]['#title']]));
    }

    $expected_errors = [
        'no_default',
        'no_default_empty_option',
        'no_default_empty_value',
        'no_default_empty_value_one',
        'multiple_no_default_required',
    ];
    foreach ($expected_errors as $key) {
      $this->assertText(t('@name field is required.', ['@name' => $form[$key]['#title']]));
    }

    // Post values for required fields.
    $edit = [
      'no_default' => 'three',
      'no_default_empty_option' => 'three',
      'no_default_empty_value' => 'three',
      'no_default_empty_value_one' => 'three',
      'multiple_no_default_required[]' => 'three',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');
    $values = Json::decode($this->getRawContent());

    // Verify expected values.
    $expected = [
      'select' => 'one',
      'empty_value' => 'one',
      'empty_value_one' => 'one',
      'no_default' => 'three',
      'no_default_optional' => 'one',
      'no_default_optional_empty_value' => '',
      'no_default_empty_option' => 'three',
      'no_default_empty_option_optional' => '',
      'no_default_empty_value' => 'three',
      'no_default_empty_value_one' => 'three',
      'no_default_empty_value_optional' => 0,
      'multiple' => ['two' => 'two'],
      'multiple_no_default' => [],
      'multiple_no_default_required' => ['three' => 'three'],
    ];
    foreach ($expected as $key => $value) {
      $this->assertIdentical($values[$key], $value, format_string('@name: @actual is equal to @expected.', [
        '@name' => $key,
        '@actual' => var_export($values[$key], TRUE),
        '@expected' => var_export($value, TRUE),
      ]));
    }
  }

  /**
   * Tests a select element when #options is not set.
   */
  public function testEmptySelect() {
    $this->drupalGet('form-test/empty-select');
    $this->assertFieldByXPath("//select[1]", NULL, 'Select element found.');
    $this->assertNoFieldByXPath("//select[1]/option", NULL, 'No option element found.');
  }

  /**
   * Tests validation of #type 'number' and 'range' elements.
   */
  public function testNumber() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestNumberForm');

    // Array with all the error messages to be checked.
    $error_messages = [
      'no_number' => '%name must be a number.',
      'too_low' => '%name must be higher than or equal to %min.',
      'too_high' => '%name must be lower than or equal to %max.',
      'step_mismatch' => '%name is not a valid number.',
    ];

    // The expected errors.
    $expected = [
      'integer_no_number' => 'no_number',
      'integer_no_step' => 0,
      'integer_no_step_step_error' => 'step_mismatch',
      'integer_step' => 0,
      'integer_step_error' => 'step_mismatch',
      'integer_step_min' => 0,
      'integer_step_min_error' => 'too_low',
      'integer_step_max' => 0,
      'integer_step_max_error' => 'too_high',
      'integer_step_min_border' => 0,
      'integer_step_max_border' => 0,
      'integer_step_based_on_min' => 0,
      'integer_step_based_on_min_error' => 'step_mismatch',
      'float_small_step' => 0,
      'float_step_no_error' => 0,
      'float_step_error' => 'step_mismatch',
      'float_step_hard_no_error' => 0,
      'float_step_hard_error' => 'step_mismatch',
      'float_step_any_no_error' => 0,
    ];

    // First test the number element type, then range.
    foreach (['form-test/number', 'form-test/number/range'] as $path) {
      // Post form and show errors.
      $this->drupalPostForm($path, [], 'Submit');

      foreach ($expected as $element => $error) {
        // Create placeholder array.
        $placeholders = [
          '%name' => $form[$element]['#title'],
          '%min' => isset($form[$element]['#min']) ? $form[$element]['#min'] : '0',
          '%max' => isset($form[$element]['#max']) ? $form[$element]['#max'] : '0',
        ];

        foreach ($error_messages as $id => $message) {
          // Check if the error exists on the page, if the current message ID is
          // expected. Otherwise ensure that the error message is not present.
          if ($id === $error) {
            $this->assertRaw(format_string($message, $placeholders));
          }
          else {
            $this->assertNoRaw(format_string($message, $placeholders));
          }
        }
      }
    }
  }

  /**
   * Tests default value handling of #type 'range' elements.
   */
  public function testRange() {
    $values = json_decode($this->drupalPostForm('form-test/range', [], 'Submit'));
    $this->assertEqual($values->with_default_value, 18);
    $this->assertEqual($values->float, 10.5);
    $this->assertEqual($values->integer, 6);
    $this->assertEqual($values->offset, 6.9);

    $this->drupalPostForm('form-test/range/invalid', [], 'Submit');
    $this->assertFieldByXPath('//input[@type="range" and contains(@class, "error")]', NULL, 'Range element has the error class.');
  }

  /**
   * Tests validation of #type 'color' elements.
   */
  public function testColorValidation() {
    // Keys are inputs, values are expected results.
    $values = [
      '' => '#000000',
      '#000' => '#000000',
      'AAA' => '#aaaaaa',
      '#af0DEE' => '#af0dee',
      '#99ccBc' => '#99ccbc',
      '#aabbcc' => '#aabbcc',
      '123456' => '#123456',
    ];

    // Tests that valid values are properly normalized.
    foreach ($values as $input => $expected) {
      $edit = [
        'color' => $input,
      ];
      $result = json_decode($this->drupalPostForm('form-test/color', $edit, 'Submit'));
      $this->assertEqual($result->color, $expected);
    }

    // Tests invalid values are rejected.
    $values = ['#0008', '#1234', '#fffffg', '#abcdef22', '17', '#uaa'];
    foreach ($values as $input) {
      $edit = [
        'color' => $input,
      ];
      $this->drupalPostForm('form-test/color', $edit, 'Submit');
      $this->assertRaw(t('%name must be a valid color.', ['%name' => 'Color']));
    }
  }

  /**
   * Test handling of disabled elements.
   *
   * @see _form_test_disabled_elements()
   */
  public function testDisabledElements() {
    // Get the raw form in its original state.
    $form_state = new FormState();
    $form = (new FormTestDisabledElementsForm())->buildForm([], $form_state);

    // Build a submission that tries to hijack the form by submitting input for
    // elements that are disabled.
    $edit = [];
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#test_hijack_value'])) {
        if (is_array($form[$key]['#test_hijack_value'])) {
          foreach ($form[$key]['#test_hijack_value'] as $subkey => $value) {
            $edit[$key . '[' . $subkey . ']'] = $value;
          }
        }
        else {
          $edit[$key] = $form[$key]['#test_hijack_value'];
        }
      }
    }

    // Submit the form with no input, as the browser does for disabled elements,
    // and fetch the $form_state->getValues() that is passed to the submit handler.
    $this->drupalPostForm('form-test/disabled-elements', [], t('Submit'));
    $returned_values['normal'] = Json::decode($this->content);

    // Do the same with input, as could happen if JavaScript un-disables an
    // element. drupalPostForm() emulates a browser by not submitting input for
    // disabled elements, so we need to un-disable those elements first.
    $this->drupalGet('form-test/disabled-elements');
    $disabled_elements = [];
    foreach ($this->xpath('//*[@disabled]') as $element) {
      $disabled_elements[] = (string) $element['name'];
      unset($element['disabled']);
    }

    // All the elements should be marked as disabled, including the ones below
    // the disabled container.
    $actual_count = count($disabled_elements);
    $expected_count = 42;
    $this->assertEqual($actual_count, $expected_count, SafeMarkup::format('Found @actual elements with disabled property (expected @expected).', [
      '@actual' => count($disabled_elements),
      '@expected' => $expected_count,
    ]));

    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $returned_values['hijacked'] = Json::decode($this->content);

    // Ensure that the returned values match the form's default values in both
    // cases.
    foreach ($returned_values as $values) {
      $this->assertFormValuesDefault($values, $form);
    }
  }

  /**
   * Assert that the values submitted to a form matches the default values of the elements.
   */
  public function assertFormValuesDefault($values, $form) {
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#default_value'])) {
        if (isset($form[$key]['#expected_value'])) {
          $expected_value = $form[$key]['#expected_value'];
        }
        else {
          $expected_value = $form[$key]['#default_value'];
        }

        if ($key == 'checkboxes_multiple') {
          // Checkboxes values are not filtered out.
          $values[$key] = array_filter($values[$key]);
        }
        $this->assertIdentical($expected_value, $values[$key], format_string('Default value for %type: expected %expected, returned %returned.', ['%type' => $key, '%expected' => var_export($expected_value, TRUE), '%returned' => var_export($values[$key], TRUE)]));
      }

      // Recurse children.
      $this->assertFormValuesDefault($values, $form[$key]);
    }
  }

  /**
   * Verify markup for disabled form elements.
   *
   * @see _form_test_disabled_elements()
   */
  public function testDisabledMarkup() {
    $this->drupalGet('form-test/disabled-elements');
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestDisabledElementsForm');
    $type_map = [
      'textarea' => 'textarea',
      'select' => 'select',
      'weight' => 'select',
      'datetime' => 'datetime',
    ];

    foreach ($form as $name => $item) {
      // Skip special #types.
      if (!isset($item['#type']) || in_array($item['#type'], ['hidden', 'text_format'])) {
        continue;
      }
      // Setup XPath and CSS class depending on #type.
      if (in_array($item['#type'], ['button', 'submit'])) {
        $path = "//!type[contains(@class, :div-class) and @value=:value]";
        $class = 'is-disabled';
      }
      elseif (in_array($item['#type'], ['image_button'])) {
        $path = "//!type[contains(@class, :div-class) and @value=:value]";
        $class = 'is-disabled';
      }
      else {
        // starts-with() required for checkboxes.
        $path = "//div[contains(@class, :div-class)]/descendant::!type[starts-with(@name, :name)]";
        $class = 'form-disabled';
      }
      // Replace DOM element name in $path according to #type.
      $type = 'input';
      if (isset($type_map[$item['#type']])) {
        $type = $type_map[$item['#type']];
      }
      $path = strtr($path, ['!type' => $type]);
      // Verify that the element exists.
      $element = $this->xpath($path, [
        ':name' => Html::escape($name),
        ':div-class' => $class,
        ':value' => isset($item['#value']) ? $item['#value'] : '',
      ]);
      $this->assertTrue(isset($element[0]), format_string('Disabled form element class found for #type %type.', ['%type' => $item['#type']]));
    }

    // Verify special element #type text-format.
    $element = $this->xpath('//div[contains(@class, :div-class)]/descendant::textarea[@name=:name]', [
      ':name' => 'text_format[value]',
      ':div-class' => 'form-disabled',
    ]);
    $this->assertTrue(isset($element[0]), format_string('Disabled form element class found for #type %type.', ['%type' => 'text_format[value]']));
    $element = $this->xpath('//div[contains(@class, :div-class)]/descendant::select[@name=:name]', [
      ':name' => 'text_format[format]',
      ':div-class' => 'form-disabled',
    ]);
    $this->assertTrue(isset($element[0]), format_string('Disabled form element class found for #type %type.', ['%type' => 'text_format[format]']));
  }

  /**
   * Test Form API protections against input forgery.
   *
   * @see _form_test_input_forgery()
   */
  public function testInputForgery() {
    $this->drupalGet('form-test/input-forgery');
    $checkbox = $this->xpath('//input[@name="checkboxes[two]"]');
    $checkbox[0]['value'] = 'FORGERY';
    $this->drupalPostForm(NULL, ['checkboxes[one]' => TRUE, 'checkboxes[two]' => TRUE], t('Submit'));
    $this->assertText('An illegal choice has been detected.', 'Input forgery was detected.');
  }

  /**
   * Tests required attribute.
   */
  public function testRequiredAttribute() {
    $this->drupalGet('form-test/required-attribute');
    $expected = 'required';
    // Test to make sure the elements have the proper required attribute.
    foreach (['textfield', 'password'] as $type) {
      $element = $this->xpath('//input[@id=:id and @required=:expected]', [
        ':id' => 'edit-' . $type,
        ':expected' => $expected,
      ]);
      $this->assertTrue(!empty($element), format_string('The @type has the proper required attribute.', ['@type' => $type]));
    }

    // Test to make sure textarea has the proper required attribute.
    $element = $this->xpath('//textarea[@id=:id and @required=:expected]', [
      ':id' => 'edit-textarea',
      ':expected' => $expected,
    ]);
    $this->assertTrue(!empty($element), 'The textarea has the proper required attribute.');
  }

}
