<?

function discussion_score($comment) {
  $value = ($comment->votes) ? ($comment->score / $comment->votes) : (($comment->score) ? $comment->score : 0);
  return (strpos($value, ".")) ? substr($value ."00", 0, 4) : $value .".00";
}

function discussion_moderate($moderate) {
  global $user, $comment_votes;

  if ($user->id && $moderate) {
    $none = $comment_votes[key($comment_votes)];

    foreach ($moderate as $id=>$vote) {
      if ($vote != $comment_votes[$none] && !user_getHistory($user->history, "c$id")) {
        ### Update the comment's score:
        $result = db_query("UPDATE comments SET score = score $vote, votes = votes + 1 WHERE cid = $id");

        ### Update the user's history:
        user_setHistory($user, "c$id", $vote);
      }
    }
  }
}

function discussion_kids($cid, $mode, $threshold, $level = 0, $dummy = 0) {
  global $user, $theme;

  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = $cid AND (c.votes = 0 OR c.score / c.votes >= $threshold) ORDER BY c.timestamp, c.cid");

  if ($mode == "nested") {
    while ($comment = db_fetch_object($result)) {
      if ($comment->score >= $threshold) {
        if ($level && !$comments) print "<UL>";
        $comments++;

        $link = "<A HREF=\"discussion.php?op=reply&sid=$comment->sid&pid=$comment->cid\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
        $theme->comment($comment->userid, check_output($comment->subject), check_output($comment->comment), $comment->timestamp, check_output($comment->url), check_output($comment->fake_email), discussion_score($comment), $comment->votes, $comment->cid, $link);
        
        discussion_kids($comment->cid, $mode, $threshold, $level + 1, $dummy + 1);
      }
    }
  } 
  else {  // mode == 'flat'
    while ($comment = db_fetch_object($result)) {
      if ($comment->score >= $threshold) {
        $link = "<A HREF=\"discussion.php?op=reply&sid=$comment->sid&pid=$comment->cid\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
        $theme->comment($comment->userid, check_output($comment->subject), check_output($comment->comment), $comment->timestamp, check_output($comment->url), check_output($comment->fake_email), discussion_score($comment), $comment->votes, $comment->cid, $link);
      } 
      discussion_kids($comment->cid, $mode, $threshold);
    }
  } 
  
  if ($level && $comments) print "</UL>";
}

function discussion_childs($cid, $threshold, $level = 0, $thread) {
  global $theme, $user;

  ### Perform SQL query:
  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = $cid AND (c.votes = 0 OR c.score / c.votes >= $threshold) ORDER BY c.timestamp, c.cid");
  
  if ($level == 0) $thread = "";

  while ($comment = db_fetch_object($result)) {
    if ($level && !$comments) {
      $thread .= "<UL>";
    }
  
    $comments++;

    ### Compose link:
    $thread .= "<LI><A HREF=\"discussion.php?id=$comment->sid&cid=$comment->cid&pid=$comment->pid\">". check_output($comment->subject) ."</A> by ". format_username($comment->userid) ." <SMALL>(". discussion_score($comment) .")<SMALL></LI>";

    ### Recursive:
    discussion_childs($comment->cid, $threshold, $level + 1, &$thread);
  } 

  if ($level && $comments) {
    $thread .= "</UL>";
  }

  return $thread;
}

function discussion_settings($mode, $order, $threshold) {
  global $user;

  if ($user->id) {
    $data[mode] = $mode;
    $data[sort] = $order;
    $data[threshold] = $threshold;
  
    user_save($data, $user->id);
  }
}

