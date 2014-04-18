<?php

/**
 * @file
 * Contains \Drupal\Core\Session\SessionManager.
 */

namespace Drupal\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Settings;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionHandler;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages user sessions.
 */
class SessionManager implements SessionManagerInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Whether a lazy session has been started.
   *
   * @var bool
   */
  protected $lazySession;

  /**
   * Whether session management is enabled or temporarily disabled.
   *
   * PHP session ID, session, and cookie handling happens in the global scope.
   * This value has to persist, since a potentially wrong or disallowed session
   * would be written otherwise.
   *
   * @var bool
   */
  protected static $enabled = TRUE;

  /**
   * Constructs a new session manager instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(RequestStack $request_stack, Connection $connection) {
    $this->requestStack = $request_stack;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize() {
    global $user;

    // Register the default session handler.
    // @todo Extract session storage from session handler into a service.
    $handler = new SessionHandler($this, $this->requestStack, $this->connection);
    session_set_save_handler($handler, TRUE);

    $is_https = $this->requestStack->getCurrentRequest()->isSecure();
    $cookies = $this->requestStack->getCurrentRequest()->cookies;
    if (($cookies->has(session_name()) && ($session_name = $cookies->get(session_name()))) || ($is_https && Settings::get('mixed_mode_sessions', FALSE) && ($cookies->has(substr(session_name(), 1))) && ($session_name = $cookies->get(substr(session_name(), 1))))) {
      // If a session cookie exists, initialize the session. Otherwise the
      // session is only started on demand in save(), making
      // anonymous users not use a session cookie unless something is stored in
      // $_SESSION. This allows HTTP proxies to cache anonymous pageviews.
      $this->start();
      if ($user->isAuthenticated() || !empty($_SESSION)) {
        drupal_page_is_cacheable(FALSE);
      }
    }
    else {
      // Set a session identifier for this request. This is necessary because
      // we lazily start sessions at the end of this request, and some
      // processes (like drupal_get_token()) needs to know the future
      // session ID in advance.
      $this->lazySession = TRUE;
      $user = new AnonymousUserSession();
      // Less random sessions (which are much faster to generate) are used for
      // anonymous users than are generated in regenerate() when
      // a user becomes authenticated.
      session_id(Crypt::randomBytesBase64());
      if ($is_https && Settings::get('mixed_mode_sessions', FALSE)) {
        $insecure_session_name = substr(session_name(), 1);
        $session_id = Crypt::randomBytesBase64();
        $cookies->set($insecure_session_name, $session_id);
      }
    }
    date_default_timezone_set(drupal_get_user_timezone());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function start() {
    if ($this->isCli() || $this->isStarted()) {
      return;
    }
    // Save current session data before starting it, as PHP will destroy it.
    $session_data = isset($_SESSION) ? $_SESSION : NULL;

    session_start();

    // Restore session data.
    if (!empty($session_data)) {
      $_SESSION += $session_data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    global $user;

    if (!$this->isEnabled()) {
      // We don't have anything to do if we are not allowed to save the session.
      return;
    }

    if ($user->isAnonymous() && empty($_SESSION)) {
      // There is no session data to store, destroy the session if it was
      // previously started.
      if ($this->isStarted()) {
        session_destroy();
      }
    }
    else {
      // There is session data to store. Start the session if it is not already
      // started.
      if (!$this->isStarted()) {
        $this->start();
        if ($this->requestStack->getCurrentRequest()->isSecure() && Settings::get('mixed_mode_sessions', FALSE)) {
          $insecure_session_name = substr(session_name(), 1);
          $params = session_get_cookie_params();
          $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
          $cookie_params = $this->requestStack->getCurrentRequest()->cookies;
          setcookie($insecure_session_name, $cookie_params->get($insecure_session_name), $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
        }
      }
      // Write the session data.
      session_write_close();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isStarted() {
    return session_status() === \PHP_SESSION_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function regenerate() {
    global $user;

    // Nothing to do if we are not allowed to change the session.
    if (!$this->isEnabled()) {
      return;
    }

    $is_https = $this->requestStack->getCurrentRequest()->isSecure();
    $cookies = $this->requestStack->getCurrentRequest()->cookies;

    if ($is_https && Settings::get('mixed_mode_sessions', FALSE)) {
      $insecure_session_name = substr(session_name(), 1);
      if (!isset($this->lazySession) && $cookies->has($insecure_session_name)) {
        $old_insecure_session_id = $cookies->get($insecure_session_name);
      }
      $params = session_get_cookie_params();
      $session_id = Crypt::randomBytesBase64();
      // If a session cookie lifetime is set, the session will expire
      // $params['lifetime'] seconds from the current request. If it is not set,
      // it will expire when the browser is closed.
      $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
      setcookie($insecure_session_name, $session_id, $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
      $cookies->set($insecure_session_name, $session_id);
    }

    if ($this->isStarted()) {
      $old_session_id = session_id();
    }
    session_id(Crypt::randomBytesBase64());

    if (isset($old_session_id)) {
      $params = session_get_cookie_params();
      $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
      setcookie(session_name(), session_id(), $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      $fields = array('sid' => Crypt::hashBase64(session_id()));
      if ($is_https) {
        $fields['ssid'] = Crypt::hashBase64(session_id());
        // If the "secure pages" setting is enabled, use the newly-created
        // insecure session identifier as the regenerated sid.
        if (Settings::get('mixed_mode_sessions', FALSE)) {
          $fields['sid'] = Crypt::hashBase64($session_id);
        }
      }
      $this->connection->update('sessions')
        ->fields($fields)
        ->condition($is_https ? 'ssid' : 'sid', Crypt::hashBase64($old_session_id))
        ->execute();
    }
    elseif (isset($old_insecure_session_id)) {
      // If logging in to the secure site, and there was no active session on
      // the secure site but a session was active on the insecure site, update
      // the insecure session with the new session identifiers.
      $this->connection->update('sessions')
        ->fields(array('sid' => Crypt::hashBase64($session_id), 'ssid' => Crypt::hashBase64(session_id())))
        ->condition('sid', Crypt::hashBase64($old_insecure_session_id))
        ->execute();
    }
    else {
      // Start the session when it doesn't exist yet.
      // Preserve the logged in user, as it will be reset to anonymous
      // by \Drupal\Core\Session\SessionHandler::read().
      $account = $user;
      $this->start();
      $user = $account;
    }
    date_default_timezone_set(drupal_get_user_timezone());
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uid) {
    // Nothing to do if we are not allowed to change the session.
    if (!$this->isEnabled()) {
      return;
    }
    $this->connection->delete('sessions')
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return static::$enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    static::$enabled = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    static::$enabled = TRUE;
    return $this;
  }

  /**
   * Returns whether the current PHP process runs on CLI.
   *
   * Command line clients do not support cookies nor sessions.
   *
   * @return bool
   */
  protected function isCli() {
    return PHP_SAPI === 'cli';
  }

}
