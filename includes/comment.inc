<?php

// Security check:
if (strstr($id, " ") || strstr($pid, " ") || strstr($lid, " ") || strstr($mode, " ") || strstr($order, " ") || strstr($threshold, " ")) {
  watchdog("error", "comment: attempt to provide malicious input through URI");
  exit();
}

$cmodes = array(1 => "List - min", 2 => "List - max", 3 => "Threaded - min", 4 => "Threaded - max");
$corder = array(1 => "Date - new", 2 => "Date - old", 3 => "Rate - high", 4 => "Rate - low");

class Comment {
  function Comment($uid, $name, $subject, $comment, $timestamp, $url, $cid, $lid) {
    $this->uid = $uid;
    $this->name = $name;
    $this->subject = $subject;
    $this->comment = $comment;
    $this->timestamp = $timestamp;
    $this->url = $url;
    $this->cid = $cid;
    $this->lid = $lid;
  }
}

function comment_moderate($moderate) {
  global $user;

  if ($user->uid && $moderate) {
    foreach ($moderate as $cid => $score) {
      if ($score > 0 && $score < 6) {
        if (db_fetch_object(db_query("SELECT * FROM moderate WHERE uid = '". check_query($user->uid) ."' AND cid = '". check_query($cid) ."'"))) {
          db_query("UPDATE moderate SET score = '". check_query($score) ."' WHERE uid = '". check_query($user->uid) ."' AND cid = '". check_query($cid) ."'");
        }
        else {
          db_query("INSERT INTO moderate (uid, cid, score, timestamp) VALUES ('". check_query($user->uid) ."', '". check_query($cid) ."', '". check_query($score) ."', '". time() ."')");
        }
      }
    }
  }
}

function comment_settings($mode, $order, $threshold) {
  global $user;

  if ($user->uid) {
    $user = user_save($user, array("mode" => $mode, "sort" => $order, "threshold" => $threshold));
  }
}

function comment_form($edit) {
  global $user;

  // name field:
  $form .= form_item(t("Your name"), format_name($user));

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

  return form($form);
}

function comment_reply($pid, $id) {
  global $theme;

  if ($pid) {
    $item = db_fetch_object(db_query("SELECT c.*, u.uid, u.name FROM comments c LEFT JOIN user u ON c.author = u.uid WHERE c.cid = '$pid'"));
    comment_view(new Comment($item->uid, $item->name, $item->subject, $item->comment, $item->timestamp, $item->url, $item->cid, $item->lid), t("reply to this comment"));
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
  global $theme, $user;

  // Preview comment:
  comment_view(new Comment($user->uid, $user->name, check_preview($edit[subject]), check_preview($edit[comment]), time(), check_preview($user->homepage), 0, 0, 0, 0), t("reply to this comment"));

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
      db_query("INSERT INTO comments (lid, pid, author, subject, comment, hostname, timestamp) VALUES ('". check_input($edit[id]) ."', '". check_input($edit[pid]) ."', '$user->uid', '". check_input($edit[subject]) ."', '". check_input($edit[comment]) ."', '". getenv("REMOTE_ADDR") ."', '". time() ."')");

      // clear cache:
      cache_clear();
    }
  }
}

function comment_num_replies($id, $count = 0) {

  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = '$id'");
  return ($result) ? db_result($result, 0) : 0;

}

function comment_moderation($comment) {
  global $user;

  $values = array("--", 1, 2, 3, 4, 5);

  $moderate = db_fetch_object(db_query("SELECT * FROM moderate WHERE cid = '$comment->cid' AND uid = '$user->uid'"));

  foreach ($values as $key => $value) {
    $options .= " <option value=\"$key\"". ($moderate->score == $key ? " selected=\"selected\"" : "") .">$value</option>\n";
  }

  $output .= "<div align=\"center\">";
  $output .= " <select name=\"moderate[comment][$comment->cid]\">$options</select><br />". ($comment->score ? $comment->score : "--") ." / $comment->votes";
  $output .= "</div>";

  return $output;
}

function comment_threshold($threshold) {
  for ($i = 0; $i < 6; $i++) $options .= " <option value=\"$i\"". ($threshold == $i ? " SELECTED" : "") .">". t("Visibility") ." - $i</option>";
  return "<select name=\"threshold\">$options</select>\n";
}

function comment_mode($mode) {
  global $cmodes;

  foreach ($cmodes as $key=>$value) $options .= " <option value=\"$key\"". ($mode == $key ? " SELECTED" : "") .">$value</option>\n";
  return "<select name=\"mode\">$options</select>\n";
}

function comment_order($order) {
  global $corder;

  foreach ($corder as $key=>$value) $options .= " <option value=\"$key\"". ($order == $key ? " SELECTED" : "") .">$value</option>\n";
  return "<select name=\"order\">$options</select>\n";
}

function comment_query($lid, $order, $pid = -1) {

  $query .= "SELECT u.*, c.*, SUM(m.score) / COUNT(m.cid) AS score, COUNT(m.cid) AS votes FROM comments c LEFT JOIN user u ON c.author = u.uid LEFT JOIN moderate m ON c.cid = m.cid WHERE c.lid = '$lid'";

  if ($pid >= 0) {
    $query .= " AND pid = '$pid'";
  }

  $query .= " GROUP BY c.cid";

  if ($order == 1) {
    $query .= " ORDER BY c.timestamp DESC";
  }
  else if ($order == 2) {
    $query .= " ORDER BY c.timestamp";
  }
  else if ($order == 3) {
    $query .= " ORDER BY score DESC";
  }
  else if ($order == 4) {
    $query .= " ORDER BY score";
  }

  return db_query($query);

}

