<?

class Story {
  function story($userid, $subject, $abstract, $article, $category, $timestamp) {
    $this->userid = $userid;
    $this->subject = $subject;
    $this->abstract = $abstract;
    $this->article = $article;
    $this->category = $category;
    $this->timestamp = $timestamp;
  }
}

?>