<?php

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the block system with admin themes.
 *
 * @group block
 */
class BlockAdminThemeTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'contextual'];

  /**
   * Check for the accessibility of the admin theme on the block admin page.
   */
  public function testAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(['administer blocks', 'administer themes']);
    $this->drupalLogin($admin_user);

    // Ensure that access to block admin page is denied when theme is not
    // installed.
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(403);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_installer')->install(['bartik']);
    $edit['admin_theme'] = 'bartik';
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(200);
  }

  /**
   * Ensure contextual links are disabled in Seven theme.
   */
  public function testSevenAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'access contextual links',
      'view the administration theme',
    ]);
    $this->drupalLogin($admin_user);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_installer')->install(['seven']);
    $edit['admin_theme'] = 'seven';
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));

    // Define our block settings.
    $settings = [
      'theme' => 'seven',
      'region' => 'header',
    ];

    // Place a block.
    $block = $this->drupalPlaceBlock('local_tasks_block', $settings);

    // Open admin page.
    $this->drupalGet('admin');

    // Check if contextual link classes are unavailable.
    $this->assertNoRaw('<div data-contextual-id="block:block=' . $block->id() . ':langcode=en"></div>');
    $this->assertNoRaw('contextual-region');
  }

}
