<?php

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify no help is displayed for modules not providing any help.
 *
 * @group help
 */
class NoHelpTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * Use one of the test modules that do not implement hook_help().
   *
   * @var array
   */
  protected static $modules = ['help', 'menu_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user who will be created.
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
   * Ensures modules not implementing help do not appear on admin/help.
   */
  public function testMainPageNoHelp() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/help');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Module overviews are provided by modules');
    $this->assertFalse(\Drupal::moduleHandler()->hasImplementations('help', 'menu_test'), 'The menu_test module does not implement hook_help');
    // Make sure the test module menu_test does not display a help link on
    // admin/help.
    $this->assertSession()->pageTextNotContains(\Drupal::moduleHandler()->getName('menu_test'));

    // Ensure that the module overview help page for a module that does not
    // implement hook_help() results in a 404.
    $this->drupalGet('admin/help/menu_test');
    $this->assertSession()->statusCodeEquals(404);
  }

}
