<?php

function conf_init() {
  global $HTTP_HOST, $REQUEST_URI;
  $file = strtolower(strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", ".."));
  while ($file && !file_exists("includes/$file.conf")) {
    $file = substr($file, 0, strrpos($file, "."));
  }
  return $file;
}

$conf = conf_init();

include_once "includes/$conf.conf";
include_once "includes/database.inc";
include_once "includes/watchdog.inc";
include_once "includes/function.inc";
include_once "includes/comment.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/search.inc";
include_once "includes/theme.inc";
include_once "includes/user.inc";

user_init();
$locale = locale_init();
$theme = theme_init();

?>