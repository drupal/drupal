<?php
// $Id: session.inc,v 1.22 2005/12/31 11:50:47 dries Exp $

/**
 * @file
 * User session handling functions.
 */

/*** Session functions *****************************************************/

function sess_open($save_path, $session_name) {
  return 1;
}

function sess_close() {
  return 1;
}

function sess_read($key) {
  global $user;

  $result = db_query("SELECT u.*, s.* FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = '%s'", $key);

  if (!db_num_rows($result)) {
    db_query("INSERT INTO {sessions} (sid, uid, hostname, timestamp) VALUES ('%s', 0, '%s', %d)", $key, $_SERVER["REMOTE_ADDR"], time());
    $result = db_query("SELECT u.* FROM {users} u WHERE u.uid = 0");
  }

  $user = db_fetch_object($result);
  $user = drupal_unpack($user);
  $user->roles = array();

  $result = db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = %d", $user->uid);

  while ($role = db_fetch_object($result)) {
    $user->roles[$role->rid] = $role->name;
  }

  return !empty($user->session) ? $user->session : '';
}

function sess_write($key, $value) {
  global $user;

  db_query("UPDATE {sessions} SET uid = %d, cache = %d, hostname = '%s', session = '%s', timestamp = %d WHERE sid = '%s'", $user->uid, $user->cache, $_SERVER["REMOTE_ADDR"], $value, time(), $key);
  if ($user->uid) {
    db_query("UPDATE {users} SET access = %d WHERE uid = %d", time(), $user->uid);
  }
  return '';
}

function sess_destroy($key) {
  db_query("DELETE FROM {sessions} WHERE sid = '%s'", $key);
}

function sess_gc($lifetime) {

  /*
  **  Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
  **   value.  For example, if you want user sessions to stay in your database
  **   for three weeks before deleting them, you need to set gc_maxlifetime
  **   to '1814400'.  At that value, only after a user doesn't log in after
  **   three weeks (1814400 seconds) will his/her session be removed.
  */
  db_query("DELETE FROM {sessions} WHERE timestamp < %d", time() - $lifetime);

  return 1;

}


