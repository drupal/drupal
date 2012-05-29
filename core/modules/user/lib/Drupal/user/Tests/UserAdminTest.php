<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserAdminTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

class UserAdminTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User administration',
      'description' => 'Test user administration page functionality.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp(array('taxonomy'));
  }

  /**
   * Registers a user and deletes it.
   */
  function testUserAdmin() {

    $user_a = $this->drupalCreateUser(array());
    $user_b = $this->drupalCreateUser(array('administer taxonomy'));
    $user_c = $this->drupalCreateUser(array('administer taxonomy'));

    // Create admin user to delete registered user.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/people');
    $this->assertText($user_a->name, t('Found user A on admin users page'));
    $this->assertText($user_b->name, t('Found user B on admin users page'));
    $this->assertText($user_c->name, t('Found user C on admin users page'));
    $this->assertText($admin_user->name, t('Found Admin user on admin users page'));

    // Test for existence of edit link in table.
    $link = l(t('edit'), "user/$user_a->uid/edit", array('query' => array('destination' => 'admin/people')));
    $this->assertRaw($link, t('Found user A edit link on admin users page'));

    // Filter the users by permission 'administer taxonomy'.
    $edit = array();
    $edit['permission'] = 'administer taxonomy';
    $this->drupalPost('admin/people', $edit, t('Filter'));

    // Check if the correct users show up.
    $this->assertNoText($user_a->name, t('User A not on filtered by perm admin users page'));
    $this->assertText($user_b->name, t('Found user B on filtered by perm admin users page'));
    $this->assertText($user_c->name, t('Found user C on filtered by perm admin users page'));

    // Filter the users by role. Grab the system-generated role name for User C.
    $edit['role'] = max(array_flip($user_c->roles));
    $this->drupalPost('admin/people', $edit, t('Refine'));

    // Check if the correct users show up when filtered by role.
    $this->assertNoText($user_a->name, t('User A not on filtered by role on admin users page'));
    $this->assertNoText($user_b->name, t('User B not on filtered by role on admin users page'));
    $this->assertText($user_c->name, t('User C on filtered by role on admin users page'));

    // Test blocking of a user.
    $account = user_load($user_c->uid);
    $this->assertEqual($account->status, 1, 'User C not blocked');
    $edit = array();
    $edit['operation'] = 'block';
    $edit['accounts[' . $account->uid . ']'] = TRUE;
    $this->drupalPost('admin/people', $edit, t('Update'));
    $account = user_load($user_c->uid, TRUE);
    $this->assertEqual($account->status, 0, 'User C blocked');

    // Test unblocking of a user from /admin/people page and sending of activation mail
    $editunblock = array();
    $editunblock['operation'] = 'unblock';
    $editunblock['accounts[' . $account->uid . ']'] = TRUE;
    $this->drupalPost('admin/people', $editunblock, t('Update'));
    $account = user_load($user_c->uid, TRUE);
    $this->assertEqual($account->status, 1, 'User C unblocked');
    $this->assertMail("to", $account->mail, "Activation mail sent to user C");

    // Test blocking and unblocking another user from /user/[uid]/edit form and sending of activation mail
    $user_d = $this->drupalCreateUser(array());
    $account1 = user_load($user_d->uid, TRUE);
    $this->drupalPost('user/' . $account1->uid . '/edit', array('status' => 0), t('Save'));
    $account1 = user_load($user_d->uid, TRUE);
    $this->assertEqual($account1->status, 0, 'User D blocked');
    $this->drupalPost('user/' . $account1->uid . '/edit', array('status' => TRUE), t('Save'));
    $account1 = user_load($user_d->uid, TRUE);
    $this->assertEqual($account1->status, 1, 'User D unblocked');
    $this->assertMail("to", $account1->mail, "Activation mail sent to user D");
  }
}
