<?

class Comment {
  function Comment($userid, $subject, $comment, $timestamp, $url, $fake_email, $score, $votes, $cid) {
    $this->userid = $userid;
    $this->subject = $subject;
    $this->comment = $comment;
    $this->timestamp = $timestamp;
    $this->url = $url;
    $this->fake_email = $fake_email;
    $this->score = $score;
    $this->votes = $votes;
    $this->cid = $cid;
  }
}

?>
