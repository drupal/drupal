<?php

include_once "includes/common.inc";

page_header();

function account_get_user($uname) {
  $result = db_query("SELECT * FROM users WHERE userid = '$uname'");
  return db_fetch_object($result);
}

function account_email() {
  $output .= "<P>". t("Lost your password?  Fill out your username and e-mail address, and your password will be mailed to you.") ."</P>\n";
  $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
  $output .= "<B>". t("Username") .":</B><BR>\n";
  $output .= "<INPUT NAME=\"userid\"><P>\n";
  $output .= "<B>". t("E-mail address") .":</B><BR>\n";
  $output .= "<INPUT NAME=\"email\"><P>\n";
  $output .= "<INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"". t("E-mail new password") ."\">\n";
  $output .= "</FORM>\n";

  return $output;
}

function account_create($error = "") {
  global $theme;

  if ($error) {
    $output .= "<P><FONT COLOR=\"red\">". t("Failed to create account") .": ". check_output($error) ."</FONT></P>\n";
    watchdog("account", "failed to create account: $error");
  }
  else {
    $output .= "<P>". t("Registering allows you to comment, to moderate comments and pending submissions, to customize the look and feel of the site and generally helps you interact with the site more efficiently.") ."</P><P>". t("To create an account, simply fill out this form an click the 'Create account' button below.  An e-mail will then be sent to you with instructions on how to validate your account.") ."</P>\n";
  }

  $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
  $output .= "<B>". t("Username") .":</B><BR>\n";
  $output .= "<INPUT NAME=\"userid\"><BR>\n";
  $output .= "<SMALL><I>". t("Enter your desired username: only letters, numbers and common special characters are allowed.") ."</I></SMALL><P>\n";
  $output .= "<B>". t("E-mail address") .":</B><BR>\n";
  $output .= "<INPUT NAME=\"email\"><BR>\n";
  $output .= "<SMALL><I>". t("You will be sent instructions on how to validate your account via this e-mail address: make sure it is accurate.") ."</I></SMALL><P>\n";

  $output .= "<INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"". t("Create account") ."\">\n";
  $output .= "</FORM>\n";

  return $output;
}

function account_session_start($userid, $passwd) {
  global $user;
  if ($userid && $passwd) $user = new User($userid, $passwd);
  if ($user->id) {
    if ($rule = user_ban($user->userid, "username")) {
      watchdog("account", "failed to login for '$user->userid': banned by $rule->type rule '$rule->mask'");
    }
    else if ($rule = user_ban($user->last_host, "hostname")) {
      watchdog("account", "failed to login for '$user->userid': banned by $rule->type rule '$rule->mask'");
    }
    else {
      session_register("user");
      watchdog("account", "session opened for '$user->userid'");
    }
  }
  else watchdog("account", "failed to login for '$userid': invalid username - password combination");
}

function account_session_close() {
  global $user;
  watchdog("account", "session closed for user '$user->userid'");
  session_unset();
  session_destroy();
  unset($user);
}

function account_user_edit() {
  global $theme, $user;

  if ($user->id) {
    // construct form:
    $form .= form_item(t("Username"), $user->userid, t("Required, unique, and can not be changed."));
    $form .= form_textfield(t("Real name"), "name", $user->name, 30, 55, t("Optional"));
    $form .= form_item(t("Real e-mail address"), $user->real_email, t("Required, unique, can not be changed.") ." ". t("Your real e-mail address is never displayed publicly: only needed in case you lose your password."));
    $form .= form_textfield(t("Fake e-mail address"), "fake_email", $user->fake_email, 30, 55, t("Optional") .". ". t("Displayed publicly so you may spam proof your real e-mail address if you want."));
    $form .= form_textfield(t("Homepage"), "url", $user->url, 30, 55, t("Optional") .". ". t("Make sure you enter fully qualified URLs only.  That is, remember to include \"http://\"."));
    $form .= form_textarea(t("Bio"), "bio", $user->bio, 35, 5, t("Optional") .". ". t("Maximal 255 characters.") ." ". t("This biographical information is publicly displayed on your user page.") ."<BR>". t("Allowed HTML tags") .": ". htmlspecialchars(variable_get("allowed_html", "")));
    $form .= form_textarea(t("Signature"), "signature", $user->signature, 35, 5, t("Optional") .". ". t("Maximal 255 characters.") ." ". t("This information will be publicly displayed at the end of your comments.") ."<BR>". t("Allowed HTML tags") .": ". htmlspecialchars(variable_get("allowed_html", "")));
    $form .= form_item(t("Password"), "<INPUT TYPE=\"password\" NAME=\"edit[pass1]\" SIZE=\"10\" MAXLENGTH=\"20\"> <INPUT TYPE=\"password\" NAME=\"edit[pass2]\" SIZE=\"10\" MAXLENGTH=\"20\">", t("Enter your new password twice if you want to change your current password or leave it blank if you are happy with your current password."));
    $form .= form_submit(t("Save user information"));

    // display form:
    $theme->header();
    $theme->box(t("Edit user information"), form("account.php", $form));
    $theme->footer();
  }
  else {
    $theme->header();
    $theme->box(t("Create user account"), account_create());
    $theme->box(t("E-mail new password"), account_email());
    $theme->footer();
  }
}

