<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Workspaces form validation.
 *
 * @group workspaces
 */
class WorkspaceFormValidationTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'form_test', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer workspaces']));
    $this->setupWorkspaceSwitcherBlock();
  }

  /**
   * Tests partial form validation through #limit_validation_errors.
   */
  public function testValidateLimitErrors(): void {
    $this->createAndActivateWorkspaceThroughUi();

    $edit = [
      'test' => 'test1',
      'test_numeric_index[0]' => 'test2',
      'test_substring[foo]' => 'test3',
    ];
    $path = 'form-test/limit-validation-errors';

    // Submit the form by pressing all the 'Partial validate' buttons.
    $this->drupalGet($path);
    $this->submitForm($edit, 'Partial validate');
    $this->assertSession()->pageTextContains('This form can only be submitted in the default workspace.');

    $this->drupalGet($path);
    $this->submitForm($edit, 'Partial validate (numeric index)');
    $this->assertSession()->pageTextContains('This form can only be submitted in the default workspace.');

    $this->drupalGet($path);
    $this->submitForm($edit, 'Partial validate (substring)');
    $this->assertSession()->pageTextContains('This form can only be submitted in the default workspace.');

    // Now test full form validation.
    $this->drupalGet($path);
    $this->submitForm($edit, 'Full validate');
    $this->assertSession()->pageTextContains('This form can only be submitted in the default workspace.');
  }

}
