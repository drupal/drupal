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

function user_get($account, $column, $field) {
  $data = explode(";", $account->$column);
  for (reset($data); current($data); next($data)) {
    $entry = explode(":", current($data));
    if (reset($entry) == $field) $rval = end($entry);
  }
  return $rval;
}

function user_set($account, $column, $name, $value) {
  $field = $account->$column;

  if (!$value) {
    // remove entry:
    $data = explode(";", $field);
    for (reset($data); current($data); next($data)) {
      $entry = explode(":", current($data));
      if ($entry[0] != $name) $rval .= "$entry[0]:$entry[1];";
    }
  }
  else if (strstr($field, "$name:")) {
    // found: update exsisting entry:
    $data = explode(";", $field);
    for (reset($data); current($data); next($data)) {
      $entry = explode(":", current($data));
      if ($entry[0] == $name) $entry[1] = $value;
      $rval .= "$entry[0]:$entry[1];";
    }
  }
  else {
    // not found:
    $rval = "$field$name:$value;";
  }

  return user_save($account, array($column => $rval));
}

function user_access($account, $section = 0) {
  global $user;
  if ($section) return (user_get($account, "access", $section) || $account->id == 1);
  else return ($account->access || $account->id == 1);
}

function user_ban($mask, $type) {
  $result = db_query("SELECT * FROM access WHERE type = '$type' AND '$mask' REGEXP mask");
  return db_fetch_object($result);
}

function user_gravity($id) {
  global $status;

  $period = 5184000;    // maximum 60 days
  $number = 30;         // maximum 30 comments

  $r1 = db_query("SELECT COUNT(nid) AS number FROM node WHERE author = '$id' AND (". time() ." - timestamp < $period) AND status = '$status[posted]'");
  if ($story = db_fetch_object($r1)) {
    $bonus += $story->number;
  }

  $r2 = db_query("SELECT COUNT(nid) AS number FROM node WHERE author = '$id' AND (". time() ." - timestamp < $period) AND status = '$status[dumped]'");
  if ($story = db_fetch_object($r2)) {
    $bonus -= $story->number;
  }

  $r3 = db_query("SELECT score, votes FROM comments WHERE author = '$id' AND votes > 0 AND (". time() ." - timestamp < $period) ORDER BY timestamp LIMIT $number");
  while ($comment = db_fetch_object($r3)) {
    $weight++;
    $score += $weight * $comment->score;
    $votes += $weight * $comment->votes;
  }

  $bonus += $weight / 5;

  return ($votes ? ($score + $weight) / $votes + $bonus : $bonus);
}

?>