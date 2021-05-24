<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that users can be assigned and unassigned roles.
 *
 * @group user
 */
class UserRolesAssignmentTest extends BrowserTestBase {

  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer permissions',
      'administer users',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that a user can be assigned a role and that the role can be removed
   * again.
   */
  public function testAssignAndRemoveRole() {
    $rid = $this->drupalCreateRole(['administer users']);
    $account = $this->drupalCreateUser();

    // Assign the role to the user.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->submitForm(["roles[{$rid}]" => $rid], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxChecked('edit-roles-' . $rid);
    $this->userLoadAndCheckRoleAssigned($account, $rid);

    // Remove the role from the user.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->submitForm(["roles[{$rid}]" => FALSE], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxNotChecked('edit-roles-' . $rid);
    $this->userLoadAndCheckRoleAssigned($account, $rid, FALSE);
  }

  /**
   * Tests that when creating a user the role can be assigned. And that it can
   * be removed again.
   */
  public function testCreateUserWithRole() {
    $rid = $this->drupalCreateRole(['administer users']);
    // Create a new user and add the role at the same time.
    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      "roles[$rid]" => $rid,
    ];
    $this->drupalGet('admin/people/create');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->pageTextContains('Created a new user account for ' . $edit['name'] . '.');
    // Get the newly added user.
    $account = user_load_by_name($edit['name']);

    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-roles-' . $rid);
    $this->userLoadAndCheckRoleAssigned($account, $rid);

    // Remove the role again.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->submitForm(["roles[{$rid}]" => FALSE], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxNotChecked('edit-roles-' . $rid);
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
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    if ($is_assigned) {
      $this->assertContains($rid, $account->getRoles());
    }
    else {
      $this->assertNotContains($rid, $account->getRoles());
    }
  }

}
