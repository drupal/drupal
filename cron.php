<?php

include_once "includes/common.inc";

function cron_run() {
  $time = time();
  $result = db_query("SELECT * FROM crons WHERE $time - timestamp > scheduled");
  while ($task = db_fetch_object($result)) module_invoke($task->module, "cron");
  db_query("UPDATE crons SET timestamp = $time WHERE $time - timestamp > scheduled");
}

cron_run();

?>