<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\Element;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests form processing and alteration via form validation handlers.
 *
 * @group Form
 */
class ValidationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests #element_validate and #validate.
   */
  public function testValidate() {
    $this->drupalGet('form-test/validate');
    // Verify that #element_validate handlers can alter the form and submitted
    // form values.
    $edit = [
      'name' => 'element_validate',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertFieldByName('name', '#value changed by #element_validate', 'Form element #value was altered.');
    $this->assertText('Name value: value changed by setValueForElement() in #element_validate', 'Form element value in $form_state was altered.');

    // Verify that #validate handlers can alter the form and submitted
    // form values.
    $edit = [
      'name' => 'validate',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertFieldByName('name', '#value changed by #validate', 'Form element #value was altered.');
    $this->assertText('Name value: value changed by setValueForElement() in #validate', 'Form element value in $form_state was altered.');

    // Verify that #element_validate handlers can make form elements
    // inaccessible, but values persist.
    $edit = [
      'name' => 'element_validate_access',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertNoFieldByName('name', 'Form element was hidden.');
    $this->assertText('Name value: element_validate_access', 'Value for inaccessible form element exists.');

    // Verify that value for inaccessible form element persists.
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertNoFieldByName('name', 'Form element was hidden.');
    $this->assertText('Name value: element_validate_access', 'Value for inaccessible form element exists.');

    // Verify that #validate handlers don't run if the CSRF token is invalid.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('form-test/validate');
    // $this->assertSession()->fieldExists() does not recognize hidden fields,
    // which breaks $this->drupalPostForm() if we try to change the value of a
    // hidden field such as form_token.
    $this->assertSession()
      ->elementExists('css', 'input[name="form_token"]')
      ->setValue('invalid_token');
    $this->drupalPostForm(NULL, ['name' => 'validate'], 'Save');
    $this->assertNoFieldByName('name', '#value changed by #validate', 'Form element #value was not altered.');
    $this->assertNoText('Name value: value changed by setValueForElement() in #validate', 'Form element value in $form_state was not altered.');
    $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
  }

  /**
   * Tests that a form with a disabled CSRF token can be validated.
   */
  public function testDisabledToken() {
    $this->drupalPostForm('form-test/validate-no-token', [], 'Save');
    $this->assertText('The form_test_validate_no_token form has been submitted successfully.');
  }

  /**
   * Tests partial form validation through #limit_validation_errors.
   */
  public function testValidateLimitErrors() {
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
      $element = $this->xpath('//input[@id=:id and @formnovalidate=:expected]', [
        ':id' => 'edit-' . $type,
        ':expected' => $expected,
      ]);
      $this->assertTrue(!empty($element), new FormattableMarkup('The @type button has the proper formnovalidate attribute.', ['@type' => $type]));
    }
    // The button with full server-side validation should not have the
    // 'formnovalidate' attribute.
    $element = $this->xpath('//input[@id=:id and not(@formnovalidate)]', [
      ':id' => 'edit-full',
    ]);
    $this->assertTrue(!empty($element), 'The button with full server-side validation does not have the formnovalidate attribute.');

    // Submit the form by pressing the 'Partial validate' button (uses
    // #limit_validation_errors) and ensure that the title field is not
    // validated, but the #element_validate handler for the 'test' field
    // is triggered.
    $this->drupalPostForm($path, $edit, t('Partial validate'));
    $this->assertNoText(t('@name field is required.', ['@name' => 'Title']));
    $this->assertText('Test element is invalid');

    // Edge case of #limit_validation_errors containing numeric indexes: same
    // thing with the 'Partial validate (numeric index)' button and the
    // 'test_numeric_index' field.
    $this->drupalPostForm($path, $edit, t('Partial validate (numeric index)'));
    $this->assertNoText(t('@name field is required.', ['@name' => 'Title']));
    $this->assertText('Test (numeric index) element is invalid');

    // Ensure something like 'foobar' isn't considered "inside" 'foo'.
    $this->drupalPostForm($path, $edit, t('Partial validate (substring)'));
    $this->assertNoText(t('@name field is required.', ['@name' => 'Title']));
    $this->assertText('Test (substring) foo element is invalid');

    // Ensure not validated values are not available to submit handlers.
    $this->drupalPostForm($path, ['title' => '', 'test' => 'valid'], t('Partial validate'));
    $this->assertText('Only validated values appear in the form values.');

    // Now test full form validation and ensure that the #element_validate
    // handler is still triggered.
    $this->drupalPostForm($path, $edit, t('Full validate'));
    $this->assertText(t('@name field is required.', ['@name' => 'Title']));
    $this->assertText('Test element is invalid');
  }

  /**
   * Tests #pattern validation.
   */
  public function testPatternValidation() {
    $textfield_error = t('%name field is not in the right format.', ['%name' => 'One digit followed by lowercase letters']);
    $tel_error = t('%name field is not in the right format.', ['%name' => 'Everything except numbers']);
    $password_error = t('%name field is not in the right format.', ['%name' => 'Password']);

    // Invalid textfield, valid tel.
    $edit = [
      'textfield' => 'invalid',
      'tel' => 'valid',
    ];
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Valid textfield, invalid tel, valid password.
    $edit = [
      'textfield' => '7seven',
      'tel' => '818937',
      'password' => '0100110',
    ];
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Non required fields are not validated if empty.
    $edit = [
      'textfield' => '',
      'tel' => '',
    ];
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Invalid password.
    $edit = [
      'password' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertRaw($password_error);

    // The pattern attribute overrides #pattern and is not validated on the
    // server side.
    $edit = [
      'textfield' => '',
      'tel' => '',
      'url' => 'http://www.example.com/',
    ];
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw(t('%name field is not in the right format.', ['%name' => 'Client side validation']));
  }

  /**
   * Tests #required with custom validation errors.
   *
   * @see \Drupal\form_test\Form\FormTestValidateRequiredForm
   */
  public function testCustomRequiredError() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestValidateRequiredForm');

    // Verify that a custom #required error can be set.
    $edit = [];
    $this->drupalPostForm('form-test/validate-required', $edit, 'Submit');

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#required_error'])) {
        $this->assertNoText(t('@name field is required.', ['@name' => $form[$key]['#title']]));
        $this->assertText($form[$key]['#required_error']);
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $this->assertNoText(t('@name field is required.', ['@name' => $form[$key]['#title']]));
        $this->assertText($form[$key]['#form_test_required_error']);
      }
    }
    $this->assertNoText(t('An illegal choice has been detected. Please contact the site administrator.'));

    // Verify that no custom validation error appears with valid values.
    $edit = [
      'textfield' => $this->randomString(),
      'checkboxes[foo]' => TRUE,
      'select' => 'foo',
    ];
    $this->drupalPostForm('form-test/validate-required', $edit, 'Submit');

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#required_error'])) {
        $this->assertNoText(t('@name field is required.', ['@name' => $form[$key]['#title']]));
        $this->assertNoText($form[$key]['#required_error']);
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $this->assertNoText(t('@name field is required.', ['@name' => $form[$key]['#title']]));
        $this->assertNoText($form[$key]['#form_test_required_error']);
      }
    }
    $this->assertNoText(t('An illegal choice has been detected. Please contact the site administrator.'));
  }

}
