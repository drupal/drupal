<?


function watchdog($level, $message) {
  global $user;

  ### Perform query to add new log entry:
  db_query("INSERT INTO watchdog (level, timestamp, user, message, location, hostname) VALUES ($level, '". time() ."', '". addslashes($user->id) ."', '". addslashes($message) ."', '". getenv("SCRIPT_NAME") ."', '". getenv("REMOTE_ADDR") ."')");

  ### Periodically remove old log entries:
  if (time() % 20 == 0) {
    $timestamp = time() - 1209600; // 2 weeks
    db_query("DELETE FROM watchdog WHERE timestamp < $timestamp");
  }
}
 
?>
