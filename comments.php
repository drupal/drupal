<?

function displayKids ($cid, $mode, $order = 0, $thold = 0, $level = 0, $dummy = 0) {
  global $user, $theme;

  include "config.inc";
  $comments = 0;

  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = $cid ORDER BY c.timestamp, c.cid");

  if ($mode == "nested") {
    while ($comment = db_fetch_object($result)) {
      if ($$comment->score >= $thold) {
        if ($level && !$comments) print "<UL>";
        $comments++;

        $link = "<A HREF=\"comments.php?op=reply&pid=$comment->cid&sid=$comment->sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
        $theme->comment($comment->userid, $comment->subject, $comment->comment, $comment->timestamp, $comment->url, $comment->femail, $comment->score, $comment->cid, $link);
        
        displayKids($comment->cid, $mode, $order, $thold, $level + 1, $dummy + 1);
      }
    }
  } 
  elseif ($mode == "flat") {
    while ($comment = db_fetch_object($result)) {
      if ($comment->score >= $thold) {
        $link = "<A HREF=\"comments.php?op=reply&pid=$comment->cid&sid=$comment->sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
        $theme->comment($comment->userid, $comment->subject, $comment->comment, $comment->timestamp, $comment->url, $comment->femail, $comment->score, $comment->cid, $link);
      } 
      displayKids($comment->cid, $mode, $order, $thold);
    }
  } else {
    print "ERROR: we should not get here!";
  }
  
  if ($level && $comments) {
    print "</UL>";
  }
}

function displayBabies($cid, $mode, $order, $thold, $level = 0, $thread) {
  global $theme, $user;

  ### Perform SQL query:
  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = $cid ORDER BY c.timestamp, c.cid");
  
  if ($level == 0) $thread = "";
  $comments = 0;

  while ($comment = db_fetch_object($result)) {
    if ($level && !$comments) {
      $thread .= "<UL>";
    }
  
    $comments++;

    ### Compose link:
    $thread .= "<LI><A HREF=\"comments.php?op=show&cid=$comment->cid&pid=$comment->pid&sid=$comment->sid";
    $thread .= ($mode) ? "&mode=$mode" : "&mode=threaded";
    $thread .= ($order) ? "&order=$order" : "&order=0";
    $thread .= ($thold) ? "&thold=$thold" : "&thold=0";
    $thread .= "\">$comment->subject</A> by $comment->userid <SMALL>(". date("D, M d, Y - H:i:s", $comment->timestamp) .")<SMALL></LI>";

    ### Recursive:
    displayBabies($comment->cid, $mode, $order, $thold, $level + 1, &$thread);
  } 

  if ($level && $comments) {
    $thread .= "</UL>";
  }

  return $thread;
}

function comments_display ($sid = 0, $pid = 0, $cid = 0, $mode = "threaded", $order = 0, $thold = 0, $level = 0, $nokids = 0) {
  global $user, $theme;

  ### Display `comment control'-box:
  $theme->commentControl($sid, $title, $thold, $mode, $order);

  ### Compose query:
  $query = "SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.sid = $sid AND c.pid = $pid";
  if ($mode == 'threaded' || mode == 'nested') {
    if ($thold != "") $query .= " AND score >= $thold";
    else $query .= " AND score >= 0"; 
  }
  if ($order == 1) $query .= " ORDER BY timestamp DESC";
  if ($order == 2) $query .= " ORDER BY score DESC";
  $result = db_query("$query");

  ### Display the comments:  
  while ($comment = db_fetch_object($result)) {
    ### Dynamically compose the `reply'-link:
    if ($pid != 0) {
      list($pid) = mysql_fetch_row(mysql_query("SELECT pid FROM comments WHERE cid = $comment->pid"));
      $link = "<A HREF=\"comments.php?op=show&pid=$pid&sid=$comment->sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">return to parent</FONT></A> | <A HREF=\"comments.php?op=reply&pid=$comment->cid&sid=$comment->sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
    }
    else {
      $link = "<A HREF=\"comments.php?op=reply&pid=$comment->cid&sid=$comment->sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A> ";
    }

    ### Display the comments:
    if ($mode == "threaded") {
      $thread = displayBabies($comment->cid, $mode, $order, $thold);
      $theme->comment($comment->userid, $comment->subject, $comment->comment, $comment->timestamp, $comment->url, $comment->femail, $comment->score, $comment->cid, $link, $thread);
    }
    else {
      $theme->comment($comment->userid, $comment->subject, $comment->comment, $comment->timestamp, $comment->url, $comment->femail, $comment->score, $comment->cid, $link);
      displayKids($comment->cid, $mode, $order, $thold, $level);
    }
/*
    print "</UL>\n";
    print "</P>\n";
*/
  }

  if ($pid == 0) return array($sid, $pid, $subject);
}

