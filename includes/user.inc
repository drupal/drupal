<?php

class User {
  function User($userid, $passwd = 0) {
    if ($passwd) {
      $result = db_query("SELECT * FROM users WHERE LOWER(userid) = LOWER('$userid') && passwd = PASSWORD('$passwd') && STATUS = 2");
      if (db_num_rows($result) == 1) {
        foreach (db_fetch_row($result) as $key=>$value) { $field = mysql_field_name($result, $key); $this->$field = stripslashes($value); $this->field[] = $field; }
        db_query("UPDATE users SET last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_ADDR]' WHERE id = $this->id");
      }
    }
    else {
      $result = db_query("SELECT * FROM users WHERE userid = '$userid' && STATUS = 2");
      if (db_num_rows($result) == 1) {
        foreach (db_fetch_row($result) as $key=>$value) { $field = mysql_field_name($result, $key); $this->$field = stripslashes($value); $this->field[] = $field; }
        db_query("UPDATE users SET last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_ADDR]' WHERE id = $this->id");
      }
    }
  }
}

function user_init() {
  global $db_name;
  session_name($db_name);
  session_start();
}

function user_load($username) {
  return new User($username);
}

function user_rehash() {
  global $user;
  if ($user->id) {
    $user = new User($user->userid);
    session_register("user");
  }
}

function user_save($account, $array) {
  // dynamically compose query:
  foreach ($array as $key=>$value) {
    if ($key == "passwd") $query .= "$key = PASSWORD('". addslashes($value) ."'), ";
    else $query .= "$key = '". addslashes($value) ."', ";
  }

  // update or instert account:
  if ($account->id) db_query("UPDATE users SET $query last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_ADDR]' WHERE id = '$account->id'");
  else db_query("INSERT INTO users SET $query last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_ADDR]'");

  // return account:
  return user_load(($account->userid ? $account->userid : $array[userid]));
}

function user_access($account, $section = 0) {
  global $user;
  if ($section) return (field_get($account->access, $section) || $account->id == 1);
  else return ($account->access || $account->id == 1);
}

function user_ban($mask, $type) {
  $result = db_query("SELECT * FROM access WHERE type = '$type' AND '$mask' REGEXP mask");
  return db_fetch_object($result);
}

?>