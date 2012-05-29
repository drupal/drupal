<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockAdminThemeTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the block system with admin themes.
 */
class BlockAdminThemeTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Admin theme block admin accessibility',
      'description' => "Check whether the block administer page for a disabled theme accessible if and only if it's the admin theme.",
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp(array('block'));
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
    $this->assertResponse(403, t('The block admin page for a disabled theme can not be accessed'));

    // Enable admin theme and confirm that tab is accessible.
    $edit['admin_theme'] = 'bartik';
    $this->drupalPost('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/structure/block/list/bartik');
    $this->assertResponse(200, t('The block admin page for the admin theme can be accessed'));
  }
}
