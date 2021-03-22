<?php

namespace Drupal\Tests\system\Functional\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ensure that when running under HTTPS two session cookies are generated.
 *
 * @group Session
 */
class SessionHttpsTest extends BrowserTestBase {

  /**
   * The name of the session cookie when using HTTP.
   *
   * @var string
   */
  protected $insecureSessionName;

  /**
   * The name of the session cookie when using HTTPS.
   *
   * @var string
   */
  protected $secureSessionName;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['session_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $request = Request::createFromGlobals();
    if ($request->isSecure()) {
      $this->secureSessionName = $this->getSessionName();
      $this->insecureSessionName = substr($this->getSessionName(), 1);
    }
    else {
      $this->secureSessionName = 'S' . $this->getSessionName();
      $this->insecureSessionName = $this->getSessionName();
    }
  }

  /**
   * Tests HTTPS sessions.
   */
  public function testHttpsSession() {
    $user = $this->drupalCreateUser(['access administration pages']);

    /** @var \Symfony\Component\BrowserKit\CookieJar $browser_kit_cookie_jar */
    $browser_kit_cookie_jar = $this->getSession()->getDriver()->getClient()->getCookieJar();

    // Test HTTPS session handling by submitting the login form through
    // https.php, which creates a mock HTTPS request.
    $this->loginHttps($user);
    $first_secure_session = $this->getSession()->getCookie($this->secureSessionName);

    // Test a second concurrent session.
    $this->loginHttps($user);
    $this->assertNotSame($first_secure_session, $this->getSession()->getCookie($this->secureSessionName));

    // Check secure cookie is set.
    $this->assertTrue((bool) $this->getSession()->getCookie($this->secureSessionName));
    // Check insecure cookie is not set.
    $this->assertFalse((bool) $this->getSession()->getCookie($this->insecureSessionName));
    $this->assertSessionIds($this->getSession()->getCookie($this->secureSessionName), 'Session has a non-empty SID and a correct secure SID.');
    $this->assertSessionIds($first_secure_session, 'The first secure session still exists.');

    // Verify that user is logged in on secure URL.
    $this->drupalGet($this->httpsUrl('admin/config'));
    $this->assertText('Configuration');
    $this->assertSession()->statusCodeEquals(200);

    // Verify that user is not logged in on non-secure URL.
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertNoText('Configuration');
    $this->assertSession()->statusCodeEquals(403);

    // Verify that empty SID cannot be used on the non-secure site.
    $browser_kit_cookie_jar->set(Cookie::fromString($this->insecureSessionName . '=', $this->baseUrl));
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertSession()->statusCodeEquals(403);

    // Remove the secure session name from the cookie jar before logging in via
    // HTTP on HTTPS environments.
    $browser_kit_cookie_jar->expire($this->secureSessionName);

    // Test HTTP session handling by submitting the login form through http.php,
    // which creates a mock HTTP request on HTTPS test environments.
    $this->loginHttp($user);
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSessionIds($this->getSession()->getCookie($this->insecureSessionName), 'Session has the correct SID and an empty secure SID.');

    // Verify that empty secure SID cannot be used on the secure site.
    $browser_kit_cookie_jar->set(Cookie::fromString($this->secureSessionName . '=', $this->baseUrl));
    $this->drupalGet($this->httpsUrl('admin/config'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Log in a user via HTTP.
   *
   * Note that the parents $session_id and $loggedInUser is not updated.
   */
  protected function loginHttp(AccountInterface $account) {
    $guzzle_cookie_jar = $this->getGuzzleCookieJar();
    $post = [
      'form_id' => 'user_login_form',
      'form_build_id' => $this->getUserLoginFormBuildId(),
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
      'op' => 'Log in',
    ];
    $url = $this->buildUrl($this->httpUrl('user/login'));
    // When posting directly to the HTTP or HTTPS mock front controller, the
    // location header on the returned response is an absolute URL. That URL
    // needs to be converted into a request to the respective mock front
    // controller in order to retrieve the target page. Because the URL in the
    // location header needs to be modified, it is necessary to disable the
    // automatic redirects normally performed by the Guzzle CurlHandler.
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->getHttpClient()->post($url, [
      'form_params' => $post,
      'http_errors' => FALSE,
      'cookies' => $guzzle_cookie_jar,
      'allow_redirects' => FALSE,
    ]);

    // When logging in via the HTTP mock, the child site will issue a session
    // cookie without the secure attribute set. While this cookie will be stored
    // in the Guzzle CookieJar, it will not be used on subsequent requests.
    // Update the BrowserKit CookieJar so that subsequent requests have the
    // correct cookie.
    $cookie = $guzzle_cookie_jar->getCookieByName($this->insecureSessionName);
    $this->assertFalse($cookie->getSecure(), 'The insecure cookie does not have the secure attribute');
    /** @var \Symfony\Component\BrowserKit\CookieJar $browser_kit_cookie_jar */
    $browser_kit_cookie_jar = $this->getSession()->getDriver()->getClient()->getCookieJar();
    $browser_kit_cookie_jar->updateFromSetCookie($response->getHeader('Set-Cookie'), $this->baseUrl);

    // Follow the location header.
    $path = $this->getPathFromLocationHeader($response, FALSE);
    $this->drupalGet($this->httpUrl($path));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Log in a user via HTTPS.
   *
   * Note that the parents $session_id and $loggedInUser is not updated.
   */
  protected function loginHttps(AccountInterface $account) {
    $guzzle_cookie_jar = $this->getGuzzleCookieJar();
    $post = [
      'form_id' => 'user_login_form',
      'form_build_id' => $this->getUserLoginFormBuildId(),
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
      'op' => 'Log in',
    ];
    $url = $this->buildUrl($this->httpsUrl('user/login'));
    // When posting directly to the HTTP or HTTPS mock front controller, the
    // location header on the returned response is an absolute URL. That URL
    // needs to be converted into a request to the respective mock front
    // controller in order to retrieve the target page. Because the URL in the
    // location header needs to be modified, it is necessary to disable the
    // automatic redirects normally performed by the Guzzle CurlHandler.
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->getHttpClient()->post($url, [
      'form_params' => $post,
      'http_errors' => FALSE,
      'cookies' => $guzzle_cookie_jar,
      'allow_redirects' => FALSE,
    ]);

    // When logging in via the HTTPS mock, the child site will issue a session
    // cookie with the secure attribute set. While this cookie will be stored in
    // the Guzzle CookieJar, it will not be used on subsequent requests.
    // Update the BrowserKit CookieJar so that subsequent requests have the
    // correct cookie.
    $cookie = $guzzle_cookie_jar->getCookieByName($this->secureSessionName);
    $this->assertTrue($cookie->getSecure(), 'The secure cookie has the secure attribute');
    /** @var \Symfony\Component\BrowserKit\CookieJar $browser_kit_cookie_jar */
    $browser_kit_cookie_jar = $this->getSession()->getDriver()->getClient()->getCookieJar();
    $browser_kit_cookie_jar->updateFromSetCookie($response->getHeader('Set-Cookie'), $this->baseUrl);

    // Follow the location header.
    $path = $this->getPathFromLocationHeader($response, TRUE);
    $this->drupalGet($this->httpsUrl($path));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Extracts internal path from the location header on the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response from logging in.
   * @param bool $https
   *   Whether the log in was via HTTPS. Defaults to FALSE.
   *
   * @return string
   *   The internal path from the location header on the response.
   */
  protected function getPathFromLocationHeader(ResponseInterface $response, $https = FALSE) {
    if ($https) {
      $base_url = str_replace('http://', 'https://', $this->baseUrl);
    }
    else {
      $base_url = str_replace('https://', 'http://', $this->baseUrl);
    }

    // The mock front controllers (http.php and https.php) add the script name
    // to $_SERVER['REQUEST_URI'] and friends. Therefore it is necessary to
    // strip that also.
    $base_url .= '/index.php/';

    // Extract relative path from location header.
    $this->assertSame(303, $response->getStatusCode());
    $location = $response->getHeader('location')[0];

    $this->assertStringStartsWith($base_url, $location, 'Location header contains expected base URL');
    return substr($location, strlen($base_url));
  }

  /**
   * Test that there exists a session with two specific session IDs.
   *
   * @param $sid
   *   The insecure session ID to search for.
   * @param $assertion_text
   *   The text to display when we perform the assertion.
   */
  protected function assertSessionIds($sid, $assertion_text) {
    $this->assertNotEmpty(\Drupal::database()->select('sessions', 's')->fields('s', ['timestamp'])->condition('sid', Crypt::hashBase64($sid))->execute()->fetchField(), $assertion_text);
  }

  /**
   * Builds a URL for submitting a mock HTTPS request to HTTP test environments.
   *
   * @param $url
   *   A Drupal path such as 'user/login'.
   *
   * @return
   *   URL prepared for the https.php mock front controller.
   */
  protected function httpsUrl($url) {
    return 'core/modules/system/tests/https.php/' . $url;
  }

  /**
   * Builds a URL for submitting a mock HTTP request to HTTPS test environments.
   *
   * @param $url
   *   A Drupal path such as 'user/login'.
   *
   * @return
   *   URL prepared for the http.php mock front controller.
   */
  protected function httpUrl($url) {
    return 'core/modules/system/tests/http.php/' . $url;
  }

  /**
   * Creates a new Guzzle CookieJar with a Xdebug cookie if necessary.
   *
   * @return \GuzzleHttp\Cookie\CookieJar
   *   The Guzzle CookieJar.
   */
  protected function getGuzzleCookieJar() {
    // @todo Add xdebug cookie.
    $cookies = $this->extractCookiesFromRequest(\Drupal::request());
    foreach ($cookies as $cookie_name => $values) {
      $cookies[$cookie_name] = $values[0];
    }
    return CookieJar::fromArray($cookies, $this->baseUrl);
  }

  /**
   * Gets the form build ID for the user login form.
   *
   * @return string
   *   The form build ID for the user login form.
   */
  protected function getUserLoginFormBuildId() {
    $this->drupalGet('user/login');
    return (string) $this->getSession()->getPage()->findField('form_build_id');
  }

}
