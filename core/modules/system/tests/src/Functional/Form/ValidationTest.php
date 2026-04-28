<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests form processing and alteration via form validation handlers.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
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

}
