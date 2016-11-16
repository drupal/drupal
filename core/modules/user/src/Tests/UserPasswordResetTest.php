<?php

namespace Drupal\user\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\system\Tests\Cache\PageCacheTagsTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that password reset methods work as expected.
 *
 * @group user
 */
class UserPasswordResetTest extends PageCacheTagsTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * This test uses the standard profile to test the password reset in
   * combination with an ajax request provided by the user picture configuration
   * in the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * The user object to test password resetting.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:account');

    // Create a user.
    $account = $this->drupalCreateUser();

    // Activate user by logging in.
    $this->drupalLogin($account);

    $this->account = User::load($account->id());
    $this->account->pass_raw = $account->pass_raw;
    $this->drupalLogout();

    // Set the last login time that is used to generate the one-time link so
    // that it is definitely over a second ago.
    $account->login = REQUEST_TIME - mt_rand(10, 100000);
    db_update('users_field_data')
      ->fields(array('login' => $account->getLastLoginTime()))
      ->condition('uid', $account->id())
      ->execute();
  }

  /**
   * Tests password reset functionality.
   */
  function testUserPasswordReset() {
    // Verify that accessing the password reset form without having the session
    // variables set results in an access denied message.
    $this->drupalGet(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));
    $this->assertResponse(403);

    // Try to reset the password for an invalid account.
    $this->drupalGet('user/password');

    $edit = array('name' => $this->randomMachineName(32));
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertText(t('@name is not recognized as a username or an email address.', array('@name' => $edit['name'])), 'Validation error message shown when trying to request password for invalid account.');
    $this->assertEqual(count($this->drupalGetMails(array('id' => 'user_password_reset'))), 0, 'No email was sent when requesting a password for an invalid account.');

    // Reset the password by username via the password reset page.
    $edit['name'] = $this->account->getUsername();
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    // Verify that the user was sent an email.
    $this->assertMail('to', $this->account->getEmail(), 'Password email sent to user.');
    $subject = t('Replacement login information for @username at @site', array('@username' => $this->account->getUsername(), '@site' => $this->config('system.site')->get('name')));
    $this->assertMail('subject', $subject, 'Password reset email subject is correct.');

    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    // Ensure that the current url does not contain the hash and timestamp.
    $this->assertUrl(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));

    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));

    // Ensure the password reset URL is not cached.
    $this->drupalGet($resetURL);
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));

    // Check the one-time login page.
    $this->assertText($this->account->getUsername(), 'One-time login page contains the correct username.');
    $this->assertText(t('This login can be used only once.'), 'Found warning about one-time login.');
    $this->assertTitle(t('Reset password | Drupal'), 'Page title is "Reset password".');

    // Check successful login.
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->assertLink(t('Log out'));
    $this->assertTitle(t('@name | @site', array('@name' => $this->account->getUsername(), '@site' => $this->config('system.site')->get('name'))), 'Logged in using password reset link.');

    // Make sure the ajax request from uploading a user picture does not
    // invalidate the reset token.
    $image = current($this->drupalGetTestFiles('image'));
    $edit = array(
      'files[user_picture_0]' => drupal_realpath($image->uri),
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'user_picture_0_upload_button');

    // Change the forgotten password.
    $password = user_password();
    $edit = array('pass[pass1]' => $password, 'pass[pass2]' => $password);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'), 'Forgotten password changed.');

    // Verify that the password reset session has been destroyed.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your current password is missing or incorrect; it\'s required to change the Password.'), 'Password needed to make profile changes.');

    // Log out, and try to log in again using the same one-time link.
    $this->drupalLogout();
    $this->drupalGet($resetURL);
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->assertText(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'One-time link is no longer valid.');

    // Request a new password again, this time using the email address.
    $this->drupalGet('user/password');
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(array('id' => 'user_password_reset')));
    $edit = array('name' => $this->account->getEmail());
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertTrue( count($this->drupalGetMails(array('id' => 'user_password_reset'))) === $before + 1, 'Email sent when requesting password reset using email address.');

    // Visit the user edit page without pass-reset-token and make sure it does
    // not cause an error.
    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->drupalGet('user/' . $this->account->id() . '/edit');
    $this->assertNoText('Expected user_string to be a string, NULL given');
    $this->drupalLogout();

    // Create a password reset link as if the request time was 60 seconds older than the allowed limit.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    $bogus_timestamp = REQUEST_TIME - $timeout - 60;
    $_uid = $this->account->id();
    $this->drupalGet("user/reset/$_uid/$bogus_timestamp/" . user_pass_rehash($this->account, $bogus_timestamp));
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->assertText(t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'Expired password reset request rejected.');

    // Create a user, block the account, and verify that a login link is denied.
    $timestamp = REQUEST_TIME - 1;
    $blocked_account = $this->drupalCreateUser()->block();
    $blocked_account->save();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp));
    $this->assertResponse(403);

    // Verify a blocked user can not request a new password.
    $this->drupalGet('user/password');
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(array('id' => 'user_password_reset')));
    $edit = array('name' => $blocked_account->getUsername());
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertRaw(t('%name is blocked or has not been activated yet.', array('%name' => $blocked_account->getUsername())), 'Notified user blocked accounts can not request a new password');
    $this->assertTrue(count($this->drupalGetMails(array('id' => 'user_password_reset'))) === $before, 'No email was sent when requesting password reset for a blocked account');

    // Verify a password reset link is invalidated when the user's email address changes.
    $this->drupalGet('user/password');
    $edit = array('name' => $this->account->getUsername());
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $old_email_reset_link = $this->getResetURL();
    $this->account->setEmail("1" . $this->account->getEmail());
    $this->account->save();
    $this->drupalGet($old_email_reset_link);
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->assertText(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'One-time link is no longer valid.');

    // Verify a password reset link will automatically log a user when /login is
    // appended.
    $this->drupalGet('user/password');
    $edit = array('name' => $this->account->getUsername());
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $reset_url = $this->getResetURL();
    $this->drupalGet($reset_url . '/login');
    $this->assertLink(t('Log out'));
    $this->assertTitle(t('@name | @site', array('@name' => $this->account->getUsername(), '@site' => $this->config('system.site')->get('name'))), 'Logged in using password reset link.');

    // Ensure blocked and deleted accounts can't access the user.reset.login
    // route.
    $this->drupalLogout();
    $timestamp = REQUEST_TIME - 1;
    $blocked_account = $this->drupalCreateUser()->block();
    $blocked_account->save();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp) . '/login');
    $this->assertResponse(403);

    $blocked_account->delete();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp) . '/login');
    $this->assertResponse(403);
  }

  /**
   * Retrieves password reset email and extracts the login link.
   */
  public function getResetURL() {
    // Assume the most recent email.
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = array();
    preg_match('#.+user/reset/.+#', $email['body'], $urls);

    return $urls[0];
  }

  /**
   * Test user password reset while logged in.
   */
  public function testUserPasswordResetLoggedIn() {
    $another_account = $this->drupalCreateUser();
    $this->drupalLogin($another_account);
    $this->drupalGet('user/password');
    $this->drupalPostForm(NULL, NULL, t('Submit'));

    // Click the reset URL while logged and change our password.
    $resetURL = $this->getResetURL();
    // Log in as a different user.
    $this->drupalLogin($this->account);
    $this->drupalGet($resetURL);
    $this->assertRaw(new FormattableMarkup(
      'Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
      ['%other_user' => $this->account->getUsername(), '%resetting_user' => $another_account->getUsername(), ':logout' => Url::fromRoute('user.logout')->toString()]
    ));

    $another_account->delete();
    $this->drupalGet($resetURL);
    $this->assertText('The one-time login link you clicked is invalid.');

    // Log in.
    $this->drupalLogin($this->account);

    // Reset the password by username via the password reset page.
    $this->drupalGet('user/password');
    $this->drupalPostForm(NULL, NULL, t('Submit'));

    // Click the reset URL while logged and change our password.
    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    $this->drupalPostForm(NULL, NULL, t('Log in'));

    // Change the password.
    $password = user_password();
    $edit = array('pass[pass1]' => $password, 'pass[pass2]' => $password);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'), 'Password changed.');

    // Logged in users should not be able to access the user.reset.login or the
    // user.reset.form routes.
    $timestamp = REQUEST_TIME - 1;
    $this->drupalGet("user/reset/" . $this->account->id() . "/$timestamp/" . user_pass_rehash($this->account, $timestamp) . '/login');
    $this->assertResponse(403);
    $this->drupalGet("user/reset/" . $this->account->id());
    $this->assertResponse(403);
  }

  /**
   * Prefill the text box on incorrect login via link to password reset page.
   */
  public function testUserResetPasswordTextboxFilled() {
    $this->drupalGet('user/login');
    $edit = array(
      'name' => $this->randomMachineName(),
      'pass' => $this->randomMachineName(),
    );
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertRaw(t('Unrecognized username or password. <a href=":password">Forgot your password?</a>',
      array(':password' => \Drupal::url('user.pass', [], array('query' => array('name' => $edit['name']))))));
    unset($edit['pass']);
    $this->drupalGet('user/password', array('query' => array('name' => $edit['name'])));
    $this->assertFieldByName('name', $edit['name'], 'User name found.');
    // Ensure the name field value is not cached.
    $this->drupalGet('user/password');
    $this->assertNoFieldByName('name', $edit['name'], 'User name not found.');
  }

  /**
   * Make sure that users cannot forge password reset URLs of other users.
   */
  function testResetImpersonation() {
    // Create two identical user accounts except for the user name. They must
    // have the same empty password, so we can't use $this->drupalCreateUser().
    $edit = array();
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['status'] = 1;
    $user1 = User::create($edit);
    $user1->save();

    $edit['name'] = $this->randomMachineName();
    $user2 = User::create($edit);
    $user2->save();

    // Unique password hashes are automatically generated, the only way to
    // change that is to update it directly in the database.
    db_update('users_field_data')
      ->fields(['pass' => NULL])
      ->condition('uid', [$user1->id(), $user2->id()], 'IN')
      ->execute();
    \Drupal::entityManager()->getStorage('user')->resetCache();
    $user1 = User::load($user1->id());
    $user2 = User::load($user2->id());

    $this->assertEqual($user1->getPassword(), $user2->getPassword(), 'Both users have the same password hash.');

    // The password reset URL must not be valid for the second user when only
    // the user ID is changed in the URL.
    $reset_url = user_pass_reset_url($user1);
    $attack_reset_url = str_replace("user/reset/{$user1->id()}", "user/reset/{$user2->id()}", $reset_url);
    $this->drupalGet($attack_reset_url);
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->assertNoText($user2->getUsername(), 'The invalid password reset page does not show the user name.');
    $this->assertUrl('user/password', array(), 'The user is redirected to the password reset request page.');
    $this->assertText('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.');
  }

}
