<?php

class User {
  function User($userid, $passwd = 0) {
    if ($passwd) {
      $result = db_query("SELECT u.*, r.perm FROM users u LEFT JOIN role r ON u.role = r.name WHERE (LOWER(u.userid) = LOWER('$userid') OR LOWER(u.name) = LOWER('$userid')) AND u.passwd = PASSWORD('$passwd') AND u.status = 2");
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

function user_get($uid) {
  return db_fetch_object(db_query("SELECT * FROM users WHERE id = '". check_output($uid) ."'"));
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
  return user_load($array[userid] ? $array[userid] : $account->userid);
}

function user_access($perm) {
  global $user;

  if ($user->id == 1) {
    return 1;
  }
  else if ($user->perm) {
    return strstr($user->perm, $perm);
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
  $words = explode(",", variable_get("account_words", "foo,bar,guy,neo,tux,moo,sun,asm,dot,god,axe,geek,nerd,fish,hack,star,mice,warp,moon,hero,cola,girl,fish,java,perl,boss,dark,sith,jedi,drop,mojo"));
  while (strlen($password) < $min_length) $password .= trim($words[mt_rand(0, count($words))]);
  return $password;
}

function user_validate_name($name) {
  if (!$name) return t("you must enter a username.");
  if (eregi("^ ", $name)) return t("the username can not begin with a space.");
  if (eregi(" \$", $name)) return t("the username can not end with a space.");
  if (eregi("  ", $name)) return t("the username can not contain multiple spaces in a row.");
  if (eregi("[^a-zA-Z0-9 ]", $name)) return t("the username contains an illegal character.");
  if (strlen($name) > 32) return t("the username '$name' is too long: it must be less than 32 characters.");
}

function user_validate_mail($mail) {
  if (!$mail) return t("your must enter an e-mail address.");
  if (!eregi("^[_+\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $mail)) return t("the e-mail address '$email' is not valid.");
}

?>