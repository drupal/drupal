<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockAdminThemeTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the block system with admin themes.
 *
 * @group block
 */
class BlockAdminThemeTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'contextual');

  /**
   * Check for the accessibility of the admin theme on the block admin page.
   */
  function testAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer themes'));
    $this->drupalLogin($admin_user);

    // Ensure that access to block admin page is denied when theme is not
    // installed.
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(403);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_handler')->install(array('bartik'));
    $edit['admin_theme'] = 'bartik';
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(200);
  }

  /**
   * Ensure contextual links are disabled in Seven theme.
   */
  function testSevenAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'access contextual links',
      'view the administration theme',
    ]);
    $this->drupalLogin($admin_user);

    // Install admin theme and confirm that tab is accessible.
    \Drupal::service('theme_handler')->install(['seven']);
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
