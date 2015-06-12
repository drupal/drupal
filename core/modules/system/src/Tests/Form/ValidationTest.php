<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\ValidationTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests form processing and alteration via form validation handlers.
 *
 * @group Form
 */
class ValidationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  /**
   * Tests #element_validate and #validate.
   */
  function testValidate() {
    $this->drupalGet('form-test/validate');
    // Verify that #element_validate handlers can alter the form and submitted
    // form values.
    $edit = array(
      'name' => 'element_validate',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertFieldByName('name', '#value changed by #element_validate', 'Form element #value was altered.');
    $this->assertText('Name value: value changed by setValueForElement() in #element_validate', 'Form element value in $form_state was altered.');

    // Verify that #validate handlers can alter the form and submitted
    // form values.
    $edit = array(
      'name' => 'validate',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertFieldByName('name', '#value changed by #validate', 'Form element #value was altered.');
    $this->assertText('Name value: value changed by setValueForElement() in #validate', 'Form element value in $form_state was altered.');

    // Verify that #element_validate handlers can make form elements
    // inaccessible, but values persist.
    $edit = array(
      'name' => 'element_validate_access',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertNoFieldByName('name', 'Form element was hidden.');
    $this->assertText('Name value: element_validate_access', 'Value for inaccessible form element exists.');

    // Verify that value for inaccessible form element persists.
    $this->drupalPostForm(NULL, array(), 'Save');
    $this->assertNoFieldByName('name', 'Form element was hidden.');
    $this->assertText('Name value: element_validate_access', 'Value for inaccessible form element exists.');

    // Verify that #validate handlers don't run if the CSRF token is invalid.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('form-test/validate');
    $edit = array(
      'name' => 'validate',
      'form_token' => 'invalid token'
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertNoFieldByName('name', '#value changed by #validate', 'Form element #value was not altered.');
    $this->assertNoText('Name value: value changed by setValueForElement() in #validate', 'Form element value in $form_state was not altered.');
    $this->assertText('The form has become outdated. Copy any unsaved work in the form below');
  }

  /**
   * Tests partial form validation through #limit_validation_errors.
   */
  function testValidateLimitErrors() {
    $edit = array(
      'test' => 'invalid',
      'test_numeric_index[0]' => 'invalid',
      'test_substring[foo]' => 'invalid',
    );
    $path = 'form-test/limit-validation-errors';

    // Render the form, and verify that the buttons with limited server-side
    // validation have the proper 'formnovalidate' attribute (to prevent
    // client-side validation by the browser).
    $this->drupalGet($path);
    $expected = 'formnovalidate';
    foreach (array('partial', 'partial-numeric-index', 'substring') as $type) {
      $element = $this->xpath('//input[@id=:id and @formnovalidate=:expected]', array(
        ':id' => 'edit-' . $type,
        ':expected' => $expected,
      ));
      $this->assertTrue(!empty($element), format_string('The @type button has the proper formnovalidate attribute.', array('@type' => $type)));
    }
    // The button with full server-side validation should not have the
    // 'formnovalidate' attribute.
    $element = $this->xpath('//input[@id=:id and not(@formnovalidate)]', array(
      ':id' => 'edit-full',
    ));
    $this->assertTrue(!empty($element), 'The button with full server-side validation does not have the formnovalidate attribute.');

    // Submit the form by pressing the 'Partial validate' button (uses
    // #limit_validation_errors) and ensure that the title field is not
    // validated, but the #element_validate handler for the 'test' field
    // is triggered.
    $this->drupalPostForm($path, $edit, t('Partial validate'));
    $this->assertNoText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test element is invalid');

    // Edge case of #limit_validation_errors containing numeric indexes: same
    // thing with the 'Partial validate (numeric index)' button and the
    // 'test_numeric_index' field.
    $this->drupalPostForm($path, $edit, t('Partial validate (numeric index)'));
    $this->assertNoText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test (numeric index) element is invalid');

    // Ensure something like 'foobar' isn't considered "inside" 'foo'.
    $this->drupalPostForm($path, $edit, t('Partial validate (substring)'));
    $this->assertNoText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test (substring) foo element is invalid');

    // Ensure not validated values are not available to submit handlers.
    $this->drupalPostForm($path, array('title' => '', 'test' => 'valid'), t('Partial validate'));
    $this->assertText('Only validated values appear in the form values.');

    // Now test full form validation and ensure that the #element_validate
    // handler is still triggered.
    $this->drupalPostForm($path, $edit, t('Full validate'));
    $this->assertText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test element is invalid');
  }

  /**
   * Tests #pattern validation.
   */
  function testPatternValidation() {
    $textfield_error = t('%name field is not in the right format.', array('%name' => 'One digit followed by lowercase letters'));
    $tel_error = t('%name field is not in the right format.', array('%name' => 'Everything except numbers'));
    $password_error = t('%name field is not in the right format.', array('%name' => 'Password'));

    // Invalid textfield, valid tel.
    $edit = array(
      'textfield' => 'invalid',
      'tel' => 'valid',
    );
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Valid textfield, invalid tel, valid password.
    $edit = array(
      'textfield' => '7seven',
      'tel' => '818937',
      'password' => '0100110',
    );
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Non required fields are not validated if empty.
    $edit = array(
      'textfield' => '',
      'tel' => '',
    );
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Invalid password.
    $edit = array(
      'password' => $this->randomMachineName(),
    );
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertRaw($password_error);

    // The pattern attribute overrides #pattern and is not validated on the
    // server side.
    $edit = array(
      'textfield' => '',
      'tel' => '',
      'url' => 'http://www.example.com/',
    );
    $this->drupalPostForm('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw(t('%name field is not in the right format.', array('%name' => 'Client side validation')));
  }

  /**
   * Tests #required with custom validation errors.
   *
   * @see \Drupal\form_test\Form\FormTestValidateRequiredForm
   */
  function testCustomRequiredError() {
    $form = \Drupal::formBuilder()->getForm('\Drupal\form_test\Form\FormTestValidateRequiredForm');

    // Verify that a custom #required error can be set.
    $edit = array();
    $this->drupalPostForm('form-test/validate-required', $edit, 'Submit');

    $messages = [];
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#required_error'])) {
        $this->assertNoText(t('!name field is required.', array('!name' => $form[$key]['#title'])));
        $messages[] = [
          'title' => $form[$key]['#title'],
          'message' => $form[$key]['#required_error'],
          'key' => $key,
        ];
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $this->assertNoText(t('!name field is required.', array('!name' => $form[$key]['#title'])));
        $messages[] = [
          'title' => $form[$key]['#title'],
          'message' => $form[$key]['#form_test_required_error'],
          'key' => $key,
        ];
      }
      elseif (!empty($form[$key]['#required'])) {
        $messages[] = [
          'title' => $form[$key]['#title'],
          'message' => t('!name field is required.', ['!name' => $form[$key]['#title']]),
          'key' => $key,
        ];
      }
    }
    $this->assertErrorMessages($messages);

    // Verify that no custom validation error appears with valid values.
    $edit = array(
      'textfield' => $this->randomString(),
      'checkboxes[foo]' => TRUE,
      'select' => 'foo',
    );
    $this->drupalPostForm('form-test/validate-required', $edit, 'Submit');

    $messages = [];
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#required_error'])) {
        $this->assertNoText(t('!name field is required.', array('!name' => $form[$key]['#title'])));
        $this->assertNoText($form[$key]['#required_error']);
      }
      elseif (isset($form[$key]['#form_test_required_error'])) {
        $this->assertNoText(t('!name field is required.', array('!name' => $form[$key]['#title'])));
        $this->assertNoText($form[$key]['#form_test_required_error']);
      }
      elseif (!empty($form[$key]['#required'])) {
        $messages[] = [
          'title' => $form[$key]['#title'],
          'message' => t('!name field is required.', ['!name' => $form[$key]['#title']]),
          'key' => $key,
        ];
      }
    }
    $this->assertErrorMessages($messages);
  }

  /**
   * Asserts that the given error messages are displayed.
   *
   * @param array $messages
   *   An associative array of error messages keyed by the order they appear on
   *   the page, with the following key-value pairs:
   *   - title: The human readable form element title.
   *   - message: The error message for this form element.
   *   - key: The key used for the form element.
   */
  protected function assertErrorMessages($messages) {
    $element = $this->xpath('//div[@class = "form-error-message"]/strong');
    $this->assertIdentical(count($messages), count($element));

    $error_links = [];
    foreach ($messages as $delta => $message) {
      // Ensure the message appears in the correct place.
      if (!isset($element[$delta])) {
        $this->fail(format_string('The error message for the "@title" element with key "@key" was not found.', ['@title' => $message['title'], '@key' => $message['key']]));
      }
      else {
        $this->assertIdentical($message['message'], (string) $element[$delta]);
      }

      // Gather the element for checking the jump link section.
      $error_links[] = \Drupal::l($message['title'], Url::fromRoute('<none>', [], ['fragment' => 'edit-' . str_replace('_', '-', $message['key']), 'external' => TRUE]));
    }
    $top_message = \Drupal::translation()->formatPlural(count($error_links), '1 error has been found: !errors', '@count errors have been found: !errors', [
      '!errors' => SafeMarkup::set(implode(', ', $error_links))
    ]);
    $this->assertRaw($top_message);
    $this->assertNoText(t('An illegal choice has been detected. Please contact the site administrator.'));
  }

}