function account_user_save($edit) {
  global $user;
  if ($user->id) {
    $user = user_save($user, array("name" => $edit[name], "fake_email" => $edit[fake_email], "url" => $edit[url], "bio" => $edit[bio], "signature" => $edit[signature]));
    if ($edit[pass1] && $edit[pass1] == $edit[pass2]) $user = user_save($user, array("passwd" => $edit[pass1]));
  }
}

function account_site_edit() {
  global $cmodes, $corder, $theme, $themes, $languages, $user;

  if ($user->id) {
    // construct form:
    foreach ($themes as $key=>$value) $options .= "<OPTION VALUE=\"$key\"". (($user->theme == $key) ? " SELECTED" : "") .">$key - $value[1]</OPTION>\n";
    $form .= form_item(t("Theme"), "<SELECT NAME=\"edit[theme]\">$options</SELECT>", t("Selecting a different theme will change the look and feel of the site."));
    for ($zone = -43200; $zone <= 46800; $zone += 3600) $zones[$zone] = date("l, F dS, Y - h:i A", time() - date("Z") + $zone) ." (GMT ". $zone / 3600 .")";
    $form .= form_select(t("Timezone"), "timezone", $user->timezone, $zones, t("Select what time you currently have and your timezone settings will be set appropriate."));
    $form .= form_select(t("Language"), "language", $user->language, $languages, t("Selecting a different language will change the language of the site."));
    $form .= form_select(t("Number of nodes to display"), "nodes", $user->nodes, array(10 => 10, 15 => 15, 20 => 20, 25 => 25, 30 => 30), t("The maximum number of nodes that will be displayed on the main page."));
    $form .= form_select(t("Comment display mode"), "mode", $user->mode, $cmodes);
    $form .= form_select(t("Comment display order"), "sort", $user->sort, $corder);
    for ($count = -1; $count < 6; $count++) $threshold[$count] = t("Filter") ." - $count";
    $form .= form_select(t("Comment filter"), "threshold", $user->threshold, $threshold, t("Comments that scored less than this threshold setting will be ignored.  Anonymous comments start at 0, comments of people logged on start at 1 and moderators can add and subtract points."));
    $form .= form_submit(t("Save site settings"));

    // display form:
    $theme->header();
    $theme->box(t("Edit your preferences"), form("account.php", $form));
    $theme->footer();
  }
  else {
    $theme->header();
    if (variable_get("account_register", 1)) $theme->box(t("Create user account"), account_create());
    $theme->box(t("E-mail new password"), account_email());
    $theme->footer();
  }
}

function account_site_save($edit) {
  global $user;
  if ($user->id) {
    $user = user_save($user, array("theme" => $edit[theme], "timezone" => $edit[timezone], "language" => $edit[language], "nodes" => $edit[nodes], "mode" => $edit[mode], "sort" => $edit[sort], "threshold" => $edit[threshold]));
  }
}

function account_content_edit() {
  global $theme, $user;

  if ($user->id) {
    // construct form:
    $result = db_query("SELECT * FROM blocks WHERE status = 1 ORDER BY module");
    while ($block = db_fetch_object($result)) {
      $entry = db_fetch_object(db_query("SELECT * FROM layout WHERE block = '$block->name' AND user = '$user->id'"));
      $options .= "<INPUT TYPE=\"checkbox\" NAME=\"edit[$block->name]\"". ($entry->user ? " CHECKED" : "") ."> ". t($block->name) ."<BR>\n";
    }

    $form .= form_item(t("Blocks in side bars"), $options, t("Enable the blocks you would like to see displayed in the side bars."));
    $form .= form_submit(t("Save content settings"));

    // display form:
    $theme->header();
    $theme->box(t("Edit your content"), form("account.php", $form));
    $theme->footer();
  }
  else {
    $theme->header();
    $theme->box(t("Create user account"), account_create());
    $theme->box(t("E-mail new password"), account_email());
    $theme->footer();
  }
}

