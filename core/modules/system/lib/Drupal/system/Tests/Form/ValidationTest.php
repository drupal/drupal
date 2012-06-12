<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\ValidationTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Test form validation handlers.
 */
class ValidationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Form validation handlers',
      'description' => 'Tests form processing and alteration via form validation handlers.',
      'group' => 'Form API',
    );
  }

  function setUp() {
    parent::setUp('form_test');
  }

  /**
   * Tests form alterations by #element_validate, #validate, and form_set_value().
   */
  function testValidate() {
    $this->drupalGet('form-test/validate');
    // Verify that #element_validate handlers can alter the form and submitted
    // form values.
    $edit = array(
      'name' => 'element_validate',
    );
    $this->drupalPost(NULL, $edit, 'Save');
    $this->assertFieldByName('name', '#value changed by #element_validate', t('Form element #value was altered.'));
    $this->assertText('Name value: value changed by form_set_value() in #element_validate', t('Form element value in $form_state was altered.'));

    // Verify that #validate handlers can alter the form and submitted
    // form values.
    $edit = array(
      'name' => 'validate',
    );
    $this->drupalPost(NULL, $edit, 'Save');
    $this->assertFieldByName('name', '#value changed by #validate', t('Form element #value was altered.'));
    $this->assertText('Name value: value changed by form_set_value() in #validate', t('Form element value in $form_state was altered.'));

    // Verify that #element_validate handlers can make form elements
    // inaccessible, but values persist.
    $edit = array(
      'name' => 'element_validate_access',
    );
    $this->drupalPost(NULL, $edit, 'Save');
    $this->assertNoFieldByName('name', t('Form element was hidden.'));
    $this->assertText('Name value: element_validate_access', t('Value for inaccessible form element exists.'));

    // Verify that value for inaccessible form element persists.
    $this->drupalPost(NULL, array(), 'Save');
    $this->assertNoFieldByName('name', t('Form element was hidden.'));
    $this->assertText('Name value: element_validate_access', t('Value for inaccessible form element exists.'));
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
      $this->assertTrue(!empty($element), t('The @type button has the proper formnovalidate attribute.', array('@type' => $type)));
    }
    // The button with full server-side validation should not have the
    // 'formnovalidate' attribute.
    $element = $this->xpath('//input[@id=:id and not(@formnovalidate)]', array(
      ':id' => 'edit-full',
    ));
    $this->assertTrue(!empty($element), t('The button with full server-side validation does not have the formnovalidate attribute.'));

    // Submit the form by pressing the 'Partial validate' button (uses
    // #limit_validation_errors) and ensure that the title field is not
    // validated, but the #element_validate handler for the 'test' field
    // is triggered.
    $this->drupalPost($path, $edit, t('Partial validate'));
    $this->assertNoText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test element is invalid');

    // Edge case of #limit_validation_errors containing numeric indexes: same
    // thing with the 'Partial validate (numeric index)' button and the
    // 'test_numeric_index' field.
    $this->drupalPost($path, $edit, t('Partial validate (numeric index)'));
    $this->assertNoText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test (numeric index) element is invalid');

    // Ensure something like 'foobar' isn't considered "inside" 'foo'.
    $this->drupalPost($path, $edit, t('Partial validate (substring)'));
    $this->assertNoText(t('!name field is required.', array('!name' => 'Title')));
    $this->assertText('Test (substring) foo element is invalid');

    // Ensure not validated values are not available to submit handlers.
    $this->drupalPost($path, array('title' => '', 'test' => 'valid'), t('Partial validate'));
    $this->assertText('Only validated values appear in the form values.');

    // Now test full form validation and ensure that the #element_validate
    // handler is still triggered.
    $this->drupalPost($path, $edit, t('Full validate'));
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
    $this->drupalPost('form-test/pattern', $edit, 'Submit');
    $this->assertRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Valid textfield, invalid tel, valid password.
    $edit = array(
      'textfield' => '7seven',
      'tel' => '818937',
      'password' => '0100110',
    );
    $this->drupalPost('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Non required fields are not validated if empty.
    $edit = array(
      'textfield' => '',
      'tel' => '',
    );
    $this->drupalPost('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw($textfield_error);
    $this->assertNoRaw($tel_error);
    $this->assertNoRaw($password_error);

    // Invalid password.
    $edit = array(
      'password' => $this->randomName(),
    );
    $this->drupalPost('form-test/pattern', $edit, 'Submit');
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
    $this->drupalPost('form-test/pattern', $edit, 'Submit');
    $this->assertNoRaw(t('%name field is not in the right format.', array('%name' => 'Client side validation')));
  }
}
