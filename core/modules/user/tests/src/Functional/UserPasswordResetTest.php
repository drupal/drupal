<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that password reset methods work as expected.
 *
 * @group user
 */
class UserPasswordResetTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * The user object to test password resetting.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Language manager object.
   *
   * @var \Drupal\language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
    $this->drupalPlaceBlock('system_menu_block:account');

    // Create a user.
    $account = $this->drupalCreateUser();

    // Activate user by logging in.
    $this->drupalLogin($account);

    $this->account = User::load($account->id());
    $this->account->passRaw = $account->passRaw;
    $this->drupalLogout();

    // Set the last login time that is used to generate the one-time link so
    // that it is definitely over a second ago.
    $account->login = REQUEST_TIME - mt_rand(10, 100000);
    Database::getConnection()->update('users_field_data')
      ->fields(['login' => $account->getLastLoginTime()])
      ->condition('uid', $account->id())
      ->execute();
  }

  /**
   * Tests password reset functionality.
   */
  public function testUserPasswordReset() {
    // Verify that accessing the password reset form without having the session
    // variables set results in an access denied message.
    $this->drupalGet(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));
    $this->assertSession()->statusCodeEquals(403);

    // Try to reset the password for an invalid account.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->randomMachineName()];
    $this->submitForm($edit, 'Submit');
    $this->assertNoValidPasswordReset($edit['name']);

    // Reset the password by username via the password reset page.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $this->assertValidPasswordReset($edit['name']);

    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    // Ensure that the current url does not contain the hash and timestamp.
    $this->assertSession()->addressEquals(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));

    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Ensure the password reset URL is not cached.
    $this->drupalGet($resetURL);
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check the one-time login page.
    $this->assertText($this->account->getAccountName());
    $this->assertText('This login can be used only once.');
    $this->assertSession()->titleEquals('Reset password | Drupal');

    // Check successful login.
    $this->submitForm([], 'Log in');
    $this->assertSession()->linkExists('Log out');
    $this->assertSession()->titleEquals($this->account->getAccountName() . ' | Drupal');

    // Change the forgotten password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->submitForm($edit, 'Save');
    $this->assertText('The changes have been saved.');

    // Verify that the password reset session has been destroyed.
    $this->submitForm($edit, 'Save');
    $this->assertText("Your current password is missing or incorrect; it's required to change the Password.");

    // Log out, and try to log in again using the same one-time link.
    $this->drupalLogout();
    $this->drupalGet($resetURL);
    $this->submitForm([], 'Log in');
    $this->assertText('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.');

    // Request a new password again, this time using the email address.
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getEmail()];
    $this->submitForm($edit, 'Submit');
    $this->assertValidPasswordReset($edit['name']);
    $this->assertCount($before + 1, $this->drupalGetMails(['id' => 'user_password_reset']), 'Email sent when requesting password reset using email address.');

    // Visit the user edit page without pass-reset-token and make sure it does
    // not cause an error.
    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    $this->submitForm([], 'Log in');
    $this->drupalGet('user/' . $this->account->id() . '/edit');
    $this->assertNoText('Expected user_string to be a string, NULL given');
    $this->drupalLogout();

    // Create a password reset link as if the request time was 60 seconds older than the allowed limit.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    $bogus_timestamp = REQUEST_TIME - $timeout - 60;
    $_uid = $this->account->id();
    $this->drupalGet("user/reset/$_uid/$bogus_timestamp/" . user_pass_rehash($this->account, $bogus_timestamp));
    $this->submitForm([], 'Log in');
    $this->assertText('You have tried to use a one-time login link that has expired. Please request a new one using the form below.');

    // Create a user, block the account, and verify that a login link is denied.
    $timestamp = REQUEST_TIME - 1;
    $blocked_account = $this->drupalCreateUser()->block();
    $blocked_account->save();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp));
    $this->assertSession()->statusCodeEquals(403);

    // Verify a blocked user can not request a new password.
    $this->drupalGet('user/password');
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));
    $edit = ['name' => $blocked_account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $this->assertRaw(t('%name is blocked or has not been activated yet.', ['%name' => $blocked_account->getAccountName()]));
    $this->assertCount($before, $this->drupalGetMails(['id' => 'user_password_reset']), 'No email was sent when requesting password reset for a blocked account');

    // Verify a password reset link is invalidated when the user's email address changes.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $old_email_reset_link = $this->getResetURL();
    $this->account->setEmail("1" . $this->account->getEmail());
    $this->account->save();
    $this->drupalGet($old_email_reset_link);
    $this->submitForm([], 'Log in');
    $this->assertText('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.');

    // Verify a password reset link will automatically log a user when /login is
    // appended.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $reset_url = $this->getResetURL();
    $this->drupalGet($reset_url . '/login');
    $this->assertSession()->linkExists('Log out');
    $this->assertSession()->titleEquals($this->account->getAccountName() . ' | Drupal');

    // Ensure blocked and deleted accounts can't access the user.reset.login
    // route.
    $this->drupalLogout();
    $timestamp = REQUEST_TIME - 1;
    $blocked_account = $this->drupalCreateUser()->block();
    $blocked_account->save();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp) . '/login');
    $this->assertSession()->statusCodeEquals(403);

    $blocked_account->delete();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp) . '/login');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests password reset functionality when user has set preferred language.
   *
   * @dataProvider languagePrefixTestProvider
   */
  public function testUserPasswordResetPreferredLanguage($setPreferredLangcode, $activeLangcode, $prefix, $visitingUrl, $expectedResetUrl, $unexpectedResetUrl) {
    // Set two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('zh-hant')->save();

    $this->languageManager = \Drupal::languageManager();

    // Set language prefixes.
    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['en' => '', 'fr' => 'fr', 'zh-hant' => 'zh'])->save();
    $this->rebuildContainer();

    $this->account->preferred_langcode = $setPreferredLangcode;
    $this->account->save();
    $this->assertSame($setPreferredLangcode, $this->account->getPreferredLangcode(FALSE));

    // Test Default langcode is different from active langcode when visiting different.
    if ($setPreferredLangcode !== 'en') {
      $this->drupalGet($prefix . '/user/password');
      $this->assertSame($activeLangcode, $this->getSession()->getResponseHeader('Content-language'));
      $this->assertSame('en', $this->languageManager->getDefaultLanguage()->getId());
    }

    // Test password reset with language prefixes.
    $this->drupalGet($visitingUrl);
    $edit = ['name' => $this->account->getAccountName()];
    $this->submitForm($edit, t('Submit'));
    $this->assertValidPasswordReset($edit['name']);

    $resetURL = $this->getResetURL();
    $this->assertStringContainsString($expectedResetUrl, $resetURL);
    $this->assertStringNotContainsString($unexpectedResetUrl, $resetURL);
  }

  /**
   * Data provider for testUserPasswordResetPreferredLanguage().
   *
   * @return array
   */
  public function languagePrefixTestProvider() {
    return [
      'Test language prefix set as \'\', visiting default with preferred language as en' => [
        'setPreferredLangcode' => 'en',
        'activeLangcode' => 'en',
        'prefix' => '',
        'visitingUrl' => 'user/password',
        'expectedResetUrl' => 'user/reset',
        'unexpectedResetUrl' => 'en/user/reset',
      ],
      'Test language prefix set as fr, visiting zh with preferred language as fr' => [
        'setPreferredLangcode' => 'fr',
        'activeLangcode' => 'fr',
        'prefix' => 'fr',
        'visitingUrl' => 'zh/user/password',
        'expectedResetUrl' => 'fr/user/reset',
        'unexpectedResetUrl' => 'zh/user/reset',
      ],
      'Test language prefix set as zh, visiting zh with preferred language as \'\'' => [
        'setPreferredLangcode' => '',
        'activeLangcode' => 'zh-hant',
        'prefix' => 'zh',
        'visitingUrl' => 'zh/user/password',
        'expectedResetUrl' => 'user/reset',
        'unexpectedResetUrl' => 'zh/user/reset',
      ],
    ];
  }

  /**
   * Retrieves password reset email and extracts the login link.
   */
  public function getResetURL() {
    // Assume the most recent email.
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
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
    $this->submitForm([], 'Submit');

    // Click the reset URL while logged and change our password.
    $resetURL = $this->getResetURL();
    // Log in as a different user.
    $this->drupalLogin($this->account);
    $this->drupalGet($resetURL);
    $this->assertRaw(new FormattableMarkup(
      'Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
      ['%other_user' => $this->account->getAccountName(), '%resetting_user' => $another_account->getAccountName(), ':logout' => Url::fromRoute('user.logout')->toString()]
    ));

    $another_account->delete();
    $this->drupalGet($resetURL);
    $this->assertText('The one-time login link you clicked is invalid.');

    // Log in.
    $this->drupalLogin($this->account);

    // Reset the password by username via the password reset page.
    $this->drupalGet('user/password');
    $this->submitForm([], 'Submit');

    // Click the reset URL while logged and change our password.
    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    $this->submitForm([], 'Log in');

    // Change the password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->submitForm($edit, 'Save');
    $this->assertText('The changes have been saved.');

    // Logged in users should not be able to access the user.reset.login or the
    // user.reset.form routes.
    $timestamp = REQUEST_TIME - 1;
    $this->drupalGet("user/reset/" . $this->account->id() . "/$timestamp/" . user_pass_rehash($this->account, $timestamp) . '/login');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("user/reset/" . $this->account->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Prefill the text box on incorrect login via link to password reset page.
   */
  public function testUserResetPasswordTextboxFilled() {
    $this->drupalGet('user/login');
    $edit = [
      'name' => $this->randomMachineName(),
      'pass' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('user/login', $edit, 'Log in');
    $this->assertRaw(t('Unrecognized username or password. <a href=":password">Forgot your password?</a>',
      [':password' => Url::fromRoute('user.pass', [], ['query' => ['name' => $edit['name']]])->toString()]));
    unset($edit['pass']);
    $this->drupalGet('user/password', ['query' => ['name' => $edit['name']]]);
    $this->assertSession()->fieldValueEquals('name', $edit['name']);
    // Ensure the name field value is not cached.
    $this->drupalGet('user/password');
    $this->assertSession()->fieldValueNotEquals('name', $edit['name']);
  }

  /**
   * Tests password reset flood control for one user.
   */
  public function testUserResetPasswordUserFloodControl() {
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('user_limit', 3)
      ->save();

    $edit = ['name' => $this->account->getAccountName()];

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/password');
      $this->submitForm($edit, 'Submit');
      $this->assertValidPasswordReset($edit['name']);
      $this->assertNoPasswordUserFlood();
    }

    // The next request should trigger flood control.
    $this->drupalGet('user/password');
    $this->submitForm($edit, 'Submit');
    $this->assertPasswordUserFlood();
  }

  /**
   * Tests password reset flood control for one IP.
   */
  public function testUserResetPasswordIpFloodControl() {
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('ip_limit', 3)
      ->save();

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/password');
      $edit = ['name' => $this->randomMachineName()];
      $this->submitForm($edit, 'Submit');
      // Because we're testing with a random name, the password reset will not be valid.
      $this->assertNoValidPasswordReset($edit['name']);
      $this->assertNoPasswordIpFlood();
    }

    // The next request should trigger flood control.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->randomMachineName()];
    $this->submitForm($edit, 'Submit');
    $this->assertPasswordIpFlood();
  }

  /**
   * Tests user password reset flood control is cleared on successful reset.
   */
  public function testUserResetPasswordUserFloodControlIsCleared() {
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('user_limit', 3)
      ->save();

    $edit = ['name' => $this->account->getAccountName()];

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/password');
      $this->submitForm($edit, 'Submit');
      $this->assertValidPasswordReset($edit['name']);
      $this->assertNoPasswordUserFlood();
    }

    // Use the last password reset URL which was generated.
    $reset_url = $this->getResetURL();
    $this->drupalGet($reset_url . '/login');
    $this->assertSession()->linkExists('Log out');
    $this->assertSession()->titleEquals($this->account->getAccountName() . ' | Drupal');
    $this->drupalLogout();

    // The next request should *not* trigger flood control, since a successful
    // password reset should have cleared flood events for this user.
    $this->drupalGet('user/password');
    $this->submitForm($edit, 'Submit');
    $this->assertValidPasswordReset($edit['name']);
    $this->assertNoPasswordUserFlood();
  }

  /**
   * Helper function to make assertions about a valid password reset.
   */
  public function assertValidPasswordReset($name) {
    // Make sure the error text is not displayed and email sent.
    $this->assertNoText("Sorry, $name is not recognized as a username or an e-mail address.");
    $this->assertMail('to', $this->account->getEmail(), 'Password e-mail sent to user.');
    $subject = t('Replacement login information for @username at @site', ['@username' => $this->account->getAccountName(), '@site' => \Drupal::config('system.site')->get('name')]);
    $this->assertMail('subject', $subject, 'Password reset e-mail subject is correct.');
  }

  /**
   * Helper function to make assertions about an invalid password reset.
   */
  public function assertNoValidPasswordReset($name) {
    // Make sure the error text is displayed and no email sent.
    $this->assertText($name . ' is not recognized as a username or an email address.');
    $this->assertCount(0, $this->drupalGetMails(['id' => 'user_password_reset']), 'No e-mail was sent when requesting a password for an invalid account.');
  }

  /**
   * Makes assertions about a password reset triggering user flood control.
   */
  public function assertPasswordUserFlood() {
    $this->assertText('Too many password recovery requests for this account. It is temporarily blocked. Try again later or contact the site administrator.');
  }

  /**
   * Makes assertions about a password reset not triggering user flood control.
   */
  public function assertNoPasswordUserFlood() {
    $this->assertNoText('Too many password recovery requests for this account. It is temporarily blocked. Try again later or contact the site administrator.');
  }

  /**
   * Makes assertions about a password reset triggering IP flood control.
   */
  public function assertPasswordIpFlood() {
    $this->assertText('Too many password recovery requests from your IP address. It is temporarily blocked. Try again later or contact the site administrator.');
  }

  /**
   * Makes assertions about a password reset not triggering IP flood control.
   */
  public function assertNoPasswordIpFlood() {
    $this->assertNoText('Too many password recovery requests from your IP address. It is temporarily blocked. Try again later or contact the site administrator.');
  }

  /**
   * Make sure that users cannot forge password reset URLs of other users.
   */
  public function testResetImpersonation() {
    // Create two identical user accounts except for the user name. They must
    // have the same empty password, so we can't use $this->drupalCreateUser().
    $edit = [];
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
    Database::getConnection()->update('users_field_data')
      ->fields(['pass' => NULL])
      ->condition('uid', [$user1->id(), $user2->id()], 'IN')
      ->execute();
    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
    $user1 = User::load($user1->id());
    $user2 = User::load($user2->id());

    $this->assertEqual($user2->getPassword(), $user1->getPassword(), 'Both users have the same password hash.');

    // The password reset URL must not be valid for the second user when only
    // the user ID is changed in the URL.
    $reset_url = user_pass_reset_url($user1);
    $attack_reset_url = str_replace("user/reset/{$user1->id()}", "user/reset/{$user2->id()}", $reset_url);
    $this->drupalGet($attack_reset_url);
    $this->submitForm([], 'Log in');
    // Verify that the invalid password reset page does not show the user name.
    $this->assertNoText($user2->getAccountName());
    $this->assertSession()->addressEquals('user/password');
    $this->assertText('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.');
  }

}
