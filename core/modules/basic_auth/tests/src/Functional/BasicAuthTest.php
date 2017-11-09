<?php

namespace Drupal\Tests\basic_auth\Functional;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Tests\basic_auth\Traits\BasicAuthTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests for BasicAuth authentication provider.
 *
 * @group basic_auth
 */
class BasicAuthTest extends BrowserTestBase {

  use BasicAuthTestTrait;

  /**
   * Modules installed for all tests.
   *
   * @var array
   */
  public static $modules = ['basic_auth', 'router_test', 'locale', 'basic_auth_test'];

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
    $this->mink->resetSessions();
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));
    $this->assertIdentical(strpos($this->drupalGetHeader('Cache-Control'), 'public'), FALSE, 'Cache-Control is not set to public');

    $this->basicAuthGet($url, $account->getUsername(), $this->randomMachineName());
    $this->assertNoText($account->getUsername(), 'Bad basic auth credentials do not authenticate the user.');
    $this->assertResponse('403', 'Access is not granted.');
    $this->mink->resetSessions();

    $this->drupalGet($url);
    $this->assertEqual($this->drupalGetHeader('WWW-Authenticate'), SafeMarkup::format('Basic realm="@realm"', ['@realm' => \Drupal::config('system.site')->get('name')]));
    $this->assertResponse('401', 'Not authenticated on the route that allows only basic_auth. Prompt to authenticate received.');

    $this->drupalGet('admin');
    $this->assertResponse('403', 'No authentication prompt for routes not explicitly defining authentication providers.');

    $account = $this->drupalCreateUser(['access administration pages']);

    $this->basicAuthGet(Url::fromRoute('system.admin'), $account->getUsername(), $account->pass_raw);
    $this->assertNoLink('Log out', 'User is not logged in');
    $this->assertResponse('403', 'No basic authentication for routes not explicitly defining authentication providers.');
    $this->mink->resetSessions();

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
  public function testGlobalLoginFloodControl() {
    $this->config('user.flood')
      ->set('ip_limit', 2)
      // Set a high per-user limit out so that it is not relevant in the test.
      ->set('user_limit', 4000)
      ->save();

    $user = $this->drupalCreateUser([]);
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
  public function testPerUserLoginFloodControl() {
    $this->config('user.flood')
      // Set a high global limit out so that it is not relevant in the test.
      ->set('ip_limit', 4000)
      ->set('user_limit', 2)
      ->save();

    $user = $this->drupalCreateUser([]);
    $incorrect_user = clone $user;
    $incorrect_user->pass_raw .= 'incorrect';
    $user2 = $this->drupalCreateUser([]);
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
  public function testLocale() {
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->config('system.site')->set('default_langcode', 'de')->save();

    $account = $this->drupalCreateUser();
    $url = Url::fromRoute('router_test.11');

    $this->basicAuthGet($url, $account->getUsername(), $account->pass_raw);
    $this->assertText($account->getUsername(), 'Account name is displayed.');
    $this->assertResponse('200', 'HTTP response is OK');
  }

  /**
   * Tests if a comprehensive message is displayed when the route is denied.
   */
  public function testUnauthorizedErrorMessage() {
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

    // Case when correct credentials but hasn't access to the route.
    $url = Url::fromRoute('router_test.15');
    $this->basicAuthGet($url, $account->getUsername(), $account->pass_raw);
    $this->assertResponse('403', 'The used authentication method is not allowed on this route.');
    $this->assertText('Access denied', "A user friendly access denied message is displayed");
  }

  /**
   * Tests the cacheability of Basic Auth's 401 response.
   *
   * @see \Drupal\basic_auth\Authentication\Provider\BasicAuth::challengeException()
   */
  public function testCacheabilityOf401Response() {
    $session = $this->getSession();
    $url = Url::fromRoute('router_test.11');

    $assert_response_cacheability = function ($expected_page_cache_header_value, $expected_dynamic_page_cache_header_value) use ($session, $url) {
      $this->drupalGet($url);
      $this->assertSession()->statusCodeEquals(401);
      $this->assertSame($expected_page_cache_header_value, $session->getResponseHeader('X-Drupal-Cache'));
      $this->assertSame($expected_dynamic_page_cache_header_value, $session->getResponseHeader('X-Drupal-Dynamic-Cache'));
    };

    // 1. First request: cold caches, both Page Cache and Dynamic Page Cache are
    // now primed.
    $assert_response_cacheability('MISS', 'MISS');
    // 2. Second request: Page Cache HIT, we don't even hit Dynamic Page Cache.
    // This is going to keep happening.
    $assert_response_cacheability('HIT', 'MISS');
    // 3. Third request: after clearing Page Cache, we now see that Dynamic Page
    // Cache is a HIT too.
    $this->container->get('cache.page')->deleteAll();
    $assert_response_cacheability('MISS', 'HIT');
    // 4. Fourth request: warm caches.
    $assert_response_cacheability('HIT', 'HIT');

    // If the permissions of the 'anonymous' role change, it may no longer be
    // necessary to be authenticated to access this route. Therefore the cached
    // 401 responses should be invalidated.
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), [$this->randomMachineName()]);
    $assert_response_cacheability('MISS', 'MISS');
    $assert_response_cacheability('HIT', 'MISS');
    // Idem for when the 'system.site' config changes.
    $this->config('system.site')->save();
    $assert_response_cacheability('MISS', 'MISS');
    $assert_response_cacheability('HIT', 'MISS');
  }

  /**
   * Tests if the controller is called before authentication.
   *
   * @see https://www.drupal.org/node/2817727
   */
  public function testControllerNotCalledBeforeAuth() {
    $this->drupalGet('/basic_auth_test/state/modify');
    $this->assertResponse(401);
    $this->drupalGet('/basic_auth_test/state/read');
    $this->assertResponse(200);
    $this->assertRaw('nope');

    $account = $this->drupalCreateUser();
    $this->basicAuthGet('/basic_auth_test/state/modify', $account->getUsername(), $account->pass_raw);
    $this->assertResponse(200);
    $this->assertRaw('Done');

    $this->mink->resetSessions();
    $this->drupalGet('/basic_auth_test/state/read');
    $this->assertResponse(200);
    $this->assertRaw('yep');
  }

}
