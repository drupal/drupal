<?php

class User {
  function User($userid, $passwd = 0) {
    if ($passwd) {
      $result = db_query("SELECT u.*, r.perm FROM users u LEFT JOIN role r ON u.role = r.name WHERE LOWER(userid) = LOWER('$userid') && passwd = PASSWORD('$passwd') AND status = 2");
      if (db_num_rows($result) == 1) {
        foreach (db_fetch_row($result) as $key=>$value) { $field = mysql_field_name($result, $key); $this->$field = stripslashes($value); $this->field[] = $field; }
        db_query("UPDATE users SET last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_ADDR]' WHERE id = $this->id");
      }
    }
    else {
      $result = db_query("SELECT u.*, r.perm FROM users u LEFT JOIN role r ON u.role = r.name WHERE u.userid = '$userid' AND u.status = 2");
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

function user_access($account, $perm) {
  if ($account->id == 1) {
    return 1;
  }
  else if ($account->perm) {
    return strstr($account->perm, $perm);
  }
  else {
    return db_fetch_object(db_query("SELECT * FROM role WHERE name = 'anonymous user' AND perm LIKE '%$perm%'"));
  }
}

function user_ban($mask, $type) {
  $result = db_query("SELECT * FROM access WHERE type = '$type' AND '$mask' REGEXP mask");
  return db_fetch_object($result);
}

function user_password($min_length=6) {
  mt_srand((double)microtime() * 1000000);
  $words = array("foo","bar","guy","neo","tux","moo","sun","asm","dot","god","axe","geek","nerd","fish","hack","star","mice","warp","moon","hero","cola","girl","fish","java","perl","boss","dark","sith","jedi","drop","mojo");
  while (strlen($password) < $min_length) $password .= $words[mt_rand(0, count($words))];
  return $password;
}

function user_validate_name($name) {
  if (!$name) return t("you must enter a username.");
  if (eregi("^ ", $name)) return t("the username can not begin with a space.");
  if (eregi(" \$", $name)) return t("the username can not end with a space.");
  if (eregi("  ", $name)) return t("the username can not contain multiple spaces in a row.");
  if (eregi("[^a-zA-Z0-9 ]", $name)) return t("the username contains an illegal character.");
  if (strlen($name) > 15) return t("the username '$name' is too long: it must be less than 15 characters.");
}

function user_validate_mail($mail) {
  if (!$mail) return t("your must enter an e-mail address.");
  if (!eregi("^[_+\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $mail)) return t("the e-mail address '$email' is not valid.");
}

function user_validate($user) {
  // Verify username:
  if ($error = user_validate_name($user[userid])) return $error;

  // Verify e-mail address:
  if ($error = user_validate_mail($user[real_email])) return $error;

  // Check to see whether the username or e-mail address are banned:
  if ($ban = user_ban($user[userid], "username")) return t("the username '$user[userid]' is banned") .": <I>$ban->reason</I>.";
  if ($ban = user_ban($user[real_email], "e-mail address")) return t("the e-mail address '$user[real_email]' is banned") .": <I>$ban->reason</I>.";

  // Verify whether username and e-mail address are unique:
  if (db_num_rows(db_query("SELECT userid FROM users WHERE LOWER(userid) = LOWER('$user[userid]')")) > 0) return t("the username '$user[userid]' is already taken.");
  if (db_num_rows(db_query("SELECT real_email FROM users WHERE LOWER(real_email) = LOWER('$user[real_email]')")) > 0) return t("the e-mail address '$user[real_email]' is already in use by another account.");
}

?>