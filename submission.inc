<?

function submission_count() {
  $result = db_query("SELECT COUNT(id) FROM stories WHERE status = 1");
  return ($result) ? mysql_result($result, 0) : 0;
}

function submission_score($id) {
  $result = db_query("SELECT score FROM stories WHERE id = $id");
  return ($result) ? mysql_result($result, 0) : 0;
}

function submission_vote($id, $vote, $comment) {
  global $user;

  include "config.inc";

  if (!getHistory($user->history, "s$id")) {
    ### Update submission's score- and votes-field:
    db_query("UPDATE stories SET score = score $vote, votes = votes + 1 WHERE id = $id");

    ### Update the comments (if required):
    if ($comment) db_query("INSERT INTO comments (sid, subject, comment, hostname, timestamp) VALUES($id, '". addslashes(substr($comment, 0, 29)) ." ...', '". addslashes($comment) ."', '". getenv("REMOTE_ADDR") ."', '". time() ."')");

    ### Update user's history record:
    $user->setHistory("s$id", "$vote");  // s = submission
    $user->save();
  
    ### Update story table (if required):
    $result = db_query("SELECT * FROM stories WHERE id = $id");  
    if ($submission = db_fetch_object($result)) {
      if ($submission->score >= $submission_post_threshold) db_query("UPDATE stories SET status = 2, timestamp = '". time() ."' WHERE id = $id");
      if ($submission->score <= $submission_dump_threshold) db_query("UPDATE stories SET status = 0, timestamp = '". time() ."' WHERE id = $id");
    }
  }
}

?>