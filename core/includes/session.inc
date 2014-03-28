<?php

/**
 * @file
 * User session handling functions.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Settings;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\SessionHandler;

/**
 * Initializes the session handler, starting a session if needed.
 */
function drupal_session_initialize() {
  global $user;

  // Register the default session handler.
  // @todo: Extract session storage from session handler into a service.
  $handler = new SessionHandler(\Drupal::request(), \Drupal::database());
  session_set_save_handler($handler, TRUE);

  $is_https = \Drupal::request()->isSecure();
  $cookies = \Drupal::request()->cookies;
  if (($cookies->has(session_name()) && ($session_name = $cookies->get(session_name()))) || ($is_https && Settings::get('mixed_mode_sessions', FALSE) && ($cookies->has(substr(session_name(), 1))) && ($session_name = $cookies->get(substr(session_name(), 1))))) {
    // If a session cookie exists, initialize the session. Otherwise the
    // session is only started on demand in drupal_session_commit(), making
    // anonymous users not use a session cookie unless something is stored in
    // $_SESSION. This allows HTTP proxies to cache anonymous pageviews.
    drupal_session_start();
    if ($user->isAuthenticated() || !empty($_SESSION)) {
      drupal_page_is_cacheable(FALSE);
    }
  }
  else {
    // Set a session identifier for this request. This is necessary because
    // we lazily start sessions at the end of this request, and some
    // processes (like drupal_get_token()) needs to know the future
    // session ID in advance.
    $GLOBALS['lazy_session'] = TRUE;
    $user = new AnonymousUserSession();
    // Less random sessions (which are much faster to generate) are used for
    // anonymous users than are generated in drupal_session_regenerate() when
    // a user becomes authenticated.
    session_id(Crypt::randomBytesBase64());
    if ($is_https && Settings::get('mixed_mode_sessions', FALSE)) {
      $insecure_session_name = substr(session_name(), 1);
      $session_id = Crypt::randomBytesBase64();
      $cookies->set($insecure_session_name, $session_id);
    }
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Starts a session forcefully, preserving already set session data.
 *
 * @ingroup php_wrappers
 */
function drupal_session_start() {
  // Command line clients do not support cookies nor sessions.
  if (!drupal_session_started() && !drupal_is_cli()) {
    // Save current session data before starting it, as PHP will destroy it.
    $session_data = isset($_SESSION) ? $_SESSION : NULL;

    session_start();
    drupal_session_started(TRUE);

    // Restore session data.
    if (!empty($session_data)) {
      $_SESSION += $session_data;
    }
  }
}

/**
 * Commits the current session, if necessary.
 *
 * If an anonymous user already have an empty session, destroy it.
 */
function drupal_session_commit() {
  global $user;

  if (!drupal_save_session()) {
    // We don't have anything to do if we are not allowed to save the session.
    return;
  }

  if ($user->isAnonymous() && empty($_SESSION)) {
    // There is no session data to store, destroy the session if it was
    // previously started.
    if (drupal_session_started()) {
      session_destroy();
    }
  }
  else {
    // There is session data to store. Start the session if it is not already
    // started.
    if (!drupal_session_started()) {
      drupal_session_start();
      if (\Drupal::request()->isSecure() && Settings::get('mixed_mode_sessions', FALSE)) {
        $insecure_session_name = substr(session_name(), 1);
        $params = session_get_cookie_params();
        $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
        $cookie_params = \Drupal::request()->cookies;
        setcookie($insecure_session_name, $cookie_params->get($insecure_session_name), $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
      }
    }
    // Write the session data.
    session_write_close();
  }
}

/**
 * Returns whether a session has been started.
 */
function drupal_session_started($set = NULL) {
  static $session_started = FALSE;
  if (isset($set)) {
    $session_started = $set;
  }
  return $session_started && session_status() === \PHP_SESSION_ACTIVE;
}

/**
 * Called when an anonymous user becomes authenticated or vice-versa.
 *
 * @ingroup php_wrappers
 */
function drupal_session_regenerate() {
  global $user;

  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }

  $is_https = \Drupal::request()->isSecure();
  $cookies = \Drupal::request()->cookies;

  if ($is_https && Settings::get('mixed_mode_sessions', FALSE)) {
    $insecure_session_name = substr(session_name(), 1);
    if (!isset($GLOBALS['lazy_session']) && $cookies->has($insecure_session_name)) {
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

  if (drupal_session_started()) {
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
    db_update('sessions')
      ->fields($fields)
      ->condition($is_https ? 'ssid' : 'sid', Crypt::hashBase64($old_session_id))
      ->execute();
  }
  elseif (isset($old_insecure_session_id)) {
    // If logging in to the secure site, and there was no active session on the
    // secure site but a session was active on the insecure site, update the
    // insecure session with the new session identifiers.
    db_update('sessions')
      ->fields(array('sid' => Crypt::hashBase64($session_id), 'ssid' => Crypt::hashBase64(session_id())))
      ->condition('sid', Crypt::hashBase64($old_insecure_session_id))
      ->execute();
  }
  else {
    // Start the session when it doesn't exist yet.
    // Preserve the logged in user, as it will be reset to anonymous
    // by _drupal_session_read.
    $account = $user;
    drupal_session_start();
    $user = $account;
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Ends a specific user's session(s).
 *
 * @param $uid
 *   User ID.
 */
function drupal_session_destroy_uid($uid) {
  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }

  db_delete('sessions')
    ->condition('uid', $uid)
    ->execute();
}

/**
 * Determines whether to save session data of the current request.
 *
 * This function allows the caller to temporarily disable writing of
 * session data, should the request end while performing potentially
 * dangerous operations, such as manipulating the global $user object.
 * See http://drupal.org/node/218104 for usage.
 *
 * @param $status
 *   Disables writing of session data when FALSE, (re-)enables
 *   writing when TRUE.
 *
 * @return
 *   FALSE if writing session data has been disabled. Otherwise, TRUE.
 */
function drupal_save_session($status = NULL) {
  // PHP session ID, session, and cookie handling happens in the global scope.
  // This value has to persist across calls to drupal_static_reset(), since a
  // potentially wrong or disallowed session would be written otherwise.
  static $save_session = TRUE;
  if (isset($status)) {
    $save_session = $status;
  }
  return $save_session;
}