function account_content_save($edit) {
  global $user;
  if ($user->id) {
    db_query("DELETE FROM layout WHERE user = '$user->id'");
    foreach (($edit ? $edit : array()) as $block=>$weight) {
      db_query("INSERT INTO layout (user, block) VALUES ('$user->id', '". check_input($block) ."')");
    }
  }
}

function account_user($uname) {
  global $user, $theme;

  if ($user->id && $user->userid == $uname) {
    $output .= "<TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"2\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("Username") .":</B></TD><TD>$user->userid</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("E-mail") .":</B></TD><TD>". format_email($user->fake_email) ."</A></TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("Homepage") .":</B></TD><TD>". format_url($user->url) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>". t("Bio") .":</B></TD><TD>". check_output($user->bio, 1) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>". t("Signature") .":</B></TD><TD>". check_output($user->signature, 1) ."</TD></TR>\n";
    $output .= "</TABLE>\n";

    // Display account information:
    $theme->header();
    $theme->box(t("Personal information"), $output);
    $theme->footer();
  }
  elseif ($uname && $account = account_get_user($uname)) {
    $output .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("Username") .":</B></TD><TD>". check_output($account->userid) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("E-mail") .":</B></TD><TD>". format_email($account->fake_email) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("Homepage") .":</B></TD><TD>". format_url($account->url) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>". t("Bio") .":</B></TD><TD>". check_output($account->bio) ."</TD></TR>\n";
    $output .= "</TABLE>\n";

    // Display account information:
    $theme->header();
    $theme->box(strtr(t("%a's user information"), array("%a" => $uname)), $output);
    $theme->footer();
  }
  else {
    // Display login form:
    $theme->header();
    if (variable_get("account_register", 1)) $theme->box(t("Create user account"), account_create());
    $theme->box(t("E-mail new password"), account_email());
    $theme->footer();
  }
}

function account_email_submit($userid, $email) {
  global $theme;

  $result = db_query("SELECT id FROM users WHERE userid = '$userid' AND real_email = '$email'");

  if ($account = db_fetch_object($result)) {
    $passwd = user_password();
    $hash = substr(md5("$userid. ". time() .""), 0, 12);
    $status = 1;

    db_query("UPDATE users SET passwd = PASSWORD('$passwd'), hash = '$hash', status = '$status' WHERE userid = '$userid'");

    $link = path_uri() ."account.php?op=confirm&name=$userid&hash=$hash";
    $subject = strtr(t("Account details for %a"), array("%a" => variable_get(site_name, "drupal")));
    $message = strtr(t("%a,\n\n\nyou requested us to e-mail you a new password for your account at %b.  You will need to re-confirm your account or you will not be able to login.  To confirm your account updates visit the URL below:\n\n   %c\n\nOnce confirmed you can login using the following username and password:\n\n   username: %a\n   password: %d\n\n\n-- %b team"), array("%a" => $userid, "%b" => variable_get(site_name, "drupal"), "%c" => $link, "%d" => $passwd));

    watchdog("account", "new password: `$userid' &lt;$email&gt;");

    mail($email, $subject, $message, "From: noreply");

    $output = t("Your password and further instructions have been sent to your e-mail address.");
  }
  else {
    watchdog("account", "new password: '$userid' and &lt;$email&gt; do not match");
    $output = t("Could not sent password: no match for the specified username and e-mail address.");
  }

  $theme->header();
  $theme->box(t("E-mail new password"), $output);
  $theme->footer();
}

