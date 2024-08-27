<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the block administration page for a non-default theme.
 *
 * @group block
 */
class NonDefaultBlockAdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
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
   * Tests non-default theme admin.
   */
  public function testNonDefaultBlockAdmin(): void {
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer themes',
    ]);
    $this->drupalLogin($admin_user);
    $new_theme = 'olivero';
    \Drupal::service('theme_installer')->install([$new_theme]);
    // Ensure that the Olivero tab is shown.
    $this->drupalGet('admin/structure/block/list/' . $new_theme);
    $this->assertSession()->pageTextContains('Olivero');
  }

}
