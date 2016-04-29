<?php

namespace Drupal\user\Tests\Views;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\user\Entity\User;

/**
 * Tests if entity access is respected on a user bulk form.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\field\UserBulkForm
 * @see \Drupal\user\Tests\Views\BulkFormTest
 */
class BulkFormAccessTest extends UserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user_access_test');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_user_bulk_form');

  /**
   * Tests if users that may not be edited, can not be edited in bulk.
   */
  public function testUserEditAccess() {
    // Create an authenticated user.
    $no_edit_user = $this->drupalCreateUser(array(), 'no_edit');
    // Ensure this account is not blocked.
    $this->assertFalse($no_edit_user->isBlocked(), 'The user is not blocked.');

    // Log in as user admin.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);

    // Ensure that the account "no_edit" can not be edited.
    $this->drupalGet('user/' . $no_edit_user->id() . '/edit');
    $this->assertFalse($no_edit_user->access('update', $admin_user));
    $this->assertResponse(403, 'The user may not be edited.');

    // Test blocking the account "no_edit".
    $edit = array(
      'user_bulk_form[' . ($no_edit_user->id() - 1) . ']' => TRUE,
      'action' => 'user_block_user_action',
    );
    $this->drupalPostForm('test-user-bulk-form', $edit, t('Apply'));
    $this->assertResponse(200);

    $this->assertRaw(SafeMarkup::format('No access to execute %action on the @entity_type_label %entity_label.', [
      '%action' => 'Block the selected user(s)',
      '@entity_type_label' => 'User',
      '%entity_label' => $no_edit_user->label(),
    ]));

    // Re-load the account "no_edit" and ensure it is not blocked.
    $no_edit_user = User::load($no_edit_user->id());
    $this->assertFalse($no_edit_user->isBlocked(), 'The user is not blocked.');

    // Create a normal user which can be edited by the admin user
    $normal_user = $this->drupalCreateUser();
    $this->assertTrue($normal_user->access('update', $admin_user));

    $edit = array(
      'user_bulk_form[' . ($normal_user->id() - 1) . ']' => TRUE,
      'action' => 'user_block_user_action',
    );
    $this->drupalPostForm('test-user-bulk-form', $edit, t('Apply'));

    $normal_user = User::load($normal_user->id());
    $this->assertTrue($normal_user->isBlocked(), 'The user is blocked.');

    // Log in as user without the 'administer users' permission.
    $this->drupalLogin($this->drupalCreateUser());

    $edit = array(
      'user_bulk_form[' . ($normal_user->id() - 1) . ']' => TRUE,
      'action' => 'user_unblock_user_action',
    );
    $this->drupalPostForm('test-user-bulk-form', $edit, t('Apply'));

    // Re-load the normal user and ensure it is still blocked.
    $normal_user = User::load($normal_user->id());
    $this->assertTrue($normal_user->isBlocked(), 'The user is still blocked.');
  }

  /**
   * Tests if users that may not be deleted, can not be deleted in bulk.
   */
  public function testUserDeleteAccess() {
    // Create two authenticated users.
    $account = $this->drupalCreateUser(array(), 'no_delete');
    $account2 = $this->drupalCreateUser(array(), 'may_delete');

    // Log in as user admin.
    $this->drupalLogin($this->drupalCreateUser(array('administer users')));

    // Ensure that the account "no_delete" can not be deleted.
    $this->drupalGet('user/' . $account->id() . '/cancel');
    $this->assertResponse(403, 'The user "no_delete" may not be deleted.');
    // Ensure that the account "may_delete" *can* be deleted.
    $this->drupalGet('user/' . $account2->id() . '/cancel');
    $this->assertResponse(200, 'The user "may_delete" may be deleted.');

    // Test deleting the accounts "no_delete" and "may_delete".
    $edit = array(
      'user_bulk_form[' . ($account->id() - 1) . ']' => TRUE,
      'user_bulk_form[' . ($account2->id() - 1) . ']' => TRUE,
      'action' => 'user_cancel_user_action',
    );
    $this->drupalPostForm('test-user-bulk-form', $edit, t('Apply'));
    $edit = array(
      'user_cancel_method' => 'user_cancel_delete',
    );
    $this->drupalPostForm(NULL, $edit, t('Cancel accounts'));

    // Ensure the account "no_delete" still exists.
    $account = User::load($account->id());
    $this->assertNotNull($account, 'The user "no_delete" is not deleted.');
    // Ensure the account "may_delete" no longer exists.
    $account = User::load($account2->id());
    $this->assertNull($account, 'The user "may_delete" is deleted.');
  }
}
