<?

function droplet_get($name) {
  $result = db_query("SELECT * FROM droplets WHERE name = '$name'");

  if ($droplet = db_fetch_object($result)) { 
    return eval($droplet->code);
  } 
  else {
    watchdog("error", "droplet '$name' does not exist");   
  }
}

function droplet_add($name, $help, $code, $message = "") {
  $result = db_query("INSERT INTO droplets (name, help, code) VALUES ('". check_input($name) ."', '". check_input($help) ."', '". check_code($code) ."')"); 
}

function droplet_delete($id) {
  $result = db_query("DELETE FROM droplets WHERE id = $id");
}

?>
