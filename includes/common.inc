<?

function conf_load() {
  global $HTTP_HOST, $REQUEST_URI;
  $file = strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", "..");
  while ($file && !file_exists("includes/$file.conf")) {
    $file = substr($file, 0, strrpos($file, "."));
  }
  return $file;
}

$conf = conf_load();

include_once "includes/$conf.conf";
include_once "includes/database.inc";
include_once "includes/watchdog.inc";
include_once "includes/function.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/theme.inc";
include_once "includes/user.inc";

user_load();

$theme = theme_load();

?>