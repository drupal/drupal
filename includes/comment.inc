<?php

// Security check:
if (strstr($id, " ") || strstr($pid, " ") || strstr($lid, " ") || strstr($mode, " ") || strstr($order, " ") || strstr($threshold, " ")) {
  watchdog("error", "comment: attempt to provide malicious input through URI");
  exit();
}

$cmodes = array(1 => "List - min", 2 => "List - max", 3 => "Threaded - min", 4 => "Threaded - max");
$corder = array(1 => "Date - new", 2 => "Date - old", 3 => "Rate - high", 4 => "Rate - low");

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
      if ($vote != $comment_votes[$none]) {
        $id = check_output($id);
        $vote = check_output($vote);
        $comment = db_fetch_object(db_query("SELECT * FROM comments WHERE cid = '$id'"));
        if ($comment && !field_get($comment->users, $user->userid)) {
          $result = db_query("UPDATE comments SET score = score $vote, votes = votes + 1, users = '". field_set($comment->users, $user->userid, $vote) ."' WHERE cid = '$id'");
        }
      }
    }
  }
}

function comment_settings($mode, $order, $threshold) {
  global $user;
  if ($user->id) $user = user_save($user, array("mode" => $mode, "sort" => $order, "threshold" => $threshold));
}

function comment_form($edit) {
  global $REQUEST_URI, $user;

  // name field:
  $form .= form_item(t("Your name"), format_username($user->userid));

  // subject field:
  $form .= form_textfield(t("Subject"), "subject", $edit[subject], 50, 64);

  // comment field:
  $form .= form_textarea(t("Comment"), "comment", $edit[comment] ? $edit[comment] : $user->signature, 70, 10, t("Allowed HTML tags") .": ". htmlspecialchars(variable_get("allowed_html", "")));

  // preview button:
  $form .= form_hidden("pid", $edit[pid]);
  $form .= form_hidden("id", $edit[id]);

  if (!$edit[comment]) {
    $form .= form_submit(t("Preview comment"));
  }
  else {
    $form .= form_submit(t("Preview comment"));
    $form .= form_submit(t("Post comment"));
  }

  return form($REQUEST_URI, $form);
}

function comment_reply($pid, $id) {
  global $theme;

  if ($pid) {
    $item = db_fetch_object(db_query("SELECT comments.*, users.userid FROM comments LEFT JOIN users ON comments.author = users.id WHERE comments.cid = '$pid'"));
    comment_view(new Comment($item->userid, $item->subject, $item->comment, $item->timestamp, $item->url, $item->fake_email, comment_score($comment), $comment->votes, $item->cid, $item->lid), t("reply to this comment"));
  }
  else {
    node_view(node_get_object(array("nid" => $id)));
    $pid = 0;
  }

  if (user_access("post comments")) {
    $theme->box(t("Reply"), comment_form(array(pid=>$pid, id=>$id)));
  }
  else {
    $theme->box(t("Reply"), t("You are not authorized to post comments."));
  }
}

function comment_preview($edit) {
  global $REQUEST_URI, $theme, $user;

  // Preview comment:
  comment_view(new Comment($user->userid, check_preview($edit[subject]), check_preview($edit[comment]), time(), check_preview($user->url), check_preview($user->fake_email), 0, 0, 0, 0), t("reply to this comment"));

  $theme->box(t("Reply"), comment_form($edit));
}

function comment_post($edit) {
  global $theme, $user;

  if (user_access("post comments")) {
    // check comment submission rate:
    throttle("post comment", variable_get(max_comment_rate, 60));

    // check for duplicate comments:
    $duplicate = db_result(db_query("SELECT COUNT(cid) FROM comments WHERE pid = '". check_input($edit[pid]) ."' AND lid = '". check_input($edit[id]) ."' AND subject = '". check_input($edit[subject]) ."' AND comment = '". check_input($edit[comment]) ."'"), 0);

    if ($duplicate != 0) {
      watchdog("warning", "comment: duplicate '$edit[subject]'");
    }
    else {
      // validate subject:
      $edit[subject] = $edit[subject] ? $edit[subject] : substr($edit[comment], 0, 29);

      // add watchdog entry:
      watchdog("special", "comment: added '$edit[subject]'");

      // add comment to database:
      db_query("INSERT INTO comments (lid, pid, author, subject, comment, hostname, timestamp, score) VALUES ('". check_input($edit[id]) ."', '". check_input($edit[pid]) ."', '$user->id', '". check_input($edit[subject]) ."', '". check_input($edit[comment]) ."', '". getenv("REMOTE_ADDR") ."', '". time() ."', '". ($user->userid ? 1 : 0) ."')");

      // clear cache:
      cache_clear();
    }
  }
}

function comment_score($comment) {
  $value = ($comment->votes) ? ($comment->score / $comment->votes) : (($comment->score) ? $comment->score : 0);
  return ((strpos($value, ".")) ? substr($value ."00", 0, 4) : $value .".00");
}

function comment_num_replies($id, $count = 0) {
  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = '$id'");
  return ($result) ? db_result($result, 0) : 0;
}

function comment_moderation($comment) {
  global $comment_votes, $op, $user;

  if ($op == "reply") {
    // preview comment:
    $output .= "&nbsp;";
  }
  else if ($user->id && $user->userid != $comment->userid && !field_get($comment->users, $user->userid)) {
    // comment hasn't been moderated yet:
    foreach ($comment_votes as $key=>$value) $options .= " <OPTION VALUE=\"$value\">$key</OPTION>\n";
    $output .= "<SELECT NAME=\"moderate[$comment->cid]\">$options</SELECT>\n";
  }
  else {
    // comment has already been moderated:
    $output .= "<TABLE BORDER=\"0\" CELLSPACING=\"1\" CELLPADDING=\"1\"><TR><TD ALIGN=\"right\">". t("score") .":</TD><TD>". check_output($comment->score) ."</TD></TR><TR><TD ALIGN=\"right\">". t("votes") .":</TD><TD>". check_output($comment->votes) ."</TD></TR></TABLE>\n";
  }

  return $output;
}

