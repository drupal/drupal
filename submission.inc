<?

function submission_count() {
  $result = db_query("SELECT COUNT(id) FROM submissions WHERE status = 1");
  return ($result) ? mysql_result($result, 0) : 0;
}

function submission_score($id) {
  $result = db_query("SELECT score FROM submissions WHERE id = $id");
  return ($result) ? mysql_result($result, 0) : 0;
}

function submission_vote($id, $vote) {
  global $user;

  include "config.inc";

  if (!$user->getHistory("s$id")) {
    ### Update submission table:
    db_query("UPDATE submissions SET score = score $vote, votes = votes + 1 WHERE id = $id");

    ### Update user record:
    $user->setHistory("s$id", "$vote");  // s = submission
    $user->save();
  
    ### Update story and submission table (if required):
    $result = db_query("SELECT * FROM submissions WHERE id = $id");  
    if ($submission = db_fetch_object($result)) {
      if ($submission->score >= $submission_post_threshold) {
        ### Hide submission from submission table:
        db_query("UPDATE submissions SET status = 0 WHERE id = $id");

        ### Copy sumbission to news table:
        db_query("INSERT INTO stories (aid, subject, time, abstract, article, category, informant) VALUES ('$submission->uid', '". addslashes($submission->subject) ."', '$submission->timestamp', '". addslashes($submission->abstract) ."', '". addslashes($submission->article) ."', '". addslashes($submission->category) ."', '". addslashes($submission->uname) ."')");
      }
      if ($submission->score <= $submission_dump_threshold) {
        ### Hide submission from submission table:
        db_query("UPDATE submissions SET status = 0 WHERE id = $id");
      }
    }
  }
}

?>