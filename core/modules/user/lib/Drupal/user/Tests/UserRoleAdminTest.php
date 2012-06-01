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
    $edit = array('name' => $role_name);
    $this->drupalPost('admin/people/permissions/roles', $edit, t('Add role'));
    $this->assertText(t('The role has been added.'), t('The role has been added.'));
    $role = user_role_load_by_name($role_name);
    $this->assertTrue(is_object($role), t('The role was successfully retrieved from the database.'));

    // Try adding a duplicate role.
    $this->drupalPost(NULL, $edit, t('Add role'));
    $this->assertRaw(t('The role name %name already exists. Choose another role name.', array('%name' => $role_name)), t('Duplicate role warning displayed.'));

    // Test renaming a role.
    $old_name = $role_name;
    $role_name = '456';
    $edit = array('name' => $role_name);
    $this->drupalPost("admin/people/permissions/roles/edit/{$role->rid}", $edit, t('Save role'));
    $this->assertText(t('The role has been renamed.'), t('The role has been renamed.'));
    $this->assertFalse(user_role_load_by_name($old_name), t('The role can no longer be retrieved from the database using its old name.'));
    $this->assertTrue(is_object(user_role_load_by_name($role_name)), t('The role can be retrieved from the database using its new name.'));

    // Test deleting a role.
    $this->drupalPost("admin/people/permissions/roles/edit/{$role->rid}", NULL, t('Delete role'));
    $this->drupalPost(NULL, NULL, t('Delete'));
    $this->assertText(t('The role has been deleted.'), t('The role has been deleted'));
    $this->assertNoLinkByHref("admin/people/permissions/roles/edit/{$role->rid}", t('Role edit link removed.'));
    $this->assertFalse(user_role_load_by_name($role_name), t('A deleted role can no longer be loaded.'));

    // Make sure that the system-defined roles cannot be edited via the user
    // interface.
    $this->drupalGet('admin/people/permissions/roles/edit/' . DRUPAL_ANONYMOUS_RID);
    $this->assertResponse(403, t('Access denied when trying to edit the built-in anonymous role.'));
    $this->drupalGet('admin/people/permissions/roles/edit/' . DRUPAL_AUTHENTICATED_RID);
    $this->assertResponse(403, t('Access denied when trying to edit the built-in authenticated role.'));
  }

  /**
   * Test user role weight change operation.
   */
  function testRoleWeightChange() {
    $this->drupalLogin($this->admin_user);

    // Pick up a random role and get its weight.
    $rid = array_rand(user_roles());
    $role = user_role_load($rid);
    $old_weight = $role->weight;

    // Change the role weight and submit the form.
    $edit = array('roles['. $rid .'][weight]' => $old_weight + 1);
    $this->drupalPost('admin/people/permissions/roles', $edit, t('Save order'));
    $this->assertText(t('The role settings have been updated.'), t('The role settings form submitted successfully.'));

    // Retrieve the saved role and compare its weight.
    $role = user_role_load($rid);
    $new_weight = $role->weight;
    $this->assertTrue(($old_weight + 1) == $new_weight, t('Role weight updated successfully.'));
  }
}
