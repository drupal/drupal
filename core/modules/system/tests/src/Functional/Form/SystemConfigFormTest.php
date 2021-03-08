<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the SystemConfigFormTestBase class.
 *
 * @group Form
 */
class SystemConfigFormTest extends BrowserTestBase {

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
   * Tests the SystemConfigFormTestBase class.
   */
  public function testSystemConfigForm() {
    $this->drupalGet('form-test/system-config-form');
    // Verify the primary action submit button is found.
    $this->assertSession()->elementExists('xpath', "//div[@id = 'edit-actions']/input[contains(@class, 'button--primary')]");
    $this->submitForm([], 'Save configuration');
    $this->assertText('The configuration options have been saved.');
  }

}
