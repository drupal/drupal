<?php

/**
 * @file
 * Contains \Drupal\basic_auth\Tests\Authentication\BasicAuthTest.
 */

namespace Drupal\basic_auth\Tests\Authentication;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for BasicAuth authentication provider.
 *
 * @group basic_auth
 */
class BasicAuthTest extends WebTestBase {

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

    // @todo Change ->drupalGet() calls to just pass $url when
    //   https://www.drupal.org/node/2350837 gets committed
    $this->drupalGet($url->setAbsolute()->toString());
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
   * Does HTTP basic auth request.
   *
   * We do not use \Drupal\simpletest\WebTestBase::drupalGet because we need to
   * set curl settings for basic authentication.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to load into internal browser
   * @param string $username
   *   The user name to authenticate with.
   * @param string $password
   *   The password.
   *
   * @return string
   *   Curl output.
   */
  protected function basicAuthGet($path, $username, $password) {
    if ($path instanceof Url) {
      $path = $path->setAbsolute()->toString();
    }

    $out = $this->curlExec(
      array(
        CURLOPT_HTTPGET => TRUE,
        CURLOPT_URL => $path,
        CURLOPT_NOBODY => FALSE,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $username . ':' . $password,
      )
    );

    $this->verbose('GET request to: ' . $path .
      '<hr />' . $out);

    return $out;
  }

}
