<?

class User {
  function User($userid, $passwd = "") {
    $result = db_query("SELECT * FROM users WHERE LOWER(userid) = LOWER('$userid') && passwd = PASSWORD('$passwd') && STATUS = 2");
    if (db_num_rows($result) == 1) {
      foreach (db_fetch_row($result) as $key=>$value) { $field = mysql_field_name($result, $key); $this->$field = stripslashes($value); $this->field[] = $field; }
      db_query("UPDATE users SET last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_HOST]' WHERE id = $this->id");
    }
  }
}

function user_save($data, $id = 0) {
  global $user;
  
  foreach ($data as $key=>$value) {
    if ($key == "passwd") $query .= "$key = PASSWORD('". addslashes($value) ."'), ";
    else $query .= "$key = '". addslashes($value) ."', ";
  }
  
  if (empty($id)) {
    db_query("INSERT INTO users SET $query last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_HOST]'");
  }
  else {
    db_query("UPDATE users SET $query last_access = '". time() ."', last_host = '$GLOBALS[REMOTE_HOST]' WHERE id = $id");
    $result = db_query("SELECT * FROM users WHERE id = $id AND status = 2");
    if (db_num_rows($result) == 1) foreach (db_fetch_array($result) as $key=>$value) { $user->$key = stripslashes($value); }
    else $user = 0;
  }
}

function user_getHistory($history, $field) {
  $data = explode(";", $history);
  for (reset($data); current($data); next($data)) {
    $entry = explode(":", current($data));
    if (reset($entry) == $field) $rval = end($entry);
  }
  return $rval;
} 

function user_setHistory(&$user, $field, $value) {

  $history = $user->history;
  if (!$value) {
    // remove entry:
    $data = explode(";", $history);
    for (reset($data); current($data); next($data)) {
      $entry = explode(":", current($data));
      if ($entry[0] != $field) $rval .= "$entry[0]:$entry[1];";
    }
  }
  else if (strstr($history, "$field:")) {
    // found: update exsisting entry:
    $data = explode(";", $history);
    for (reset($data); current($data); next($data)) {
      $entry = explode(":", current($data));
      if ($entry[0] == $field) $entry[1] = $value;
      $rval .= "$entry[0]:$entry[1];";
    } 
  }
  else {
    // not found: add new entry:
    $rval = "$history$field:$value;";
  }
  $user->history = $rval;

  // save new history:
  $query .= "UPDATE users SET ";
  foreach ($user->field as $key=>$field) { $value = $user->$field; $query .= "$field = '". addslashes($value) ."', "; }
  $query .= " id = $user->id WHERE id = $user->id";
  db_query($query);
}

function user_clean() {
  // todo -  called by cron job
}

?>