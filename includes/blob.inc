<?

function blob_get($name) {
  $result = db_query("SELECT * FROM blobs WHERE name = '$name'");

  if ($blob = db_fetch_object($result)) { 
    return eval($blob->code);
  } 
  else {
    watchdog("error", "blob '$name' does not exist");   
  }
}

function blob_add($name, $help, $code, $message = "") {
  $result = db_query("INSERT INTO blobs (name, help, code) VALUES ('". check_input($name) ."', '". check_input($help) ."', '". check_code($code) ."')"); 
}

function blob_delete($id) {
  $result = db_query("DELETE FROM blobs WHERE id = $id");
}

?>