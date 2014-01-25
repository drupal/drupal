<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserPermissionsTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleStorageController;

class UserPermissionsTest extends WebTestBase {
  protected $admin_user;
  protected $rid;

  public static function getInfo() {
    return array(
      'name' => 'Role permissions',
      'description' => 'Verify that role permissions can be added and removed via the permissions page.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer permissions', 'access user profiles', 'administer site configuration', 'administer modules', 'administer account settings'));

    // Find the new role ID.
    $all_rids = $this->admin_user->getRoles();
    unset($all_rids[array_search(DRUPAL_AUTHENTICATED_RID, $all_rids)]);
    $this->rid = reset($all_rids);
  }

  /**
   * Test changing user permissions through the permissions page.
   */
  function testUserPermissionChanges() {
    $permissions_hash_generator = $this->container->get('user.permissions_hash');

    $this->drupalLogin($this->admin_user);
    $rid = $this->rid;
    $account = $this->admin_user;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertIdentical($previous_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));

    // Add a permission.
    $this->assertFalse($account->hasPermission('administer nodes'), 'User does not have "administer nodes" permission.');
    $edit = array();
    $edit[$rid . '[administer nodes]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));
    $this->assertText(t('The changes have been saved.'), 'Successful save message displayed.');
    $storage_controller = $this->container->get('entity.manager')->getStorageController('user_role');
    $storage_controller->resetCache();
    $this->assertTrue($account->hasPermission('administer nodes'), 'User now has "administer nodes" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertIdentical($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEqual($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
    $previous_permissions_hash = $current_permissions_hash;

    // Remove a permission.
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $edit = array();
    $edit[$rid . '[access user profiles]'] = FALSE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));
    $this->assertText(t('The changes have been saved.'), 'Successful save message displayed.');
    $storage_controller->resetCache();
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertIdentical($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEqual($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
  }

  /**
   * Test assigning of permissions for the administrator role.
   */
  function testAdministratorRole() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/people/accounts');

    // Set the user's role to be the administrator role.
    $edit = array();
    $edit['user_admin_role'] = $this->rid;
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));

    // Enable aggregator module and ensure the 'administer news feeds'
    // permission is assigned by default.
    $edit = array();
    $edit['modules[Core][aggregator][enable]'] = TRUE;
    // Aggregator depends on file module, enable that as well.
    $edit['modules[Field types][file][enable]'] = TRUE;
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->assertTrue($this->admin_user->hasPermission('administer news feeds'), 'The permission was automatically assigned to the administrator role');
  }

  /**
   * Verify proper permission changes by user_role_change_permissions().
   */
  function testUserRoleChangePermissions() {
    $permissions_hash_generator = $this->container->get('user.permissions_hash');

    $rid = $this->rid;
    $account = $this->admin_user;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);

    // Verify current permissions.
    $this->assertFalse($account->hasPermission('administer nodes'), 'User does not have "administer nodes" permission.');
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User has "administer site configuration" permission.');

    // Change permissions.
    $permissions = array(
      'administer nodes' => 1,
      'access user profiles' => 0,
    );
    user_role_change_permissions($rid, $permissions);

    // Verify proper permission changes.
    $this->assertTrue($account->hasPermission('administer nodes'), 'User now has "administer nodes" permission.');
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User still has "administer site configuration" permission.');

    // Verify the permissions hash has changed.
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertNotEqual($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
  }

}
