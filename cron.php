<?

include "includes/theme.inc";

function cron_run($cron) {
  global $repository;

  $time = time();
  
  $result = db_query("SELECT * FROM crons WHERE $time - timestamp > scheduled");

  while ($task = db_fetch_object($result)) {
    if ($repository[$task->module]["cron"]) {
      watchdog("message", "cron: executed '". $task->module ."_cron()'"); 
      $repository[$task->module]["cron"]();
    }
  }

  db_query("UPDATE crons SET timestamp = $time WHERE $time - timestamp > scheduled");
}

cron_run();

?>