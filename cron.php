<?php
// $Id: cron.php,v 1.19 2003/12/29 11:25:02 dries Exp $

include_once "includes/bootstrap.inc";
include_once "includes/common.inc";

/*
** If not in 'safe mode', increase the maximum execution time:
*/

if (!ini_get("safe_mode")) {
  set_time_limit(240);
}

/*
** Iterate through the modules calling their cron handlers (if any):
*/

module_invoke_all("cron");

watchdog("message", "cron run completed");
?>
