<?

function submission_count() {
  $result = db_query("SELECT COUNT(id) FROM stories WHERE status = 1");
  return ($result) ? db_result($result, 0) : 0;
}

function submission_score($id) {
  $result = db_query("SELECT score FROM stories WHERE id = $id");
  return ($result) ? db_result($result, 0) : 0;
}

function submission_vote($id, $vote, $comment) {
  global $user;
  
  if (!user_getHistory($user->history, "s$id")) {
    // Update submission's score- and votes-field:
    db_query("UPDATE stories SET score = score $vote, votes = votes + 1 WHERE id = $id");

    // Update the comments (if required):
    if ($comment) {
      db_query("INSERT INTO comments (lid, link, author, subject, comment, hostname, timestamp, score) VALUES($id, 'story', $user->id, '". check_input(substr($comment, 0, 29)) ." ...', '". check_input($comment) ."', '". getenv("REMOTE_ADDR") ."', '". time() ."', '1')");
      watchdog("comment", "moderation: added comment with subject '$subject'");
    }

    // Update user's history record:
    user_setHistory($user, "s$id", $vote);
  
    // Update story table (if required):
    $result = db_query("SELECT * FROM stories WHERE id = $id");  
    if ($submission = db_fetch_object($result)) {
      if (section_post_threshold($submission->section) <= $submission->score) {
        db_query("UPDATE stories SET status = 2, timestamp = '". time() ."' WHERE id = $id");
        watchdog("message", "posted story '$submission->subject'");
      }
      else if (section_dump_threshold($submission->section) >= $submission->score) {
        db_query("UPDATE stories SET status = 0, timestamp = '". time() ."' WHERE id = $id");
        watchdog("message", "dumped story '$submission->subject'");
      }
      else if (section_timout_threshold($submission->section) <= $submission->votes) {
        db_query("UPDATE stories SET status = 0, timestamp = '". time() ."' WHERE id = $id");
        watchdog("message", "expired story '$submission->subject'");
      }
    }
  }
}

?>