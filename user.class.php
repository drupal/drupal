<?
$access = array("Administrator"	=> 0x00000001,
		"User manager"	=> 0x00000002,
		"News manager"	=> 0x00000004);

class User {
  function User($userid, $passwd="") {
    dbconnect();
    $result = mysql_query("SELECT * FROM users WHERE LOWER(userid)=LOWER('$userid') && passwd=PASSWORD('$passwd') && STATUS=0") or die(sprintf("Critical error at line %d in %s: %s", __LINE__, __FILE__, mysql_error()));
    if (mysql_num_rows($result) == 1) {
      foreach (mysql_fetch_array($result) as $key=>$value) { $this->$key = $value; }
    }
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
      $this->last_access = time();
      $this->last_host = (!empty($GLOBALS[REMOTE_HOST]) ? $GLOBALS[REMOTE_HOST] : $GLOBALS[REMOTE_ADDR] );
      dbconnect();
      mysql_query("UPDATE users SET last_access='$this->last_access',last_host='$this->last_host' WHERE id=$this->id") or die(sprintf("Critical error at line %d in %s: %s", __LINE__, __FILE__, mysql_error()));
      if ($this->access & $access || $access == 0) return 1;
    }
    return 0;
  }
}
?>
