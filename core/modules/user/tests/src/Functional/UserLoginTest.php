<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that login works as expected.
 *
 * @group user
 */
class UserLoginTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog'];

  /**
   * Tests login with destination.
   */
  public function testLoginCacheTagsAndDestination() {
    $this->drupalGet('user/login');
    // The user login form says "Enter your <site name> username.", hence it
    // depends on config:system.site, and its cache tags should be present.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:system.site');

    $user = $this->drupalCreateUser([]);
    $this->drupalGet('user/login', ['query' => ['destination' => 'foo']]);
    $edit = ['name' => $user->getAccountName(), 'pass' => $user->passRaw];
    $this->submitForm($edit, 'Log in');
    $this->assertSession()->addressEquals('foo');
  }

  /**
   * Tests the global login flood control.
   */
  public function testGlobalLoginFloodControl() {
    $this->config('user.flood')
      ->set('ip_limit', 10)
      // Set a high per-user limit out so that it is not relevant in the test.
      ->set('user_limit', 4000)
      ->save();

    $user1 = $this->drupalCreateUser([]);
    $incorrect_user1 = clone $user1;
    $incorrect_user1->passRaw .= 'incorrect';

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
   * Tests the per-user login flood control.
   */
  public function testPerUserLoginFloodControl() {
    $this->config('user.flood')
      // Set a high global limit out so that it is not relevant in the test.
      ->set('ip_limit', 4000)
      ->set('user_limit', 3)
      ->save();

    $user1 = $this->drupalCreateUser([]);
    $incorrect_user1 = clone $user1;
    $incorrect_user1->passRaw .= 'incorrect';

    $user2 = $this->drupalCreateUser([]);

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
   * Tests user password is re-hashed upon login after changing $count_log2.
   */
  public function testPasswordRehashOnLogin() {
    // Determine default log2 for phpass hashing algorithm
    $default_count_log2 = 16;

    // Retrieve instance of password hashing algorithm
    $password_hasher = $this->container->get('password');

    // Create a new user and authenticate.
    $account = $this->drupalCreateUser([]);
    $password = $account->passRaw;
    $this->drupalLogin($account);
    $this->drupalLogout();
    // Load the stored user. The password hash should reflect $default_count_log2.
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
    $account = User::load($account->id());
    $this->assertSame($default_count_log2, $password_hasher->getCountLog2($account->getPassword()));

    // Change the required number of iterations by loading a test-module
    // containing the necessary container builder code and then verify that the
    // users password gets rehashed during the login.
    $overridden_count_log2 = 19;
    \Drupal::service('module_installer')->install(['user_custom_phpass_params_test']);
    $this->resetAll();

    $account->passRaw = $password;
    $this->drupalLogin($account);
    // Load the stored user, which should have a different password hash now.
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    $this->assertSame($overridden_count_log2, $password_hasher->getCountLog2($account->getPassword()));
    $this->assertTrue($password_hasher->check($password, $account->getPassword()));
  }

  /**
   * Tests with a browser that denies cookies.
   */
  public function testCookiesNotAccepted() {
    $this->drupalGet('user/login');
    $form_build_id = $this->getSession()->getPage()->findField('form_build_id');

    $account = $this->drupalCreateUser([]);
    $post = [
      'form_id' => 'user_login_form',
      'form_build_id' => $form_build_id,
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
      'op' => 'Log in',
    ];
    $url = $this->buildUrl(Url::fromRoute('user.login'));

    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->getHttpClient()->post($url, [
      'form_params' => $post,
      'http_errors' => FALSE,
      'cookies' => FALSE,
      'allow_redirects' => FALSE,
    ]);

    // Follow the location header.
    $this->drupalGet($response->getHeader('location')[0]);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('To log in to this site, your browser must accept cookies from the domain');
  }

  /**
   * Make an unsuccessful login attempt.
   *
   * @param \Drupal\user\Entity\User $account
   *   A user object with name and passRaw attributes for the login attempt.
   * @param string $flood_trigger
   *   (optional) Whether or not to expect that the flood control mechanism
   *    will be triggered. Defaults to NULL.
   *   - Set to 'user' to expect a 'too many failed logins error.
   *   - Set to any value to expect an error for too many failed logins per IP.
   *   - Set to NULL to expect a failed login.
   *
   * @internal
   */
  public function assertFailedLogin(User $account, string $flood_trigger = NULL): void {
    $database = \Drupal::database();
    $edit = [
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    if (isset($flood_trigger)) {
      $this->assertSession()->statusCodeEquals(403);
      $this->assertSession()->fieldNotExists('pass');
      $last_log = $database->select('watchdog', 'w')
        ->fields('w', ['message'])
        ->condition('type', 'user')
        ->orderBy('wid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();
      if ($flood_trigger == 'user') {
        $this->assertSession()->pageTextMatches("/There (has|have) been more than \w+ failed login attempt.* for this account. It is temporarily blocked. Try again later or request a new password./");
        $this->assertSession()->linkExists("request a new password");
        $this->assertSession()->linkByHrefExists(Url::fromRoute('user.pass')->toString());
        $this->assertEquals('Flood control blocked login attempt for uid %uid from %ip', $last_log, 'A watchdog message was logged for the login attempt blocked by flood control per user.');
      }
      else {
        // No uid, so the limit is IP-based.
        $this->assertSession()->pageTextContains("Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or request a new password.");
        $this->assertSession()->linkExists("request a new password");
        $this->assertSession()->linkByHrefExists(Url::fromRoute('user.pass')->toString());
        $this->assertEquals('Flood control blocked login attempt from %ip', $last_log, 'A watchdog message was logged for the login attempt blocked by flood control per IP.');
      }
    }
    else {
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->fieldValueEquals('pass', '');
      $this->assertSession()->pageTextContains('Unrecognized username or password. Forgot your password?');
    }
  }

}
