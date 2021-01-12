<?php

namespace Drupal\Tests\system\Functional\Session;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Drupal session handling tests.
 *
 * @group Session
 */
class SessionTest extends BrowserTestBase {

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

  protected $dumpHeaders = TRUE;

  /**
   * Tests for \Drupal\Core\Session\WriteSafeSessionHandler::setSessionWritable()
   * ::isSessionWritable and \Drupal\Core\Session\SessionManager::regenerate().
   */
  public function testSessionSaveRegenerate() {
    $session_handler = $this->container->get('session_handler.write_safe');
    $this->assertTrue($session_handler->isSessionWritable(), 'session_handler->isSessionWritable() initially returns TRUE.');
    $session_handler->setSessionWritable(FALSE);
    $this->assertFalse($session_handler->isSessionWritable(), '$session_handler->isSessionWritable() returns FALSE after disabling.');
    $session_handler->setSessionWritable(TRUE);
    $this->assertTrue($session_handler->isSessionWritable(), '$session_handler->isSessionWritable() returns TRUE after enabling.');

    // Test session hardening code from SA-2008-044.
    $user = $this->drupalCreateUser();

    // Enable sessions.
    $this->sessionReset();

    // Make sure the session cookie is set as HttpOnly. We can only test this in
    // the header, with the test setup
    // \GuzzleHttp\Cookie\SetCookie::getHttpOnly() always returns FALSE.
    // Start a new session by setting a message.
    $this->drupalGet('session-test/set-message');
    $this->assertSessionCookie(TRUE);
    // Verify that the session cookie is set as HttpOnly.
    $this->assertSession()->responseHeaderMatches('Set-Cookie', '/HttpOnly/i');

    // Verify that the session is regenerated if a module calls exit
    // in hook_user_login().
    $user->name = 'session_test_user';
    $user->save();
    $this->drupalGet('session-test/id');
    $matches = [];
    preg_match('/\s*session_id:(.*)\n/', $this->getSession()->getPage()->getContent(), $matches);
    $this->assertTrue(!empty($matches[1]), 'Found session ID before logging in.');
    $original_session = $matches[1];

    // We cannot use $this->drupalLogin($user); because we exit in
    // session_test_user_login() which breaks a normal assertion.
    $edit = [
      'name' => $user->getAccountName(),
      'pass' => $user->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, 'Log in');
    $this->drupalGet('user');
    $pass = $this->assertText($user->getAccountName(), new FormattableMarkup('Found name: %name', ['%name' => $user->getAccountName()]), 'User login');
    $this->_logged_in = $pass;

    $this->drupalGet('session-test/id');
    $matches = [];
    preg_match('/\s*session_id:(.*)\n/', $this->getSession()->getPage()->getContent(), $matches);
    $this->assertTrue(!empty($matches[1]), 'Found session ID after logging in.');
    $this->assertTrue($matches[1] != $original_session, 'Session ID changed after login.');
  }

