<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\Core\Render\Element;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests form validation handlers and messages.
 *
 * Implemented as FunctionalJavascript to use browser that supports
 * HTML5 validation.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class ValidationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->writeSettings([
      'settings' => [
        'enable_html5_validation' => (object) [
          'value' => FALSE,
          'required' => TRUE,
        ],
      ],
    ]);
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
