<?

$access = array("Administrator"	=> 0x00000001,
		"User manager"	=> 0x00000002,
		"News manager"	=> 0x00000004);

class User {
  function User($userid, $passwd="") {
    $result = db_query("SELECT * FROM users WHERE LOWER(userid) = LOWER('$userid') && passwd = PASSWORD('$passwd') && STATUS = 0");
    if (db_num_rows($result) == 1) {
      foreach (db_fetch_row($result) as $key=>$value) { $field = mysql_field_name($result, $key); $this->$field = stripslashes($value); $this->field[] = $field; }
    }
  }
}

function user_save() {
  global $user;
  ### Compose query to update user record:
}

function user_rehash() {
  global $user;
  $result = db_query("SELECT * FROM users WHERE id=$user->id");
  if (db_num_rows($result) == 1) {
    foreach (db_fetch_array($result) as $key=>$value) { $user->$key = stripslashes($value); }
  }
}

function user_valid($access = 0) {
  global $user;
  if ($user->userid) {
    user_rehash();  // synchronisation purpose
    $user->last_access = time();
    $user->last_host = ($GLOBALS[REMOTE_HOST]) ? $GLOBALS[REMOTE_HOST] : $GLOBALS[REMOTE_ADDR];
    db_query("UPDATE users SET last_access = '$user->last_access', last_host = '$user->last_host' WHERE id = $user->id");
    if ($user->access & $access || $access == 0) return 1;
  }
  return 0;
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
    ### remove entry:
    $data = explode(";", $history);
    for (reset($data); current($data); next($data)) {
      $entry = explode(":", current($data));
      if ($entry[0] != $field) $rval .= "$entry[0]:$entry[1];";
    }
  }
  else if (strstr($history, "$field:")) {
    ### found: update exsisting entry:
    $data = explode(";", $history);
    for (reset($data); current($data); next($data)) {
      $entry = explode(":", current($data));
      if ($entry[0] == $field) $entry[1] = $value;
      $rval .= "$entry[0]:$entry[1];";
    } 
  }
  else {
    ### not found: add new entry:
    $rval = "$history$field:$value;";
  }
  $user->history = $rval;

  ### save new history:
  $query .= "UPDATE users SET ";
  foreach ($user->field as $key=>$field) { $value = $user->$field; $query .= "$field = '". addslashes($value) ."', "; }
  $query .= " id = $user->id WHERE id = $user->id";
  db_query($query);
}

?>
