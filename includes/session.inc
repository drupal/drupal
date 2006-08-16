<?php
// $Id: session.inc,v 1.29 2006/08/16 13:13:34 dries Exp $

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

  // Handle the case of first time visitors and clients that don't store cookies (eg. web crawlers).
  if (!isset($_COOKIE[session_name()])) {
    $user = drupal_anonymous_user();
    return '';
  }

  // Otherwise, if the session is still active, we have a record of the client's session in the database.
  $user = db_fetch_object(db_query("SELECT u.*, s.* FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = '%s'", $key));

  // We found the client's session record and they are an authenticated user
  if ($user->uid > 0) {
    // This is done to unserialize the data member of $user
    $user = drupal_unpack($user);

    // Add roles element to $user
    $user->roles = array();
    $user->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';
    $result = db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = %d", $user->uid);
    while ($role = db_fetch_object($result)) {
      $user->roles[$role->rid] = $role->name;
    }
  }
  // We didn't find the client's record (session has expired), or they are an anonymous user.
  else  {
    $user = drupal_anonymous_user();
  }

  return $user->session;
}

function sess_write($key, $value) {
  global $user;

  // If the client doesn't have a session, and one isn't being created ($value), do nothing.
  if (empty($_COOKIE[session_name()]) && empty($value)) {
    return TRUE;
  }

  $result = db_query("SELECT sid FROM {sessions} WHERE sid = '%s'", $key);

  if (!db_num_rows($result)) {
    // Only save session data when when the browser sends a cookie. This keeps
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

function sess_destroy($key) {
  db_query("DELETE FROM {sessions} WHERE sid = '%s'", $key);
}

function sess_gc($lifetime) {
  // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
  // value. For example, if you want user sessions to stay in your database
  // for three weeks before deleting them, you need to set gc_maxlifetime
  // to '1814400'. At that value, only after a user doesn't log in after
  // three weeks (1814400 seconds) will his/her session be removed.
  db_query("DELETE FROM {sessions} WHERE timestamp < %d", time() - $lifetime);

  return TRUE;
}