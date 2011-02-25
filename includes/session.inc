<?php

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

  // retrieve data for a $user object
  $result = db_query("SELECT sid FROM {sessions} WHERE sid = '%s'", $key);
  if (!db_num_rows($result)) {
    $result = db_query("SELECT u.* FROM {users} u WHERE u.uid = 0");
  }
  else {
    $result = db_query("SELECT u.*, s.* FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = '%s'", $key);
  }

  // Build $user object:
  $user = db_fetch_object($result);
  $user = drupal_unpack($user);

  // Add roles element to $user:
  $user->roles = array();
  if ($user->uid) {
    $user->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';

    $result = db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = %d", $user->uid);
    while ($role = db_fetch_object($result)) {
      $user->roles[$role->rid] = $role->name;
    }
  }
  else {
    $user->roles[DRUPAL_ANONYMOUS_RID] = 'anonymous user';
  }

  return !empty($user->session) ? $user->session : '';
}

function sess_write($key, $value) {
  global $user;

  $result = db_query("SELECT sid FROM {sessions} WHERE sid = '%s'", $key);

  if (!db_num_rows($result)) {
    // Only save session data when when the browser sends a cookie.  This keeps
    // crawlers out of session table. This improves speed up queries, reduces
    // memory, and gives more useful statistics. We can't eliminate anonymous
    // session table rows without breaking throttle module and "Who's Online"
    // block.
    if ($user->uid || $value || count($_COOKIE)) {
      db_query("INSERT INTO {sessions} (sid, uid, cache, hostname, session, timestamp) VALUES ('%s', %d, %d, '%s', '%s', %d)", $key, $user->uid, $user->cache, $_SERVER["REMOTE_ADDR"], $value, time());
    }
  }
  else {
    db_query("UPDATE {sessions} SET uid = %d, cache = %d, hostname = '%s', session = '%s', timestamp = %d WHERE sid = '%s'", $user->uid, $user->cache, $_SERVER["REMOTE_ADDR"], $value, time(), $key);

    // TODO: this can be an expensive query. Perhaps only execute it every x minutes. Requires investigation into cache expiration.
    if ($user->uid) {
      db_query("UPDATE {users} SET access = %d WHERE uid = %d", time(), $user->uid);
    }
  }

  return TRUE;
}

/**
 * Called when an anonymous user becomes authenticated or vice-versa.
 */
function sess_regenerate() {
  $old_session_id = session_id();

  // We code around http://bugs.php.net/bug.php?id=32802 by destroying
  // the session cookie by setting expiration in the past (a negative
  // value).  This issue only arises in PHP versions before 4.4.0,
  // regardless of the Drupal configuration.
  // TODO: remove this when we require at least PHP 4.4.0
  if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
  }

  session_regenerate_id();

  db_query("UPDATE {sessions} SET sid = '%s' WHERE sid = '%s'", session_id(), $old_session_id);
}

function sess_destroy($key) {
  db_query("DELETE FROM {sessions} WHERE sid = '%s'", $key);
}

function sess_gc($lifetime) {
  // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
  // value.  For example, if you want user sessions to stay in your database
  // for three weeks before deleting them, you need to set gc_maxlifetime
  // to '1814400'.  At that value, only after a user doesn't log in after
  // three weeks (1814400 seconds) will his/her session be removed.
  db_query("DELETE FROM {sessions} WHERE timestamp < %d", time() - $lifetime);

  return TRUE;
}