function comment_visible($comment, $threshold = 0) {
  if ($comment->votes == 0 || $comment->score >= $threshold) {
    return 1;
  }
  else {
    return 0;
  }
}

function comment_links($comment, $return = 1) {
  global $theme;

  if ($return) {
    return "<a href=\"node.php?id=$comment->lid#$comment->cid\"><font color=\"$theme->type\">". t("return") ."</font></a> | <a href=\"node.php?op=reply&id=$comment->lid&pid=$comment->cid\"><font color=\"$theme->type\">". t("reply to this comment") ."</font></a>";
  }
  else {
    return "<a href=\"node.php?op=reply&id=$comment->lid&pid=$comment->cid\"><font color=\"$theme->type\">". t("reply to this comment") ."</font></a>";
  }
}

function comment_view($comment, $folded = 0) {
  global $theme;

  if ($folded) {
    $theme->comment($comment, $folded);
  }
  else {
    print "<a href=\"node.php?id=$comment->lid&cid=$comment->cid#$comment->cid\">". check_output($comment->subject) ."</a> by ". format_name($comment) ." <small>(". ($comment->score ? $comment->score : "--") ." / $comment->votes)</small><p />";
  }
}

function comment_thread_min($cid, $threshold) {
  global $user;

  $result = db_query("SELECT u.*, c.*, SUM(m.score) / COUNT(m.cid) AS score, COUNT(m.cid) AS votes FROM comments c LEFT JOIN user u ON c.author = u.uid LEFT JOIN moderate m ON c.cid = m.cid WHERE c.pid = '$cid' GROUP BY c.cid ORDER BY c.timestamp, c.cid");

  while ($comment = db_fetch_object($result)) {
    print "<ul>";
    print comment_view($comment);
    comment_thread_min($comment->cid, $threshold);
    print "</ul>";
  }
}

function comment_thread_max($cid, $mode, $threshold, $level = 0, $dummy = 0) {
  global $user;

  /*
  ** We had quite a few browser specific issues: expanded comments below
  ** the top level got truncated on the right hand side.  A range of
  ** solutions have been proposed and tried but either the right margins of
  ** the comments didn't line up well, or the heavily nested tables made
  ** for slow rendering and cluttered HTML.  This is the best work-around
  ** in terms of speed and size.
  */

  $result = db_query("SELECT u.*, c.*, SUM(m.score) / COUNT(m.cid) AS score, COUNT(m.cid) AS votes FROM comments c LEFT JOIN user u ON c.author = u.uid LEFT JOIN moderate m ON c.cid = m.cid WHERE c.pid = '$cid' GROUP BY c.cid ORDER BY c.timestamp, c.cid");

  while ($comment = db_fetch_object($result)) {
    print "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td width=\"". ($level * 25) ."\">&nbsp;</td><td>\n";
    comment_view($comment, (comment_visible($comment, $threshold) ? comment_links($comment, 0) : 0));
    print "</td></tr></table>\n";

    comment_thread_max($comment->cid, $mode, $threshold, $level + 1, $dummy + 1);
  }

}

function comment_render($lid, $cid) {
  global $user, $theme, $mode, $order, $threshold, $REQUEST_URI;

  if (user_access("access comments")) {

    /*
    ** Pre-process variables:
    */

    if (empty($lid)) {
      $lid = 0;
    }

    if (empty($cid)) {
      $cid = 0;
    }

    if (empty($mode)) {
      $mode = $user->uid ? $user->mode : variable_get(default_comment_mode, 4);
    }

    if (empty($order)) {
      $order = $user->uid ? $user->sort : variable_get(default_comment_order, 1);
    }

    if (empty($threshold)) {
      $threshold = $user->uid ? $user->threshold : variable_get(default_comment_threshold, 3);
    }

    print "<form method=\"post\" action=\"$REQUEST_URI\">\n";

    /*
    ** Render control panel:
    */

    $theme->box(t("Control panel"), $theme->comment_controls($threshold, $mode, $order));

    if ($cid > 0) {
      $result = db_query("SELECT u.*, c.*, SUM(m.score) / COUNT(m.cid) AS score, COUNT(m.cid) AS votes  FROM comments c LEFT JOIN user u ON c.author = u.uid LEFT JOIN moderate m ON c.cid = m.cid WHERE c.cid = '$cid' GROUP BY c.cid");
      if ($comment = db_fetch_object($result)) {
        comment_view($comment, comment_links($comment));
      }
    }
    else {
      if ($mode == 1) {
        $result = comment_query($lid, $order);
        print "<table border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n";
        print " <tr><th>Subject</th><th>Author</th><th>Date</th><th>Score</th></tr>\n";
        while ($comment = db_fetch_object($result)) {
          if (comment_visible($comment, $threshold)) {
            print " <tr><td><a href=\"node.php?id=$comment->lid&cid=$comment->cid#$comment->cid\">". check_output($comment->subject) ."</a></td><td>". format_name($comment) ."</td><td>". format_date($comment->timestamp, "small") ."</td><td>$comment->score</td></tr>\n";
          }
        }
        print "</table>\n";
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

    print "</form>";

  }
}

?>