function comments_reply($pid, $sid, $mode, $order, $thold) {
  global $user, $theme;

  ### Extract parent-information/data:
  if ($pid) {
    $item = db_fetch_object(db_query("SELECT comments.*, users.userid FROM comments LEFT JOIN users ON comments.author = users.id WHERE comments.cid = $pid"));
    $theme->comment($item->userid, $item->subject, $item->comment, $item->timestamp, $item->url, $item->femail, $item->score, $item->cid, "reply to this comment");
  }
  else {
    $item = db_fetch_object(db_query("SELECT stories.*, users.userid FROM stories LEFT JOIN users ON stories.author = users.id WHERE stories.status = 2 AND stories.id = $sid"));
    $theme->article($item, "");
  }

  ### Build reply form:
  $output .= "<FORM ACTION=\"comments.php\" METHOD=\"post\">\n";

  ### Name field:
  if ($user) {
    $output .= "<P>\n";
    $output .= " <B>Your name:</B><BR>\n";
    $output .= " <A HREF=\"account.php\">$user->userid</A> &nbsp; &nbsp; <FONT SIZE=\"2\">[ <A HREF=\"account.php?op=logout\">logout</A> ]</FONT>\n";
    $output .= "</P>\n";
  }
  else {
    $output .= "<P>\n";
    $output .= " <B>Your name:</B><BR>\n";
    $output .= " $anonymous\n"; 
    $output .= "</P>\n";
  }

  ### Subject field:
  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  if (!eregi("Re:",$item->subject)) $item->subject = "Re: $item->subject"; 
    // Only one 'Re:' will just do fine. ;)
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"$item->subject\">\n";
  $output .= "</P>\n";

  ### Comment field:
  $output .= "<P>\n";
  $output .= " <B>Comment:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">$user->signature</TEXTAREA><BR>\n";
  $output .= "</P>\n";
 
  ### Hidden fields:
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"mode\" VALUE=\"$mode\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"order\" VALUE=\"$order\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"thold\" VALUE=\"$thold\">\n";

  ### Preview button:
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview comment\"> (You must preview at least once before you can submit.)\n";
  $output .= "</FORM>\n";

  $theme->box("Reply", $output); 
}

function comment_preview($pid, $sid, $subject, $comment, $mode, $order, $thold) {
  global $user, $theme;

  ### Preview comment:
  if ($user) $theme->comment("", $subject, $comment, time(), "", "", "na", "", "reply to this comment");
  else $theme->comment($user->userid, $subject, $comment, time(), $user->url, $user->femail, "na", "", "reply to this comment");

  ### Build reply form:
  $output .= "<FORM ACTION=\"comments.php\" METHOD=\"post\">\n";

  ### Name field:
  if ($user) {
    $output .= "<P>\n";
    $output .= " <B>Your name:</B><BR>\n";
    $output .= " <A HREF=\"account.php\">$user->userid</A> &nbsp; &nbsp; <FONT SIZE=\"2\">[ <A HREF=\"account.php?op=logout\">logout</A> ]</FONT>\n";
    $output .= "</P>\n";
  }
  else {
    $output .= "<P>\n";
    $output .= " <B>Your name:</B><BR>\n";
    $output .= " $anonymous\n"; 
    $output .= "</P>\n";
  }

  ### Subject field:
  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"$subject\">\n";
  $output .= "</P>\n";

  ### Comment field:
  $output .= "<P>\n";
  $output .= " <B>Comment:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">$comment</TEXTAREA><BR>\n";
  $output .= "</P>\n";
  
  ### Hidden fields:
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"mode\" VALUE=\"$mode\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"order\" VALUE=\"$order\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"thold\" VALUE=\"$thold\">\n";

  ### Preview and submit buttons:
  if (empty($subject)) {
    $output .= "<P>\n";
    $output .= " <FONT COLOR=\"red\"><B>Warning:</B></FONT> you did not supply a <U>subject</U>.\n";
    $outout .= "</P>\n";
    $output .= "<P>\n";
    $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview comment\">\n";
    $output .= "</P>\n";
  }
  else {
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview comment\">\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Post comment\">\n";
    $output .= "</FORM>\n";
  }

  $theme->box("Reply", $output); 
}

