<?
$access = array("Administrator"	=> 0x00000001,
		"User manager"	=> 0x00000002,
		"News manager"	=> 0x00000004);

class User {
  function User($userid, $passwd="") {
    dbconnect();
    $result = mysql_query("SELECT * FROM users WHERE LOWER(userid)=LOWER('$userid') && passwd=PASSWORD('$passwd') && STATUS=0") or die(sprintf("Critical error at line %d in %s: %s", __LINE__, __FILE__, mysql_error()));
    if (mysql_num_rows($result) == 1) {
      foreach (mysql_fetch_row($result) as $key=>$value) { $field = mysql_field_name($result, $key); $this->$field = stripslashes($value); $this->field[] = $field; }
    }
  }
  function save() {
    ### Compose query to update user record:
    $query .= "UPDATE users SET ";
    foreach ($this->field as $key=>$field) { $value = $this->$field; $query .= "$field = '". addslashes($value) ."', "; }
    $query .= " id = $this->id WHERE id = $this->id";
    ### Perform query:
    mysql_query($query);
  }
  function update() {
    dbconnect();
    $result = mysql_query("SELECT * FROM users WHERE id=$this->id") or die(sprintf("Critical error at line %d in %s: %s", __LINE__, __FILE__, mysql_error()));
    if (mysql_num_rows($result) == 1) {
      foreach (mysql_fetch_array($result) as $key=>$value) { $this->$key = $value; }
    }
  }
  function valid($access=0) {
    if (!empty($this->userid)) {
      $this->update();  // synchronisation purpose
      $this->last_access = time();
      $this->last_host = (!empty($GLOBALS[REMOTE_HOST]) ? $GLOBALS[REMOTE_HOST] : $GLOBALS[REMOTE_ADDR] );
      dbconnect();
      mysql_query("UPDATE users SET last_access='$this->last_access',last_host='$this->last_host' WHERE id=$this->id") or die(sprintf("Critical error at line %d in %s: %s", __LINE__, __FILE__, mysql_error()));
      if ($this->access & $access || $access == 0) return 1;
    }
    return 0;
  }
  function getHistory($field) {
    return getHistory($this->history, $field);
  }
  function setHistory($field, $value) {
    $this->history = setHistory($this->history, $field, $value);
  }
}

function getHistory($history, $field) {
  $data = explode(";", $history);
  for (reset($data); current($data); next($data)) {
    $entry = explode(":", current($data));
    if (reset($entry) == $field) $rval = end($entry);
  }
  return $rval;
} 

function setHistory($history, $field, $value) {
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
  return $rval;
}

?>
