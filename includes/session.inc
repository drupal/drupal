<?php
// $Id$

/**
 * @file
 * User session handling functions.
 */

function sess_open($save_path, $session_name) {
  return TRUE;
}

function sess_close() {
  return TRUE;
}

function sess_read($key) {
  global $user;

  // Write and Close handlers are called after destructing objects since PHP 5.0.5
  // Thus destructors can use sessions but session handler can't use objects.
  // So we are moving session closure before destructing objects.
  register_shutdown_function('session_write_close');

  // Handle the case of first time visitors and clients that don't store cookies (eg. web crawlers).
  if (!isset($_COOKIE[session_name()])) {
    $user = drupal_anonymous_user();
    return '';
  }

  // Otherwise, if the session is still active, we have a record of the client's session in the database.
  $user = db_query("SELECT u.*, s.* FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = :sid", array(':sid' => $key))->fetch();

  // We found the client's session record and they are an authenticated user
  if ($user && $user->uid > 0) {
    // This is done to unserialize the data member of $user
    $user = drupal_unpack($user);

    // Add roles element to $user
    $user->roles = array();
    $user->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';
    $result = db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = :uid", array(':uid' => $user->uid));
    while ($role = db_fetch_object($result)) {
      $user->roles[$role->rid] = $role->name;
    }
  }
  // We didn't find the client's record (session has expired), or they are an anonymous user.
  else {
    $session = isset($user->session) ? $user->session : '';
    $user = drupal_anonymous_user($session);
  }

  return $user->session;
}

function sess_write($key, $value) {
  global $user;

  // If saving of session data is disabled or if the client doesn't have a session,
  // and one isn't being created ($value), do nothing. This keeps crawlers out of
  // the session table. This reduces memory and server load, and gives more useful
  // statistics. We can't eliminate anonymous session table rows without breaking
  // the "Who's Online" block.
  if (!session_save_session() || (empty($_COOKIE[session_name()]) && empty($value))) {
    return TRUE;
  }

  $fields = array(
    'uid' => $user->uid,
    'cache' => isset($user->cache) ? $user->cache : 0,
    'hostname' => ip_address(),
    'session' => $value,
    'timestamp' => time(),
  );

  db_merge('sessions')->key(array('sid' => $key))->fields($fields)->execute();

  // Last access time is updated no more frequently than once every 180 seconds.
  // This reduces contention in the users table.
  if ($user->uid && time() - $user->access > variable_get('session_write_interval', 180)) {
    db_update('users')->fields(array('access' => time()))->condition('uid', $user->uid)->execute();
  }

  return TRUE;
}

/**
 * Called when an anonymous user becomes authenticated or vice-versa.
 */
function sess_regenerate() {
  $old_session_id = session_id();
  session_regenerate_id();
  db_update('sessions')->fields(array('sid' => session_id()))->condition('sid', $old_session_id)->execute();
}

/**
 * Counts how many users have sessions. Can count either anonymous sessions or authenticated sessions.
 *
 * @param int $timestamp
 *   A Unix timestamp representing a point of time in the past.
 *   The default is 0, which counts all existing sessions.
 * @param boolean $anonymous
 *   TRUE counts only anonymous users.
 *   FALSE counts only authenticated users.
 * @return  int
 *   The number of users with sessions.
 */
function sess_count($timestamp = 0, $anonymous = true) {
  $query = db_select('sessions');
  $query->addExpression('COUNT(sid)', 'count');
  $query->condition('timestamp', $timestamp, '>=');
  $query->condition('uid', 0, $anonymous ? '=' : '>');
  return $query->execute()->fetchField();
}

/**
 * Called by PHP session handling with the PHP session ID to end a user's session.
 *
 * @param  string $sid
 *   the session id
 */
function sess_destroy_sid($sid) {
  db_query("DELETE FROM {sessions} WHERE sid = :sid", array(':sid' => $sid));
}

/**
 * End a specific user's session
 *
 * @param  string $uid
 *   the user id
 */
function sess_destroy_uid($uid) {
  db_query('DELETE FROM {sessions} WHERE uid = :uid', array(':uid' => $uid));
}

function sess_gc($lifetime) {
  // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
  // value. For example, if you want user sessions to stay in your database
  // for three weeks before deleting them, you need to set gc_maxlifetime
  // to '1814400'. At that value, only after a user doesn't log in after
  // three weeks (1814400 seconds) will his/her session be removed.
  db_query("DELETE FROM {sessions} WHERE timestamp < :timestamp", array(':timestamp' => time() - $lifetime));

  return TRUE;
}

/**
 * Determine whether to save session data of the current request.
 *
 * This function allows the caller to temporarily disable writing of session data,
 * should the request end while performing potentially dangerous operations, such as
 * manipulating the global $user object.  See http://drupal.org/node/218104 for usage
 *
 * @param $status
 *   Disables writing of session data when FALSE, (re-)enables writing when TRUE.
 * @return
 *   FALSE if writing session data has been disabled. Otherwise, TRUE.
 */
function session_save_session($status = NULL) {
  static $save_session = TRUE;
  if (isset($status)) {
    $save_session = $status;
  }
  return ($save_session);
}
