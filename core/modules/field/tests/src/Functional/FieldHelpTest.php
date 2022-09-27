<?php

namespace Drupal\Tests\field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests help display for the Field module.
 *
 * @group field
 */
class FieldHelpTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field', 'help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user that will be created.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
    ]);
  }

  /**
   * Tests the Field module's help page.
   */
  public function testFieldHelp() {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Visit the Help page and make sure no warnings or notices are thrown.
    $this->drupalGet('admin/help/field');

    // Enable the Options, Email and Field API Test modules.
    \Drupal::service('module_installer')->install(['options', 'field_test']);

    $this->drupalGet('admin/help/field');
    $this->assertSession()->linkExists('Options', 0, 'Options module is listed on the Field help page.');
    // Verify that modules with field types that do not implement hook_help are
    // listed.
    $this->assertSession()->pageTextContains('Field API Test');
    $this->assertSession()->linkNotExists('Field API Test', 'Modules with field types that do not implement hook_help are not linked.');
    $this->assertSession()->linkNotExists('Link', 'Modules that have not been installed, are not listed.');
  }

}
