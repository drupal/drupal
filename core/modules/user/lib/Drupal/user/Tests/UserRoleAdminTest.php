<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserRoleAdminTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test case to test adding, editing and deleting roles.
 */
class UserRoleAdminTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User role administration',
      'description' => 'Test adding, editing and deleting user roles and changing role weights.',
      'group' => 'User',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer permissions', 'administer users'));
  }

  /**
   * Test adding, renaming and deleting roles.
   */
  function testRoleAdministration() {
    $this->drupalLogin($this->admin_user);

    // Test adding a role. (In doing so, we use a role name that happens to
    // correspond to an integer, to test that the role administration pages
    // correctly distinguish between role names and IDs.)
    $role_name = '123';
    $edit = array('role[label]' => $role_name, 'role[id]' => $role_name);
    $this->drupalPost('admin/people/roles', $edit, t('Add role'));
    $this->assertText(t('The role has been added.'), 'The role has been added.');
    $role = entity_load('user_role', $role_name);
    $this->assertTrue(is_object($role), 'The role was successfully retrieved from the database.');

    // Try adding a duplicate role.
    $this->drupalPost(NULL, $edit, t('Add role'));
    $this->assertRaw(t('The machine-readable name is already in use. It must be unique.'), 'Duplicate role warning displayed.');

    // Test renaming a role.
    $old_name = $role_name;
    $role_name = '456';
    $edit = array('role[label]' => $role_name);
    $this->drupalPost("admin/people/roles/edit/{$role->id()}", $edit, t('Save role'));
    $this->assertText(t('The role has been renamed.'), 'The role has been renamed.');
    $new_role = entity_load('user_role', $old_name);
    $this->assertEqual($new_role->label(), $role_name, 'The role name has been successfully changed.');

    // Test deleting a role.
    $this->drupalPost("admin/people/roles/edit/{$role->id()}", NULL, t('Delete role'));
    $this->drupalPost(NULL, NULL, t('Delete'));
    $this->assertText(t('The role has been deleted.'), 'The role has been deleted');
    $this->assertNoLinkByHref("admin/people/roles/edit/{$role->id()}", 'Role edit link removed.');
    $this->assertFalse(entity_load('user_role', $role_name), 'A deleted role can no longer be loaded.');

    // Make sure that the system-defined roles can be edited via the user
    // interface.
    $this->drupalGet('admin/people/roles/edit/' . DRUPAL_ANONYMOUS_RID);
    $this->assertResponse(200, 'Access granted when trying to edit the built-in anonymous role.');
    $this->assertNoText(t('Delete role'), 'Delete button for the anonymous role is not present.');
    $this->drupalGet('admin/people/roles/edit/' . DRUPAL_AUTHENTICATED_RID);
    $this->assertResponse(200, 'Access granted when trying to edit the built-in authenticated role.');
    $this->assertNoText(t('Delete role'), 'Delete button for the authenticated role is not present.');
  }

  /**
   * Test user role weight change operation and ordering.
   */
  function testRoleWeightOrdering() {
    $this->drupalLogin($this->admin_user);
    $roles = user_roles();
    $weight = count($roles);
    $new_role_weights = array();
    $saved_rids = array();

    // Change the role weights to make the roles in reverse order.
    $edit = array();
    foreach ($roles as $role) {
      $edit['roles['. $role->id() .'][weight]'] =  $weight;
      $new_role_weights[$role->id()] = $weight;
      $saved_rids[] = $role->id;
      $weight--;
    }
    $this->drupalPost('admin/people/roles', $edit, t('Save order'));
    $this->assertText(t('The role settings have been updated.'), 'The role settings form submitted successfully.');

    // Load up the user roles with the new weights.
    drupal_static_reset('user_roles');
    $roles = user_roles();
    $rids = array();
    // Test that the role weights have been correctly saved.
    foreach ($roles as $role) {
      $this->assertEqual($role->weight, $new_role_weights[$role->id()]);
      $rids[] = $role->id;
    }
    // The order of the roles should be reversed.
    $this->assertIdentical($rids, array_reverse($saved_rids));
  }
}