function account_create_submit($userid, $email) {
  global $theme, $HTTP_HOST, $REQUEST_URI;

  $new[userid] = $userid;
  $new[real_email] = $email;

  if ($error = user_validate($new)) {
    $theme->header();
    $theme->box(t("Create user account"), account_create($error));
    $theme->footer();
  }
  else {
    $new[passwd] = user_password();
    $new[hash] = substr(md5("$new[userid]. ". time()), 0, 12);

    $user = user_save("", array("userid" => $new[userid], "real_email" => $new[real_email], "passwd" => $new[passwd], "status" => 1, "hash" => $new[hash]));

    $link = path_uri() ."account.php?op=confirm&name=$new[userid]&hash=$new[hash]";
    $subject = strtr(t("Account details for %a"), array("%a" => variable_get(site_name, "drupal")));
    $message = strtr(t("%a,\n\n\nsomeone signed up for a user account on %b and supplied this e-mail address as their contact.  If it wasn't you, don't get your panties in a knot and simply ignore this mail.  If this was you, you will have to confirm your account first or you will not be able to login.  To confirm your account visit the URL below:\n\n   %c\n\nOnce confirmed you can login using the following username and password:\n\n   username: %a\n   password: %d\n\n\n-- %b team\n"), array("%a" => $new[userid], "%b" => variable_get(site_name, "drupal"), "%c" => $link, "%d" => $new[passwd]));

    watchdog("account", "new account: `$new[userid]' &lt;$new[real_email]&gt;");

    mail($new[real_email], $subject, $message, "From: noreply");

    $theme->header();
    $theme->box(t("Create user account"), t("Congratulations!  Your member account has been successfully created and further instructions on how to confirm your account have been sent to your e-mail address.  You have to confirm your account first or you will not be able to login."));
    $theme->footer();
  }
}

function account_create_confirm($name, $hash) {
  global $theme;

  $result = db_query("SELECT userid, hash, status FROM users WHERE userid = '$name'");

  if ($account = db_fetch_object($result)) {
    if ($account->status == 1) {
      if ($account->hash == $hash) {
        db_query("UPDATE users SET status = '2', hash = '' WHERE userid = '$name'");
        $output = t("Your account has been successfully confirmed.");
        watchdog("account", "$name: account confirmation successful");
      }
      else {
        $output = t("Confirmation failed: invalid confirmation hash.");
        watchdog("warning", "$name: invalid confirmation hash");
      }
    }
    else {
      $output = t("Confirmation failed: your account has already been confirmed.");
      watchdog("warning", "$name: attempt to re-confirm account");
    }
  }
  else {
    $output = t("Confirmation failed: non-existing account.");
    watchdog("warning", "$name: attempt to confirm non-existing account");
  }

  $theme->header();
  $theme->box(t("Create user account"), $output);
  $theme->footer();
}

function account_track_comments() {
  global $theme, $user;

  $sresult = db_query("SELECT n.nid, n.title, COUNT(n.nid) AS count FROM comments c LEFT JOIN node n ON c.lid = n.nid WHERE c.author = '$user->id' GROUP BY n.nid DESC ORDER BY n.nid DESC LIMIT 5");

  while ($node = db_fetch_object($sresult)) {
    $output .= "<LI>". format_plural($node->count, "comment", "comments") ." ". t("attached to node") ." `<A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A>`:</LI>\n";
    $output .= " <UL>\n";

    $cresult = db_query("SELECT * FROM comments WHERE author = '$user->id' AND lid = '$node->nid'");
    while ($comment = db_fetch_object($cresult)) {
      $output .= "  <LI><A HREF=\"node.php?id=$node->nid&cid=$comment->cid&pid=$comment->pid#$comment->cid\">". check_output($comment->subject) ."</A> (". t("replies") .": ". comment_num_replies($comment->cid) .", ". t("votes") .": $comment->votes, ". t("score") .": ". comment_score($comment) .")</LI>\n";
    }
    $output .= " </UL>\n";
  }

  $theme->header();
  $theme->box(t("Track your comments"), ($output ? $output : t("You have not posted any comments recently.")));
  $theme->footer();
}

function account_track_nodes() {
  global $theme, $user;

  $result = db_query("SELECT n.nid, n.type, n.title, n.timestamp, COUNT(c.cid) AS count FROM node n LEFT JOIN comments c ON c.lid = n.nid WHERE n.status = '". node_status("posted") ."' AND n.author = '$user->id' GROUP BY n.nid DESC ORDER BY n.nid DESC LIMIT 25");

  while ($node = db_fetch_object($result)) {
    $output .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>". t("Subject") .":</B></TD><TD><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A> (". format_plural($node->count, "comment", "comments") .")</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>". t("Type") .":</B></TD><TD>". check_output($node->type) ."</A></TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>". t("Date") .":</B></TD><TD>". format_date($node->timestamp) ."</TD></TR>\n";
    $output .= "</TABLE>\n";
    $output .= "<P>\n";
  }

  $theme->header();
  $theme->box(t("Track your nodes"), ($output ? $output : t("You have not posted any nodes.")));
  $theme->footer();
}

