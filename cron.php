<?php
// $Id: cron.php,v 1.17 2003/09/14 18:33:16 dries Exp $

include_once "includes/common.inc";

/*
** If not in 'safe mode', increase the maximum execution time:
*/

if (!get_cfg_var("safe_mode")) {
  set_time_limit(240);
}

/*
** Iterate through the modules calling their cron handlers (if any):
*/

module_invoke_all("cron");

watchdog("message", "cron run completed");
?>
