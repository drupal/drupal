<?

include_once "includes/common.inc";
include_once "includes/story.inc";

function story_render($id, $cid) {
  global $theme, $user;

  $story = db_fetch_object(db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = $id"));

  if (story_visible($story)) {
    $theme->article($story, "[ <A HREF=\"story.php?op=reply&id=$id&pid=0\">reply to this story</A> ]");
    comment_render($id, $cid);
  }
  else {
    $theme->box("Warning message", "The story you requested is not available or does not exist.");
  }
}

switch($op) {
  case "Preview comment":
    $theme->header();
    comment_preview($pid, $id, $subject, $comment);
    $theme->footer();
    break;
  case "Post comment":
    comment_post($pid, $id, $subject, $comment);
    $theme->header();
    story_render($id, $cid);
    $theme->footer();
    break;
  case "Add comment":
    $theme->header();
    comment_reply($cid, $id);
    $theme->footer();
    break;
  case "reply":
    $theme->header();
    comment_reply($pid, $id);
    $theme->footer();
    break;
  case "Update settings":
    comment_settings($mode, $order, $threshold);
    $theme->header();
    story_render($id, $cid);
    $theme->footer();
    break;
  case "Moderate comments":
    comment_moderate($moderate);
    $theme->header();
    story_render($id, $cid);
    $theme->footer();
    break;
  default:
    $theme->header();
    story_render($id, $cid);
    $theme->footer();
}

?>
