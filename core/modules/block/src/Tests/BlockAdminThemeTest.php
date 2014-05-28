<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockAdminThemeTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the block system with admin themes.
 */
class BlockAdminThemeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Administration theme',
      'description' => 'Tests the block system with admin themes.',
      'group' => 'Block',
    );
  }

  /**
   * Check for the accessibility of the admin theme on the  block admin page.
   */
  function testAdminTheme() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer themes'));
    $this->drupalLogin($admin_user);

    // Ensure that access to block admin page is denied when theme is disabled.
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(403);

    // Enable admin theme and confirm that tab is accessible.
    theme_enable(array('bartik'));
    $edit['admin_theme'] = 'bartik';
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(200);
  }
}
