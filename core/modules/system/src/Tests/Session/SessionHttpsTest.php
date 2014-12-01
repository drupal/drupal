<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Session\SessionHttpsTest.
 */

namespace Drupal\system\Tests\Session;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\String;

/**
 * Ensure that when running under HTTPS two session cookies are generated.
 *
 * @group Session
 */
class SessionHttpsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('session_test');

  protected function setUp() {
    parent::setUp();
    $this->request = Request::createFromGlobals();
    $this->container->get('request_stack')->push($this->request);
  }

  protected function testHttpsSession() {
    if ($this->request->isSecure()) {
      $secure_session_name = $this->getSessionName();
      $insecure_session_name = substr($this->getSessionName(), 1);
    }
    else {
      $secure_session_name = 'S' . $this->getSessionName();
      $insecure_session_name = $this->getSessionName();
    }

    $user = $this->drupalCreateUser(array('access administration pages'));

    // Test HTTPS session handling by altering the form action to submit the
    // login form through https.php, which creates a mock HTTPS request.
    $this->drupalGet('user/login');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user/login');
    $edit = array('name' => $user->getUsername(), 'pass' => $user->pass_raw);
    $this->drupalPostForm(NULL, $edit, t('Log in'));

    // Test a second concurrent session.
    $this->curlClose();
    $this->drupalGet('user/login');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user/login');
    $this->drupalPostForm(NULL, $edit, t('Log in'));

    // Check secure cookie on secure page.
    $this->assertTrue($this->cookies[$secure_session_name]['secure'], 'The secure cookie has the secure attribute');
    // Check insecure cookie is not set.
    $this->assertFalse(isset($this->cookies[$insecure_session_name]));
    $ssid = $this->cookies[$secure_session_name]['value'];
    $this->assertSessionIds($ssid, 'Session has a non-empty SID and a correct secure SID.');
    $cookie = $secure_session_name . '=' . $ssid;

    // Verify that user is logged in on secure URL.
    $this->curlClose();
    $this->drupalGet($this->httpsUrl('admin/config'), array(), array('Cookie: ' . $cookie));
    $this->assertText(t('Configuration'));
    $this->assertResponse(200);

    // Verify that user is not logged in on non-secure URL.
    $this->curlClose();
    $this->drupalGet($this->httpUrl('admin/config'), array(), array('Cookie: ' . $cookie));
    $this->assertNoText(t('Configuration'));
    $this->assertResponse(403);

    // Verify that empty SID cannot be used on the non-secure site.
    $this->curlClose();
    $cookie = $insecure_session_name . '=';
    $this->drupalGet($this->httpUrl('admin/config'), array(), array('Cookie: ' . $cookie));
    $this->assertResponse(403);

    // Test HTTP session handling by altering the form action to submit the
    // login form through http.php, which creates a mock HTTP request on HTTPS
    // test environments.
    $this->curlClose();
    $this->drupalGet('user/login');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpUrl('user/login');
    $edit = array('name' => $user->getUsername(), 'pass' => $user->pass_raw);
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->drupalGet($this->httpUrl('admin/config'));
    $this->assertResponse(200);
    $sid = $this->cookies[$insecure_session_name]['value'];
    $this->assertSessionIds($sid, '', 'Session has the correct SID and an empty secure SID.');

    // Verify that empty secure SID cannot be used on the secure site.
    $this->curlClose();
    $cookie = $secure_session_name . '=';
    $this->drupalGet($this->httpsUrl('admin/config'), array(), array('Cookie: ' . $cookie));
    $this->assertResponse(403);

    // Clear browser cookie jar.
    $this->cookies = array();
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
   *   An absolute URL.
   */
  protected function httpsUrl($url) {
    global $base_url;
    $this->request->server->set('HTTPS', 'on');
    return $base_url . '/core/modules/system/tests/https.php/' . $url;
  }

  /**
   * Builds a URL for submitting a mock HTTP request to HTTPS test environments.
   *
   * @param $url
   *   A Drupal path such as 'user/login'.
   *
   * @return
   *   An absolute URL.
   */
  protected function httpUrl($url) {
    global $base_url;
    return $base_url . '/core/modules/system/tests/http.php/' . $url;
  }
}
