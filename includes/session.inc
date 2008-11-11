<?php
// $Id$

/**
 * @file
 * User session handling functions.
 *
 * The user-level session storage handlers:
 * - _sess_open()
 * - _sess_close()
 * - _sess_read()
 * - _sess_write()
 * - _sess_destroy_sid()
 * - _sess_gc()
 * are assigned by session_set_save_handler() in bootstrap.inc and are called
 * automatically by PHP. These functions should not be called directly. Session
 * data should instead be accessed via the $_SESSION superglobal.
 */

/**
 * Session handler assigned by session_set_save_handler().
 *
 * This function is used to handle any initialization, such as file paths or
 * database connections, that is needed before accessing session data. Drupal
 * does not need to initialize anything in this function.
 *
 * This function should not be called directly.
 *
 * @return
 *   This function will always return TRUE.
 */
function _sess_open() {
  return TRUE;
}

/**
 * Session handler assigned by session_set_save_handler().
 *
 * This function is used to close the current session. Because Drupal stores
 * session data in the database immediately on write, this function does
 * not need to do anything.
 *
 * This function should not be called directly.
 *
 * @return
 *   This function will always return TRUE.
 */
function _sess_close() {
  return TRUE;
}

/**
 * Session handler assigned by session_set_save_handler().
 *
 * This function will be called by PHP to retrieve the current user's
 * session data, which is stored in the database. It also loads the
 * current user's appropriate roles into the user object.
 *
 * This function should not be called directly. Session data should
 * instead be accessed via the $_SESSION superglobal.
 *
 * @param $key
 *   Session ID.
 * @return
 *   Either an array of the session data, or an empty string, if no data
 *   was found or the user is anonymous.
 */
function _sess_read($key) {
  global $user;

  // Write and Close handlers are called after destructing objects
  // since PHP 5.0.5.
  // Thus destructors can use sessions but session handler can't use objects.
  // So we are moving session closure before destructing objects.
  register_shutdown_function('session_write_close');

  // Handle the case of first time visitors and clients that don't store
  // cookies (eg. web crawlers).
  if (!isset($_COOKIE[session_name()])) {
    $user = drupal_anonymous_user();
    return '';
  }

  // Otherwise, if the session is still active, we have a record of the
  // client's session in the database.
  $user = db_query("SELECT u.*, s.* FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = :sid", array(':sid' => $key))->fetchObject();

  // We found the client's session record and they are an authenticated user.
  if ($user && $user->uid > 0) {
    // This is done to unserialize the data member of $user.
    $user = drupal_unpack($user);

    // Add roles element to $user.
    $user->roles = array();
    $user->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';
    $user->roles += db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = :uid", array(':uid' => $user->uid))->fetchAllKeyed(0, 1);
  }
  // We didn't find the client's record (session has expired), or they
  // are an anonymous user.
  else {
    $session = isset($user->session) ? $user->session : '';
    $user = drupal_anonymous_user($session);
  }

  return $user->session;
}

/**
 * Session handler assigned by session_set_save_handler().
 *
 * This function will be called by PHP to store the current user's
 * session, which Drupal saves to the database.
 *
 * This function should not be called directly. Session data should
 * instead be accessed via the $_SESSION superglobal.
 *
 * @param $key
 *   Session ID.
 * @param $value
 *   Serialized array of the session data.
 * @return
 *   This function will always return TRUE.
 */
function _sess_write($key, $value) {
  global $user;

  // If saving of session data is disabled or if the client doesn't have a session,
  // and one isn't being created ($value), do nothing. This keeps crawlers out of
  // the session table. This reduces memory and server load, and gives more useful
  // statistics. We can't eliminate anonymous session table rows without breaking
  // the "Who's Online" block.
  if (!drupal_save_session() || ($user->uid == 0 && empty($_COOKIE[session_name()]) && empty($value))) {
    return TRUE;
  }

  db_merge('sessions')
    ->key(array('sid' => $key))
    ->fields(array(
      'uid' => $user->uid,
      'cache' => isset($user->cache) ? $user->cache : 0,
      'hostname' => ip_address(),
      'session' => $value,
      'timestamp' => REQUEST_TIME,
    ))
    ->execute();

  // Last access time is updated no more frequently than once every 180 seconds.
  // This reduces contention in the users table.
  if ($user->uid && REQUEST_TIME - $user->access > variable_get('session_write_interval', 180)) {
    db_update('users')
      ->fields(array(
        'access' => REQUEST_TIME
      ))
      ->condition('uid', $user->uid)
      ->execute();
  }

  return TRUE;
}

/**
 * Called when an anonymous user becomes authenticated or vice-versa.
 */
function drupal_session_regenerate() {
  $old_session_id = session_id();
  session_regenerate_id();
  db_update('sessions')
    ->fields(array(
      'sid' => session_id()
    ))
    ->condition('sid', $old_session_id)
    ->execute();
}

/**
 * Counts how many users are active on the site.
 *
 * Counts how many users have sessions which have been active since the
 * specified time. Can count either anonymous sessions or
 * authenticated sessions.
 *
 * @param int $timestamp.
 *   A Unix timestamp. Users who have been active since this time will be
 *   counted. The default is 0, which counts all existing sessions.
 * @param boolean $anonymous
 *   TRUE counts only anonymous users.
 *   FALSE counts only authenticated users.
 * @return int
 *   The number of users with sessions.
 */
function drupal_session_count($timestamp = 0, $anonymous = TRUE) {
  $query = db_select('sessions');
  $query->addExpression('COUNT(sid)', 'count');
  $query->condition('timestamp', $timestamp, '>=');
  $query->condition('uid', 0, $anonymous ? '=' : '>');
  return $query->execute()->fetchField();
}

/**
 * Session handler assigned by session_set_save_handler().
 *
 * Cleanup a specific session.
 *
 * @param string $sid
 *   Session ID.
 */
function _sess_destroy_sid($sid) {
  db_delete('sessions')
    ->condition('sid', $sid)
    ->execute();
}

/**
 * End a specific user's session(s).
 *
 * @param string $uid
 *   User ID.
 */
function drupal_session_destroy_uid($uid) {
  db_delete('sessions')
    ->condition('uid', $uid)
    ->execute();
}

/**
 * Session handler assigned by session_set_save_handler().
 *
 * Cleanup stalled sessions.
 *
 * @param int $lifetime
 *   The value of session.gc_maxlifetime, passed by PHP.
 *   Sessions not updated for more than $lifetime seconds will be removed.
 */
function _sess_gc($lifetime) {
  // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
  // value. For example, if you want user sessions to stay in your database
  // for three weeks before deleting them, you need to set gc_maxlifetime
  // to '1814400'. At that value, only after a user doesn't log in after
  // three weeks (1814400 seconds) will his/her session be removed.
  db_delete('sessions')
    ->condition('timestamp', REQUEST_TIME - $lifetime, '<')
    ->execute();
  return TRUE;
}

/**
 * Determine whether to save session data of the current request.
 *
 * This function allows the caller to temporarily disable writing of
 * session data, should the request end while performing potentially
 * dangerous operations, such as manipulating the global $user object.
 * See http://drupal.org/node/218104 for usage.
 *
 * @param $status
 *   Disables writing of session data when FALSE, (re-)enables
 *   writing when TRUE.
 * @return
 *   FALSE if writing session data has been disabled. Otherwise, TRUE.
 */
function drupal_save_session($status = NULL) {
  static $save_session = TRUE;
  if (isset($status)) {
    $save_session = $status;
  }
  return $save_session;
}
