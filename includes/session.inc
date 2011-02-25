<?php

/**
 * @file
 * User session handling functions.
 */

session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
session_start();

/*** Session functions *****************************************************/

function sess_open($save_path, $session_name) {
  return 1;
}

function sess_close() {
  return 1;
}

function sess_read($key) {
  global $user;

  $result = db_query_range("SELECT u.*, s.* FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid WHERE s.sid = '%s' AND u.status < 3", $key, 0, 1);

  if (!db_num_rows($result)) {
    db_query("INSERT INTO {sessions} (sid, uid, hostname, timestamp) values('%s', 0, '%s', %d)", $key, $_SERVER["REMOTE_ADDR"], time());
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

  db_query("UPDATE {sessions} SET uid = %d, hostname = '%s', session = '%s', timestamp = %d WHERE sid = '%s'", $user->uid, $_SERVER["REMOTE_ADDR"], $value, time(), $key);

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

?>
