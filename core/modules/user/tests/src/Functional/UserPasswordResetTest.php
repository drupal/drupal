<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Ensure that password reset methods work as expected.
 *
 * @group user
 * @group #slow
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
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
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
    $account->login = \Drupal::time()->getRequestTime() - mt_rand(10, 100000);
    Database::getConnection()->update('users_field_data')
      ->fields(['login' => $account->getLastLoginTime()])
      ->condition('uid', $account->id())
      ->execute();
  }

  /**
   * Tests password reset functionality.
   */
  public function testUserPasswordReset(): void {
    // Verify that accessing the password reset form without having the session
    // variables set results in an access denied message.
    $this->drupalGet(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));
    $this->assertSession()->statusCodeEquals(403);

    // Try to reset the password for a completely invalid username.
    $this->drupalGet('user/password');
    $long_name = $this->randomMachineName(UserInterface::USERNAME_MAX_LENGTH + 10);
    $edit = ['name' => $long_name];
    $this->submitForm($edit, 'Submit');
    $this->assertCount(0, $this->drupalGetMails(['id' => 'user_password_reset']), 'No email was sent when requesting a password for an invalid user name.');
    $this->assertSession()->pageTextContains("The username or email address is invalid.");

    // Try to reset the password for an invalid account.
    $this->drupalGet('user/password');
    $random_name = $this->randomMachineName();
    $edit = ['name' => $random_name];
    $this->submitForm($edit, 'Submit');
    $this->assertNoValidPasswordReset($random_name);

    // Try to reset the password for a valid email address longer than
    // UserInterface::USERNAME_MAX_LENGTH (invalid username, valid email).
    // This should pass validation and print the generic message.
    $this->drupalGet('user/password');
    $long_name = $this->randomMachineName(UserInterface::USERNAME_MAX_LENGTH) . '@example.com';
    $edit = ['name' => $long_name];
    $this->submitForm($edit, 'Submit');
    $this->assertNoValidPasswordReset($long_name);

    // Reset the password by username via the password reset page.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $this->assertValidPasswordReset($edit['name']);

    $resetURL = $this->getResetURL();
    $this->drupalGet($resetURL);
    // Ensure that the current URL does not contain the hash and timestamp.
    $this->assertSession()->addressEquals(Url::fromRoute('user.reset.form', ['uid' => $this->account->id()]));

    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Ensure the password reset URL is not cached.
    $this->drupalGet($resetURL);
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');

    // Check the one-time login page.
    $this->assertSession()->pageTextContains($this->account->getAccountName());
    $this->assertSession()->pageTextContains('This login can be used only once.');
    $this->assertSession()->titleEquals('Reset password | Drupal');

    // Check successful login.
    $this->submitForm([], 'Log in');
    $this->assertSession()->linkExists('Log out');
    $this->assertSession()->titleEquals($this->account->getAccountName() . ' | Drupal');

    // Change the forgotten password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Verify that the password reset session has been destroyed.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Your current password is missing or incorrect; it's required to change the Password.");

    // Log out, and try to log in again using the same one-time link.
    $this->drupalLogout();
    $this->drupalGet($resetURL);
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has either been used or is no longer valid. Request a new one using the form below.');
    $this->drupalGet($resetURL . '/login');
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has either been used or is no longer valid. Request a new one using the form below.');

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
    $this->assertSession()->pageTextNotContains('Expected user_string to be a string, NULL given');
    $this->drupalLogout();

    // Create a password reset link as if the request time was 60 seconds older than the allowed limit.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    $bogus_timestamp = \Drupal::time()->getRequestTime() - $timeout - 60;
    $_uid = $this->account->id();
    $this->drupalGet("user/reset/$_uid/$bogus_timestamp/" . user_pass_rehash($this->account, $bogus_timestamp));
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has expired. Request a new one using the form below.');
    $this->drupalGet("user/reset/$_uid/$bogus_timestamp/" . user_pass_rehash($this->account, $bogus_timestamp) . '/login');
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has expired. Request a new one using the form below.');

    // Create a user, block the account, and verify that a login link is denied.
    $timestamp = \Drupal::time()->getRequestTime() - 1;
    $blocked_account = $this->drupalCreateUser()->block();
    $blocked_account->save();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp) . '/login');
    $this->assertSession()->statusCodeEquals(403);

    // Verify a blocked user can not request a new password.
    $this->drupalGet('user/password');
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));
    $edit = ['name' => $blocked_account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $this->assertCount($before, $this->drupalGetMails(['id' => 'user_password_reset']), 'No email was sent when requesting password reset for a blocked account');

    // Verify a password reset link is invalidated when the user's email address changes.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getAccountName()];
    $this->submitForm($edit, 'Submit');
    $old_email_reset_link = $this->getResetURL();
    $this->account->setEmail("1" . $this->account->getEmail());
    $this->account->save();
    $this->drupalGet($old_email_reset_link);
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has either been used or is no longer valid. Request a new one using the form below.');
    $this->drupalGet($old_email_reset_link . '/login');
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has either been used or is no longer valid. Request a new one using the form below.');

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
    $timestamp = \Drupal::time()->getRequestTime() - 1;
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
   */
  public function testUserPasswordResetPreferredLanguage(): void {
    // Set two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('zh-hant')->save();

    $this->languageManager = \Drupal::languageManager();

    // Set language prefixes.
    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['en' => '', 'fr' => 'fr', 'zh-hant' => 'zh'])->save();
    $this->rebuildContainer();

    foreach ($this->languagePrefixTestProvider() as $scenario) {
      [$setPreferredLangcode, $activeLangcode, $prefix, $visitingUrl, $expectedResetUrl, $unexpectedResetUrl] = array_values($scenario);
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
      $this->submitForm($edit, 'Submit');
      $this->assertValidPasswordReset($edit['name']);

      $resetURL = $this->getResetURL();
      $this->assertStringContainsString($expectedResetUrl, $resetURL);
      $this->assertStringNotContainsString($unexpectedResetUrl, $resetURL);
    }
  }

  /**
   * Provides scenarios for testUserPasswordResetPreferredLanguage().
   *
   * @return array
   */
  protected function languagePrefixTestProvider() {
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
   * Tests user password reset while logged in.
   */
  public function testUserPasswordResetLoggedIn(): void {
    $another_account = $this->drupalCreateUser();
    $this->drupalLogin($another_account);
    $this->drupalGet('user/password');
    $this->submitForm([], 'Submit');

    // Click the reset URL while logged and change our password.
    $resetURL = $this->getResetURL();
    // Log in as a different user.
    $this->drupalLogin($this->account);
    $this->drupalGet($resetURL);
    $this->assertSession()->pageTextContains("Another user ({$this->account->getAccountName()}) is already logged into the site on this computer, but you tried to use a one-time link for user {$another_account->getAccountName()}. Log out and try using the link again.");
    $this->assertSession()->linkExists('Log out');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('user.logout')->toString());

    // Verify that the invalid password reset page does not show the user name.
    $attack_reset_url = "user/reset/" . $another_account->id() . "/1/1";
    $this->drupalGet($attack_reset_url);
    $this->assertSession()->pageTextNotContains($another_account->getAccountName());
    $this->assertSession()->addressEquals('user/' . $this->account->id());
    $this->assertSession()->pageTextContains('The one-time login link you clicked is invalid.');

    $another_account->delete();
    $this->drupalGet($resetURL);
    $this->assertSession()->pageTextContains('The one-time login link you clicked is invalid.');

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
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Logged in users should not be able to access the user.reset.login or the
    // user.reset.form routes.
    $timestamp = \Drupal::time()->getRequestTime() - 1;
    $this->drupalGet("user/reset/" . $this->account->id() . "/$timestamp/" . user_pass_rehash($this->account, $timestamp) . '/login');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("user/reset/" . $this->account->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the text box on incorrect login via link to password reset page.
   */
  public function testUserResetPasswordTextboxNotFilled(): void {
    $this->drupalGet('user/login');
    $edit = [
      'name' => $this->randomMachineName(),
      'pass' => $this->randomMachineName(),
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains("Unrecognized username or password. Forgot your password?");
    $this->assertSession()->linkExists("Forgot your password?");
    // Verify we don't pass the username as a query parameter.
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('user.pass', [], ['query' => ['name' => $edit['name']]])->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('user.pass')->toString());
    unset($edit['pass']);
    // Verify the field is empty by default.
    $this->drupalGet('user/password');
    $this->assertSession()->fieldValueEquals('name', '');
    // Ensure the name field value is not cached.
    $this->drupalGet('user/password', ['query' => ['name' => $edit['name']]]);
    $this->assertSession()->fieldValueEquals('name', $edit['name']);
    $this->drupalGet('user/password');
    $this->assertSession()->fieldValueNotEquals('name', $edit['name']);
  }

  /**
   * Tests password reset flood control for one user.
   */
  public function testUserResetPasswordUserFloodControl(): void {
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('user_limit', 3)
      ->save();

    $edit = ['name' => $this->account->getAccountName()];

    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/password');
      $this->submitForm($edit, 'Submit');
      $this->assertValidPasswordReset($edit['name']);
    }

    // Ensure 3 emails were sent.
    $this->assertCount($before + 3, $this->drupalGetMails(['id' => 'user_password_reset']), '3 emails sent without triggering flood control.');

    // The next request should trigger flood control.
    $this->drupalGet('user/password');
    $this->submitForm($edit, 'Submit');

    // Ensure no further emails were sent.
    $this->assertCount($before + 3, $this->drupalGetMails(['id' => 'user_password_reset']), 'No further email was sent after triggering flood control.');
  }

  /**
   * Tests password reset flood control for one IP.
   */
  public function testUserResetPasswordIpFloodControl(): void {
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('ip_limit', 3)
      ->save();

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/password');
      $random_name = $this->randomMachineName();
      $edit = ['name' => $random_name];
      $this->submitForm($edit, 'Submit');
      // Because we're testing with a random name, the password reset will not be valid.
      $this->assertNoValidPasswordReset($random_name);
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
  public function testUserResetPasswordUserFloodControlIsCleared(): void {
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('user_limit', 3)
      ->save();

    $edit = ['name' => $this->account->getAccountName()];

    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/password');
      $this->submitForm($edit, 'Submit');
      $this->assertValidPasswordReset($edit['name']);
    }

    // Ensure 3 emails were sent.
    $this->assertCount($before + 3, $this->drupalGetMails(['id' => 'user_password_reset']), '3 emails sent without triggering flood control.');

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

    // Ensure another email was sent.
    $this->assertCount($before + 4, $this->drupalGetMails(['id' => 'user_password_reset']), 'Another email was sent after clearing flood control.');
  }

  /**
   * Tests user password reset flood control is cleared on admin reset.
   */
  public function testUserResetPasswordUserFloodControlAdmin(): void {
    $admin_user = $this->drupalCreateUser([
      'administer account settings',
      'administer users',
    ]);
    \Drupal::configFactory()->getEditable('user.flood')
      ->set('user_limit', 3)
      ->save();

    $edit = [
      'name' => $this->account->getAccountName(),
      'pass' => 'wrong_password',
    ];

    // Try 3 requests that should not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->drupalGet('user/login');
      $this->submitForm($edit, 'Log in');
      $this->assertSession()->pageTextNotContains('There have been more than 3 failed login attempts for this account. It is temporarily blocked.');
    }
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->pageTextContains('There have been more than 3 failed login attempts for this account. It is temporarily blocked.');

    $password = $this->randomMachineName();
    $edit = [
      'pass[pass1]' => $password,
      'pass[pass2]' => $password,
    ];
    // Log in as admin and change the user password.
    $this->drupalLogin($admin_user);
    $this->drupalGet('user/' . $this->account->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->drupalLogout();

    $edit = [
      'name' => $this->account->getAccountName(),
      'pass' => $password,
    ];

    // The next request should *not* trigger flood control, since the
    // password change should have cleared flood events for this user.
    $this->account->passRaw = $password;
    $this->drupalLogin($this->account);

    $this->assertSession()->pageTextNotContains('There have been more than 3 failed login attempts for this account. It is temporarily blocked.');
  }

  /**
   * Helper function to make assertions about a valid password reset.
   *
   * @internal
   */
  public function assertValidPasswordReset(string $name): void {
    $this->assertSession()->pageTextContains("If $name is a valid account, an email will be sent with instructions to reset your password.");
    $this->assertMail('to', $this->account->getEmail(), 'Password email sent to user.');
    $subject = 'Replacement login information for ' . $this->account->getAccountName() . ' at Drupal';
    $this->assertMail('subject', $subject, 'Password reset email subject is correct.');
  }

  /**
   * Helper function to make assertions about an invalid password reset.
   *
   * @param string $name
   *   The user name.
   *
   * @internal
   */
  public function assertNoValidPasswordReset(string $name): void {
    // This message is the same as the valid reset for privacy reasons.
    $this->assertSession()->pageTextContains("If $name is a valid account, an email will be sent with instructions to reset your password.");
    // The difference is that no email is sent.
    $this->assertCount(0, $this->drupalGetMails(['id' => 'user_password_reset']), 'No email was sent when requesting a password for an invalid account.');
  }

  /**
   * Makes assertions about a password reset triggering IP flood control.
   *
   * @internal
   */
  public function assertPasswordIpFlood(): void {
    $this->assertSession()->pageTextContains('Too many password recovery requests from your IP address. It is temporarily blocked. Try again later or contact the site administrator.');
  }

  /**
   * Makes assertions about a password reset not triggering IP flood control.
   *
   * @internal
   */
  public function assertNoPasswordIpFlood(): void {
    $this->assertSession()->pageTextNotContains('Too many password recovery requests from your IP address. It is temporarily blocked. Try again later or contact the site administrator.');
  }

  /**
   * Make sure that users cannot forge password reset URLs of other users.
   */
  public function testResetImpersonation(): void {
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

    $this->assertEquals($user2->getPassword(), $user1->getPassword(), 'Both users have the same password hash.');

    // The password reset URL must not be valid for the second user when only
    // the user ID is changed in the URL.
    $reset_url = user_pass_reset_url($user1);
    $attack_reset_url = str_replace("user/reset/{$user1->id()}", "user/reset/{$user2->id()}", $reset_url);
    $this->drupalGet($attack_reset_url);
    // Verify that the invalid password reset page does not show the user name.
    $this->assertSession()->pageTextNotContains($user2->getAccountName());
    $this->assertSession()->addressEquals('user/password');
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has either been used or is no longer valid. Request a new one using the form below.');
    $this->drupalGet($attack_reset_url . '/login');
    // Verify that the invalid password reset page does not show the user name.
    $this->assertSession()->pageTextNotContains($user2->getAccountName());
    $this->assertSession()->addressEquals('user/password');
    $this->assertSession()->pageTextContains('You have tried to use a one-time login link that has either been used or is no longer valid. Request a new one using the form below.');
  }

  /**
   * Test the autocomplete attribute is present.
   */
  public function testResetFormHasAutocompleteAttribute(): void {
    $this->drupalGet('user/password');
    $field = $this->getSession()->getPage()->findField('name');
    $this->assertEquals('username', $field->getAttribute('autocomplete'));
  }

}