function comment_threshold($threshold) {
  for ($i = -1; $i < 6; $i++) $options .= " <OPTION VALUE=\"$i\"". ($threshold == $i ? " SELECTED" : "") .">". t("Filter") ." - $i</OPTION>";
  return "<SELECT NAME=\"threshold\">$options</SELECT>\n";
}

function comment_mode($mode) {
  global $cmodes;
  foreach ($cmodes as $key=>$value) $options .= " <OPTION VALUE=\"$key\"". ($mode == $key ? " SELECTED" : "") .">$value</OPTION>\n";
  return "<SELECT NAME=\"mode\">$options</SELECT>\n";
}

function comment_order($order) {
  global $corder;
  foreach ($corder as $key=>$value) $options .= " <OPTION VALUE=\"$key\"". ($order == $key ? " SELECTED" : "") .">$value</OPTION>\n";
  return "<SELECT NAME=\"order\">$options</SELECT>\n";
}

function comment_query($lid, $order, $pid = -1) {
  $query .= "SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.lid = '$lid'";
  if ($pid >= 0) $query .= " AND pid = '$pid'";
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
  global $mod;
  if ($args) return ($mod) ? "module.php?mod=$mod;$args" : "node.php?$args";
  else return ($mod) ? "module.php?mod=$mod" : "node.php";
}

function comment_links($comment, $return = 1) {
  global $theme;
  if ($return) return "<A HREF=\"". comment_uri("id=$comment->lid#$comment->cid") ."\"><FONT COLOR=\"$theme->type\">". t("return") ."</FONT></A> | <A HREF=\"". comment_uri("op=reply&id=$comment->lid&pid=$comment->cid") ."\"><FONT COLOR=\"$theme->type\">". t("reply to this comment") ."</FONT></A>";
  else return "<A HREF=\"". comment_uri("op=reply&id=$comment->lid&pid=$comment->cid") ."\"><FONT COLOR=\"$theme->type\">". t("reply to this comment") ."</FONT></A>";
}

function comment_view($comment, $folded = 0) {
  global $theme;

  // calculate comment's score:
  $comment->score = comment_score($comment);

  // display comment:
  if ($folded) $theme->comment($comment, $folded);
  else print "<A HREF=\"". comment_uri("id=$comment->lid&cid=$comment->cid#$comment->cid") ."\">". check_output($comment->subject) ."</A> by ". format_username($comment->userid) ." <SMALL>($comment->score)</SMALL><P>";
}

function comment_thread_min($cid, $threshold) {
  global $user;

  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = '$cid' ORDER BY c.timestamp, c.cid");

  while ($comment = db_fetch_object($result)) {
    print "<ul>";
    print comment_view($comment);
    comment_thread_min($comment->cid, $threshold);
    print "</ul>";
  }
}

function comment_thread_max($cid, $mode, $threshold, $level = 0, $dummy = 0) {
  global $user;

  $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.pid = '$cid' ORDER BY c.timestamp, c.cid");

  while ($comment = db_fetch_object($result)) {
    print "<table width=100%><tr><td><ul>";
    comment_view($comment, (comment_visible($comment, $threshold) ? comment_links($comment, 0) : 0));
    comment_thread_max($comment->cid, $mode, $threshold, $level + 1, $dummy + 1);
    print "</ul></td></tr></table>";
  }
}

function comment_render($lid, $cid) {
  global $user, $theme, $REQUEST_URI;

  if (user_access("access comments")) {

    // Pre-process variables:
    $lid = empty($lid) ? 0 : $lid;
    $cid = empty($cid) ? 0 : $cid;
    $mode  = ($user->id) ? $user->mode : variable_get(default_comment_mode, 4);
    $order = ($user->id) ? $user->sort : variable_get(default_comment_order, 1);
    $threshold = ($user->id) ? $user->threshold : variable_get(default_comment_threshold, 3);

    if ($user->id) {
      // Comment control:
      $theme->box(t("Comment control"), $theme->comment_controls($threshold, $mode, $order));

      // Print moderation form:
      print "<FORM METHOD=\"post\" ACTION=\"$REQUEST_URI\">\n";
    }

    if ($cid > 0) {
      $result = db_query("SELECT c.*, u.* FROM comments c LEFT JOIN users u ON c.author = u.id WHERE cid = '$cid'");
      if ($comment = db_fetch_object($result)) {
        comment_view($comment, comment_links($comment));
      }
    }
    else {
      if ($mode == 1) {
        $result = comment_query($lid, $order);
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
        $result = comment_query($lid, $order);
        while ($comment = db_fetch_object($result)) {
          comment_view($comment, (comment_visible($comment, $threshold) ? comment_links($comment, 0) : 0));
        }
      }
      else if ($mode == 3) {
        $result = comment_query($lid, $order, 0);
        while ($comment = db_fetch_object($result)) {
          comment_view($comment);
          comment_thread_min($comment->cid, $threshold);
        }
      }
      else {
        $result = comment_query($lid, $order, 0);
        while ($comment = db_fetch_object($result)) {
          comment_view($comment, (comment_visible($comment, $threshold) ? comment_links($comment, 0) : 0));
          comment_thread_max($comment->cid, $mode, $threshold, $level + 1);
        }
      }
    }

    if ($user->id) {
      // Print moderation form:
      print "  <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$lid\">\n";
      print "  <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Moderate comments") ."\">\n";
      print "</FORM>\n";
    }
  }
}

?>
