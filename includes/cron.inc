<?

function cron_add($name, $help, $code, $scheduled, $message = "") {
  if (empty($name) || empty($code) || empty($scheduled)) {
    $message = "failed: information missing";
  }
  else {
    $result = db_query("INSERT INTO cron (name, help, code, scheduled) VALUES ('". check_input($name) ."', '". check_input($help) ."', '". check_code($code) ."', '". check_input($scheduled) ."')");
  } 
}

function cron_delete($id) {
  $result = db_query("DELETE FROM cron WHERE id = $id");
}

function cron_execute($cron) {
  eval($cron->code);
  
  db_query("UPDATE cron SET timestamp = '". time() ."' WHERE id = $cron->id");

  watchdog("message", "cron: executed '$cron->name'"); 
}

?>
