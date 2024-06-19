<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verifies help for experimental modules.
 *
 * @group help
 */
class ExperimentalHelpTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * The experimental_module_test module implements hook_help() and is in the
   * Core (Experimental) package.
   *
   * @var array
   */
  protected static $modules = [
    'help',
    'experimental_module_test',
    'help_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['access help pages']);
  }

  /**
   * Verifies that a warning message is displayed for experimental modules.
   */
  public function testExperimentalHelp(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/help/experimental_module_test');
    $this->assertSession()->statusMessageContains('This module is experimental.', 'warning');

    // Regular modules should not display the message.
    $this->drupalGet('admin/help/help_page_test');
    $this->assertSession()->statusMessageNotContains('This module is experimental.');

    // Ensure the actual help page is displayed to avoid a false positive.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('online documentation for the Help Page Test module');
  }

}
