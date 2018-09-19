<?php

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test 'sub-admin' account with permission to edit some users but without 'administer users' permission.
 *
 * @group user
 */
class UserSubAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
    $this->assertField("edit-name", "Name field exists.");
    $this->assertField("edit-notify", "Notify field exists.");

    // Not 'status' or 'roles' as they require extra permission.
    $this->assertNoField("edit-status-0", "Status field missing.");
    $this->assertNoField("edit-role", "Role field missing.");

    // Test that create user gives an admin style message.
    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      'notify' => FALSE,
    ];
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));
    $this->assertText(t('Created a new user account for @name. No email has been sent.', ['@name' => $edit['name']]), 'User created');

    // Test that the cancel user page has admin fields.
    $cancel_user = $this->createUser();
    $this->drupalGet('user/' . $cancel_user->id() . '/cancel');
    $this->assertRaw(t('Are you sure you want to cancel the account %name?', ['%name' => $cancel_user->getUsername()]), 'Confirmation form to cancel account displayed.');
    $this->assertRaw(t('Disable the account and keep its content.') . ' ' . t('This action cannot be undone.'), 'Cannot select account cancellation method.');

    // Test that cancel confirmation gives an admin style message.
    $this->drupalPostForm(NULL, NULL, t('Cancel account'));
    $this->assertRaw(t('%name has been disabled.', ['%name' => $cancel_user->getUsername()]), "Confirmation message displayed to user.");

    // Repeat with permission to select account cancellation method.
    $user->addRole($this->drupalCreateRole(['select account cancellation method']));
    $user->save();
    $cancel_user = $this->createUser();
    $this->drupalGet('user/' . $cancel_user->id() . '/cancel');
    $this->assertText(t('Select the method to cancel the account above.'), 'Allows to select account cancellation method.');
  }

}
