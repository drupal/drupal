<?php

/**
 * @file
 * Contains \Drupal\basic_auth\Tests\Authentication\BasicAuthTest.
 */

namespace Drupal\basic_auth\Tests\Authentication;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\basic_auth\Tests\BasicAuthTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for BasicAuth authentication provider.
 *
 * @group basic_auth
 */
class BasicAuthTest extends WebTestBase {

  use BasicAuthTestTrait;

  /**
   * Modules installed for all tests.
   *
   * @var array
   */
  public static $modules = array('basic_auth', 'router_test', 'locale');

  /**
   * Test http basic authentication.
   */
  public function testBasicAuth() {
    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    $account = $this->drupalCreateUser();
    $url = Url::fromRoute('router_test.11');

    $this->basicAuthGet($url, $account->getUsername(), $account->pass_raw);
    $this->assertText($account->getUsername(), 'Account name is displayed.');
    $this->assertResponse('200', 'HTTP response is OK');
    $this->curlClose();
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertIdentical(strpos($this->drupalGetHeader('Cache-Control'), 'public'), FALSE, 'Cache-Control is not set to public');

    $this->basicAuthGet($url, $account->getUsername(), $this->randomMachineName());
    $this->assertNoText($account->getUsername(), 'Bad basic auth credentials do not authenticate the user.');
    $this->assertResponse('403', 'Access is not granted.');
    $this->curlClose();

    $this->drupalGet($url);
    $this->assertEqual($this->drupalGetHeader('WWW-Authenticate'), SafeMarkup::format('Basic realm="@realm"', ['@realm' => \Drupal::config('system.site')->get('name')]));
    $this->assertResponse('401', 'Not authenticated on the route that allows only basic_auth. Prompt to authenticate received.');

    $this->drupalGet('admin');
    $this->assertResponse('403', 'No authentication prompt for routes not explicitly defining authentication providers.');

    $account = $this->drupalCreateUser(array('access administration pages'));

    $this->basicAuthGet(Url::fromRoute('system.admin'), $account->getUsername(), $account->pass_raw);
    $this->assertNoLink('Log out', 'User is not logged in');
    $this->assertResponse('403', 'No basic authentication for routes not explicitly defining authentication providers.');
    $this->curlClose();

    // Ensure that pages already in the page cache aren't returned from page
    // cache if basic auth credentials are provided.
    $url = Url::fromRoute('router_test.10');
    $this->drupalGet($url);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->basicAuthGet($url, $account->getUsername(), $account->pass_raw);
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertIdentical(strpos($this->drupalGetHeader('Cache-Control'), 'public'), FALSE, 'No page cache response when requesting a cached page with basic auth credentials.');
  }

  /**
   * Test the global login flood control.
   */
  function testGlobalLoginFloodControl() {
    $this->config('user.flood')
      ->set('ip_limit', 2)
      // Set a high per-user limit out so that it is not relevant in the test.
      ->set('user_limit', 4000)
      ->save();

    $user = $this->drupalCreateUser(array());
    $incorrect_user = clone $user;
    $incorrect_user->pass_raw .= 'incorrect';
    $url = Url::fromRoute('router_test.11');

    // Try 2 failed logins.
    for ($i = 0; $i < 2; $i++) {
      $this->basicAuthGet($url, $incorrect_user->getUsername(), $incorrect_user->pass_raw);
    }

    // IP limit has reached to its limit. Even valid user credentials will fail.
    $this->basicAuthGet($url, $user->getUsername(), $user->pass_raw);
    $this->assertResponse('403', 'Access is blocked because of IP based flood prevention.');
  }

  /**
   * Test the per-user login flood control.
   */
  function testPerUserLoginFloodControl() {
    $this->config('user.flood')
      // Set a high global limit out so that it is not relevant in the test.
      ->set('ip_limit', 4000)
      ->set('user_limit', 2)
      ->save();

    $user = $this->drupalCreateUser(array());
    $incorrect_user = clone $user;
    $incorrect_user->pass_raw .= 'incorrect';
    $user2 = $this->drupalCreateUser(array());
    $url = Url::fromRoute('router_test.11');

    // Try a failed login.
    $this->basicAuthGet($url, $incorrect_user->getUsername(), $incorrect_user->pass_raw);

    // A successful login will reset the per-user flood control count.
    $this->basicAuthGet($url, $user->getUsername(), $user->pass_raw);
    $this->assertResponse('200', 'Per user flood prevention gets reset on a successful login.');

    // Try 2 failed logins for a user. They will trigger flood control.
    for ($i = 0; $i < 2; $i++) {
      $this->basicAuthGet($url, $incorrect_user->getUsername(), $incorrect_user->pass_raw);
    }

    // Now the user account is blocked.
    $this->basicAuthGet($url, $user->getUsername(), $user->pass_raw);
    $this->assertResponse('403', 'The user account is blocked due to per user flood prevention.');

    // Try one successful attempt for a different user, it should not trigger
    // any flood control.
    $this->basicAuthGet($url, $user2->getUsername(), $user2->pass_raw);
    $this->assertResponse('200', 'Per user flood prevention does not block access for other users.');
  }

  /**
   * Tests compatibility with locale/UI translation.
   */
  function testLocale() {
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->config('system.site')->set('default_langcode', 'de')->save();

    $account = $this->drupalCreateUser();
    $url = Url::fromRoute('router_test.11');

    $this->basicAuthGet($url, $account->getUsername(), $account->pass_raw);
    $this->assertText($account->getUsername(), 'Account name is displayed.');
    $this->assertResponse('200', 'HTTP response is OK');
    $this->curlClose();
  }

  /**
   * Tests if a comprehensive message is displayed when the route is denied.
   */
  function testUnauthorizedErrorMessage() {
    $account = $this->drupalCreateUser();
    $url = Url::fromRoute('router_test.11');

    // Case when no credentials are passed.
    $this->drupalGet($url);
    $this->assertResponse('401', 'The user is blocked when no credentials are passed.');
    $this->assertNoText('Exception', "No raw exception is displayed on the page.");
    $this->assertText('Please log in to access this page.', "A user friendly access unauthorized message is displayed.");

    // Case when empty credentials are passed.
    $this->basicAuthGet($url, NULL, NULL);
    $this->assertResponse('403', 'The user is blocked when empty credentials are passed.');
    $this->assertText('Access denied', "A user friendly access denied message is displayed");

    // Case when wrong credentials are passed.
    $this->basicAuthGet($url, $account->getUsername(), $this->randomMachineName());
    $this->assertResponse('403', 'The user is blocked when wrong credentials are passed.');
    $this->assertText('Access denied', "A user friendly access denied message is displayed");
  }

}