function discussion_display($sid, $pid, $cid, $level = 0) {
  global $user, $theme;

  ### Pre-process variables:
  $pid = (empty($pid)) ? 0 : $pid;
  $cid = (empty($cid)) ? 0 : $cid;
  $mode  = ($user->id) ? $user->mode  : "threaded";
  $order = ($user->id) ? $user->sort : "1";
  $threshold = ($user->id) ? $user->threshold  : "0";

  ### Compose story-query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.status != 0 AND s.id = $sid");
  $story = db_fetch_object($result);

  ### Display story:
  if ($story->status == 1) $theme->article($story, "[ <A HREF=\"submission.php\"><FONT COLOR=\"$theme->hlcolor2\">submission queue</FONT></A> | <A HREF=\"discussion.php?op=reply&sid=$story->id&pid=0\"><FONT COLOR=\"$theme->hlcolor2\">add a comment</FONT></A> ]");
  else $theme->article($story, "[ <A HREF=\"\"><FONT COLOR=\"$theme->hlcolor2\">home</FONT></A> | <A HREF=\"discussion.php?op=reply&sid=$story->id&pid=0\"><FONT COLOR=\"$theme->hlcolor2\">add a comment</FONT></A> ]");

  ### Display `comment control'-box:
  if ($user->id) $theme->commentControl($sid, $title, $threshold, $mode, $order);

  ### Compose query:
  $query .= "SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.sid = $sid AND c.pid = $pid AND (c.votes = 0 OR c.score / c.votes >= $threshold)";
  if ($order == 1) $query .= " ORDER BY c.timestamp DESC";
  if ($order == 2) $query .= " ORDER BY c.score DESC";
  $result = db_query($query);

  print "<FORM METHOD=\"post\" ACTION=\"discussion.php\">\n";

  ### Display the comments:  
  while ($comment = db_fetch_object($result)) {
    ### Dynamically compose the `reply'-link:
    if ($pid != 0) {
      list($pid) = db_fetch_row(db_query("SELECT pid FROM comments WHERE cid = $comment->pid"));
      $link = "<A HREF=\"discussion.php?id=$comment->sid&pid=$pid\"><FONT COLOR=\"$theme->hlcolor2\">return to parent</FONT></A> | <A HREF=\"discussion.php?op=reply&sid=$comment->sid&pid=$comment->cid\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
    }
    else {
      $link = "<A HREF=\"discussion.php?op=reply&sid=$comment->sid&pid=$comment->cid\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A> ";
    }

    ### Display the comments:
    if (empty($mode) || $mode == "threaded") {
      $thread = discussion_childs($comment->cid, $threshold);
      $theme->comment($comment->userid, check_output($comment->subject), check_output($comment->comment), $comment->timestamp, $comment->url, $comment->fake_email, discussion_score($comment), $comment->votes, $comment->cid, $link, $thread);
    }
    else {
      $theme->comment($comment->userid, check_output($comment->subject), check_output($comment->comment), $comment->timestamp, $comment->url, $comment->fake_email, discussion_score($comment), $comment->votes, $comment->cid, $link);
      discussion_kids($comment->cid, $mode, $threshold, $level);
    }
  }

  print " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$sid\">\n";  
  print " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Moderate comments\">\n";  
  print "</FORM>\n";
}

function discussion_reply($pid, $sid) {
  global $user, $theme, $allowed_html;

  ### Extract parent-information/data:
  if ($pid) {
    $item = db_fetch_object(db_query("SELECT comments.*, users.userid FROM comments LEFT JOIN users ON comments.author = users.id WHERE comments.cid = $pid"));
    $theme->comment($item->userid, check_output($item->subject), check_output($item->comment), $item->timestamp, check_output($item->url), check_output($item->fake_email), discussion_score($comment), $comment->votes, $item->cid, "reply to this comment");
  }
  else {
    $item = db_fetch_object(db_query("SELECT stories.*, users.userid FROM stories LEFT JOIN users ON stories.author = users.id WHERE stories.status != 0 AND stories.id = $sid"));
    $theme->article($item, "");
  }

  ### Build reply form:
  $output .= "<FORM ACTION=\"discussion.php\" METHOD=\"post\">\n";

  ### Name field:
  $output .= "<P>\n";
  $output .= " <B>Your name:</B><BR>\n";
  $output .= format_username($user->userid);
  $output .= "</P>\n";

  ### Subject field:
  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\">\n";
  $output .= "</P>\n";

  ### Comment field:
  $output .= "<P>\n";
  $output .= " <B>Comment:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">". check_output(check_field($user->signature)) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";
 
  ### Hidden fields:
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\">\n";

  ### Preview button:
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview comment\"> (You must preview at least once before you can submit.)\n";
  $output .= "</FORM>\n";

  $theme->box("Reply", $output); 
}

