<?

$cf = strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", "..");
include_once "includes/$cf.conf";
include_once "includes/database.inc";
include_once "includes/watchdog.inc";
include_once "includes/function.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/theme.inc";
include_once "includes/user.inc";

session_start();

$theme = theme_load();

?>