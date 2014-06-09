<?php

/**
 * @file
 * Definition of Drupal\contact\Tests\ContactPersonalTest.
 */

namespace Drupal\contact\Tests;

use Drupal\Component\Utility\String;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the personal contact form.
 */
class ContactPersonalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact', 'dblog');

  /**
   * A user with some administrative permissions.
   *
   * @var object
   */
  private $admin_user;

  /**
   * A user with 'access user contact forms' permission.
   *
   * @var object
   */
  private $web_user;

  /**
   * A user without any permissions.
   *
   * @var object
   */
  private $contact_user;

  public static function getInfo() {
    return array(
      'name' => 'Personal contact form',
      'description' => 'Tests personal contact form functionality.',
      'group' => 'Contact',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an admin user.
    $this->admin_user = $this->drupalCreateUser(array('administer contact forms', 'administer users', 'administer account settings', 'access site reports'));

    // Create some normal users with their contact forms enabled by default.
    \Drupal::config('contact.settings')->set('user_default_enabled', 1)->save();
    $this->web_user = $this->drupalCreateUser(array('access user contact forms'));
    $this->contact_user = $this->drupalCreateUser();
  }

  /**
   * Tests that mails for contact messages are correctly sent.
   */
  function testSendPersonalContactMessage() {
    $this->drupalLogin($this->web_user);

    $message = $this->submitPersonalContact($this->contact_user);
    $mails = $this->drupalGetMails();
    $this->assertEqual(1, count($mails));
    $mail = $mails[0];
    $this->assertEqual($mail['to'], $this->contact_user->getEmail());
    $this->assertEqual($mail['from'], \Drupal::config('system.site')->get('mail'));
    $this->assertEqual($mail['reply-to'], $this->web_user->getEmail());
    $this->assertEqual($mail['key'], 'user_mail');
    $variables = array(
      '!site-name' => \Drupal::config('system.site')->get('name'),
      '!subject' => $message['subject'],
      '!recipient-name' => $this->contact_user->getUsername(),
    );
    $this->assertEqual($mail['subject'], t('[!site-name] !subject', $variables), 'Subject is in sent message.');
    $this->assertTrue(strpos($mail['body'], t('Hello !recipient-name,', $variables)) !== FALSE, 'Recipient name is in sent message.');
    $this->assertTrue(strpos($mail['body'], $this->web_user->getUsername()) !== FALSE, 'Sender name is in sent message.');
    $this->assertTrue(strpos($mail['body'], $message['message']) !== FALSE, 'Message body is in sent message.');

    // Check there was no problems raised during sending.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    // Verify that the correct watchdog message has been logged.
    $this->drupalGet('/admin/reports/dblog');
    $placeholders = array(
      '@sender_name' => $this->web_user->username,
      '@sender_email' => $this->web_user->getEmail(),
      '@recipient_name' => $this->contact_user->getUsername()
    );
    $this->assertText(String::format('@sender_name (@sender_email) sent @recipient_name an email.', $placeholders));
  }

  /**
   * Tests access to the personal contact form.
   */
  function testPersonalContactAccess() {
    // Test allowed access to admin user's contact form.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->admin_user->id() . '/contact');
    $this->assertResponse(200);
    // Check the page title is properly displayed.
    $this->assertRaw(t('Contact @username', array('@username' => $this->admin_user->getUsername())));

    // Test denied access to admin user's own contact form.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('user/' . $this->admin_user->id() . '/contact');
    $this->assertResponse(403);

    // Test allowed access to user with contact form enabled.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(200);

    // Test denied access to the user's own contact form.
    $this->drupalGet('user/' . $this->web_user->id() . '/contact');
    $this->assertResponse(403);

    // Test always denied access to the anonymous user contact form.
    $this->drupalGet('user/0/contact');
    $this->assertResponse(403);

    // Test that anonymous users can access the contact form.
    $this->drupalLogout();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(200);

    // Test that anonymous users can access admin user's contact form.
    $this->drupalGet('user/' . $this->admin_user->id() . '/contact');
    $this->assertResponse(200);

    // Revoke the personal contact permission for the anonymous user.
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(403);
    $this->drupalGet('user/' . $this->admin_user->id() . '/contact');
    $this->assertResponse(403);

    // Disable the personal contact form.
    $this->drupalLogin($this->admin_user);
    $edit = array('contact_default_status' => FALSE);
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Setting successfully saved.');
    $this->drupalLogout();

    // Re-create our contacted user with personal contact forms disabled by
    // default.
    $this->contact_user = $this->drupalCreateUser();

    // Test denied access to a user with contact form disabled.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(403);

    // Test allowed access for admin user to a user with contact form disabled.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(200);

    // Re-create our contacted user as a blocked user.
    $this->contact_user = $this->drupalCreateUser();
    $this->contact_user->block();
    $this->contact_user->save();

    // Test that blocked users can still be contacted by admin.
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(200);

    // Test that blocked users cannot be contacted by non-admins.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('user/' . $this->contact_user->id() . '/contact');
    $this->assertResponse(403);

    // Test enabling and disabling the contact page through the user profile
    // form.
    $this->drupalGet('user/' . $this->web_user->id() . '/edit');
    $this->assertNoFieldChecked('edit-contact--2');
    $this->assertFalse(\Drupal::service('user.data')->get('contact', $this->web_user->id(), 'enabled'), 'Personal contact form disabled');
    $this->drupalPostForm(NULL, array('contact' => TRUE), t('Save'));
    $this->assertFieldChecked('edit-contact--2');
    $this->assertTrue(\Drupal::service('user.data')->get('contact', $this->web_user->id(), 'enabled'), 'Personal contact form enabled');
  }

  /**
   * Tests the personal contact form flood protection.
   */
  function testPersonalContactFlood() {
    $flood_limit = 3;
    \Drupal::config('contact.settings')->set('flood.limit', $flood_limit)->save();

    // Clear flood table in preparation for flood test and allow other checks to complete.
    db_delete('flood')->execute();
    $num_records_flood = db_query("SELECT COUNT(*) FROM {flood}")->fetchField();
    $this->assertIdentical($num_records_flood, '0', 'Flood table emptied.');

    $this->drupalLogin($this->web_user);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitPersonalContact($this->contact_user);
      $this->assertText(t('Your message has been sent.'), 'Message sent.');
    }

    // Submit contact form one over limit.
    $this->drupalGet('user/' . $this->contact_user->id(). '/contact');
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => $flood_limit, '@interval' => format_interval(\Drupal::config('contact.settings')->get('flood.interval')))), 'Normal user denied access to flooded contact form.');

    // Test that the admin user can still access the contact form even though
    // the flood limit was reached.
    $this->drupalLogin($this->admin_user);
    $this->assertNoText('Try again later.', 'Admin user not denied access to flooded contact form.');
  }

  /**
   * Fills out a user's personal contact form and submits it.
   *
   * @param $account
   *   A user object of the user being contacted.
   * @param $message
   *   (optional) An array with the form fields being used. Defaults to an empty
   *   array.
   */
  protected function submitPersonalContact($account, array $message = array()) {
    $message += array(
      'subject' => $this->randomName(16),
      'message' => $this->randomName(64),
    );
    $this->drupalPostForm('user/' . $account->id() . '/contact', $message, t('Send message'));
    return $message;
  }
}
