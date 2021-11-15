<?php

namespace Drupal\Tests\contact\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests personal contact form functionality.
 *
 * @group contact
 */
class ContactPersonalTest extends BrowserTestBase {

  use AssertMailTrait;
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contact', 'dblog', 'mail_html_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with some administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  private $adminUser;

  /**
   * A user with permission to view profiles and access user contact forms.
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

  protected function setUp(): void {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer contact forms',
      'administer users',
      'administer account settings',
      'access site reports',
    ]);

    // Create some normal users with their contact forms enabled by default.
    $this->config('contact.settings')->set('user_default_enabled', TRUE)->save();
    $this->webUser = $this->drupalCreateUser([
      'access user profiles',
      'access user contact forms',
    ]);
    $this->contactUser = $this->drupalCreateUser();
  }

  /**
   * Tests that mails for contact messages are correctly sent.
   */
  public function testSendPersonalContactMessage() {
    // Ensure that the web user's email needs escaping.
    $mail = $this->webUser->getAccountName() . '&escaped@example.com';
    $this->webUser->setEmail($mail)->save();
    $this->drupalLogin($this->webUser);

    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->assertEscaped($mail);
    $message = $this->submitPersonalContact($this->contactUser);
    $mails = $this->getMails();
    $this->assertCount(1, $mails);
    $mail = $mails[0];
    $this->assertEquals($this->contactUser->getEmail(), $mail['to']);
    $this->assertEquals($this->config('system.site')->get('mail'), $mail['from']);
    $this->assertEquals($this->webUser->getEmail(), $mail['reply-to']);
    $this->assertEquals('user_mail', $mail['key']);
    $subject = '[' . $this->config('system.site')->get('name') . '] ' . $message['subject[0][value]'];
    $this->assertEquals($subject, $mail['subject'], 'Subject is in sent message.');
    $this->assertStringContainsString('Hello ' . $this->contactUser->getDisplayName(), $mail['body'], 'Recipient name is in sent message.');
    $this->assertStringContainsString($this->webUser->getDisplayName(), $mail['body'], 'Sender name is in sent message.');
    $this->assertStringContainsString($message['message[0][value]'], $mail['body'], 'Message body is in sent message.');

    // Check there was no problems raised during sending.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    // Verify that the correct watchdog message has been logged.
    $this->drupalGet('/admin/reports/dblog');
    $placeholders = [
      '@sender_name' => $this->webUser->getAccountName(),
      '@sender_email' => $this->webUser->getEmail(),
      '@recipient_name' => $this->contactUser->getAccountName(),
    ];
    $this->assertSession()->responseContains(new FormattableMarkup('@sender_name (@sender_email) sent @recipient_name an email.', $placeholders));
    // Ensure an unescaped version of the email does not exist anywhere.
    $this->assertSession()->responseNotContains($this->webUser->getEmail());

    // Test HTML mails.
    $mail_config = $this->config('system.mail');
    $mail_config->set('interface.default', 'test_html_mail_collector');
    $mail_config->save();

    $this->drupalLogin($this->webUser);
    $message['message[0][value]'] = 'This <i>is</i> a more <b>specific</b> <sup>test</sup>, the emails are formatted now.';
    $message = $this->submitPersonalContact($this->contactUser, $message);

    // Assert mail content.
    $this->assertMailString('body', 'Hello ' . $this->contactUser->getDisplayName(), 1);
    $this->assertMailString('body', $this->webUser->getDisplayName(), 1);
    $this->assertMailString('body', Html::Escape($message['message[0][value]']), 1);
  }

