<?

include_once "includes/". getenv("HTTP_HOST") .".conf";
include_once "includes/database.inc";
include_once "includes/watchdog.inc";
include_once "includes/function.inc";
include_once "includes/droplet.inc";
include_once "includes/module.inc";
include_once "includes/widget.inc";
include_once "includes/user.inc";

global $user;

$theme = load_theme();

?>
