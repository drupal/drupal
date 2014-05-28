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
 */
class SessionHttpsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('session_test');

  public static function getInfo() {
    return array(
      'name' => 'Session HTTPS handling',
      'description' => 'Ensure that when running under HTTPS two session cookies are generated.',
      'group' => 'Session'
    );
  }

  public function setUp() {
    parent::setUp();
    $this->request = Request::createFromGlobals();
    $this->container->set('request', $this->request);
  }

  protected function testHttpsSession() {
    if ($this->request->isSecure()) {
      $secure_session_name = session_name();
      $insecure_session_name = substr(session_name(), 1);
    }
    else {
      $secure_session_name = 'S' . session_name();
      $insecure_session_name = session_name();
    }

    $user = $this->drupalCreateUser(array('access administration pages'));

    // Test HTTPS session handling by altering the form action to submit the
    // login form through https.php, which creates a mock HTTPS request.
    $this->drupalGet('user');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user');
    $edit = array('name' => $user->getUsername(), 'pass' => $user->pass_raw);
    $this->drupalPostForm(NULL, $edit, t('Log in'));

    // Test a second concurrent session.
    $this->curlClose();
    $this->drupalGet('user');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user');
    $this->drupalPostForm(NULL, $edit, t('Log in'));

    // Check secure cookie on secure page.
    $this->assertTrue($this->cookies[$secure_session_name]['secure'], 'The secure cookie has the secure attribute');
    // Check insecure cookie is not set.
    $this->assertFalse(isset($this->cookies[$insecure_session_name]));
    $ssid = $this->cookies[$secure_session_name]['value'];
    $this->assertSessionIds($ssid, $ssid, 'Session has a non-empty SID and a correct secure SID.');
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
    $this->drupalGet('user');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpUrl('user');
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
   * Tests sessions in SSL mixed mode.
   */
  protected function testMixedModeSslSession() {
    if ($this->request->isSecure()) {
      // The functionality does not make sense when running on HTTPS.
      return;
    }
    else {
      $secure_session_name = 'S' . session_name();
      $insecure_session_name = session_name();
    }

    // Enable secure pages.
    $this->settingsSet('mixed_mode_sessions', TRUE);
    // Write that value also into the test settings.php file.
    $settings['settings']['mixed_mode_sessions'] = (object) array(
      'value' => TRUE,
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    $user = $this->drupalCreateUser(array('access administration pages'));

    $this->curlClose();
    // Start an anonymous session on the insecure site.
    $session_data = $this->randomName();
    $this->drupalGet('session-test/set/' . $session_data);
    // Check secure cookie on insecure page.
    $this->assertFalse(isset($this->cookies[$secure_session_name]), 'The secure cookie is not sent on insecure pages.');
    // Check insecure cookie on insecure page.
    $this->assertFalse($this->cookies[$insecure_session_name]['secure'], 'The insecure cookie does not have the secure attribute');

    // Store the anonymous cookie so we can validate that its session is killed
    // after login.
    $anonymous_cookie = $insecure_session_name . '=' . $this->cookies[$insecure_session_name]['value'];

    // Check that password request form action is not secure.
    $this->drupalGet('user/password');
    $form = $this->xpath('//form[@id="user-pass"]');
    $this->assertNotEqual(substr($form[0]['action'], 0, 6), 'https:', 'Password request form action is not secure');
    $form[0]['action'] = $this->httpsUrl('user');

    // Check that user login form action is secure.
    $this->drupalGet('user');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $this->assertEqual(substr($form[0]['action'], 0, 6), 'https:', 'Login form action is secure');
    $form[0]['action'] = $this->httpsUrl('user');

    $edit = array(
      'name' => $user->getUsername(),
      'pass' => $user->pass_raw,
    );
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    // Check secure cookie on secure page.
    $this->assertTrue($this->cookies[$secure_session_name]['secure'], 'The secure cookie has the secure attribute');
    // Check insecure cookie on secure page.
    $this->assertFalse($this->cookies[$insecure_session_name]['secure'], 'The insecure cookie does not have the secure attribute');

    $sid = $this->cookies[$insecure_session_name]['value'];
    $ssid = $this->cookies[$secure_session_name]['value'];
    $this->assertSessionIds($sid, $ssid, 'Session has both secure and insecure SIDs');
    $cookies = array(
      $insecure_session_name . '=' . $sid,
      $secure_session_name . '=' . $ssid,
    );

    // Test that session data saved before login is still available on the
    // authenticated session.
    $this->drupalGet('session-test/get');
    $this->assertText($session_data, 'Session correctly returned the stored data set by the anonymous session.');

    foreach ($cookies as $cookie_key => $cookie) {
      foreach (array('admin/config', $this->httpsUrl('admin/config')) as $url_key => $url) {
        $this->curlClose();

        $this->drupalGet($url, array(), array('Cookie: ' . $cookie));
        if ($cookie_key == $url_key) {
          $this->assertText(t('Configuration'));
          $this->assertResponse(200);
        }
        else {
          $this->assertNoText(t('Configuration'));
          $this->assertResponse(403);
        }
      }
    }

    // Test that session data saved before login is not available using the
    // pre-login anonymous cookie.
    $this->cookies = array();
    $this->drupalGet('session-test/get', array('Cookie: ' . $anonymous_cookie));
    $this->assertNoText($session_data, 'Initial anonymous session is inactive after login.');

    // Clear browser cookie jar.
    $this->cookies = array();

    // Start an anonymous session on the secure site.
    $this->drupalGet($this->httpsUrl('session-test/set/1'));

    // Mock a login to the secure site using the secure session cookie.
    $this->drupalGet('user');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user');
    $this->drupalPostForm(NULL, $edit, t('Log in'));

    // Test that the user is also authenticated on the insecure site.
    $this->drupalGet("user/" . $user->id() . "/edit");
    $this->assertResponse(200);
  }

  /**
   * Ensure that a CSRF form token is shared in SSL mixed mode.
   */
  protected function testCsrfTokenWithMixedModeSsl() {
    if ($this->request->isSecure()) {
      $secure_session_name = session_name();
      $insecure_session_name = substr(session_name(), 1);
    }
    else {
      $secure_session_name = 'S' . session_name();
      $insecure_session_name = session_name();
    }

    // Enable mixed mode SSL.
    $this->settingsSet('mixed_mode_sessions', TRUE);
    // Write that value also into the test settings.php file.
    $settings['settings']['mixed_mode_sessions'] = (object) array(
      'value' => TRUE,
      'required' => TRUE,
    );
    $this->writeSettings($settings);

    $user = $this->drupalCreateUser(array('access administration pages'));

    // Login using the HTTPS user-login form.
    $this->drupalGet('user');
    $form = $this->xpath('//form[@id="user-login-form"]');
    $form[0]['action'] = $this->httpsUrl('user');
    $edit = array('name' => $user->getUsername(), 'pass' => $user->pass_raw);
    $this->drupalPostForm(NULL, $edit, t('Log in'));

    // Collect session id cookies.
    $sid = $this->cookies[$insecure_session_name]['value'];
    $ssid = $this->cookies[$secure_session_name]['value'];
    $this->assertSessionIds($sid, $ssid, 'Session has both secure and insecure SIDs');

    // Retrieve the form via HTTP.
    $this->curlClose();
    $this->drupalGet($this->httpUrl('session-test/form'), array(), array('Cookie: ' . $insecure_session_name . '=' . $sid));
    $http_token = $this->getFormToken();

    // Verify that submitting form values via HTTPS to a form originally
    // retrieved over HTTP works.
    $form = $this->xpath('//form[@id="session-test-form"]');
    $form[0]['action'] = $this->httpsUrl('session-test/form');
    $edit = array('input' => $this->randomName(32));
    $this->curlClose();
    $this->drupalPostForm(NULL, $edit, 'Save', array('Cookie: ' . $secure_session_name . '=' . $ssid));
    $this->assertText(String::format('Ok: @input', array('@input' => $edit['input'])));

    // Retrieve the same form via HTTPS.
    $this->curlClose();
    $this->drupalGet($this->httpsUrl('session-test/form'), array(), array('Cookie: ' . $secure_session_name . '=' . $ssid));
    $https_token = $this->getFormToken();

    // Verify that CSRF token values are the same for a form regardless of
    // whether it was accessed via HTTP or HTTPS when SSL mixed mode is enabled.
    $this->assertEqual($http_token, $https_token, 'Form token is the same on HTTP as well as HTTPS form');
  }

  /**
   * Return the token of the current form.
   */
  protected function getFormToken() {
    $token_fields = $this->xpath('//input[@name="form_token"]');
    $this->assertEqual(count($token_fields), 1, 'One form token field on the page');
    return (string) $token_fields[0]['value'];
  }

  /**
   * Test that there exists a session with two specific session IDs.
   *
   * @param $sid
   *   The insecure session ID to search for.
   * @param $ssid
   *   The secure session ID to search for.
   * @param $assertion_text
   *   The text to display when we perform the assertion.
   *
   * @return
   *   The result of assertTrue() that there's a session in the system that
   *   has the given insecure and secure session IDs.
   */
  protected function assertSessionIds($sid, $ssid, $assertion_text) {
    $args = array(
      ':sid' => Crypt::hashBase64($sid),
      ':ssid' => !empty($ssid) ? Crypt::hashBase64($ssid) : '',
    );
    return $this->assertTrue(db_query('SELECT timestamp FROM {sessions} WHERE sid = :sid AND ssid = :ssid', $args)->fetchField(), $assertion_text);
  }

  /**
   * Builds a URL for submitting a mock HTTPS request to HTTP test environments.
   *
   * @param $url
   *   A Drupal path such as 'user'.
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
   *   A Drupal path such as 'user'.
   *
   * @return
   *   An absolute URL.
   */
  protected function httpUrl($url) {
    global $base_url;
    return $base_url . '/core/modules/system/tests/http.php/' . $url;
  }
}
