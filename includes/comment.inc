<?

// Security check:
if (strstr($id, " ") || strstr($pid, " ") || strstr($lid, " ") || strstr($mode, " ") || strstr($order, " ") || strstr($threshold, " ")) {
  watchdog("error", "comment: attempt to provide malicious input through URI");
  exit();
}

$cmodes = array(1 => "List - min", 2 => "List - max", 3 => "Threaded - min", 4 => "Threaded - max");
$corder = array(1 => "Date - new", 2 => "Date - old", 3 => "Rate - high", 4 => "Rate - low");
$link = ($mod) ? $mod : substr(strrchr($PHP_SELF, "/"), 1, strrchr($PHP_SELF, "/") - 4);

class Comment {
  function Comment($userid, $subject, $comment, $timestamp, $url, $fake_email, $score, $votes, $cid, $lid) {
    $this->userid = $userid;
    $this->subject = $subject;
    $this->comment = $comment;
    $this->timestamp = $timestamp;
    $this->url = $url;
    $this->fake_email = $fake_email;
    $this->score = $score;
    $this->votes = $votes;
    $this->cid = $cid;
    $this->lid = $lid;
  }
}

function comment_moderate($moderate) {
  global $user, $comment_votes;

  if ($user->id && $moderate) {
    $none = $comment_votes[key($comment_votes)];

    foreach ($moderate as $id=>$vote) {
      if ($vote != $comment_votes[$none] && !user_get($user, "history", "c$id")) {
        // Update the comment's score:
        $result = db_query("UPDATE comments SET score = score $vote, votes = votes + 1 WHERE cid = $id");

        // Update the user's history:
        $user = user_set($user, "history", "c$id", $vote);
      }
    }
  }
}

function comment_settings($mode, $order, $threshold) {
  global $user;
  if ($user->id) $user = user_save($user, array("mode" => $mode, "sort" => $order, "threshold" => $threshold));
}

function comment_reply($pid, $id) {
  global $allowed_html, $link, $REQUEST_URI, $theme, $user;

  if ($pid) {
    $item = db_fetch_object(db_query("SELECT comments.*, users.userid FROM comments LEFT JOIN users ON comments.author = users.id WHERE comments.cid = $pid"));
    $theme->comment(new Comment($item->userid, $item->subject, $item->comment, $item->timestamp, $item->url, $item->fake_email, comment_score($comment), $comment->votes, $item->cid, $item->lid), "reply to this comment");
  }
  else {
    $pid = 0;
    if ($link == "story") {
      $item = db_fetch_object(db_query("SELECT stories.*, users.userid FROM stories LEFT JOIN users ON stories.author = users.id WHERE stories.status != 0 AND stories.id = $id"));
      $theme->article($item, "");
    }
  }

  // Build reply form:
  $output .= "<FORM ACTION=\"$REQUEST_URI\" METHOD=\"post\">\n";

  // Name field:
  $output .= "<B>". t("Your name") .":</B><BR>\n";
  $output .= format_username($user->userid) ."<P>\n";

  // Subject field:
  $output .= "<B>". t("Subject") .":</B><BR>\n";
  $output .= "<INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\"><P>\n";

  // Comment field:
  $output .= "<B>".t("Comment") .":</B><BR>\n";
  $output .= "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">". check_textarea($user->signature) ."</TEXTAREA><BR>\n";
  $output .= "<SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL><P>\n";

  // Preview button:
  $output .= "<SMALL><I>". t("You must preview at least once before you can submit") .":</I></SMALL><BR>\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n";
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview comment") ."\"><BR>\n";

  $output .= "</FORM>\n";

  $theme->box("Reply", $output);
}

function comment_preview($pid, $id, $subject, $comment) {
  global $allowed_html, $link, $REQUEST_URI, $theme, $user;

  // Preview comment:
  $theme->comment(new Comment($user->userid, $subject, $comment, time(), $user->url, $user->fake_email, 0, 0, 0, 0), "reply to this comment");

  // Build reply form:
  $output .= "<FORM ACTION=\"$REQUEST_URI\" METHOD=\"post\">\n";

  // Name field:
  $output .= "<B>". t("Your name") .":</B><BR>\n";
  $output .= format_username($user->userid) ."<P>\n";

  // Subject field:
  $output .= "<B>". t("Subject") .":</B><BR>\n";
  $output .= "<INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"". check_textfield($subject) ."\"><P>\n";

  // Comment field:
  $output .= "<B>". t("Comment") .":</B><BR>\n";
  $output .= "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">". check_textarea($comment) ."</TEXTAREA><BR>\n";
  $output .= "<SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL><P>\n";

  // Hidden fields:
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">\n";
  $output .= "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n";

  if (empty($subject)) {
    $output .= "<FONT COLOR=\"red\">". t("Warning: you did not supply a subject.") ."</FONT><P>\n";
  }

  // Preview and submit button:
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview comment") ."\">\n";
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Post comment") ."\">\n";
  $output .= "</FORM>\n";

  $theme->box(t("Reply"), $output);
}

