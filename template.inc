<?
include "user.class.php";

function display_morelink($theme, $story) {
  return ($story->article) ? "[ ". format_story_link($story, "<FONT COLOR=\"$theme->hlcolor2\"><B>read more</B></FONT>") ." | ". strlen($story->article) ." bytes | ". format_story_link($story, "<FONT COLOR=\"$theme->hlcolor2\">". format_plural($story->comments, "comment", "comments") ."</FONT>") ." ]" : "[ ". format_story_link($story, "<FONT COLOR=\"$theme->hlcolor2\">". format_plural($story->comments, "comment", "comments") ."</FONT>") ." ]";
}

function displayModerationResults($theme, $story) {
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

function displayRelatedLinks($theme, $story) {
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

function displayOldHeadlines($theme, $num = 10) {
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

function displayCommentModeration($id) {
  global $user, $comment_votes;
  
  if ($user && !user_getHistory($user->history, "c$id")) {
    $output .= "<SELECT NAME=\"moderate[$id]\">\n";
    foreach ($comment_votes as $key=>$value) $output .= " <OPTION VALUE=\"$value\">$key</OPTION>\n";
    $output .= "</SELECT>\n";
  }

  print $output;
}

function displayNewDiaries($theme, $num = 20) {
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

function displayNewHeadlines($theme, $num = 10) {
  global $user;

  $content = "";
  $result = db_query("SELECT id, subject FROM stories WHERE status = 2 ORDER BY id DESC LIMIT $num");
  while ($story = db_fetch_object($result)) $content .= "<LI>". format_story_link($story) ."</LI>\n";
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";
  $theme->box("Latest headlines", $content);
}

function displayAdminblock($theme) {
  $result = db_query("SELECT title, content FROM blocks");
  while (list($title, $content) = mysql_fetch_array($result)) {
    $theme->box($title, nl2br($content));
  }
}

function displayUserblock($theme) {
  global $user;

  if ($user && $user->ublockon) {
    $content .= "<P ALIGN=\"right\">[ <A HREF=\"account.php?op=edithome\"><FONT COLOR=\"$theme->hlcolor2\">edit</FONT></A> | <A HREF=\"account.php?op=discussion\"><FONT COLOR=\"$theme->hlcolor2\">Track comments</FONT></A> | <A HREF=\"account.php?op=logout\"><FONT COLOR=\"$theme->hlcolor2\">logout</FONT></A>]</P>";
    $theme->box("$user->userid's box", $user->content);
  }
}

function displayLogin($theme) {
  global $user;
  
  if ($user && $user->userid) { 
    ### Display userblock if any:
    displayUserblock();
  }
  else {
    $content  = "<CENTER><FORM METHOD=\"post\" ACTION=\"account.php\">\n";
    $content .= "<P>Username:<BR><INPUT NAME=userid MAXLENGTH=50 SIZE=12></P>\n";
    $content .= "<P>Password:<BR> <INPUT TYPE=password NAME=passwd MAXLENGTH=25 SIZE=12></P>\n";
    $content .= "<INPUT TYPE=submit NAME=op VALUE=\"Login\">\n";
    $content .= "</FORM>\n";
    $content .= "<P><A HREF=\"account.php?op=new\">Register</A> as new user.<BR><A HREF=\"account.php?op=forgot\">Forgot</A> your password?</P></CENTER>";
    $theme->box("Login", $content);
  }
}

function displayCalendar($theme, $date) {
  include "calendar.class.php";
  $calendar = new calendar($date);
  $theme->box("Browse archives", $calendar->display());
}

function displayAccount($theme) {
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

function displayPoll($theme) {
  global $answer, $answer1, $answer2, $answer3, $answer4, $answer5, $answer6, $id, $method, $section, $poll, $question;
    // Pass the URI and FORM parameters along to poll.php.
  $box = 1;
  include "poll.php";
}
?>
