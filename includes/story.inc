<?

class Story {
  function Story($userid, $subject, $abstract, $article, $section, $timestamp) {
    $this->userid = $userid;
    $this->subject = $subject;
    $this->abstract = $abstract;
    $this->article = $article;
    $this->section = $section;
    $this->timestamp = $timestamp;
  }
}

function story_visible($story) {
  global $user;
  return ($story->status == 1 && $user->id) || ($story->status == 2) || user_permission();
}

?>