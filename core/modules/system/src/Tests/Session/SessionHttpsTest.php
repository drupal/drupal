<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Session\SessionHttpsTest.
 */

namespace Drupal\system\Tests\Session;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountInterface;

/**
 * Ensure that when running under HTTPS two session cookies are generated.
 *
 * @group Session
 */
class SessionHttpsTest extends WebTestBase {

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
  public static $modules = array('session_test');

  protected function setUp() {
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

  public function testHttpsSession() {
    $user = $this->drupalCreateUser(array('access administration pages'));

    // Test HTTPS session handling by altering the form action to submit the
    // login form through https.php, which creates a mock HTTPS request.
    $this->loginHttps($user);

    // Test a second concurrent session.
    $this->curlClose();
    $this->curlCookies = array();
    $this->loginHttps($user);

    // Check secure cookie on secure page.
    $this->assertTrue($this->cookies[$this->secureSessionName]['secure'], 'The secure cookie has the secure attribute');
    // Check insecure cookie is not set.
    $this->assertFalse(isset($this->cookies[$this->insecureSessionName]));
    $ssid = $this->cookies[$this->secureSessionName]['value'];
    $this->assertSessionIds($ssid, 'Session has a non-empty SID and a correct secure SID.');

    // Verify that user is logged in on secure URL.
    $this->drupalGet($this->httpsUrl('admin/config'));
    $this->assertText(t('Configuration'));
    $this->assertResponse(200);

    // Verify that user is not logged in on non-secure URL.
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertNoText(t('Configuration'));
    $this->assertResponse(403);

    // Verify that empty SID cannot be used on the non-secure site.
    $this->curlClose();
    $this->curlCookies = array($this->insecureSessionName . '=');
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertResponse(403);

    // Test HTTP session handling by altering the form action to submit the
    // login form through http.php, which creates a mock HTTP request on HTTPS
    // test environments.
    $this->curlClose();
    $this->curlCookies = array();
    $this->loginHttp($user);
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertResponse(200);
    $sid = $this->cookies[$this->insecureSessionName]['value'];
    $this->assertSessionIds($sid, '', 'Session has the correct SID and an empty secure SID.');

    // Verify that empty secure SID cannot be used on the secure site.
    $this->curlClose();
    $this->curlCookies = array($this->secureSessionName . '=');
    $this->drupalGet($this->httpsUrl('admin/config'));
    $this->assertResponse(403);

    // Clear browser cookie jar.
    $this->cookies = array();
  }

  /**
   * Log in a user via HTTP.
   *
   * Note that the parents $session_id and $loggedInUser is not updated.
   */
  protected function loginHttp(AccountInterface $account) {
    $this->drupalGet('user/login');

    // Alter the form action to submit the login form through http.php, which
    // creates a mock HTTP request on HTTPS test environments.
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpUrl('user/login');
    $edit = array('name' => $account->getUsername(), 'pass' => $account->pass_raw);

    // When posting directly to the HTTP or HTTPS mock front controller, the
    // location header on the returned response is an absolute URL. That URL
    // needs to be converted into a request to the respective mock front
    // controller in order to retrieve the target page. Because the URL in the
    // location header needs to be modified, it is necessary to disable the
    // automatic redirects normally performed by parent::curlExec().
    $maximum_redirects = $this->maximumRedirects;
    $this->maximumRedirects = 0;
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->maximumRedirects = $maximum_redirects;

    // Follow the location header.
    $path = $this->getPathFromLocationHeader(FALSE);
    $this->drupalGet($this->httpUrl($path));
    $this->assertResponse(200);
  }

  /**
   * Log in a user via HTTPS.
   *
   * Note that the parents $session_id and $loggedInUser is not updated.
   */
  protected function loginHttps(AccountInterface $account) {
    $this->drupalGet('user/login');

    // Alter the form action to submit the login form through https.php, which
    // creates a mock HTTPS request on HTTP test environments.
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user/login');
    $edit = array('name' => $account->getUsername(), 'pass' => $account->pass_raw);

    // When posting directly to the HTTP or HTTPS mock front controller, the
    // location header on the returned response is an absolute URL. That URL
    // needs to be converted into a request to the respective mock front
    // controller in order to retrieve the target page. Because the URL in the
    // location header needs to be modified, it is necessary to disable the
    // automatic redirects normally performed by parent::curlExec().
    $maximum_redirects = $this->maximumRedirects;
    $this->maximumRedirects = 0;
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->maximumRedirects = $maximum_redirects;

    // When logging in via the HTTPS mock, the child site will issue a session
    // cookie with the secure attribute set. While this cookie will be stored in
    // the curl handle, it will not be used on subsequent requests via the HTTPS
    // mock, unless when operating in a true HTTPS environment. Therefore it is
    // necessary to manually collect the session cookie and add it to the
    // curlCookies property such that it will be used on subsequent requests via
    // the HTTPS mock.
    $this->curlCookies = array($this->secureSessionName . '=' . $this->cookies[$this->secureSessionName]['value']);

    // Follow the location header.
    $path = $this->getPathFromLocationHeader(TRUE);
    $this->drupalGet($this->httpsUrl($path));
    $this->assertResponse(200);
  }

  /**
   * Extract internal path from the location header on the response.
   */
  protected function getPathFromLocationHeader($https = FALSE, $response_code = 303) {
    // Generate the base_url.
    $base_url = $this->container->get('url_generator')->generateFromRoute('<front>', [], ['absolute' => TRUE]);
    if ($https) {
      $base_url = str_replace('http://', 'https://', $base_url);
    }
    else {
      $base_url = str_replace('https://', 'http://', $base_url);
    }

    // The mock front controllers (http.php and https.php) add the script name
    // to $_SERVER['REQEUST_URI'] and friends. Therefore it is necessary to
    // strip that also.
    $base_url .= 'index.php/';

    // Extract relative path from location header.
    $this->assertResponse($response_code);
    $location = $this->drupalGetHeader('location');

    $this->assertIdentical(strpos($location, $base_url), 0, 'Location header contains expected base URL');
    return substr($location, strlen($base_url));
  }

  /**
   * Test that there exists a session with two specific session IDs.
   *
   * @param $sid
   *   The insecure session ID to search for.
   * @param $assertion_text
   *   The text to display when we perform the assertion.
   *
   * @return
   *   The result of assertTrue() that there's a session in the system that
   *   has the given insecure and secure session IDs.
   */
  protected function assertSessionIds($sid, $assertion_text) {
    $args = array(
      ':sid' => Crypt::hashBase64($sid),
    );
    return $this->assertTrue(db_query('SELECT timestamp FROM {sessions} WHERE sid = :sid', $args)->fetchField(), $assertion_text);
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

}
