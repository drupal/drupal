<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserAdminTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests user administration page functionality.
 *
 * @group user
 */
class UserAdminTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'views');

  /**
   * Registers a user and deletes it.
   */
  function testUserAdmin() {
    $user_a = $this->drupalCreateUser();
    $user_a->name = 'User A';
    $user_a->mail = $this->randomMachineName() . '@example.com';
    $user_a->save();
    $user_b = $this->drupalCreateUser(array('administer taxonomy'));
    $user_b->name = 'User B';
    $user_b->save();
    $user_c = $this->drupalCreateUser(array('administer taxonomy'));
    $user_c->name = 'User C';
    $user_c->save();

    $user_storage = $this->container->get('entity.manager')->getStorage('user');

    // Create admin user to delete registered user.
    $admin_user = $this->drupalCreateUser(array('administer users'));
    // Use a predictable name so that we can reliably order the user admin page
    // by name.
    $admin_user->name = 'Admin user';
    $admin_user->save();
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/people');
    $this->assertText($user_a->getUsername(), 'Found user A on admin users page');
    $this->assertText($user_b->getUsername(), 'Found user B on admin users page');
    $this->assertText($user_c->getUsername(), 'Found user C on admin users page');
    $this->assertText($admin_user->getUsername(), 'Found Admin user on admin users page');

    // Test for existence of edit link in table.
    $link = $user_a->link(t('Edit'), 'edit-form', array('query' => array('destination' => $user_a->url('collection'))));
    $this->assertRaw($link, 'Found user A edit link on admin users page');

    // Test exposed filter elements.
    foreach (array('user', 'role', 'permission', 'status') as $field) {
      $this->assertField("edit-$field", "$field exposed filter found.");
    }
    // Make sure the reduce duplicates element from the ManyToOneHelper is not
    // displayed.
    $this->assertNoField('edit-reduce-duplicates', 'Reduce duplicates form element not found in exposed filters.');

    // Filter the users by name/email.
    $this->drupalGet('admin/people', array('query' => array('user' => $user_a->getUsername())));
    $result = $this->xpath('//table/tbody/tr');
    $this->assertEqual(1, count($result), 'Filter by username returned the right amount.');
    $this->assertEqual($user_a->getUsername(), (string) $result[0]->td[1]->span, 'Filter by username returned the right user.');

    $this->drupalGet('admin/people', array('query' => array('user' => $user_a->getEmail())));
    $result = $this->xpath('//table/tbody/tr');
    $this->assertEqual(1, count($result), 'Filter by username returned the right amount.');
    $this->assertEqual($user_a->getUsername(), (string) $result[0]->td[1]->span, 'Filter by username returned the right user.');

    // Filter the users by permission 'administer taxonomy'.
    $this->drupalGet('admin/people', array('query' => array('permission' => 'administer taxonomy')));

    // Check if the correct users show up.
    $this->assertNoText($user_a->getUsername(), 'User A not on filtered by perm admin users page');
    $this->assertText($user_b->getUsername(), 'Found user B on filtered by perm admin users page');
    $this->assertText($user_c->getUsername(), 'Found user C on filtered by perm admin users page');

    // Filter the users by role. Grab the system-generated role name for User C.
    $roles = $user_c->getRoles();
    unset($roles[array_search(RoleInterface::AUTHENTICATED_ID, $roles)]);
    $this->drupalGet('admin/people', array('query' => array('role' => reset($roles))));

    // Check if the correct users show up when filtered by role.
    $this->assertNoText($user_a->getUsername(), 'User A not on filtered by role on admin users page');
    $this->assertNoText($user_b->getUsername(), 'User B not on filtered by role on admin users page');
    $this->assertText($user_c->getUsername(), 'User C on filtered by role on admin users page');

    // Test blocking of a user.
    $account = $user_storage->load($user_c->id());
    $this->assertTrue($account->isActive(), 'User C not blocked');
    $edit = array();
    $edit['action'] = 'user_block_user_action';
    $edit['user_bulk_form[4]'] = TRUE;
    $this->drupalPostForm('admin/people', $edit, t('Apply'), array(
      // Sort the table by username so that we know reliably which user will be
      // targeted with the blocking action.
      'query' => array('order' => 'name', 'sort' => 'asc')
    ));
    $user_storage->resetCache(array($user_c->id()));
    $account = $user_storage->load($user_c->id());
    $this->assertTrue($account->isBlocked(), 'User C blocked');

    // Test filtering on admin page for blocked users
    $this->drupalGet('admin/people', array('query' => array('status' => 2)));
    $this->assertNoText($user_a->getUsername(), 'User A not on filtered by status on admin users page');
    $this->assertNoText($user_b->getUsername(), 'User B not on filtered by status on admin users page');
    $this->assertText($user_c->getUsername(), 'User C on filtered by status on admin users page');

    // Test unblocking of a user from /admin/people page and sending of activation mail
    $editunblock = array();
    $editunblock['action'] = 'user_unblock_user_action';
    $editunblock['user_bulk_form[4]'] = TRUE;
    $this->drupalPostForm('admin/people', $editunblock, t('Apply'), array(
      // Sort the table by username so that we know reliably which user will be
      // targeted with the blocking action.
      'query' => array('order' => 'name', 'sort' => 'asc')
    ));
    $user_storage->resetCache(array($user_c->id()));
    $account = $user_storage->load($user_c->id());
    $this->assertTrue($account->isActive(), 'User C unblocked');
    $this->assertMail("to", $account->getEmail(), "Activation mail sent to user C");

    // Test blocking and unblocking another user from /user/[uid]/edit form and sending of activation mail
    $user_d = $this->drupalCreateUser(array());
    $user_storage->resetCache(array($user_d->id()));
    $account1 = $user_storage->load($user_d->id());
    $this->drupalPostForm('user/' . $account1->id() . '/edit', array('status' => 0), t('Save'));
    $user_storage->resetCache(array($user_d->id()));
    $account1 = $user_storage->load($user_d->id());
    $this->assertTrue($account1->isBlocked(), 'User D blocked');
    $this->drupalPostForm('user/' . $account1->id() . '/edit', array('status' => TRUE), t('Save'));
    $user_storage->resetCache(array($user_d->id()));
    $account1 = $user_storage->load($user_d->id());
    $this->assertTrue($account1->isActive(), 'User D unblocked');
    $this->assertMail("to", $account1->getEmail(), "Activation mail sent to user D");
  }

  /**
   * Tests the alternate notification email address for user mails.
   */
  function testNotificationEmailAddress() {
    // Test that the Notification Email address field is on the config page.
    $admin_user = $this->drupalCreateUser(array('administer users', 'administer account settings'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/people/accounts');
    $this->assertRaw('id="edit-mail-notification-address"', 'Notification Email address field exists');
    $this->drupalLogout();

    // Test custom user registration approval email address(es).
    $config = $this->config('user.settings');
    // Allow users to register with admin approval.
    $config
      ->set('verify_mail', TRUE)
      ->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)
      ->save();
    // Set the site and notification email addresses.
    $system = $this->config('system.site');
    $server_address = $this->randomMachineName() . '@example.com';
    $notify_address = $this->randomMachineName() . '@example.com';
    $system
      ->set('mail', $server_address)
      ->set('mail_notification', $notify_address)
      ->save();
    // Register a new user account.
    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $subject = 'Account details for ' . $edit['name'] . ' at ' . $system->get('name') . ' (pending admin approval)';
    // Ensure that admin notification mail is sent to the configured
    // Notification Email address.
    $admin_mail = $this->drupalGetMails(array(
      'to' => $notify_address,
      'from' => $server_address,
      'subject' => $subject,
    ));
    $this->assertTrue(count($admin_mail), 'New user mail to admin is sent to configured Notification Email address');
    // Ensure that user notification mail is sent from the configured
    // Notification Email address.
    $user_mail = $this->drupalGetMails(array(
      'to' => $edit['mail'],
      'from' => $server_address,
      'reply-to' => $notify_address,
      'subject' => $subject,
    ));
    $this->assertTrue(count($user_mail), 'New user mail to user is sent from configured Notification Email address');
  }
}
