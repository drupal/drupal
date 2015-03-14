<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserPermissionsTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleStorage;

/**
 * Verify that role permissions can be added and removed via the permissions
 * page.
 *
 * @group user
 */
class UserPermissionsTest extends WebTestBase {

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User's role ID.
   *
   * @var string
   */
  protected $rid;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer permissions', 'access user profiles', 'administer site configuration', 'administer modules', 'administer account settings'));

    // Find the new role ID.
    $all_rids = $this->adminUser->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    $this->rid = reset($all_rids);
  }

  /**
   * Test changing user permissions through the permissions page.
   */
  function testUserPermissionChanges() {
    $permissions_hash_generator = $this->container->get('user.permissions_hash');

    $storage = $this->container->get('entity.manager')->getStorage('user_role');

    // Create an additional role and mark it as admin role.
    Role::create(['is_admin' => TRUE, 'id' => 'administrator', 'label' => 'Administrator'])->save();
    $storage->resetCache();

    $this->drupalLogin($this->adminUser);
    $rid = $this->rid;
    $account = $this->adminUser;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertIdentical($previous_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));

    // Add a permission.
    $this->assertFalse($account->hasPermission('administer users'), 'User does not have "administer users" permission.');
    $edit = array();
    $edit[$rid . '[administer users]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));
    $this->assertText(t('The changes have been saved.'), 'Successful save message displayed.');
    $storage->resetCache();
    $this->assertTrue($account->hasPermission('administer users'), 'User now has "administer users" permission.');
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
    $storage->resetCache();
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertIdentical($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEqual($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');

    // Ensure that the admin role doesn't have any checkboxes.
    $this->drupalGet('admin/people/permissions');
    foreach (array_keys($this->container->get('user.permissions')->getPermissions()) as $permission) {
      $this->assertNoFieldByName('administrator[' . $permission . ']');
    }
  }

  /**
   * Test assigning of permissions for the administrator role.
   */
  function testAdministratorRole() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/accounts');

    // Verify that the administration role is none by default.
    $this->assertOptionSelected('edit-user-admin-role', '', 'Administration role defaults to none.');

    $this->assertFalse(Role::load($this->rid)->isAdmin());

    // Set the user's role to be the administrator role.
    $edit = array();
    $edit['user_admin_role'] = $this->rid;
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));

    \Drupal::entityManager()->getStorage('user_role')->resetCache();
    $this->assertTrue(Role::load($this->rid)->isAdmin());

    // Enable aggregator module and ensure the 'administer news feeds'
    // permission is assigned by default.
    \Drupal::service('module_installer')->install(array('aggregator'));

    $this->assertTrue($this->adminUser->hasPermission('administer news feeds'), 'The permission was automatically assigned to the administrator role');

    // Ensure that selecting '- None -' removes the admin role.
    $edit = array();
    $edit['user_admin_role'] = '';
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));

    \Drupal::entityManager()->getStorage('user_role')->resetCache();
    \Drupal::configFactory()->reset();
    $this->assertFalse(Role::load($this->rid)->isAdmin());

    // Manually create two admin roles, in that case the single select should be
    // hidden.
    Role::create(['id' => 'admin_role_0', 'is_admin' => TRUE, 'label' => 'Admin role 0'])->save();
    Role::create(['id' => 'admin_role_1', 'is_admin' => TRUE, 'label' => 'Admin role 1'])->save();
    $this->drupalGet('admin/config/people/accounts');
    $this->assertNoFieldByName('user_admin_role');
  }

  /**
   * Verify proper permission changes by user_role_change_permissions().
   */
  function testUserRoleChangePermissions() {
    $permissions_hash_generator = $this->container->get('user.permissions_hash');

    $rid = $this->rid;
    $account = $this->adminUser;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);

    // Verify current permissions.
    $this->assertFalse($account->hasPermission('administer users'), 'User does not have "administer users" permission.');
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User has "administer site configuration" permission.');

    // Change permissions.
    $permissions = array(
      'administer users' => 1,
      'access user profiles' => 0,
    );
    user_role_change_permissions($rid, $permissions);

    // Verify proper permission changes.
    $this->assertTrue($account->hasPermission('administer users'), 'User now has "administer users" permission.');
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User still has "administer site configuration" permission.');

    // Verify the permissions hash has changed.
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertNotEqual($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
  }

}
