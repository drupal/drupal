<?php

function conf_init() {
  global $HTTP_HOST, $REQUEST_URI;
  $file = strtolower(strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", ".."));
  while ($file && !file_exists("includes/$file.conf")) {
    $file = substr($file, 0, strrpos($file, "."));
  }
  return $file;
}

function watchdog($type, $message) {
  global $user, $watchdog, $PHP_SELF;
  $link = ($mod) ? $mod : substr(strrchr($PHP_SELF, "/"), 1, strrchr($PHP_SELF, "/") - 4);
  db_query("INSERT INTO watchdog (user, type, link, message, location, hostname, timestamp) VALUES ('$user->id', '". check_input($type) ."', '". check_input($link) ."', '". check_input($message) ."', '". check_input(getenv("REQUEST_URI")) ."', '". check_input(getenv("REMOTE_ADDR")) ."', '". time() ."')");
}

function throttle($type, $rate) {
  global $user;
  if (!(user_access($user, "watchdog") || user_access($user, "comment") || user_access($user, "node"))) {
    if ($throttle = db_fetch_object(db_query("SELECT * FROM watchdog WHERE type = '$type' AND hostname = '". getenv("REMOTE_ADDR") ."' AND ". time() ." - timestamp < $rate"))) {
      watchdog("warning", "throttle: '". getenv("REMOTE_ADDR") ."' exceeded submission rate - $throttle->type");
      header("Location: error.php?op=throttle");
      exit();
    }
    else {
      watchdog($type, "throttle control");
    }
  }
}

$conf = conf_init();

include_once "includes/$conf.conf";
include_once "includes/database.inc";
include_once "includes/variable.inc";
include_once "includes/function.inc";
include_once "includes/comment.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/search.inc";
include_once "includes/timer.inc";
include_once "includes/theme.inc";
include_once "includes/user.inc";
include_once "includes/node.inc";

user_init();
$locale = locale_init();
$conf = variable_init();
$theme = theme_init();

?>