<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\UserRoleUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrading a bare database with user role data.
 *
 * Loads a bare installation of Drupal 7 with role data and runs the
 * upgrade process on it. Tests for the conversion of serial role IDs to role
 * machine names.
 */
class UserRoleUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name'  => 'Role upgrade test',
      'description'  => 'Upgrade tests with role data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.roles.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests expected role ID conversions after a successful upgrade.
   */
  public function testRoleUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that "gärtner" has been converted to "4" and that the role
    // edit page for it exists.
    $this->drupalGet('admin/people/roles/manage/4');
    $this->assertResponse(200, 'Role edit page for "gärtner" was found.');
    $this->assertField('label', 'Role edit page for "gärtner" was found.');
    $this->assertRaw('gärtner', 'Role edit page for "gärtner" was found.');

    // Check that the anonymous user role ID has been converted from "1" to
    // "anonymous".
    $this->drupalGet('admin/people/permissions/' . DRUPAL_ANONYMOUS_RID);
    $this->assertResponse(200, 'Permission edit page for "anonymous" was found.');

    // Check that the authenticated user role ID has been converted from "2" to
    // "authenticated".
    $this->drupalGet('admin/people/permissions/' . DRUPAL_AUTHENTICATED_RID);
    $this->assertResponse(200, 'Permission edit page for "authenticated" was found.');

    // Check that the permission for "gärtner" still exists.
    $this->drupalGet('admin/people/permissions/4');
    $this->assertFieldChecked('edit-4-edit-own-comments', 'Edit own comments permission for "gärtner" is set correctly.');

    // Check that the role visibility setting for the who's online block still
    // exists.
    $this->drupalGet('admin/structure/block/add/user_online_block/bartik');

    // @todo Blocks are not being upgraded.
    //   $this->assertFieldChecked('edit-visibility-role-roles-5', "Who's online block visibility setting is correctly set for the long role name.");

    // Check that the role name is still displayed as expected.
    $this->assertText('gärtner', 'Role name is displayed on block visibility settings.');
    $this->assertText('very long role name that has exactly sixty-four characters in it', 'Role name is displayed on block visibility settings.');
    $this->assertText('very_long role name that has exactly sixty-four characters in it', 'Role name is displayed on block visibility settings.');

    // The administrative user role must still be assigned to the
    // "administrator" role (rid 3).
    $this->drupalGet('admin/config/people/accounts');
    $this->assertFieldByName('user_admin_role', 3);
  }

  /**
   * Tests that roles were converted to config.
   */
  public function testRoleUpgradeToConfig() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the 'anonymous' role has been converted to config.
    $anonymous = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $this->assertNotEqual(FALSE, $anonymous, "The 'anonymous' role has been converted to config.");

    // Check that the 'authenticated' role has been converted to config.
    $authenticated = entity_load('user_role', DRUPAL_AUTHENTICATED_RID);
    $this->assertNotEqual(FALSE, $authenticated, "The 'authenticated' role has been converted to config.");
  }
}
