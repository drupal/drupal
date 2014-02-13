<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserRolesAssignmentTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test role assignment.
 */
class UserRolesAssignmentTest extends WebTestBase {
  protected $admin_user;

  public static function getInfo() {
    return array(
      'name' => 'Role assignment',
      'description' => 'Tests that users can be assigned and unassigned roles.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer permissions', 'administer users'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests that a user can be assigned a role and that the role can be removed
   * again.
   */
  function testAssignAndRemoveRole()  {
    $rid = $this->drupalCreateRole(array('administer users'));
    $account = $this->drupalCreateUser();

    // Assign the role to the user.
    $this->drupalPostForm('user/' . $account->id() . '/edit', array("roles[$rid]" => $rid), t('Save'));
    $this->assertText(t('The changes have been saved.'));
    $this->assertFieldChecked('edit-roles-' . $rid, 'Role is assigned.');
    $this->userLoadAndCheckRoleAssigned($account, $rid);

    // Remove the role from the user.
    $this->drupalPostForm('user/' . $account->id() . '/edit', array("roles[$rid]" => FALSE), t('Save'));
    $this->assertText(t('The changes have been saved.'));
    $this->assertNoFieldChecked('edit-roles-' . $rid, 'Role is removed from user.');
    $this->userLoadAndCheckRoleAssigned($account, $rid, FALSE);
  }

  /**
   * Tests that when creating a user the role can be assigned. And that it can
   * be removed again.
   */
  function testCreateUserWithRole() {
    $rid = $this->drupalCreateRole(array('administer users'));
    // Create a new user and add the role at the same time.
    $edit = array(
      'name' => $this->randomName(),
      'mail' => $this->randomName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      "roles[$rid]" => $rid,
    );
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));
    $this->assertText(t('Created a new user account for !name.', array('!name' => $edit['name'])));
    // Get the newly added user.
    $account = user_load_by_name($edit['name']);

    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->assertFieldChecked('edit-roles-' . $rid, 'Role is assigned.');
    $this->userLoadAndCheckRoleAssigned($account, $rid);

    // Remove the role again.
    $this->drupalPostForm('user/' . $account->id() . '/edit', array("roles[$rid]" => FALSE), t('Save'));
    $this->assertText(t('The changes have been saved.'));
    $this->assertNoFieldChecked('edit-roles-' . $rid, 'Role is removed from user.');
    $this->userLoadAndCheckRoleAssigned($account, $rid, FALSE);
  }

  /**
   * Check role on user object.
   *
   * @param object $account
   *   The user account to check.
   * @param string $rid
   *   The role ID to search for.
   * @param bool $is_assigned
   *   (optional) Whether to assert that $rid exists (TRUE) or not (FALSE).
   *   Defaults to TRUE.
   */
  private function userLoadAndCheckRoleAssigned($account, $rid, $is_assigned = TRUE) {
    $account = user_load($account->id(), TRUE);
    if ($is_assigned) {
      $this->assertTrue(array_search($rid, $account->getRoles()), 'The role is present in the user object.');
    }
    else {
      $this->assertFalse(array_search($rid, $account->getRoles()), 'The role is not present in the user object.');
    }
  }
}
