<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the SystemConfigFormTestBase class.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class SystemConfigFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the SystemConfigFormTestBase class.
   */
  public function testSystemConfigForm(): void {
    $this->drupalGet('form-test/system-config-form');
    // Verify the primary action submit button is found.
    $this->assertSession()->elementExists('xpath', "//div[@id = 'edit-actions']/input[contains(@class, 'button--primary')]");
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