  /**
   * Tests access to the personal contact form.
   */
  public function testPersonalContactAccess() {
    // Test allowed access to admin user's contact form.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);
    // Check the page title is properly displayed.
    $this->assertSession()->pageTextContains('Contact ' . $this->adminUser->getDisplayName());

    // Test denied access to admin user's own contact form.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(403);

    // Test allowed access to user with contact form enabled.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);

    // Test that there is no access to personal contact forms for users
    // without an email address configured.
    $original_email = $this->contactUser->getEmail();
    $this->contactUser->setEmail(FALSE)->save();
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(404);

    // Test that the 'contact tab' does not appear on the user profiles
    // for users without an email address configured.
    $this->drupalGet('user/' . $this->contactUser->id());
    $contact_link = '/user/' . $this->contactUser->id() . '/contact';
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefNotExists($contact_link, 'The "contact" tab is hidden on profiles for users with no email address');

    // Restore original email address.
    $this->contactUser->setEmail($original_email)->save();

    // Test denied access to the user's own contact form.
    $this->drupalGet('user/' . $this->webUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(403);

    // Test always denied access to the anonymous user contact form.
    $this->drupalGet('user/0/contact');
    $this->assertSession()->statusCodeEquals(403);

    // Test that anonymous users can access the contact form.
    $this->drupalLogout();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access user contact forms']);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);

    // Test that anonymous users can access admin user's contact form.
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('user');

    // Revoke the personal contact permission for the anonymous user.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['access user contact forms']);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('user');
    $this->drupalGet('user/' . $this->adminUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(403);

    // Disable the personal contact form.
    $this->drupalLogin($this->adminUser);
    $edit = ['contact_default_status' => FALSE];
    $this->drupalGet('admin/config/people/accounts');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();

    // Re-create our contacted user with personal contact forms disabled by
    // default.
    $this->contactUser = $this->drupalCreateUser();

    // Test denied access to a user with contact form disabled.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(403);

    // Test allowed access for admin user to a user with contact form disabled.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);

    // Re-create our contacted user as a blocked user.
    $this->contactUser = $this->drupalCreateUser();
    $this->contactUser->block();
    $this->contactUser->save();

    // Test that blocked users can still be contacted by admin.
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);

    // Test that blocked users cannot be contacted by non-admins.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(403);

    // Test enabling and disabling the contact page through the user profile
    // form.
    $this->drupalGet('user/' . $this->webUser->id() . '/edit');
    $this->assertSession()->checkboxNotChecked('edit-contact--2');
    $this->assertNull(\Drupal::service('user.data')->get('contact', $this->webUser->id(), 'enabled'), 'Personal contact form disabled');
    $this->submitForm(['contact' => TRUE], 'Save');
    $this->assertSession()->checkboxChecked('edit-contact--2');
    $this->assertNotEmpty(\Drupal::service('user.data')->get('contact', $this->webUser->id(), 'enabled'), 'Personal contact form enabled');

    // Test with disabled global default contact form in combination with a user
    // that has the contact form enabled.
    $this->config('contact.settings')->set('user_default_enabled', FALSE)->save();
    $this->contactUser = $this->drupalCreateUser();
    \Drupal::service('user.data')->set('contact', $this->contactUser->id(), 'enabled', 1);

    $this->drupalGet('user/' . $this->contactUser->id() . '/contact');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the personal contact form flood protection.
   */
  public function testPersonalContactFlood() {
    $flood_limit = 3;
    $this->config('contact.settings')->set('flood.limit', $flood_limit)->save();

    $this->drupalLogin($this->webUser);

    // Submit contact form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitPersonalContact($this->contactUser);
      $this->assertSession()->pageTextContains('Your message has been sent.');
    }

    // Submit contact form one over limit.
    $this->submitPersonalContact($this->contactUser);
    // Normal user should be denied access to flooded contact form.
    $interval = \Drupal::service('date.formatter')->formatInterval($this->config('contact.settings')->get('flood.interval'));
    $this->assertSession()->pageTextContains("You cannot send more than 3 messages in {$interval}. Try again later.");

    // Test that the admin user can still access the contact form even though
    // the flood limit was reached.
    $this->drupalLogin($this->adminUser);
    $this->assertSession()->pageTextNotContains('Try again later.');
  }

  /**
   * Tests the personal contact form based access when an admin adds users.
   */
  public function testAdminContact() {
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access user contact forms']);
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
   * @param int $response
   *   The expected response code.
   * @param bool $contact_value
   *   (optional) The value the contact field should be set too.
   */
  protected function checkContactAccess($response, $contact_value = NULL) {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/create');
    if ($this->config('contact.settings')->get('user_default_enabled', TRUE)) {
      $this->assertSession()->checkboxChecked('edit-contact--2');
    }
    else {
      $this->assertSession()->checkboxNotChecked('edit-contact--2');
    }
    $name = $this->randomMachineName();
    $edit = [
      'name' => $name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      'notify' => FALSE,
    ];
    if (isset($contact_value)) {
      $edit['contact'] = $contact_value;
    }
    $this->drupalGet('admin/people/create');
    $this->submitForm($edit, 'Create new account');
    $user = user_load_by_name($name);
    $this->drupalLogout();

    $this->drupalGet('user/' . $user->id() . '/contact');
    $this->assertSession()->statusCodeEquals($response);
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
  protected function submitPersonalContact(AccountInterface $account, array $message = []) {
    $message += [
      'subject[0][value]' => $this->randomMachineName(16) . '< " =+ >',
      'message[0][value]' => $this->randomMachineName(64) . '< " =+ >',
    ];
    $this->drupalGet('user/' . $account->id() . '/contact');
    $this->submitForm($message, 'Send message');
    return $message;
  }

}
