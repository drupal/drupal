<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\BulkFormTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;

/**
 * Tests a user bulk form.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\field\UserBulkForm
 */
class BulkFormTest extends UserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_user_bulk_form');

  /**
   * Tests the user bulk form.
   */
  public function testBulkForm() {
    $this->drupalLogin($this->drupalCreateUser(array('administer permissions')));

    // Test submitting the page with no selection.
    $edit = array(
      'action' => 'user_block_user_action',
    );
    $this->drupalPostForm('test-user-bulk-form', $edit, t('Apply'));
    $this->assertText(t('No users selected.'));

    // Assign a role to a user.
    $account = entity_load('user', $this->users[0]->id());
    $roles = user_role_names(TRUE);
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    $role = key($roles);

    $this->assertFalse($account->hasRole($role), 'The user currently does not have a custom role.');
    $edit = array(
      'user_bulk_form[1]' => TRUE,
      'action' => 'user_add_role_action.' . $role,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the user and check their roles.
    $account = entity_load('user', $account->id(), TRUE);
    $this->assertTrue($account->hasRole($role), 'The user now has the custom role.');

    $edit = array(
      'user_bulk_form[1]' => TRUE,
      'action' => 'user_remove_role_action.' . $role,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the user and check their roles.
    $account = entity_load('user', $account->id(), TRUE);
    $this->assertFalse($account->hasRole($role), 'The user no longer has the custom role.');

    // Block a user using the bulk form.
    $this->assertTrue($account->isActive(), 'The user is not blocked.');
    $this->assertRaw($account->label(), 'The user is found in the table.');
    $edit = array(
      'user_bulk_form[1]' => TRUE,
      'action' => 'user_block_user_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the user and check their status.
    $account = entity_load('user', $account->id(), TRUE);
    $this->assertTrue($account->isBlocked(), 'The user is blocked.');
    $this->assertNoRaw($account->label(), 'The user is not found in the table.');

    // Remove the user status filter from the view.
    $view = Views::getView('test_user_bulk_form');
    $view->removeHandler('default', 'filter', 'status');
    $view->storage->save();

    // Ensure the anonymous user is found.
    $this->drupalGet('test-user-bulk-form');
    $this->assertText(\Drupal::config('user.settings')->get('anonymous'));

    // Attempt to block the anonymous user.
    $edit = array(
      'user_bulk_form[0]' => TRUE,
      'action' => 'user_block_user_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $anonymous_account = user_load(0);
    $this->assertTrue($anonymous_account->isBlocked(), 'Ensure the anonymous user got blocked.');

    // Test the list of available actions with a value that contains a dot.
    $this->drupalLogin($this->drupalCreateUser(array('administer permissions', 'administer views')));
    $action_id = 'user_add_role_action.' . $role;
    $edit = [
      'options[include_exclude]' => 'exclude',
      "options[selected_actions][$action_id]" => $action_id,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_user_bulk_form/default/field/user_bulk_form', $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet('test-user-bulk-form');
    $this->assertNoOption('edit-action', $action_id);
    $edit['options[include_exclude]'] = 'include';
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_user_bulk_form/default/field/user_bulk_form', $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet('test-user-bulk-form');
    $this->assertOption('edit-action', $action_id);
  }

}
