<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test 'sub-admin' account with permission to edit some users but without 'administer users' permission.
 *
 * @group user
 */
class UserSubAdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user_access_test'];

  /**
   * Test create and cancel forms as 'sub-admin'.
   */
  public function testSubAdmin() {
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);

    // Test that the create user page has admin fields.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->fieldExists("edit-name");
    $this->assertSession()->fieldExists("edit-notify");

    // Not 'status' or 'roles' as they require extra permission.
    $this->assertSession()->fieldNotExists("edit-status-0");
    $this->assertSession()->fieldNotExists("edit-role");

    // Test that create user gives an admin style message.
    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      'notify' => FALSE,
    ];
    $this->drupalPostForm('admin/people/create', $edit, 'Create new account');
    $this->assertSession()->pageTextContains('Created a new user account for ' . $edit['name'] . '. No email has been sent.');

    // Test that the cancel user page has admin fields.
    $cancel_user = $this->createUser();
    $this->drupalGet('user/' . $cancel_user->id() . '/cancel');
    $this->assertSession()->responseContains('Are you sure you want to cancel the account ' . $cancel_user->getAccountName() . '?');
    $this->assertSession()->responseContains('Disable the account and keep its content. This action cannot be undone.');

    // Test that cancel confirmation gives an admin style message.
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertSession()->pageTextContains($cancel_user->getAccountName() . ' has been disabled.');

    // Repeat with permission to select account cancellation method.
    $user->addRole($this->drupalCreateRole(['select account cancellation method']));
    $user->save();
    $cancel_user = $this->createUser();
    $this->drupalGet('user/' . $cancel_user->id() . '/cancel');
    $this->assertSession()->pageTextContains('Select the method to cancel the account above.');
  }

}
