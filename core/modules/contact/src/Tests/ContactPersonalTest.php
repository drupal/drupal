<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\ContactPersonalTest.
 */

namespace Drupal\contact\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests personal contact form functionality.
 *
 * @group contact
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
   * @var \Drupal\user\UserInterface
   */
  private $adminUser;

  /**
   * A user with 'access user contact forms' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  private $webUser;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  private $contactUser;

  protected function setUp() {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser(array('administer contact forms', 'administer users', 'administer account settings', 'access site reports'));

    // Create some normal users with their contact forms enabled by default.
    $this->config('contact.settings')->set('user_default_enabled', TRUE)->save();
    $this->webUser = $this->drupalCreateUser(array('access user contact forms'));
    $this->contactUser = $this->drupalCreateUser();
  }

  /**
   * Tests that mails for contact messages are correctly sent.
   */
  function testSendPersonalContactMessage() {
    $this->drupalLogin($this->webUser);

    $message = $this->submitPersonalContact($this->contactUser);
    $mails = $this->drupalGetMails();
    $this->assertEqual(1, count($mails));
    $mail = $mails[0];
    $this->assertEqual($mail['to'], $this->contactUser->getEmail());
    $this->assertEqual($mail['from'], $this->config('system.site')->get('mail'));
    $this->assertEqual($mail['reply-to'], $this->webUser->getEmail());
    $this->assertEqual($mail['key'], 'user_mail');
    $variables = array(
      '!site-name' => $this->config('system.site')->get('name'),
      '!subject' => $message['subject[0][value]'],
      '!recipient-name' => $this->contactUser->getUsername(),
    );
    $this->assertEqual($mail['subject'], t('[!site-name] !subject', $variables), 'Subject is in sent message.');
    $this->assertTrue(strpos($mail['body'], t('Hello !recipient-name,', $variables)) !== FALSE, 'Recipient name is in sent message.');
    $this->assertTrue(strpos($mail['body'], $this->webUser->getUsername()) !== FALSE, 'Sender name is in sent message.');
    $this->assertTrue(strpos($mail['body'], $message['message[0][value]']) !== FALSE, 'Message body is in sent message.');

    // Check there was no problems raised during sending.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    // Verify that the correct watchdog message has been logged.
    $this->drupalGet('/admin/reports/dblog');
    $placeholders = array(
      '@sender_name' => $this->webUser->username,
      '@sender_email' => $this->webUser->getEmail(),
      '@recipient_name' => $this->contactUser->getUsername()
    );
    $this->assertText(String::format('@sender_name (@sender_email) sent @recipient_name an email.', $placeholders));
  }

  /**
   * Tests access to the personal contact form.
   */
  function testPersonalContactAccess() {
    // Test allowed access to admin user's contact form.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertResponse(200);
    // Check the page title is properly displayed.
    $this->assertRaw(t('Contact @username', array('@username' => $this->adminUser->getUsername())));

    // Test denied access to admin user's own contact form.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertResponse(403);

    // Test allowed access to user with contact form enabled.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(200);

    // Test denied access to the user's own contact form.
    $this->drupalGet('user/' . $this->webUser->id() . '/contact');
    $this->assertResponse(403);

    // Test always denied access to the anonymous user contact form.
    $this->drupalGet('user/0/contact');
    $this->assertResponse(403);

    // Test that anonymous users can access the contact form.
    $this->drupalLogout();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(200);

    // Test that anonymous users can access admin user's contact form.
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertResponse(200);

    // Revoke the personal contact permission for the anonymous user.
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(403);
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertResponse(403);

    // Disable the personal contact form.
    $this->drupalLogin($this->adminUser);
    $edit = array('contact_default_status' => FALSE);
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Setting successfully saved.');
    $this->drupalLogout();

    // Re-create our contacted user with personal contact forms disabled by
    // default.
    $this->contactUser = $this->drupalCreateUser();

    // Test denied access to a user with contact form disabled.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(403);

    // Test allowed access for admin user to a user with contact form disabled.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(200);

    // Re-create our contacted user as a blocked user.
    $this->contactUser = $this->drupalCreateUser();
    $this->contactUser->block();
    $this->contactUser->save();

    // Test that blocked users can still be contacted by admin.
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(200);

    // Test that blocked users cannot be contacted by non-admins.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(403);

    // Test enabling and disabling the contact page through the user profile
    // form.
    $this->drupalGet('user/' . $this->webUser->id() . '/edit');
    $this->assertNoFieldChecked('edit-contact--2');
    $this->assertFalse(\Drupal::service('user.data')->get('contact', $this->webUser->id(), 'enabled'), 'Personal contact form disabled');
    $this->drupalPostForm(NULL, array('contact' => TRUE), t('Save'));
    $this->assertFieldChecked('edit-contact--2');
    $this->assertTrue(\Drupal::service('user.data')->get('contact', $this->webUser->id(), 'enabled'), 'Personal contact form enabled');

    // Test with disabled global default contact form in combination with a user
    // that has the contact form enabled.
    $this->config('contact.settings')->set('user_default_enabled', FALSE)->save();
    $this->contactUser = $this->drupalCreateUser();
    \Drupal::service('user.data')->set('contact', $this->contactUser->id(), 'enabled', 1);

    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertResponse(200);
  }

  /**
   * Tests the personal contact form flood protection.
   */
  function testPersonalContactFlood() {
    $flood_limit = 3;
    $this->config('contact.settings')->set('flood.limit', $flood_limit)->save();

    // Clear flood table in preparation for flood test and allow other checks to complete.
    db_delete('flood')->execute();
    $num_records_flood = db_query("SELECT COUNT(*) FROM {flood}")->fetchField();
    $this->assertIdentical($num_records_flood, '0', 'Flood table emptied.');

    $this->drupalLogin($this->webUser);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitPersonalContact($this->contactUser);
      $this->assertText(t('Your message has been sent.'), 'Message sent.');
    }

    // Submit contact form one over limit.
    $this->drupalGet('user/' . $this->contactUser->id(). '/contact');
    $this->assertRaw(t('You cannot send more than %number messages in @interval. Try again later.', array('%number' => $flood_limit, '@interval' => \Drupal::service('date.formatter')->formatInterval($this->config('contact.settings')->get('flood.interval')))), 'Normal user denied access to flooded contact form.');

    // Test that the admin user can still access the contact form even though
    // the flood limit was reached.
    $this->drupalLogin($this->adminUser);
    $this->assertNoText('Try again later.', 'Admin user not denied access to flooded contact form.');
  }

  /**
   * Tests the personal contact form based access when an admin adds users.
   */
  function testAdminContact() {
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access user contact forms'));
    $this->checkContactAccess(200);
    $this->checkContactAccess(403, FALSE);
    $config = $this->config('contact.settings');
    $config->set('user_default_enabled', FALSE);
    $config->save();
    $this->checkContactAccess(403);
  }

  /**
   * Creates a user and then checks contact form access.
   *
   * @param integer $response
   *   The expected response code.
   * @param boolean $contact_value
   *   (optional) The value the contact field should be set too.
   */
  protected function checkContactAccess($response, $contact_value = NULL) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/create');
    if ($this->config('contact.settings')->get('user_default_enabled', TRUE)) {
      $this->assertFieldChecked('edit-contact--2');
    }
    else {
      $this->assertNoFieldChecked('edit-contact--2');
    }
    $name = $this->randomMachineName();
    $edit = array(
      'name' => $name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      'notify' => FALSE,
    );
    if (isset($contact_value)) {
      $edit['contact'] = $contact_value;
    }
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));
    $user = user_load_by_name($name);
    $this->drupalLogout();

    $this->drupalGet('user/' . $user->id() . '/contact');
    $this->assertResponse($response);
  }

  /**
   * Fills out a user's personal contact form and submits it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object of the user being contacted.
   * @param array $message
   *   (optional) An array with the form fields being used. Defaults to an empty
   *   array.
   *
   * @return array
   *   An array with the form fields being used.
   */
  protected function submitPersonalContact(AccountInterface $account, array $message = array()) {
    $message += array(
      'subject[0][value]' => $this->randomMachineName(16),
      'message[0][value]' => $this->randomMachineName(64),
    );
    $this->drupalPostForm('user/' . $account->id() . '/contact', $message, t('Send message'));
    return $message;
  }

}