function comment_post($pid, $id, $subject, $comment) {
  global $theme, $link, $user;

  // Check for duplicate comments:
  $duplicate = db_result(db_query("SELECT COUNT(cid) FROM comments WHERE link = '$link' AND pid = '$pid' AND lid = '$id' AND subject = '". check_input($subject) ."' AND comment = '". check_input($comment) ."'"), 0);

  if ($duplicate != 0) {
    watchdog("error", "comment: attempt to insert duplicate comment");
  }
  else {
    // Validate subject:
    $subject = ($subject) ? $subject : substr($comment, 0, 29);

    // Add watchdog entry:
    watchdog("comment", "comment: added comment with subject '$subject'");

    // Add comment to database:
    db_query("INSERT INTO comments (link, lid, pid, author, subject, comment, hostname, timestamp, score) VALUES ('". check_input($link) ."', $id, $pid, '$user->id', '". check_input($subject) ."', '". check_input($comment) ."', '". getenv("REMOTE_ADDR") ."', '". time() ."', '". ($user->userid ? 1 : 0) ."')");
  }
}

function comment_score($comment) {
  $value = ($comment->votes) ? ($comment->score / $comment->votes) : (($comment->score) ? $comment->score : 0);
  return ((strpos($value, ".")) ? substr($value ."00", 0, 4) : $value .".00");
}

function comment_num_replies($id, $count = 0) {
  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = $id");
  return ($result) ? db_result($result, 0) : 0;
}

function comment_num_filtered($lid, $pid) {
  global $user;

  $threshold = ($user->id) ? $user->threshold  : "0";
  $pid = ($pid) ? $pid : 0;

  $result = db_query("SELECT COUNT(cid) FROM comments WHERE lid = $lid AND pid = $pid AND ((votes = 0 AND score < $threshold) OR (score / votes < $threshold))");
  return ($result) ? db_result($result, 0) : 0;
}

function comment_moderation($comment) {
  global $comment_votes, $op, $user;

  if ($op == "reply") {
    $output .= "&nbsp;";
  }
  else if ($user->id && $user->userid != $comment->userid && !user_get($user, "history", "c$comment->cid")) {
    $output .= "<SELECT NAME=\"moderate[$comment->cid]\">\n";
    foreach ($comment_votes as $key=>$value) $output .= " <OPTION VALUE=\"$value\">$key</OPTION>\n";
    $output .= "</SELECT>\n";
  }
  else {
    $output .= "<TABLE BORDER=\"0\" CELLSPACING=\"1\" CELLPADDING=\"1\"><TR><TD>". t("score") .":</TD><TD>". check_output($comment->score) ."</TD></TR><TR><TD>". t("votes") .":</TD><TD>". check_output($comment->votes) ."</TR></TABLE>\n";
  }

  return $output;
}

function comment_controls($threshold = 1, $mode = 3, $order = 1) {
  global $REQUEST_URI, $user;
  $output .= "<FONT SIZE=\"2\">\n";
  $output .= "<FORM METHOD=\"post\" ACTION=\"$REQUEST_URI\">\n";
  $output .= comment_mode(($user->id ? $user->mode : $mode));
  $output .= comment_order(($user->id ? $user->sort : $order));
  $output .= comment_threshold(($user->id ? $user->threshold : $threshold));
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Update settings") ."\">\n";
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Add comment") ."\">\n";
  $output .= "</FORM>\n";
  $output .= "</FONT>\n";
  return $output;
}

function comment_threshold($threshold) {
  $output .= "<SELECT NAME=\"threshold\">\n";
  for ($i = -1; $i < 6; $i++) {
    $output .= " <OPTION VALUE=\"$i\"". ($threshold == $i ? " SELECTED" : "") .">". t("Filter") ." - $i</OPTION>";
  }
  $output .= "</SELECT>\n";
  return $output;
}

function comment_mode($mode) {
  global $cmodes;
  $output .= "<SELECT NAME=\"mode\">\n";
  foreach ($cmodes as $key=>$value) {
    $output .= " <OPTION VALUE=\"$key\"". ($mode == $key ? " SELECTED" : "") .">$value</OPTION>\n";
  }
  $output .= "</SELECT>\n";
  return $output;
}

function comment_order($order) {
  global $corder;
  $output .= "<SELECT NAME=\"order\">\n";
  foreach ($corder as $key=>$value) {
    $output .= " <OPTION VALUE=\"$key\"". ($order == $key ? " SELECTED" : "") .">$value</OPTION>\n";
  }
  $output .= "</SELECT>\n";
  return $output;
}

function comment_query($link, $lid, $order, $pid = -1) {
  $query .= "SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE link = '$link' AND c.lid = $lid";
  if ($pid >= 0) $query .= " AND pid = $pid";
  if ($order == 1) $query .= " ORDER BY c.timestamp DESC";
  else if ($order == 2) $query .= " ORDER BY c.timestamp";
  else if ($order == 3) $query .= " ORDER BY c.score DESC";
  else if ($order == 4) $query .= " ORDER BY c.score";
  return db_query($query);
}

function comment_visible($comment, $threshold = 0) {
  if ($comment->votes == 0 && $comment->score >= $threshold) return 1;
  else if ($comment->votes > 0 && $comment->score / $comment->votes >= $threshold) return 1;
  else return 0;
}

