<?

$watchdog = array("comment" => array("0", $submission_rate["comment"]),
                  "diary"   => array("1", $submission_rate["diary"]),
                  "story"   => array("2", $submission_rate["story"]),
                  "message" => array("3", "0"),
                  "warning" => array("4", "0"),
                  "error"   => array("5", "0"));

function watchdog($id, $message) {
  global $user, $watchdog, $watchdog_history;

  if ($watchdog[$id][1] && !user_permission($user)) {
    if ($log = db_fetch_object(db_query("SELECT * FROM watchdog WHERE hostname = '". getenv("REMOTE_ADDR") ."' AND level = '". $watchdog[$id][0] ."'"))) {
      if (time() - $log->timestamp < $watchdog[$id][1]) {
        watchdog("warning", "'". getenv("REMOTE_ADDR") ."' exceeded '$id' submission rate");
        header("Location: error.php?op=flood");
        exit();
      }
    }
  }

  // Perform query to add new watchdog entry:
  db_query("INSERT INTO watchdog (level, timestamp, user, message, location, hostname) VALUES ('". $watchdog[$id][0] ."', '". time() ."', '". check_input($user->id) ."', '". check_input($message) ."', '". check_input(getenv("REQUEST_URI")) ."', '". check_input(getenv("REMOTE_ADDR")) ."')");
}

function watchdog_clean($history = 302400) {
  $timestamp = time() - $history;
  db_query("DELETE FROM watchdog WHERE timestamp < $timestamp");
}

?>
