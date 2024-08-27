<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Core\Render\Element;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests form processing and alteration via form validation handlers.
 *
 * @group Form
 * @group #slow
 */
class ValidationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests #element_validate and #validate.
   */
  public function testValidate(): void {
    $this->drupalGet('form-test/validate');
    // Verify that #element_validate handlers can alter the form and submitted
    // form values.
    $edit = [
      'name' => 'element_validate',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->fieldValueEquals('name', '#value changed by #element_validate');
    $this->assertSession()->pageTextContains('Name value: value changed by setValueForElement() in #element_validate');

    // Verify that #validate handlers can alter the form and submitted
    // form values.
    $edit = [
      'name' => 'validate',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->fieldValueEquals('name', '#value changed by #validate');
    $this->assertSession()->pageTextContains('Name value: value changed by setValueForElement() in #validate');

    // Verify that #element_validate handlers can make form elements
    // inaccessible, but values persist.
    $edit = [
      'name' => 'element_validate_access',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->fieldNotExists('name');
    $this->assertSession()->pageTextContains('Name value: element_validate_access');

    // Verify that value for inaccessible form element persists.
    $this->submitForm([], 'Save');
    $this->assertSession()->fieldValueNotEquals('name', 'Form element was hidden.');
    $this->assertSession()->pageTextContains('Name value: element_validate_access');

    // Verify that #validate handlers don't run if the CSRF token is invalid.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('form-test/validate');
    // $this->assertSession()->fieldExists() does not recognize hidden fields,
    // which breaks $this->submitForm() if we try to change the value of a
    // hidden field such as form_token.
    $this->assertSession()
      ->elementExists('css', 'input[name="form_token"]')
      ->setValue('invalid_token');
    $this->submitForm(['name' => 'validate'], 'Save');
    $this->assertSession()->fieldValueNotEquals('name', '#value changed by #validate');
    $this->assertSession()->pageTextNotContains('Name value: value changed by setValueForElement() in #validate');
    $this->assertSession()->pageTextContains('The form has become outdated.');
  }

  /**
   * Tests that a form with a disabled CSRF token can be validated.
   */
  public function testDisabledToken(): void {
    $this->drupalGet('form-test/validate-no-token');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('The form_test_validate_no_token form has been submitted successfully.');
  }

  /**
   * Tests partial form validation through #limit_validation_errors.
   */
  public function testValidateLimitErrors(): void {
    $edit = [
      'test' => 'invalid',
      'test_numeric_index[0]' => 'invalid',
      'test_substring[foo]' => 'invalid',
    ];
    $path = 'form-test/limit-validation-errors';

    // Render the form, and verify that the buttons with limited server-side
    // validation have the proper 'formnovalidate' attribute (to prevent
    // client-side validation by the browser).
    $this->drupalGet($path);
    $expected = 'formnovalidate';
    foreach (['partial', 'partial-numeric-index', 'substring'] as $type) {
      // Verify the $type button has the proper formnovalidate attribute.
      $this->assertSession()->elementExists('xpath', "//input[@id='edit-$type' and @formnovalidate='$expected']");
    }
    // The button with full server-side validation should not have the
    // 'formnovalidate' attribute.
    $this->assertSession()->elementExists('xpath', "//input[@id='edit-full' and not(@formnovalidate)]");

    // Submit the form by pressing the 'Partial validate' button (uses
    // #limit_validation_errors) and ensure that the title field is not
    // validated, but the #element_validate handler for the 'test' field
    // is triggered.
    $this->drupalGet($path);
    $this->submitForm($edit, 'Partial validate');
    $this->assertSession()->pageTextNotContains('Title field is required.');
    $this->assertSession()->pageTextContains('Test element is invalid');

    // Edge case of #limit_validation_errors containing numeric indexes: same
    // thing with the 'Partial validate (numeric index)' button and the
    // 'test_numeric_index' field.
    $this->drupalGet($path);
    $this->submitForm($edit, 'Partial validate (numeric index)');
    $this->assertSession()->pageTextNotContains('Title field is required.');
    $this->assertSession()->pageTextContains('Test (numeric index) element is invalid');

    // Ensure something like 'foobar' isn't considered "inside" 'foo'.
    $this->drupalGet($path);
    $this->submitForm($edit, 'Partial validate (substring)');
    $this->assertSession()->pageTextNotContains('Title field is required.');
    $this->assertSession()->pageTextContains('Test (substring) foo element is invalid');

    // Ensure not validated values are not available to submit handlers.
    $this->drupalGet($path);
    $this->submitForm([
      'title' => '',
      'test' => 'valid',
    ], 'Partial validate');
    $this->assertSession()->pageTextContains('Only validated values appear in the form values.');

    // Now test full form validation and ensure that the #element_validate
    // handler is still triggered.
    $this->drupalGet($path);
    $this->submitForm($edit, 'Full validate');
    $this->assertSession()->pageTextContains('Title field is required.');
    $this->assertSession()->pageTextContains('Test element is invalid');
  }

  /**
   * Tests #pattern validation.
   */
  public function testPatternValidation(): void {
    $textfield_error = 'One digit followed by lowercase letters field is not in the right format.';
    $tel_error = 'Everything except numbers field is not in the right format.';
    $password_error = 'Password field is not in the right format.';

    // Invalid textfield, valid tel.
    $edit = [
      'textfield' => 'invalid',
      'tel' => 'valid',
    ];
    $this->drupalGet('form-test/pattern');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains($textfield_error);
    $this->assertSession()->pageTextNotContains($tel_error);
    $this->assertSession()->pageTextNotContains($password_error);

    // Valid textfield, invalid tel, valid password.
    $edit = [
      'textfield' => '7seven',
      'tel' => '818937',
      'password' => '0100110',
    ];
    $this->drupalGet('form-test/pattern');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextNotContains($textfield_error);
    $this->assertSession()->pageTextContains($tel_error);
    $this->assertSession()->pageTextNotContains($password_error);

    // Non required fields are not validated if empty.
    $edit = [
      'textfield' => '',
      'tel' => '',
    ];
    $this->drupalGet('form-test/pattern');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextNotContains($textfield_error);
    $this->assertSession()->pageTextNotContains($tel_error);
    $this->assertSession()->pageTextNotContains($password_error);

    // Invalid password.
    $edit = [
      'password' => $this->randomMachineName(),
    ];
    $this->drupalGet('form-test/pattern');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextNotContains($textfield_error);
    $this->assertSession()->pageTextNotContains($tel_error);
    $this->assertSession()->pageTextContains($password_error);

    // The pattern attribute overrides #pattern and is not validated on the
    // server side.
    $edit = [
      'textfield' => '',
      'tel' => '',
      'url' => 'http://www.example.com/',
    ];
    $this->drupalGet('form-test/pattern');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextNotContains('Client side validation field is not in the right format.');
  }

  /**
   * Tests #required with custom validation errors.
   *
   * @see \Drupal\form_test\Form\FormTestValidateRequiredForm
   */
  public function testCustomRequiredError(): void {
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestValidateRequiredForm');

    // Verify that a custom #required error can be set.
    $edit = [];
    $this->drupalGet('form-test/validate-required');
    $this->submitForm($edit, 'Submit');

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#required_error'])) {
        $this->assertSession()->pageTextNotContains($form[$key]['#title'] . ' field is required.');
        $this->assertSession()->pageTextContains((string) $form[$key]['#required_error']);
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $this->assertSession()->pageTextNotContains($form[$key]['#title'] . ' field is required.');
        $this->assertSession()->pageTextContains((string) $form[$key]['#form_test_required_error']);
      }
      if (isset($form[$key]['#title'])) {
        $this->assertSession()->pageTextNotContains('The submitted value in the ' . $form[$key]['#title'] . ' element is not allowed.');
      }
    }

    // Verify that no custom validation error appears with valid values.
    $edit = [
      'textfield' => $this->randomString(),
      'checkboxes[foo]' => TRUE,
      'select' => 'foo',
    ];
    $this->drupalGet('form-test/validate-required');
    $this->submitForm($edit, 'Submit');

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#required_error'])) {
        $this->assertSession()->pageTextNotContains($form[$key]['#title'] . ' field is required.');
        $this->assertSession()->pageTextNotContains((string) $form[$key]['#required_error']);
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $this->assertSession()->pageTextNotContains($form[$key]['#title'] . ' field is required.');
        $this->assertSession()->pageTextNotContains((string) $form[$key]['#form_test_required_error']);
      }
      if (isset($form[$key]['#title'])) {
        $this->assertSession()->pageTextNotContains('The submitted value in the ' . $form[$key]['#title'] . ' element is not allowed.');
      }
    }
  }

}
