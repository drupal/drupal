<?
include "includes/user.inc";

function display_morelink($theme, $story) {
  return ($story->article) ? "[ <A HREF=\"discussion.php?id=$story->id\"><FONT COLOR=\"$theme->hlcolor2\"><B>read more</B></FONT></A> | ". strlen($story->article) ." bytes | <A HREF=\"discussion.php?id=$story->id\"><FONT COLOR=\"$theme->hlcolor2\">". format_plural($story->comments, "comment", "comments") ."</FONT></A> ]" : "[ <A HREF=\"discussion.php?id=$story->id\"><FONT COLOR=\"$theme->hlcolor2\">". format_plural($story->comments, "comment", "comments") ."</FONT></A> ]";
}

function display_moderation_results($theme, $story) {
  global $user;

  if ($user->id && $story->id && $vote = user_getHistory($user->history, "s$story->id")) {
    $output .= "<P><B>You voted `$vote'.</B></P>\n";
    $output .= "<P>\n";
    $output .= "<B>Other people voted:</B><BR>\n";

    $result = db_query("SELECT * FROM users WHERE history LIKE '%s$story->id%'");
    while ($account = db_fetch_object($result)) {
      $output .= "". format_username($account->userid) ." voted `". user_getHistory($account->history, "s$story->id") ."'.<BR>";
    }
    
    $theme->box("Moderation results", $output);
  }
}

function display_related_links($theme, $story) {
  ### Parse story for <A HREF="">-tags:
  $text = stripslashes("$story->abstract $story->updates $story->article");
  while ($text = stristr($text, "<A HREF=")) {
    $link = substr($text, 0, strpos(strtolower($text), "</a>") + 4);
    $text = stristr($text, "</A>");
    if (!stristr($link, "mailto:")) $content .= "<LI>$link</LI>";
  }

  ### Stories in the same category: 
  $content .= " <LI>More about <A HREF=\"search.php?category=". urlencode($story->category) ."\">$story->category</A>.</LI>";

  ### Stories from the same author:
  if ($story->userid) $content .= " <LI>Also by <A HREF=\"search.php?author=". urlencode($story->userid) ."\">$story->userid</A>.</LI>";

  $theme->box("Related links", $content);
}

function display_old_headlines($theme, $num = 10) {
  global $user;
  
  if ($user->storynum) $result = db_query("SELECT id, subject, timestamp FROM stories WHERE status = 2 ORDER BY timestamp DESC LIMIT $user->storynum, $num");
  else $result = db_query("SELECT id, subject, timestamp FROM stories WHERE status = 2 ORDER BY timestamp DESC LIMIT $num, $num");

  while ($story = db_fetch_object($result)) {    
    if ($time != date("F jS", $story->timestamp)) {
      $content .= "<P><B>". date("l, M jS", $story->timestamp) ."</B></P>\n";
      $time = date("F jS", $story->timestamp);
    }
    $content .= "<LI>". format_story_link($story) ."</LI>\n";
  }
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";

  $theme->box("Older headlines", $content);
}

function display_comment_moderation($id, $author, $score, $votes) {
  global $user, $comment_votes;
  
  if ($user && $user->userid != $author && !user_getHistory($user->history, "c$id")) {
    $output .= "<SELECT NAME=\"moderate[$id]\">\n";
    foreach ($comment_votes as $key=>$value) $output .= " <OPTION VALUE=\"$value\">$key</OPTION>\n";
    $output .= "</SELECT>\n";
  }
  else {
    $output .= "<TABLE BORDER=\"0\" CELLSPACING=\"1\" CELLPADDING=\"0\"><TR><TD>score:</TD><TD>$score</TD></TR><TR><TD>votes:</TD><TD>$votes</TR></TABLE>";
  }

  print $output;
}

function display_new_diaries($theme, $num = 20) {
  $result = db_query("SELECT u.userid, d.timestamp FROM diaries d LEFT JOIN users u ON d.author = u.id ORDER BY timestamp DESC LIMIT $num");

  while ($diary = db_fetch_object($result)) {
    if ($time != date("F jS", $diary->timestamp)) {
      $content .= "<P><B>". date("l, M jS", $diary->timestamp) ."</B></P>\n";
      $time = date("F jS", $diary->timestamp);
    }
    $content .= "<LI><A HREF=\"diary.php?op=view&name=$diary->userid\">$diary->userid</A></LI>\n";
  }
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"diary.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";
  $theme->box("Recent diary entries", $content);
}

function display_new_headlines($theme, $num = 10) {
  global $user;

  $content = "";
  $result = db_query("SELECT id, subject FROM stories WHERE status = 2 ORDER BY id DESC LIMIT $num");
  while ($story = db_fetch_object($result)) $content .= "<LI>". format_story_link($story) ."</LI>\n";
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";
  $theme->box("Latest headlines", $content);
}

function display_calendar($theme, $date) {
  include "includes/calendar.inc";
  $calendar = new calendar($date);
  $theme->box("Browse archives", $calendar->display());
}

function display_account($theme) {
  global $user;
  
  if ($user && $user->userid) { 
    function submission_number() {
      $result = db_query("SELECT COUNT(id) FROM stories WHERE status = 1");
      return ($result) ? mysql_result($result, 0) : 0;
    }

    ### Display account settings:
    $content  = "<LI><A HREF=\"account.php\">view your information</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=user\">edit your information</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=page\">customize your page</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=discussion\">track your comments</A></LI>";
    $content .= "<LI><A HREF=\"submission.php\">moderate submissions</A> (<FONT COLOR=\"red\">". submission_number() ."</FONT>)</LI>";
    $content .= "<LI><A HREF=\"diary.php?op=view&name=$user->userid\">update your diary</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=logout\">logout</A></LI>";

    $theme->box("$user->userid's account", "$content");
  }
}

?>
