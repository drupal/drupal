<?php

namespace Drupal\system\Tests\Session;

use Drupal\simpletest\WebTestBase;

/**
 * Drupal session handling tests.
 *
 * @group Session
 */
class SessionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('session_test');

  protected $dumpHeaders = TRUE;

  /**
   * Tests for \Drupal\Core\Session\WriteSafeSessionHandler::setSessionWritable()
   * ::isSessionWritable and \Drupal\Core\Session\SessionManager::regenerate().
   */
  function testSessionSaveRegenerate() {
    $session_handler = $this->container->get('session_handler.write_safe');
    $this->assertTrue($session_handler->isSessionWritable(), 'session_handler->isSessionWritable() initially returns TRUE.');
    $session_handler->setSessionWritable(FALSE);
    $this->assertFalse($session_handler->isSessionWritable(), '$session_handler->isSessionWritable() returns FALSE after disabling.');
    $session_handler->setSessionWritable(TRUE);
    $this->assertTrue($session_handler->isSessionWritable(), '$session_handler->isSessionWritable() returns TRUE after enabling.');

    // Test session hardening code from SA-2008-044.
    $user = $this->drupalCreateUser();

    // Enable sessions.
    $this->sessionReset($user->id());

    // Make sure the session cookie is set as HttpOnly.
    $this->drupalLogin($user);
    $this->assertTrue(preg_match('/HttpOnly/i', $this->drupalGetHeader('Set-Cookie', TRUE)), 'Session cookie is set as HttpOnly.');
    $this->drupalLogout();

    // Verify that the session is regenerated if a module calls exit
    // in hook_user_login().
    $user->name = 'session_test_user';
    $user->save();
    $this->drupalGet('session-test/id');
    $matches = array();
    preg_match('/\s*session_id:(.*)\n/', $this->getRawContent(), $matches);
    $this->assertTrue(!empty($matches[1]), 'Found session ID before logging in.');
    $original_session = $matches[1];

    // We cannot use $this->drupalLogin($user); because we exit in
    // session_test_user_login() which breaks a normal assertion.
    $edit = array(
      'name' => $user->getUsername(),
      'pass' => $user->pass_raw
    );
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->drupalGet('user');
    $pass = $this->assertText($user->getUsername(), format_string('Found name: %name', array('%name' => $user->getUsername())), 'User login');
    $this->_logged_in = $pass;

    $this->drupalGet('session-test/id');
    $matches = array();
    preg_match('/\s*session_id:(.*)\n/', $this->getRawContent(), $matches);
    $this->assertTrue(!empty($matches[1]), 'Found session ID after logging in.');
    $this->assertTrue($matches[1] != $original_session, 'Session ID changed after login.');
  }

  /**
   * Test data persistence via the session_test module callbacks.
   */
  function testDataPersistence() {
    $user = $this->drupalCreateUser(array());
    // Enable sessions.
    $this->sessionReset($user->id());

    $this->drupalLogin($user);

    $value_1 = $this->randomMachineName();
    $this->drupalGet('session-test/set/' . $value_1);
    $this->assertText($value_1, 'The session value was stored.', 'Session');
    $this->drupalGet('session-test/get');
    $this->assertText($value_1, 'Session correctly returned the stored data for an authenticated user.', 'Session');

    // Attempt to write over val_1. If drupal_save_session(FALSE) is working.
    // properly, val_1 will still be set.
    $value_2 = $this->randomMachineName();
    $this->drupalGet('session-test/no-set/' . $value_2);
    $this->assertText($value_2, 'The session value was correctly passed to session-test/no-set.', 'Session');
    $this->drupalGet('session-test/get');
    $this->assertText($value_1, 'Session data is not saved for drupal_save_session(FALSE).', 'Session');

    // Switch browser cookie to anonymous user, then back to user 1.
    $this->sessionReset();
    $this->sessionReset($user->id());
    $this->assertText($value_1, 'Session data persists through browser close.', 'Session');

    // Logout the user and make sure the stored value no longer persists.
    $this->drupalLogout();
    $this->sessionReset();
    $this->drupalGet('session-test/get');
    $this->assertNoText($value_1, "After logout, previous user's session data is not available.", 'Session');

    // Now try to store some data as an anonymous user.
    $value_3 = $this->randomMachineName();
    $this->drupalGet('session-test/set/' . $value_3);
    $this->assertText($value_3, 'Session data stored for anonymous user.', 'Session');
    $this->drupalGet('session-test/get');
    $this->assertText($value_3, 'Session correctly returned the stored data for an anonymous user.', 'Session');

    // Try to store data when drupal_save_session(FALSE).
    $value_4 = $this->randomMachineName();
    $this->drupalGet('session-test/no-set/' . $value_4);
    $this->assertText($value_4, 'The session value was correctly passed to session-test/no-set.', 'Session');
    $this->drupalGet('session-test/get');
    $this->assertText($value_3, 'Session data is not saved for drupal_save_session(FALSE).', 'Session');

    // Login, the data should persist.
    $this->drupalLogin($user);
    $this->sessionReset($user->id());
    $this->drupalGet('session-test/get');
    $this->assertNoText($value_1, 'Session has persisted for an authenticated user after logging out and then back in.', 'Session');

    // Change session and create another user.
    $user2 = $this->drupalCreateUser(array());
    $this->sessionReset($user2->id());
    $this->drupalLogin($user2);
  }

  /**
   * Tests storing data in Session() object.
   */
  public function testSessionPersistenceOnLogin() {
    // Store information via hook_user_login().
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    // Test property added to session object form hook_user_login().
    $this->drupalGet('session-test/get-from-session-object');
    $this->assertText('foobar', 'Session data is saved in Session() object.', 'Session');
  }

  /**
   * Test that empty anonymous sessions are destroyed.
   */
  function testEmptyAnonymousSession() {
    // Disable the dynamic_page_cache module; it'd cause session_test's debug
    // output (that is added in
    // SessionTestSubscriber::onKernelResponseSessionTest()) to not be added.
    $this->container->get('module_installer')->uninstall(['dynamic_page_cache']);

    // Verify that no session is automatically created for anonymous user when
    // page caching is disabled.
    $this->container->get('module_installer')->uninstall(['page_cache']);
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(TRUE);

    // The same behavior is expected when caching is enabled.
    $this->container->get('module_installer')->install(['page_cache']);
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    // @todo Reinstate when REQUEST and RESPONSE events fire for cached pages.
    // $this->assertSessionEmpty(TRUE);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');

    // Start a new session by setting a message.
    $this->drupalGet('session-test/set-message');
    $this->assertSessionCookie(TRUE);
    $this->assertTrue($this->drupalGetHeader('Set-Cookie'), 'New session was started.');

    // Display the message, during the same request the session is destroyed
    // and the session cookie is unset.
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(FALSE);
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'), 'Caching was bypassed.');
    $this->assertText(t('This is a dummy message.'), 'Message was displayed.');
    $this->assertTrue(preg_match('/SESS\w+=deleted/', $this->drupalGetHeader('Set-Cookie')), 'Session cookie was deleted.');

    // Verify that session was destroyed.
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    // @todo Reinstate when REQUEST and RESPONSE events fire for cached pages.
    // $this->assertSessionEmpty(TRUE);
    $this->assertNoText(t('This is a dummy message.'), 'Message was not cached.');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertFalse($this->drupalGetHeader('Set-Cookie'), 'New session was not started.');

    // Verify that no session is created if drupal_save_session(FALSE) is called.
    $this->drupalGet('session-test/set-message-but-dont-save');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(TRUE);

    // Verify that no message is displayed.
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    // @todo Reinstate when REQUEST and RESPONSE events fire for cached pages.
    // $this->assertSessionEmpty(TRUE);
    $this->assertNoText(t('This is a dummy message.'), 'The message was not saved.');
  }

  /**
   * Test that sessions are only saved when necessary.
   */
  function testSessionWrite() {
    $user = $this->drupalCreateUser(array());
    $this->drupalLogin($user);

    $sql = 'SELECT u.access, s.timestamp FROM {users_field_data} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE u.uid = :uid';
    $times1 = db_query($sql, array(':uid' => $user->id()))->fetchObject();

    // Before every request we sleep one second to make sure that if the session
    // is saved, its timestamp will change.

    // Modify the session.
    sleep(1);
    $this->drupalGet('session-test/set/foo');
    $times2 = db_query($sql, array(':uid' => $user->id()))->fetchObject();
    $this->assertEqual($times2->access, $times1->access, 'Users table was not updated.');
    $this->assertNotEqual($times2->timestamp, $times1->timestamp, 'Sessions table was updated.');

    // Write the same value again, i.e. do not modify the session.
    sleep(1);
    $this->drupalGet('session-test/set/foo');
    $times3 = db_query($sql, array(':uid' => $user->id()))->fetchObject();
    $this->assertEqual($times3->access, $times1->access, 'Users table was not updated.');
    $this->assertEqual($times3->timestamp, $times2->timestamp, 'Sessions table was not updated.');

    // Do not change the session.
    sleep(1);
    $this->drupalGet('');
    $times4 = db_query($sql, array(':uid' => $user->id()))->fetchObject();
    $this->assertEqual($times4->access, $times3->access, 'Users table was not updated.');
    $this->assertEqual($times4->timestamp, $times3->timestamp, 'Sessions table was not updated.');

    // Force updating of users and sessions table once per second.
    $this->settingsSet('session_write_interval', 0);
    // Write that value also into the test settings.php file.
    $settings['settings']['session_write_interval'] = (object) array(
      'value' => 0,
      'required' => TRUE,
    );
    $this->writeSettings($settings);
    $this->drupalGet('');
    $times5 = db_query($sql, array(':uid' => $user->id()))->fetchObject();
    $this->assertNotEqual($times5->access, $times4->access, 'Users table was updated.');
    $this->assertNotEqual($times5->timestamp, $times4->timestamp, 'Sessions table was updated.');
  }

  /**
   * Test that empty session IDs are not allowed.
   */
  function testEmptySessionID() {
    $user = $this->drupalCreateUser(array());
    $this->drupalLogin($user);
    $this->drupalGet('session-test/is-logged-in');
    $this->assertResponse(200, 'User is logged in.');

    // Reset the sid in {sessions} to a blank string. This may exist in the
    // wild in some cases, although we normally prevent it from happening.
    db_query("UPDATE {sessions} SET sid = '' WHERE uid = :uid", array(':uid' => $user->id()));
    // Send a blank sid in the session cookie, and the session should no longer
    // be valid. Closing the curl handler will stop the previous session ID
    // from persisting.
    $this->curlClose();
    $this->additionalCurlOptions[CURLOPT_COOKIE] = rawurlencode($this->getSessionName()) . '=;';
    $this->drupalGet('session-test/id-from-cookie');
    $this->assertRaw("session_id:\n", 'Session ID is blank as sent from cookie header.');
    // Assert that we have an anonymous session now.
    $this->drupalGet('session-test/is-logged-in');
    $this->assertResponse(403, 'An empty session ID is not allowed.');
  }

  /**
   * Reset the cookie file so that it refers to the specified user.
   *
   * @param $uid User id to set as the active session.
   */
  function sessionReset($uid = 0) {
    // Close the internal browser.
    $this->curlClose();
    $this->loggedInUser = FALSE;

    // Change cookie file for user.
    $this->cookieFile = \Drupal::service('stream_wrapper_manager')->getViaScheme('temporary')->getDirectoryPath() . '/cookie.' . $uid . '.txt';
    $this->additionalCurlOptions[CURLOPT_COOKIEFILE] = $this->cookieFile;
    $this->additionalCurlOptions[CURLOPT_COOKIESESSION] = TRUE;
    $this->drupalGet('session-test/get');
    $this->assertResponse(200, 'Session test module is correctly enabled.', 'Session');
  }

  /**
   * Assert whether the SimpleTest browser sent a session cookie.
   */
  function assertSessionCookie($sent) {
    if ($sent) {
      $this->assertNotNull($this->sessionId, 'Session cookie was sent.');
    }
    else {
      $this->assertNull($this->sessionId, 'Session cookie was not sent.');
    }
  }

  /**
   * Assert whether $_SESSION is empty at the beginning of the request.
   */
  function assertSessionEmpty($empty) {
    if ($empty) {
      $this->assertIdentical($this->drupalGetHeader('X-Session-Empty'), '1', 'Session was empty.');
    }
    else {
      $this->assertIdentical($this->drupalGetHeader('X-Session-Empty'), '0', 'Session was not empty.');
    }
  }

}