function account_track_site() {
  global $theme, $user;

  $period = 259200; // 3 days

  $theme->header();

  $nresult = db_query("SELECT n.nid, n.title, COUNT(c.cid) AS count FROM comments c LEFT JOIN node n ON n.nid = c.lid WHERE n.status = '". node_status("posted") ."' AND c.timestamp > ". (time() - $period) ." GROUP BY c.lid ORDER BY count DESC");
  while ($node = db_fetch_object($nresult)) {
    $output .= "<LI>". format_plural($node->count, "comment", "comments") ." ". t("attached to") ." '<A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A>':</LI>";

    $cresult = db_query("SELECT c.subject, c.cid, c.pid, u.userid FROM comments c LEFT JOIN users u ON u.id = c.author WHERE c.lid = $node->nid ORDER BY c.timestamp DESC LIMIT $node->count");
    $output .= "<UL>\n";
    while ($comment = db_fetch_object($cresult)) {
      $output .= " <LI>'<A HREF=\"node.php?id=$node->nid&cid=$comment->cid&pid=$comment->pid#$comment->cid\">". check_output($comment->subject) ."</A>' ". t("by") ." ". format_username($comment->userid) ."</LI>\n";
    }
    $output .= "</UL>\n";
  }

  $theme->box(t("Recent comments"), ($output ? $output : t("No comments recently.")));

  unset($output);

  $result = db_query("SELECT n.title, n.nid, n.type, n.status, u.userid FROM node n LEFT JOIN users u ON n.author = u.id WHERE ". time() ." - n.timestamp < $period ORDER BY n.timestamp DESC LIMIT 10");

  $output .= "<TABLE BORDER=\"0\" CELLSPACING=\"4\" CELLPADDING=\"4\">\n";
  $output .= " <TR><TH>". t("Subject") ."</TH><TH>". t("Author") ."</TH><TH>". t("Type") ."</TH><TH>". t("Status") ."</TH></TR>\n";
  while ($node = db_fetch_object($result)) {
    $output .= " <TR><TD><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></TD><TD ALIGN=\"center\">". format_username($node->userid) ."</TD><TD ALIGN=\"center\">$node->type</TD><TD>". node_status($node->status) ."</TD></TR>";
  }
  $output .= "</TABLE>";

  $theme->box(t("Recent nodes"), ($output ? $output : t("No nodes recently.")));

  $theme->footer();
}

// Security check:
if (strstr($name, " ") || strstr($hash, " ")) {
  watchdog("error", "account: attempt to provide malicious input through URI");
  exit();
}

switch ($op) {
  case t("E-mail new password"):
    account_email_submit(check_input($userid), check_input($email));
    break;
  case t("Create account"):
    if (variable_get("account_register", 1)) account_create_submit(check_input($userid), check_input($email));
    break;
  case t("Save user information"):
    account_user_save($edit);
    account_user($user->userid);
    break;
  case t("Save site settings"):
    account_site_save($edit);
    header("Location: account.php?op=info");
    break;
  case t("Save content settings"):
    account_content_save($edit);
    account_user($user->userid);
    break;
  case "confirm":
    account_create_confirm(check_input($name), check_input($hash));
    break;
  case "login":
    account_session_start(check_input($userid), check_input($passwd));
    header("Location: account.php?op=info");
    break;
  case "logout":
    account_session_close();
    header("Location: account.php?op=info");
    break;
  case "view":
    switch ($topic) {
      case "info":
        account_user($user->userid);
        break;
      default:
        account_user(check_input($name));
    }
    break;
  case "track":
    switch ($topic) {
      case "site":
        account_track_site();
        break;
      case "nodes":
        account_track_nodes();
        break;
      default:
        account_track_comments();
    }
    break;
  case "edit":
    switch ($topic) {
      case "content":
        account_content_edit();
        break;
      case "site":
        account_site_edit();
        break;
      default:
        account_user_edit();
    }
    break;
  default:
    account_user($user->userid);
}

page_footer();

?>