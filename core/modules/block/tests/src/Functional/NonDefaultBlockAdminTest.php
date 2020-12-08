<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the block administration page for a non-default theme.
 *
 * @group block
 */
class NonDefaultBlockAdminTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test non-default theme admin.
   */
  public function testNonDefaultBlockAdmin() {
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
    ]);
    $this->drupalLogin($admin_user);
    $new_theme = 'bartik';
    \Drupal::service('theme_installer')->install([$new_theme]);
    // Ensure that the Bartik tab is shown.
    $this->drupalGet('admin/structure/block/list/' . $new_theme);
    $this->assertSession()->pageTextContains('Bartik(active tab)');
  }

}
