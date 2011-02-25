<?php

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

  $result = db_query_range("SELECT u.*, s.*, r.name AS role FROM {users} u INNER JOIN {sessions} s ON u.uid = s.uid LEFT JOIN {role} r ON u.rid = r.rid WHERE s.sid = '%s' AND u.status < 3", $key, 0, 1);

  if (!db_num_rows($result)) {
    $result = db_query("SELECT u.*, r.name AS role FROM {users} u INNER JOIN {role} r ON u.rid = r.rid WHERE u.uid = 0");
    db_query("INSERT INTO {sessions} (uid, sid, hostname, timestamp) values(%d, '%s', '%s', %d)", $user->uid, $key, $_SERVER["REMOTE_ADDR"], time());
  }

  $user = db_fetch_object($result);
  $user = drupal_unpack($user);

  return !empty($user->session) ? $user->session : '';
}

function sess_write($key, $value) {
  global $user;

  db_query("UPDATE {sessions} SET uid = %d, hostname = '%s', session = '%s', timestamp = %d WHERE sid = '%s'", $user->uid, $_SERVER["REMOTE_ADDR"], $value, time(), $key);

  return '';
}

function sess_destroy($key) {
  db_query("DELETE FROM {sessions} WHERE sid = '$key'");
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