function comment_post($pid, $sid, $subject, $comment, $mode, $order, $thold) {
  global $user, $theme;

  ### Check for fake threads:
  $fake = db_result(db_query("SELECT COUNT(*) FROM stories WHERE id = $sid"), 0);

  ### Check for duplicate comments:
  $duplicate = db_result(db_query("SELECT COUNT(*) FROM comments WHERE pid = '$pid' AND sid = '$sid' AND subject = '$subject' AND comment = '$comment'"), 0);

  if ($fake != 1) {
    $theme->box("fake comment", "fake comment: $fake");
  }
  elseif ($duplicate != 0) {
    $theme->box("duplicate comment", "duplicate comment: $duplicate");
  }
  else { 
    if ($user) {
      ### Add comment to database:
      db_query("INSERT INTO comments (pid, sid, author, subject, comment, hostname, timestamp) VALUES ($pid, $sid, $user->id, '$subject', '$comment', '". getenv("REMOTE_ADDR") ."', '". time() ."')");

      ### Compose header:
      $header = "article.php?id=$sid";
      $header .= ($mode) ? "&mode=$mode" : "&mode=threaded";
      $header .= ($order) ? "&order=$order" : "&order=0";
      $header .= ($thold) ? "&thold=$thold" : "&thold=0";
    }
    else {
      ### Add comment to database:
      db_query("INSERT INTO comments (pid, sid, subject, comment, hostname, timestamp) VALUES ($pid, $sid, '$subject', '$comment', '". getenv("REMOTE_ADDR") ."', '". time() ."')");

      ### Compose header:
      $header .= "article.php?id=$sid&mode=threaded&order=1&thold=0";
    }
    header("Location: $header");
  }
}

function moderate($cid, $meta_value = 0) {
  include "config.inc";
  if ($meta_value != -1) {
    ### Compose query:
    $query = "UPDATE comments SET";
    if ($meta_value > (sizeof($comments_meta_reasons) / 2)) {
      $query .= " score = score + 1, reason = $meta_value WHERE cid = $cid";
    } 
    elseif ($meta_value < ((sizeof($comments_meta_reasons) / 2) - 1)) {
      $query .= " score = score - 1, reason = $meta_value WHERE cid = $cid";
    }
    else {
      $query .= " reason = $meta_value WHERE cid = $cid";
    }

    ### Perform query:
    mysql_query("$query");
  }
}

if (strstr($PHP_SELF, "comments.php")) {
  include "theme.inc";
  include "functions.inc";
}

switch($op) {
  case "reply":
    $theme->header();
    comments_reply($pid, $sid, $mode, $order, $thold);
    $theme->footer();
    break;
  case "Preview comment":
    $theme->header();
    comment_preview($pid, $sid, $subject, $comment, $mode, $order, $thold);
    $theme->footer();
    break;
  case "Post comment":
    comment_post($pid, $sid, $subject, $comment, $mode, $order, $thold);
    break;
  case "Moderate":
    while (list($name, $value) = each($HTTP_POST_VARS)) {
      if (eregi("meta", $name)) {
        ### extract comment id (cid):
        $info = explode(":", $name);
        moderate($info[1], $value);
      }
    }

    Header("Location: article.php?sid=$sid&mode=$mode&order=$order&thold=$thold");
    break;
  case "show":
    $theme->header();
    comments_display($sid, $pid, $cid, $mode, $order, $thold);
    $theme->footer();
    break;
  default:
    comments_display($id, 0, 0, $mode, $order, $thold);
}

?>