function comment_preview($pid, $sid, $subject, $comment) {
  global $user, $theme, $allowed_html;

  ### Preview comment:
  $theme->comment($user->userid, check_output($subject), check_output($comment), time(), check_output($user->url), check_output($user->fake_email), "", "", "", "reply to this comment");

  ### Build reply form:
  $output .= "<FORM ACTION=\"discussion.php\" METHOD=\"post\">\n";

  ### Name field:
  $output .= "<P>\n";
  $output .= " <B>Your name:</B><BR>\n";
  $output .= format_username($user->userid);
  $output .= "</P>\n";

  ### Subject field:
  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"". check_output(check_field($subject)) ."\">\n";
  $output .= "</P>\n";

  ### Comment field:
  $output .= "<P>\n";
  $output .= " <B>Comment:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">". check_output(check_field($comment)) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";
  
  ### Hidden fields:
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\">\n";

  if (empty($subject)) {
    $output .= "<P>\n";
    $output .= " <FONT COLOR=\"red\"><B>Warning:</B></FONT> you did not supply a <U>subject</U>.\n";
    $outout .= "</P>\n";
  }

  ### Preview and submit button:
  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview comment\">\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Post comment\">\n";
  $output .= " </FORM>\n";
  $output .= "</P>\n";

  $theme->box("Reply", $output); 
}

function comment_post($pid, $sid, $subject, $comment) {
  global $user, $theme;

  ### Check for fake threads:
  $fake = db_result(db_query("SELECT COUNT(*) FROM stories WHERE id = $sid"), 0);

  ### Check for duplicate comments:
  $duplicate = db_result(db_query("SELECT COUNT(*) FROM comments WHERE pid = '$pid' AND sid = '$sid' AND subject = '". addslashes($subject) ."' AND comment = '". addslashes($comment) ."'"), 0);

  if ($fake != 1) {
    watchdog(3, "attemp to insert fake comment");
    $theme->box("fake comment", "fake comment: $fake");
  }
  elseif ($duplicate != 0) {
    watchdog(3, "attemp to insert duplicate comment");
    $theme->box("duplicate comment", "duplicate comment: $duplicate");
  }
  else { 
    ### Validate subject:
    $subject = ($subject) ? $subject : substr($comment, 0, 29);

    ### Add comment to database:
    db_insert("INSERT INTO comments (pid, sid, author, subject, comment, hostname, timestamp) VALUES ($pid, $sid, '$user->id', '". addslashes($subject) ."', '". addslashes($comment) ."', '". getenv("REMOTE_ADDR") ."', '". time() ."')");

    ### Compose header:
    header("Location: discussion.php?id=$sid");
  }
}

include "includes/theme.inc";

switch($op) {  
  case "Preview comment":
    $theme->header();
    comment_preview($pid, $sid, $subject, $comment);
    $theme->footer();
    break;
  case "Post comment":
    comment_post($pid, $sid, $subject, $comment);
    break;
  case "reply":
    $theme->header();
    discussion_reply($pid, $sid);
    $theme->footer();
    break;
  case "Save":
    discussion_settings($mode, $order, $threshold);
    $theme->header();
    discussion_display($id, $pid, $sid);
    $theme->footer();
    break;
  case "Moderate comments":
    discussion_moderate($moderate);
    $theme->header();
    discussion_display($id, $pid, $sid);
    $theme->footer();
    break;
  default:
    $theme->header();
    discussion_display($id, $pid, $sid);
    $theme->footer();
}

?>