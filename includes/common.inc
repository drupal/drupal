<?

include_once "includes/". getenv("HTTP_HOST") .".conf";
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