<?


function watchdog($level, $message) {
  global $user;

  ### Perform query to add new log entry:
  db_query("INSERT INTO logs (level, timestamp, user, message, hostname) VALUES ($level, '". time() ."', '". addslashes($user->id) ."', '". addslashes($message) ."', '". getenv("REMOTE_ADDR") ."')");

  ### Periodically remove old log entries:
  if (time() % 20 == 0) {
    $timestamp = time() - 1209600; // 2 weeks
    db_query("DELETE FROM logs WHERE timestamp < $timestamp");
  }
}
 
?>
