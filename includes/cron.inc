<?

include "includes/theme.inc";

function cron_set($module, $scheduled, $message = "") {
  if (empty($module) || empty($scheduled)) {
    $message = "failed: information missing";
  }
  else if (db_fetch_object(db_query("SELECT * FROM cron WHERE module = '$module'"))) {
    db_query("UPDATE cron SET scheduled = $scheduled WHERE module = '$module'");
  }
  else {
    db_query("INSERT INTO cron (module, scheduled, timestamp) VALUES ('". check_input($module) ."', '". check_input($scheduled) ."', '42')");
  } 
}

function cron_get($module) {
  return db_fetch_object(db_query("SELECT * FROM cron WHERE module = '$module'"), 0);
}

function cron_delete($module) {
  $result = db_query("DELETE FROM cron WHERE module = '$module'");
}

function cron_run($cron) {
  $time = time();
  
  $result = db_query("SELECT * FROM cron WHERE $time - timestamp > scheduled");

  while ($task = db_fetch_object($result)) {
    include "modules/". $task->module .".module";
    if ($function = $module["cron"]) {
      watchdog("message", "cron: executed '". $task->module ."_cron()'"); 
      $function();
    }
  }

  db_query("UPDATE cron SET timestamp = $time WHERE $time - timestamp > scheduled");
}

?>
