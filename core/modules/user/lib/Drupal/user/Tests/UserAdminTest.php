<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserAdminTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

class UserAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'User administration',
      'description' => 'Test user administration page functionality.',
      'group' => 'User'
    );
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
    $this->assertText($user_a->name, 'Found user A on admin users page');
    $this->assertText($user_b->name, 'Found user B on admin users page');
    $this->assertText($user_c->name, 'Found user C on admin users page');
    $this->assertText($admin_user->name, 'Found Admin user on admin users page');

    // Test for existence of edit link in table.
    $link = l(t('Edit'), "user/$user_a->uid/edit", array('query' => array('destination' => 'admin/people')));
    $this->assertRaw($link, 'Found user A edit link on admin users page');

    // Filter the users by permission 'administer taxonomy'.
    $edit = array();
    $edit['permission'] = 'administer taxonomy';
    $this->drupalPost('admin/people', $edit, t('Filter'));

    // Check if the correct users show up.
    $this->assertNoText($user_a->name, 'User A not on filtered by perm admin users page');
    $this->assertText($user_b->name, 'Found user B on filtered by perm admin users page');
    $this->assertText($user_c->name, 'Found user C on filtered by perm admin users page');

    // Filter the users by role. Grab the system-generated role name for User C.
    $roles = $user_c->roles;
    unset($roles[array_search(DRUPAL_AUTHENTICATED_RID, $roles)]);
    $edit['role'] = reset($roles);
    $this->drupalPost('admin/people', $edit, t('Refine'));

    // Check if the correct users show up when filtered by role.
    $this->assertNoText($user_a->name, 'User A not on filtered by role on admin users page');
    $this->assertNoText($user_b->name, 'User B not on filtered by role on admin users page');
    $this->assertText($user_c->name, 'User C on filtered by role on admin users page');

    // Test blocking of a user.
    $account = user_load($user_c->uid);
    $this->assertEqual($account->status, 1, 'User C not blocked');
    $edit = array();
    $edit['operation'] = 'user_block_user_action';
    $edit['accounts[' . $account->uid . ']'] = TRUE;
    $this->drupalPost('admin/people', $edit, t('Update'));
    $account = user_load($user_c->uid, TRUE);
    $this->assertEqual($account->status, 0, 'User C blocked');

    // Test unblocking of a user from /admin/people page and sending of activation mail
    $editunblock = array();
    $editunblock['operation'] = 'user_unblock_user_action';
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

  /**
   * Tests the alternate notification e-mail address for user mails.
   */
  function testNotificationEmailAddress() {
    // Test that the Notification E-mail address field is on the config page.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/people/accounts');
    $this->assertRaw('id="edit-mail-notification-address"', 'Notification E-mail address field exists');
    $this->drupalLogout();

    // Test custom user registration approval email address(es).
    $config = config('user.settings');
    // Allow users to register with admin approval.
    $config
      ->set('verify_mail', TRUE)
      ->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)
      ->save();
    // Set the site and notification email addresses.
    $system = config('system.site');
    $server_address = $this->randomName() . '@example.com';
    $notify_address = $this->randomName() . '@example.com';
    $system
      ->set('mail', $server_address)
      ->set('mail_notification', $notify_address)
      ->save();
    // Register a new user account.
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $subject = 'Account details for ' . $edit['name'] . ' at ' . $system->get('name') . ' (pending admin approval)';
    // Ensure that admin notification mail is sent to the configured
    // Notification E-mail address.
    $admin_mail = $this->drupalGetMails(array(
      'to' => $notify_address,
      'from' => $server_address,
      'subject' => $subject,
    ));
    $this->assertTrue(count($admin_mail), 'New user mail to admin is sent to configured Notification E-mail address');
    // Ensure that user notification mail is sent from the configured
    // Notification E-mail address.
    $user_mail = $this->drupalGetMails(array(
      'to' => $edit['mail'],
      'from' => $notify_address,
      'subject' => $subject,
    ));
    $this->assertTrue(count($user_mail), 'New user mail to user is sent from configured Notification E-mail address');
  }
}