  /**
   * Test data persistence via the session_test module callbacks.
   */
  public function testDataPersistence() {
    $user = $this->drupalCreateUser([]);
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
    $session = $this->getSession();
    $this->assertText($value_2, 'The session value was correctly passed to session-test/no-set.', 'Session');
    $this->drupalGet('session-test/get');
    $this->assertText($value_1, 'Session data is not saved for drupal_save_session(FALSE).', 'Session');

    // Switch browser cookie to anonymous user, then back to user 1.
    $session_cookie_name = $this->getSessionName();
    $session_cookie_value = $session->getCookie($session_cookie_name);
    $session->restart();
    $this->initFrontPage();
    // Session restart always resets all the cookies by design, so we need to
    // add the old session cookie again.
    $session->setCookie($session_cookie_name, $session_cookie_value);
    $this->drupalGet('session-test/get');
    $this->assertText($value_1, 'Session data persists through browser close.', 'Session');
    $this->mink->setDefaultSessionName('default');

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
    $user2 = $this->drupalCreateUser([]);
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
  public function testEmptyAnonymousSession() {
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
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Start a new session by setting a message.
    $this->drupalGet('session-test/set-message');
    $this->assertSessionCookie(TRUE);
    $this->assertNotNull($this->getSession()->getResponseHeader('Set-Cookie'));

    // Display the message, during the same request the session is destroyed
    // and the session cookie is unset.
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(FALSE);
    // Verify that caching was bypassed.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    $this->assertText('This is a dummy message.', 'Message was displayed.');
    // Verify that session cookie was deleted.
    $this->assertSession()->responseHeaderMatches('Set-Cookie', '/SESS\w+=deleted/');

    // Verify that session was destroyed.
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    // @todo Reinstate when REQUEST and RESPONSE events fire for cached pages.
    // $this->assertSessionEmpty(TRUE);
    $this->assertNoText('This is a dummy message.', 'Message was not cached.');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->responseHeaderDoesNotExist('Set-Cookie');

    // Verify that no session is created if drupal_save_session(FALSE) is called.
    $this->drupalGet('session-test/set-message-but-do-not-save');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(TRUE);

    // Verify that no message is displayed.
    $this->drupalGet('');
    $this->assertSessionCookie(FALSE);
    // @todo Reinstate when REQUEST and RESPONSE events fire for cached pages.
    // $this->assertSessionEmpty(TRUE);
    $this->assertNoText('This is a dummy message.', 'The message was not saved.');
  }

  /**
   * Test that sessions are only saved when necessary.
   */
  public function testSessionWrite() {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);
    $connection = Database::getConnection();

    $query = $connection->select('users_field_data', 'u');
    $query->innerJoin('sessions', 's', 'u.uid = s.uid');
    $query->fields('u', ['access'])
      ->fields('s', ['timestamp'])
      ->condition('u.uid', $user->id());
    $times1 = $query->execute()->fetchObject();

    // Before every request we sleep one second to make sure that if the session
    // is saved, its timestamp will change.

    // Modify the session.
    sleep(1);
    $this->drupalGet('session-test/set/foo');
    $times2 = $query->execute()->fetchObject();
    $this->assertEqual($times2->access, $times1->access, 'Users table was not updated.');
    $this->assertNotEqual($times2->timestamp, $times1->timestamp, 'Sessions table was updated.');

    // Write the same value again, i.e. do not modify the session.
    sleep(1);
    $this->drupalGet('session-test/set/foo');
    $times3 = $query->execute()->fetchObject();
    $this->assertEqual($times3->access, $times1->access, 'Users table was not updated.');
    $this->assertEqual($times3->timestamp, $times2->timestamp, 'Sessions table was not updated.');

    // Do not change the session.
    sleep(1);
    $this->drupalGet('');
    $times4 = $query->execute()->fetchObject();
    $this->assertEqual($times4->access, $times3->access, 'Users table was not updated.');
    $this->assertEqual($times4->timestamp, $times3->timestamp, 'Sessions table was not updated.');

    // Force updating of users and sessions table once per second.
    $settings['settings']['session_write_interval'] = (object) [
      'value' => 0,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    $this->drupalGet('');
    $times5 = $query->execute()->fetchObject();
    $this->assertNotEqual($times5->access, $times4->access, 'Users table was updated.');
    $this->assertNotEqual($times5->timestamp, $times4->timestamp, 'Sessions table was updated.');
  }

  /**
   * Test that empty session IDs are not allowed.
   */
  public function testEmptySessionID() {
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);
    $this->drupalGet('session-test/is-logged-in');
    $this->assertSession()->statusCodeEquals(200);

    // Reset the sid in {sessions} to a blank string. This may exist in the
    // wild in some cases, although we normally prevent it from happening.
    Database::getConnection()->update('sessions')
      ->fields([
        'sid' => '',
      ])
      ->condition('uid', $user->id())
      ->execute();
    // Send a blank sid in the session cookie, and the session should no longer
    // be valid. Closing the curl handler will stop the previous session ID
    // from persisting.
    $this->mink->resetSessions();
    $this->drupalGet('session-test/id-from-cookie');
    // Verify that session ID is blank as sent from cookie header.
    $this->assertRaw("session_id:\n");
    // Assert that we have an anonymous session now.
    $this->drupalGet('session-test/is-logged-in');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test session bag.
   */
  public function testSessionBag() {
    // Ensure the flag is absent to start with.
    $this->drupalGet('/session-test/has-bag-flag');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(TRUE);
    $this->assertSession()->statusCodeEquals(200);

    // Set the flag.
    $this->drupalGet('/session-test/set-bag-flag');
    $this->assertSessionCookie(TRUE);
    $this->assertSessionEmpty(TRUE);
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the flag is set.
    $this->drupalGet('/session-test/has-bag-flag');
    $this->assertSessionCookie(TRUE);
    $this->assertSessionEmpty(FALSE);
    $this->assertSession()->statusCodeEquals(200);

    // Clear the flag.
    $this->drupalGet('/session-test/clear-bag-flag');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(FALSE);
    $this->assertSession()->statusCodeEquals(200);

    // Ensure the flag is absent again.
    $this->drupalGet('/session-test/has-bag-flag');
    $this->assertSessionCookie(FALSE);
    $this->assertSessionEmpty(TRUE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Reset the cookie file so that it refers to the specified user.
   */
  public function sessionReset() {
    // Close the internal browser.
    $this->mink->resetSessions();
    $this->loggedInUser = FALSE;

    // Change cookie file for user.
    $this->drupalGet('session-test/get');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Assert whether the SimpleTest browser sent a session cookie.
   */
  public function assertSessionCookie($sent) {
    if ($sent) {
      $this->assertNotEmpty($this->getSessionCookies()->count(), 'Session cookie was sent.');
    }
    else {
      $this->assertEmpty($this->getSessionCookies()->count(), 'Session cookie was not sent.');
    }
  }

  /**
   * Assert whether $_SESSION is empty at the beginning of the request.
   */
  public function assertSessionEmpty($empty) {
    if ($empty) {
      $this->assertSession()->responseHeaderEquals('X-Session-Empty', '1');
    }
    else {
      $this->assertSession()->responseHeaderEquals('X-Session-Empty', '0');
    }
  }

}
