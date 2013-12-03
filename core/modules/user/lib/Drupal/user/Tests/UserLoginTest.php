<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserLoginTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Password\PhpassHashedPassword;

/**
 * Functional tests for user logins, including rate limiting of login attempts.
 */
class UserLoginTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User login',
      'description' => 'Ensure that login works as expected.',
      'group' => 'User',
    );
  }

  /**
   * Test the global login flood control.
   */
  function testGlobalLoginFloodControl() {
    \Drupal::config('user.flood')
      ->set('ip_limit', 10)
      // Set a high per-user limit out so that it is not relevant in the test.
      ->set('user_limit', 4000)
      ->save();

    $user1 = $this->drupalCreateUser(array());
    $incorrect_user1 = clone $user1;
    $incorrect_user1->pass_raw .= 'incorrect';

    // Try 2 failed logins.
    for ($i = 0; $i < 2; $i++) {
      $this->assertFailedLogin($incorrect_user1);
    }

    // A successful login will not reset the IP-based flood control count.
    $this->drupalLogin($user1);
    $this->drupalLogout();

    // Try 8 more failed logins, they should not trigger the flood control
    // mechanism.
    for ($i = 0; $i < 8; $i++) {
      $this->assertFailedLogin($incorrect_user1);
    }

    // The next login trial should result in an IP-based flood error message.
    $this->assertFailedLogin($incorrect_user1, 'ip');

    // A login with the correct password should also result in a flood error
    // message.
    $this->assertFailedLogin($user1, 'ip');
  }

  /**
   * Test the per-user login flood control.
   */
  function testPerUserLoginFloodControl() {
    \Drupal::config('user.flood')
      // Set a high global limit out so that it is not relevant in the test.
      ->set('ip_limit', 4000)
      ->set('user_limit', 3)
      ->save();

    $user1 = $this->drupalCreateUser(array());
    $incorrect_user1 = clone $user1;
    $incorrect_user1->pass_raw .= 'incorrect';

    $user2 = $this->drupalCreateUser(array());

    // Try 2 failed logins.
    for ($i = 0; $i < 2; $i++) {
      $this->assertFailedLogin($incorrect_user1);
    }

    // A successful login will reset the per-user flood control count.
    $this->drupalLogin($user1);
    $this->drupalLogout();

    // Try 3 failed logins for user 1, they will not trigger flood control.
    for ($i = 0; $i < 3; $i++) {
      $this->assertFailedLogin($incorrect_user1);
    }

    // Try one successful attempt for user 2, it should not trigger any
    // flood control.
    $this->drupalLogin($user2);
    $this->drupalLogout();

    // Try one more attempt for user 1, it should be rejected, even if the
    // correct password has been used.
    $this->assertFailedLogin($user1, 'user');
  }

  /**
   * Test that user password is re-hashed upon login after changing $count_log2.
   */
  function testPasswordRehashOnLogin() {
    // Determine default log2 for phpass hashing algorithm
    $default_count_log2 = 16;

    // Retrieve instance of password hashing algorithm
    $password_hasher = $this->container->get('password');

    // Create a new user and authenticate.
    $account = $this->drupalCreateUser(array());
    $password = $account->pass_raw;
    $this->drupalLogin($account);
    $this->drupalLogout();
    // Load the stored user. The password hash should reflect $default_count_log2.
    $account = user_load($account->id());
    $this->assertIdentical($password_hasher->getCountLog2($account->getPassword()), $default_count_log2);

    // Change the required number of iterations by loading a test-module
    // containing the necessary container builder code and then verify that the
    // users password gets rehashed during the login.
    $overridden_count_log2 = 19;
    \Drupal::moduleHandler()->install(array('user_custom_phpass_params_test'));

    $account->pass_raw = $password;
    $this->drupalLogin($account);
    // Load the stored user, which should have a different password hash now.
    $account = user_load($account->id(), TRUE);
    $this->assertIdentical($password_hasher->getCountLog2($account->getPassword()), $overridden_count_log2);
  }

  /**
   * Make an unsuccessful login attempt.
   *
   * @param $account
   *   A user object with name and pass_raw attributes for the login attempt.
   * @param $flood_trigger
   *   Whether or not to expect that the flood control mechanism will be
   *   triggered.
   */
  function assertFailedLogin($account, $flood_trigger = NULL) {
    $edit = array(
      'name' => $account->getUsername(),
      'pass' => $account->pass_raw,
    );
    $this->drupalPostForm('user', $edit, t('Log in'));
    $this->assertNoFieldByXPath("//input[@name='pass' and @value!='']", NULL, 'Password value attribute is blank.');
    if (isset($flood_trigger)) {
      if ($flood_trigger == 'user') {
        $this->assertRaw(format_plural(\Drupal::config('user.flood')->get('user_limit'), 'Sorry, there has been more than one failed login attempt for this account. It is temporarily blocked. Try again later or <a href="@url">request a new password</a>.', 'Sorry, there have been more than @count failed login attempts for this account. It is temporarily blocked. Try again later or <a href="@url">request a new password</a>.', array('@url' => url('user/password'))));
      }
      else {
        // No uid, so the limit is IP-based.
        $this->assertRaw(t('Sorry, too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href="@url">request a new password</a>.', array('@url' => url('user/password'))));
      }
    }
    else {
      $this->assertText(t('Sorry, unrecognized username or password. Have you forgotten your password?'));
    }
  }
}
