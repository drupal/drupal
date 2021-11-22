<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\form_test\Form\FormTestDisabledElementsForm;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\filter\Entity\FilterFormat;
use Behat\Mink\Element\NodeElement;

/**
 * Tests various form element validation mechanisms.
 *
 * @group Form
 */
class FormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['filter', 'form_test', 'file', 'datetime'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  protected function setUp(): void {
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

    $elements['radios']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'radios', '#options' => ['' => 'None', $this->randomMachineName(), $this->randomMachineName(), $this->randomMachineName()]];
    $elements['radios']['empty_values'] = $empty_arrays;

    $elements['checkbox']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'checkbox', '#required' => TRUE];
    $elements['checkbox']['empty_values'] = $empty_checkbox;

    $elements['checkboxes']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'checkboxes', '#options' => [$this->randomMachineName(), $this->randomMachineName(), $this->randomMachineName()]];
    $elements['checkboxes']['empty_values'] = $empty_arrays;

    $elements['select']['element'] = ['#title' => $this->randomMachineName(), '#type' => 'select', '#options' => ['' => 'None', $this->randomMachineName(), $this->randomMachineName(), $this->randomMachineName()]];
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
          $form['op'] = ['#type' => 'submit', '#value' => 'Submit'];
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
          $form_output = \Drupal::service('renderer')->renderRoot($form);
          if ($required) {
            // Make sure we have a form error for this element.
            $this->assertTrue(isset($errors[$element]), "Check empty($key) '$type' field '$element'");
            if (!empty($form_output)) {
              // Make sure the form element is marked as required.
              $this->assertMatchesRegularExpression($required_marker_preg, (string) $form_output, "Required '$type' field is marked as required");
            }
          }
          else {
            if (!empty($form_output)) {
              // Make sure the form element is *not* marked as required.
              $this->assertDoesNotMatchRegularExpression($required_marker_preg, (string) $form_output, "Optional '$type' field is not marked as required");
            }
            if ($type == 'select') {
              // Select elements are going to have validation errors with empty
              // input, since those are illegal choices. Just make sure the
              // error is not "field is required".
              $this->assertTrue((empty($errors[$element]) || strpos('field is required', (string) $errors[$element]) === FALSE), "Optional '$type' field '$element' is not treated as a required element");
            }
            else {
              // Make sure there is *no* form error for this element. We're
              // not using assertEmpty() because the array key might not exist.
              $this->assertTrue(empty($errors[$element]), "Optional '$type' field '$element' has no errors with empty input");
            }
          }
        }
      }
    }
    // Clear the expected form error messages so they don't appear as exceptions.
    \Drupal::messenger()->deleteAll();
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
    $this->drupalGet('form-test/validate-required');
    $this->submitForm($edit, 'Submit');

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
        $expected[] = $form[$key]['#title'] . ' field is required.';
      }
    }

    // Check the page for error messages.
    $errors = $this->xpath('//div[contains(@class, "error")]//li');
    foreach ($errors as $error) {
      $expected_key = array_search($error->getText(), $expected);
      // If the error message is not one of the expected messages, fail.
      if ($expected_key === FALSE) {
        $this->fail(new FormattableMarkup("Unexpected error message: @error", ['@error' => $error[0]]));
      }
      // Remove the expected message from the list once it is found.
      else {
        unset($expected[$expected_key]);
      }
    }

    // Fail if any expected messages were not found.
    foreach ($expected as $not_found) {
      $this->fail(new FormattableMarkup("Found error message: @error", ['@error' => $not_found]));
    }

    // Verify that input elements are still empty.
    $this->assertSession()->fieldValueEquals('textfield', '');
    $this->assertSession()->checkboxNotChecked('edit-checkboxes-foo');
    $this->assertSession()->checkboxNotChecked('edit-checkboxes-bar');
    $this->assertTrue($this->assertSession()->optionExists('edit-select', '')->isSelected());
    $this->assertSession()->checkboxNotChecked('edit-radios-foo');
    $this->assertSession()->checkboxNotChecked('edit-radios-bar');
    $this->assertSession()->checkboxNotChecked('edit-radios-optional-foo');
    $this->assertSession()->checkboxNotChecked('edit-radios-optional-bar');
    $this->assertSession()->checkboxNotChecked('edit-radios-optional-default-value-false-foo');
    $this->assertSession()->checkboxNotChecked('edit-radios-optional-default-value-false-bar');

    // Submit again with required fields set and verify that there are no
    // error messages.
    $edit = [
      'textfield' => $this->randomString(),
      'checkboxes[foo]' => TRUE,
      'select' => 'foo',
      'radios' => 'bar',
    ];
    $this->submitForm($edit, 'Submit');
    // Verify that no error message is displayed when all required fields are
    // filled.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "error")]');
    $this->assertSession()->pageTextContains("The form_test_validate_required_form form was submitted successfully.");
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
    $this->drupalGet(Url::fromRoute('form_test.validate_required'));
    $this->assertSession()
      ->elementExists('css', 'input[name="form_token"]')
      ->setValue('invalid token');
    $random_string = $this->randomString();
    $edit = [
      'textfield' => $random_string,
      'checkboxes[bar]' => TRUE,
      'select' => 'bar',
      'radios' => 'foo',
    ];
    $this->submitForm($edit, 'Submit');
    // Verify that error message is displayed with invalid token even when
    // required fields are filled.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "error")]');

    $assert = $this->assertSession();
    $element = $assert->fieldExists('textfield');
    $this->assertEmpty($element->getValue());
    $assert->responseNotContains($random_string);
    $this->assertSession()->pageTextContains('The form has become outdated.');
    // Ensure that we don't use the posted values.
    $this->assertSession()->fieldValueEquals('textfield', '');
    $this->assertSession()->checkboxNotChecked('edit-checkboxes-foo');
    $this->assertSession()->checkboxNotChecked('edit-checkboxes-bar');
    $this->assertTrue($this->assertSession()->optionExists('edit-select', '')->isSelected());
    $this->assertSession()->checkboxNotChecked('edit-radios-foo');

    // Check another form that has a textarea input.
    $this->drupalGet(Url::fromRoute('form_test.required'));
    $this->assertSession()
      ->elementExists('css', 'input[name="form_token"]')
      ->setValue('invalid token');
    $edit = [
      'textfield' => $this->randomString(),
      'textarea' => $this->randomString() . "\n",
    ];
    $this->submitForm($edit, 'Submit');
    // Verify that the error message is displayed with invalid token even when
    // required fields are filled.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "error")]');
    $this->assertSession()->pageTextContains('The form has become outdated.');
    $this->assertSession()->fieldValueEquals('textfield', '');
    $this->assertSession()->fieldValueEquals('textarea', '');

    // Check another form that has a number input.
    $this->drupalGet(Url::fromRoute('form_test.number'));
    $this->assertSession()
      ->elementExists('css', 'input[name="form_token"]')
      ->setValue('invalid token');
    $edit = [
      // We choose a random value which is higher than the default value,
      // so we don't accidentally generate the default value.
      'integer_step' => mt_rand(6, 100),
    ];
    $this->submitForm($edit, 'Submit');
    // Verify that the error message is displayed with invalid token even when
    // required fields are filled.'
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "error")]');
    $this->assertSession()->pageTextContains('The form has become outdated.');
    $this->assertSession()->fieldValueEquals('integer_step', 5);

    // Check a form with a Url field
    $this->drupalGet(Url::fromRoute('form_test.url'));
    $this->assertSession()
      ->elementExists('css', 'input[name="form_token"]')
      ->setValue('invalid token');
    $edit = [
      'url' => $this->randomString(),
    ];
    $this->submitForm($edit, 'Submit');
    // Verify that the error message is displayed with invalid token even when
    // required fields are filled.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "error")]');
    $this->assertSession()->pageTextContains('The form has become outdated.');
    $this->assertSession()->fieldValueEquals('url', '');
  }

  /**
   * CSRF tokens for GET forms should not be added by default.
   */
  public function testGetFormsCsrfToken() {
    // We need to be logged in to have CSRF tokens.
    $account = $this->createUser();
    $this->drupalLogin($account);

    $this->drupalGet(Url::fromRoute('form_test.get_form'));
    $this->assertSession()->responseNotContains('form_token');
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
    $this->drupalGet('form-test/validate-required-no-title');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextNotContains("The form_test_validate_required_form_no_title form was submitted successfully.");

    // Check the page for the error class on the textfield.
    $this->assertSession()->elementExists('xpath', '//input[contains(@class, "error")]');

    // Check the page for the aria-invalid attribute on the textfield.
    $this->assertSession()->elementExists('xpath', '//input[contains(@aria-invalid, "true")]');

    // Submit again with required fields set and verify that there are no
    // error messages.
    $edit = [
      'textfield' => $this->randomString(),
    ];
    $this->submitForm($edit, 'Submit');
    // Verify that no error input form element class is present.
    $this->assertSession()->elementNotExists('xpath', '//input[contains(@class, "error")]');
    $this->assertSession()->pageTextContains("The form_test_validate_required_form_no_title form was submitted successfully.");
  }

  /**
   * Tests default value handling for checkboxes.
   *
   * @see _form_test_checkbox()
   */
  public function testCheckboxProcessing() {
    // First, try to submit without the required checkbox.
    $edit = [];
    $this->drupalGet('form-test/checkbox');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains("required_checkbox field is required.");

    // Now try to submit the form correctly.
    $this->submitForm(['required_checkbox' => 1], 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());
    $expected_values = [
      'disabled_checkbox_on' => 'disabled_checkbox_on',
      'disabled_checkbox_off' => 0,
      'checkbox_on' => 'checkbox_on',
      'checkbox_off' => 0,
      'zero_checkbox_on' => '0',
      'zero_checkbox_off' => 0,
    ];
    foreach ($expected_values as $widget => $expected_value) {
      $this->assertSame($values[$widget], $expected_value, new FormattableMarkup('Checkbox %widget returns expected value (expected: %expected, got: %value)', [
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
    $this->assertSession()->assertEscaped('<strong>four</strong>');
    $this->assertSession()->responseNotContains('<strong>four</strong>');

    // Posting without any values should throw validation errors.
    $this->submitForm([], 'Submit');
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
      $this->assertSession()->pageTextNotContains($form[$key]['#title'] . ' field is required.');
    }

    $expected_errors = [
        'no_default',
        'no_default_empty_option',
        'no_default_empty_value',
        'no_default_empty_value_one',
        'multiple_no_default_required',
    ];
    foreach ($expected_errors as $key) {
      $this->assertSession()->pageTextContains($form[$key]['#title'] . ' field is required.');
    }

    // Post values for required fields.
    $edit = [
      'no_default' => 'three',
      'no_default_empty_option' => 'three',
      'no_default_empty_value' => 'three',
      'no_default_empty_value_one' => 'three',
      'multiple_no_default_required[]' => 'three',
    ];
    $this->submitForm($edit, 'Submit');
    $values = Json::decode($this->getSession()->getPage()->getContent());

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
      $this->assertSame($value, $values[$key], new FormattableMarkup('@name: @actual is equal to @expected.', ['@name' => $key, '@actual' => var_export($values[$key], TRUE), '@expected' => var_export($value, TRUE)]));
    }
  }

  /**
   * Tests a select element when #options is not set.
   */
  public function testEmptySelect() {
    $this->drupalGet('form-test/empty-select');
    $this->assertSession()->elementExists('xpath', "//select[1]");
    $this->assertSession()->elementNotExists('xpath', "//select[1]/option");
  }

  /**
   * Tests sorting and not sorting of options in a select element.
   */
  public function testSelectSorting() {
    $this->drupalGet('form-test/select');

    // Verify the order of the select options.
    $this->validateSelectSorting('unsorted', [
      'uso_first_element',
      'uso_second',
      'uso_zzgroup',
      'uso_gc',
      'uso_ga',
      'uso_gb',
      'uso_yygroup',
      'uso_ge',
      'uso_gd',
      'uso_gf',
      'uso_xxgroup',
      'uso_gz',
      'uso_gi',
      'uso_gh',
      'uso_d',
      'uso_c',
      'uso_b',
      'uso_a',
    ]);

    $this->validateSelectSorting('sorted', [
      'sso_a',
      'sso_d',
      'sso_first_element',
      'sso_b',
      'sso_c',
      'sso_second',
      'sso_xxgroup',
      'sso_gz',
      'sso_gh',
      'sso_gi',
      'sso_yygroup',
      'sso_ge',
      'sso_gd',
      'sso_gf',
      'sso_zzgroup',
      'sso_ga',
      'sso_gb',
      'sso_gc',
    ]);

    $this->validateSelectSorting('sorted_none', [
      'sno_empty',
      'sno_first_element',
      'sno_second',
      'sno_zzgroup',
      'sno_ga',
      'sno_gb',
      'sno_gc',
      'sno_a',
      'sno_d',
      'sno_b',
      'sno_c',
      'sno_xxgroup',
      'sno_gz',
      'sno_gi',
      'sno_gh',
      'sno_yygroup',
      'sno_ge',
      'sno_gd',
      'sno_gf',
    ]);

    $this->validateSelectSorting('sorted_none_nostart', [
      'snn_empty',
      'snn_a',
      'snn_d',
      'snn_first_element',
      'snn_b',
      'snn_c',
      'snn_second',
      'snn_xxgroup',
      'snn_gz',
      'snn_gi',
      'snn_gh',
      'snn_yygroup',
      'snn_ge',
      'snn_gd',
      'snn_gf',
      'snn_zzgroup',
      'snn_ga',
      'snn_gb',
      'snn_gc',
    ]);

    // Verify that #sort_order and #sort_start are not in the page.
    $this->assertSession()->responseNotContains('#sort_order');
    $this->assertSession()->responseNotContains('#sort_start');
  }

  /**
   * Validates that the options are in the right order in a select.
   *
   * @param string $select
   *   Name of the select to verify.
   * @param string[] $order
   *   Expected order of its options.
   */
  protected function validateSelectSorting($select, array $order) {
    $option_map_function = function (NodeElement $node) {
      return ($node->getTagName() === 'optgroup') ?
        $node->getAttribute('label') : $node->getValue();
    };
    $option_nodes = $this->getSession()
      ->getPage()
      ->findField($select)
      ->findAll('css', 'option, optgroup');

    $options = array_map($option_map_function, $option_nodes);
    $this->assertSame($order, $options);
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
      $this->drupalGet($path);
      $this->submitForm([], 'Submit');

      foreach ($expected as $element => $error) {
        // Create placeholder array.
        $placeholders = [
          '%name' => $form[$element]['#title'],
          '%min' => $form[$element]['#min'] ?? '0',
          '%max' => $form[$element]['#max'] ?? '0',
        ];

        foreach ($error_messages as $id => $message) {
          // Check if the error exists on the page, if the current message ID is
          // expected. Otherwise ensure that the error message is not present.
          if ($id === $error) {
            $this->assertSession()->responseContains(new FormattableMarkup($message, $placeholders));
          }
          else {
            $this->assertSession()->responseNotContains(new FormattableMarkup($message, $placeholders));
          }
        }
      }
    }
  }

  /**
   * Tests default value handling of #type 'range' elements.
   */
  public function testRange() {
    $this->drupalGet('form-test/range');
    $this->submitForm([], 'Submit');
    $values = json_decode($this->getSession()->getPage()->getContent());
    $this->assertEquals(18, $values->with_default_value);
    $this->assertEquals(10.5, $values->float);
    $this->assertEquals(6, $values->integer);
    $this->assertEquals(6.9, $values->offset);

    $this->drupalGet('form-test/range/invalid');
    $this->submitForm([], 'Submit');
    // Verify that the 'range' element has the error class.
    $this->assertSession()->elementExists('xpath', '//input[@type="range" and contains(@class, "error")]');
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
      $this->drupalGet('form-test/color');
      $this->submitForm($edit, 'Submit');
      $result = json_decode($this->getSession()->getPage()->getContent());
      $this->assertEquals($expected, $result->color);
    }

    // Tests invalid values are rejected.
    $values = ['#0008', '#1234', '#fffffg', '#abcdef22', '17', '#uaa'];
    foreach ($values as $input) {
      $edit = [
        'color' => $input,
      ];
      $this->drupalGet('form-test/color');
      $this->submitForm($edit, 'Submit');
      $this->assertSession()->pageTextContains("Color must be a valid color.");
    }
  }

  /**
   * Tests handling of disabled elements.
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
    $this->drupalGet('form-test/disabled-elements');
    $this->submitForm([], 'Submit');
    $returned_values['normal'] = Json::decode($this->getSession()->getPage()->getContent());

    // Do the same with input, as could happen if JavaScript un-disables an
    // element. submitForm() emulates a browser by not submitting input for
    // disabled elements, so we need to un-disable those elements first.
    $this->drupalGet('form-test/disabled-elements');
    $disabled_elements = [];
    foreach ($this->xpath('//*[@disabled]') as $element) {
      $disabled_elements[] = (string) $element->getAttribute('name');
    }

    // All the elements should be marked as disabled, including the ones below
    // the disabled container.
    $actual_count = count($disabled_elements);
    $expected_count = 42;
    $this->assertEquals($expected_count, $actual_count, new FormattableMarkup('Found @actual elements with disabled property (expected @expected).', ['@actual' => count($disabled_elements), '@expected' => $expected_count]));

    // Mink does not "see" hidden elements, so we need to set the value of the
    // hidden element directly.
    $this->assertSession()
      ->elementExists('css', 'input[name="hidden"]')
      ->setValue($edit['hidden']);
    unset($edit['hidden']);
    $this->submitForm($edit, 'Submit');
    $returned_values['hijacked'] = Json::decode($this->getSession()->getPage()->getContent());

    // Ensure that the returned values match the form's default values in both
    // cases.
    foreach ($returned_values as $values) {
      $this->assertFormValuesDefault($values, $form);
    }
  }

  /**
   * Assert that the values submitted to a form matches the default values of the elements.
   *
   * @internal
   */
  public function assertFormValuesDefault(array $values, array $form): void {
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
        $this->assertSame($expected_value, $values[$key], new FormattableMarkup('Default value for %type: expected %expected, returned %returned.', ['%type' => $key, '%expected' => var_export($expected_value, TRUE), '%returned' => var_export($values[$key], TRUE)]));
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
      if (isset($item['#value']) && is_object($item['#value'])) {
        $item['#value'] = (string) $item['#value'];
      }
      $path = strtr($path, ['!type' => $type]);
      // Verify that the element exists.
      $element = $this->xpath($path, [
        ':name' => Html::escape($name),
        ':div-class' => $class,
        ':value' => $item['#value'] ?? '',
      ]);
      $this->assertTrue(isset($element[0]), new FormattableMarkup('Disabled form element class found for #type %type.', ['%type' => $item['#type']]));
    }

    // Verify special element #type text-format.
    $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'form-disabled')]/descendant::textarea[@name='text_format[value]']");
    $this->assertSession()->elementExists('xpath', "//div[contains(@class, 'form-disabled')]/descendant::select[@name='text_format[format]']");
  }

  /**
   * Tests Form API protections against input forgery.
   *
   * @see \Drupal\form_test\Form\FormTestInputForgeryForm
   */
  public function testInputForgery() {
    $this->drupalGet('form-test/input-forgery');
    // The value for checkboxes[two] was changed using post render to simulate
    // an input forgery.
    // @see \Drupal\form_test\Form\FormTestInputForgeryForm::postRender
    $this->submitForm(['checkboxes[one]' => TRUE, 'checkboxes[two]' => TRUE], 'Submit');
    $this->assertSession()->pageTextContains('An illegal choice has been detected.');
  }

  /**
   * Tests required attribute.
   */
  public function testRequiredAttribute() {
    $this->drupalGet('form-test/required-attribute');
    foreach (['textfield', 'password', 'textarea'] as $type) {
      $field = $this->assertSession()->fieldExists("edit-$type");
      $this->assertSame('required', $field->getAttribute('required'), "The $type has the proper required attribute.");
    }
  }

}