function comment_uri($args = 0) {
  global $link, $mod;
  if ($args) return ($mod) ? "module.php?mod=$mod&$args" : $link .".php?$args";
  else return ($mod) ? "module.php?mod=$mod" : $link .".php";
}

function comment_link($comment, $return = 1) {
  global $link, $theme;
  if ($return) return "<A HREF=\"". comment_uri("id=$comment->lid#$comment->cid") ."\"><FONT COLOR=\"$theme->link\">return</FONT></A> | <A HREF=\"". comment_uri("op=reply&id=$comment->lid&pid=$comment->cid") ."\"><FONT COLOR=\"$theme->link\">reply to this comment</FONT></A>";
  else return "<A HREF=\"". comment_uri("op=reply&id=$comment->lid&pid=$comment->cid") ."\"><FONT COLOR=\"$theme->link\">reply to this comment</FONT></A>";
}

function comment_comment($comment, $folded = 0) {
  global $link, $theme;
  if ($folded) $theme->comment($comment, $folded);
  else print "<A HREF=\"". comment_uri("id=$comment->lid&cid=$comment->cid#$comment->cid") ."\">". check_output($comment->subject) ."</A> by ". format_username($comment->userid) ." <SMALL>(". $score = comment_score($comment) .")</SMALL></LI>";
}

function comment_thread_min($cid, $threshold) {
  global $user, $theme;

  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = $cid ORDER BY c.timestamp, c.cid");

  print "<UL>";
  while ($comment = db_fetch_object($result)) {
    comment_comment($comment);
    comment_thread_min($comment->cid, $threshold);
  }
  print "</UL>";
}

function comment_thread_max($cid, $mode, $threshold, $level = 0, $dummy = 0) {
  global $link, $theme, $user;

  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE link = '$link' AND c.pid = $cid ORDER BY c.timestamp, c.cid");

  print "<UL>";
  while ($comment = db_fetch_object($result)) {
    if (comment_visible($comment, $threshold)) {
      $comment = new Comment($comment->userid, $comment->subject, $comment->comment, $comment->timestamp, $comment->url, $comment->fake_email, comment_score($comment), $comment->votes, $comment->cid, $comment->lid);
      $theme->comment($comment, comment_link($comment, 0));
    }
    else {
      comment_comment($comment);
      print "<P>";
    }
    comment_thread_max($comment->cid, $mode, $threshold, $level + 1, $dummy + 1);
  }
  print "</UL>";
}

function comment_render($lid, $cid) {
  global $link, $theme, $REQUEST_URI, $user;

  // Pre-process variables:
  $lid = empty($lid) ? 0 : $lid;
  $cid = empty($cid) ? 0 : $cid;
  $mode  = ($user->id) ? $user->mode : 4;
  $order = ($user->id) ? $user->sort : 1;
  $threshold = ($user->id) ? $user->threshold : 3;

  if ($user->id) {
    // Comment control:
    $theme->controls($threshold, $mode, $order);

    // Print moderation form:
    print "<FORM METHOD=\"post\" ACTION=\"$REQUEST_URI\">\n";
  }

  if ($cid > 0) {
    $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE cid = $cid");
    if ($comment = db_fetch_object($result)) {
      $theme->comment($comment, comment_link($comment));
    }
  }
  else {
    if ($mode == 1) {
      $result = comment_query($link, $lid, $order);
      print "<TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"2\">\n";
      print " <TR><TH>Subject</TH><TH>Author</TH><TH>Date</TH><TH>Score</TH></TR>\n";
      while ($comment = db_fetch_object($result)) {
        if (comment_visible($comment, $threshold)) {
          print " <TR><TD><A HREF=\"". comment_uri("id=$comment->lid&cid=$comment->cid#$comment->cid") ."\">". check_output($comment->subject) ."</A></TD><TD>". format_username($comment->userid) ."</TD><TD>". format_date($comment->timestamp, "small") ."</TD><TD>". comment_score($comment) ."</TD></TR>\n";
        }
      }
      print "</TABLE>\n";
    }
    else if ($mode == 2) {
      $result = comment_query($link, $lid, $order);
      while ($comment = db_fetch_object($result)) {
        if (comment_visible($comment, $threshold)) {
          $theme->comment($comment, comment_link($comment, 0));
        }
      }
    }
    else if ($mode == 3) {
      $result = comment_query($link, $lid, $order, 0);
      while ($comment = db_fetch_object($result)) {
        comment_comment($comment);
        comment_thread_min($comment->cid, $threshold);
      }
    }
    else {
      $result = comment_query($link, $lid, $order, 0);
      while ($comment = db_fetch_object($result)) {
        comment_comment($comment, (comment_visible($comment, $threshold) ? comment_link($comment, 0) : 0));
        comment_thread_max($comment->cid, $mode, $threshold, $level + 1);
      }
    }
  }

  if ($user->id) {
    // Print moderation form:
    print " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$lid\">\n";
    print " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Moderate comments") ."\">\n";
    print "</FORM>\n";
  }
}

?>