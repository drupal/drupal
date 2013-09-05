<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\UserPermissionUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Session\UserSession;

/**
 * Tests upgrading a bare database with user role data.
 *
 * Loads a bare installation of Drupal 7 with role data and runs the
 * upgrade process on it. Tests for the upgrade of user permissions.
 */
class UserPermissionUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'User permission upgrade test',
      'description'  => 'Upgrade tests for user permissions.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.user_permission.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests user-related permissions after a successful upgrade.
   */
  public function testUserPermissionUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    $this->drupalGet('');
    $this->assertResponse(200);

    // Verify that we are still logged in.
    $this->drupalGet('user');
    $this->clickLink(t('Edit'));
    $this->assertEqual($this->getUrl(), url('user/1/edit', array('absolute' => TRUE)), 'We are still logged in as admin at the end of the upgrade.');

    // Login as another 'administrator' role user whose uid != 1
    $this->drupalLogout();
    $user = new UserSession(array(
      'uid' => 2,
      'name' => 'user1',
      'pass_raw' => 'user1',
    ));
    $this->drupalLogin($user);

    // Check that user with permission 'administer users' also gets
    // 'administer account settings' access.
    $this->drupalGet('admin/config/people/accounts');
    $this->assertResponse(200, '"Administer account settings" page was found.');
  }

}
