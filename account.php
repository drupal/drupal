<?php

include_once "includes/common.inc";

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
    $output .= "<P><FONT COLOR=\"red\">". t("Failed to create account") .": ". check_output($error) .".</FONT></P>\n";
    watchdog("message", "failed to create account: $error.");
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
      watchdog("message", "failed to login for '$user->userid': banned by $rule->type rule '$rule->mask'");
    }
    else if ($rule = user_ban($user->last_host, "hostname")) {
      watchdog("message", "failed to login for '$user->userid': banned by $rule->type rule '$rule->mask'");
    }
    else {
      session_register("user");
      watchdog("message", "session opened for '$user->userid'");
    }
  }
  else watchdog("message", "failed to login for '$userid': invalid username - password combination");
}

function account_session_close() {
  global $user;
  watchdog("message", "session closed for user '$user->userid'");
  session_unset();
  session_destroy();
  unset($user);
}

function account_user_edit() {
  global $allowed_html, $theme, $user;

  if ($user->id) {
    // Generate output/content:
    $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";

    $output .= "<B>". t("Username") .":</B><BR>\n";
    $output .= "$user->userid<P>\n";
    $output .= "<I><SMALL>". t("Required, unique, and can not be changed.") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Real name") .":</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[name]\" MAXLENGTH=\"55\" SIZE=\"30\" VALUE=\"$user->name\"><BR>\n";
    $output .= "<I><SMALL>". t("Optional") .".</SMALL></I><P>\n";

    $output .= "<B>". t("Real e-mail address") .":</B><BR>\n";
    $output .= "$user->real_email<P>\n";
    $output .= "<I><SMALL>". t("Required, unique, can not be changed.") ." ". t("Your real e-mail address is never displayed publicly: only needed in case you lose your password.") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Fake e-mail address") .":</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[fake_email]\" MAXLENGTH=\"55\" SIZE=\"30\" VALUE=\"$user->fake_email\"><BR>\n";
    $output .= "<I><SMALL>". t("Optional") .". ". t("Displayed publicly so you may spam proof your real e-mail address if you want.") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Homepage") .":</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[url]\" MAXLENGTH=\"55\" SIZE=\"30\" VALUE=\"$user->url\"><BR>\n";
    $output .= "<I><SMALL>". t("Optional") .". ". t("Make sure you enter fully qualified URLs only.  That is, remember to include \"http://\".") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Bio") .":</B> (". t("maximal 255 characters") .")<BR>\n";
    $output .= "<TEXTAREA NAME=\"edit[bio]\" COLS=\"35\" ROWS=\"5\" WRAP=\"virtual\">$user->bio</TEXTAREA><BR>\n";
    $output .= "<I><SMALL>". t("Optional") .". ". t("This biographical information is publicly displayed on your user page.") ."<BR>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</SMALL></I><P>\n";

    $output .= "<B>". t("Signature") .":</B> (". t("maximal 255 characters") .")<BR>\n";
    $output .= "<TEXTAREA NAME=\"edit[signature]\" COLS=\"35\" ROWS=\"5\" WRAP=\"virtual\">$user->signature</TEXTAREA><BR>\n";
    $output .= "<I><SMALL>". t("Optional") .". ". t("This information will be publicly displayed at the end of your comments.") ."<BR>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</SMALL></I><P>\n";

    $output .= "<B>". t("Password") .":</B><BR>\n";
    $output .= "<INPUT TYPE=\"password\" NAME=\"edit[pass1]\" SIZE=\"10\" MAXLENGTH=\"20\"> <INPUT TYPE=\"password\" NAME=\"edit[pass2]\" SIZE=\"10\" MAXLENGTH=\"20\"><BR>\n";
    $output .= "<I><SMALL>". t("Enter your new password twice if you want to change your current password or leave it blank if you are happy with your current password.") ."</SMALL></I><P>\n";

    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Save user information") ."\"><BR>\n";
    $output .= "</FORM>\n";

    // Display output/content:
    $theme->header();
    $theme->box(t("Edit user information"), $output);
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
    $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";

    $output .= "<B>". t("Theme") .":</B><BR>\n";
    foreach ($themes as $key=>$value) $options1 .= " <OPTION VALUE=\"$key\"". (($user->theme == $key) ? " SELECTED" : "") .">$key - $value[1]</OPTION>\n";
    $output .= "<SELECT NAME=\"edit[theme]\">\n$options1</SELECT><BR>\n";
    $output .= "<I><SMALL>". t("Selecting a different theme will change the look and feel of the site.") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Timezone") .":</B><BR>\n";
    $date = time() - date("Z");
    for ($zone = -43200; $zone <= 46800; $zone += 3600) $options2 .= " <OPTION VALUE=\"$zone\"". (($user->timezone == $zone) ? " SELECTED" : "") .">". date("l, F dS, Y - h:i A", $date + $zone) ." (GMT ". $zone / 3600 .")</OPTION>\n";
    $output .= "<SELECT NAME=\"edit[timezone]\">\n$options2</SELECT><BR>\n";
    $output .= "<I><SMALL>". t("Select what time you currently have and your timezone settings will be set appropriate.") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Language" ) .":</B><BR>\n";
    foreach ($languages as $key=>$value) $options3 .= " <OPTION VALUE=\"$key\"". (($user->language == $key) ? " SELECTED" : "") .">$value - $key</OPTION>\n";
    $output .= "<SELECT NAME=\"edit[language]\">\n$options3</SELECT><BR>\n";
    $output .= "<I><SMALL>". t("Selecting a different language will change the language the site.") ."</SMALL></I><P>\n";

    $output .= "<B>". t("Maximum number of items to display") .":</B><BR>\n";
    for ($nodes = 10; $nodes <= 30; $nodes += 5) $options4 .= "<OPTION VALUE=\"$nodes\"". (($user->nodes == $nodes) ? " SELECTED" : "") .">$nodes</OPTION>\n";
    $output .= "<SELECT NAME=\"edit[nodes]\">\n$options4</SELECT><BR>\n";
    $output .= "<I><SMALL>". t("The maximum number of nodes that will be displayed on the main page.") ."</SMALL></I><P>\n";

    foreach ($cmodes as $key=>$value) $options5 .= "<OPTION VALUE=\"$key\"". ($user->mode == $key ? " SELECTED" : "") .">$value</OPTION>\n";
    $output .= "<B>". t("Comment display mode") .":</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[mode]\">$options5</SELECT><P>\n";

    foreach ($corder as $key=>$value) $options6 .= "<OPTION VALUE=\"$key\"". ($user->sort == $key ? " SELECTED" : "") .">$value</OPTION>\n";
    $output .= "<B>". t("Comment sort order") .":</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[sort]\">$options6</SELECT><P>\n";

    for ($i = -1; $i < 6; $i++) $options7 .= " <OPTION VALUE=\"$i\"". ($user->threshold == $i ? " SELECTED" : "") .">Filter - $i</OPTION>";
    $output .= "<B>". t("Comment filter") .":</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[threshold]\">$options7</SELECT><BR>\n";
    $output .= "<I><SMALL>". t("Comments that scored less than this threshold setting will be ignored.  Anonymous comments start at 0, comments of people logged on start at 1 and moderators can add and subtract points.") ."</SMALL></I><P>\n";

    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Save site settings") ."\"><BR>\n";
    $output .= "</FORM>\n";

    $theme->header();
    $theme->box(t("Edit your preferences"), $output);
    $theme->footer();
  }
  else {
    $theme->header();
    $theme->box(t("Create user account"), account_create());
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
    $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
    $output .= "<B>". t("Blocks in side bars") .":</B><BR>\n";
    $result = db_query("SELECT * FROM blocks WHERE status = 1 ORDER BY module");
    while ($block = db_fetch_object($result)) {
      $entry = db_fetch_object(db_query("SELECT * FROM layout WHERE block = '$block->name' AND user = '$user->id'"));
      $output .= "<INPUT TYPE=\"checkbox\" NAME=\"edit[$block->name]\"". ($entry->user ? " CHECKED" : "") ."> ". t($block->name) ."<BR>\n";
    }
    $output .= "<P><I><SMALL>". t("Enable the blocks you would like to see displayed in the side bars.") ."</SMALL></I></P>\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Save content settings") ."\">\n";
    $output .= "</FORM>\n";

    $theme->header();
    $theme->box(t("Edit your content"), $output);
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
  global $user, $status, $theme;

  function module($name, $module, $username) {
    global $theme;
    if ($module[account] && $block = $module[account]($username, "account", "view")) {
      if ($block[content]) $theme->box($block[subject], $block[content]);
    }
  }

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
    $block1 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $block1 .= " <TR><TD ALIGN=\"right\"><B>". t("Username") .":</B></TD><TD>$account->userid</TD></TR>\n";
    $block1 .= " <TR><TD ALIGN=\"right\"><B>". t("E-mail") .":</B></TD><TD>". format_email($account->fake_email) ."</TD></TR>\n";
    $block1 .= " <TR><TD ALIGN=\"right\"><B>". t("Homepage") .":</B></TD><TD>". format_url($account->url) ."</TD></TR>\n";
    $block1 .= " <TR><TD ALIGN=\"right\"><B>". t("Bio") .":</B></TD><TD>". check_output($account->bio) ."</TD></TR>\n";
    $block1 .= "</TABLE>\n";

/*
    $result = db_query("SELECT c.cid, c.pid, c.lid, c.subject, c.timestamp, n.title AS node FROM comments c LEFT JOIN users u ON u.id = c.author LEFT JOIN node ON n.id = c.lid WHERE u.userid = '$uname' AND n.status = '$status[posted]' AND s.timestamp > ". (time() - 1209600) ." ORDER BY cid DESC LIMIT 10");
    while ($comment = db_fetch_object($result)) {
      $block2 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
      $block2 .= " <TR><TD ALIGN=\"right\"><B>". t("Comment") .":</B></TD><TD><A HREF=\"node.php?id=$comment->lid&cid=$comment->cid&pid=$comment->pid#$comment->cid\">". check_output($comment->subject) ."</A></TD></TR>\n";
      $block2 .= " <TR><TD ALIGN=\"right\"><B>". t("Date") .":</B></TD><TD>". format_date($comment->timestamp) ."</TD></TR>\n";
      $block2 .= " <TR><TD ALIGN=\"right\"><B>". t("Story") .":</B></TD><TD><A HREF=\"node.php?id=$comment->lid\">". check_output($comment->story) ."</A></TD></TR>\n";
      $block2 .= "</TABLE>\n";
      $block2 .= "<P>\n";
      $comments++;
    }
*/

    // Display account information:
    $theme->header();
    if ($block1) $theme->box(strtr(t("%a's user information"), array("%a" => $uname)), $block1);
//    if ($block2) $theme->box(strtr(t("%a has posted %b recently"), array("%a" => $uname, "%b" => format_plural($comments, "comment", "comments"))), $block2);
    module_iterate("module", $uname);
    $theme->footer();
  }
  else {
    // Display login form:
    $theme->header();
    $theme->box(t("Create user account"), account_create());
    $theme->box(t("E-mail new password"), account_email());
    $theme->footer();
  }
}

function account_validate($user) {
  // Verify username and e-mail address:
  if (empty($user[real_email]) || (!eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $user[real_email]))) $error = t("the e-mail address '$user[real_email]' is not valid");
  if (empty($user[userid]) || (ereg("[^a-zA-Z0-9_-]", $user[userid]))) $error = t("the username '$user[userid]' is not valid");
  if (strlen($user[userid]) > 15) $error = t("the username '$user[userid]' is too long: it must be less than 15 characters");

  // Check to see whether the username or e-mail address are banned:
  if ($ban = user_ban($user[userid], "username")) $error = t("the username '$user[userid]' is banned") .": <I>$ban->reason</I>";
  if ($ban = user_ban($user[real_email], "e-mail address")) $error = t("the e-mail address '$user[real_email]' is banned") .": <I>$ban->reason</I>";

  // Verify whether username and e-mail address are unique:
  if (db_num_rows(db_query("SELECT userid FROM users WHERE LOWER(userid) = LOWER('$user[userid]')")) > 0) $error = t("the username '$user[userid]' is already taken");
  if (db_num_rows(db_query("SELECT real_email FROM users WHERE LOWER(real_email) = LOWER('$user[real_email]')")) > 0) $error = t("the e-mail address '$user[real_email]' is already in use by another account");

  return $error;
}

function account_email_submit($userid, $email) {
  global $theme;

  $result = db_query("SELECT id FROM users WHERE userid = '$userid' AND real_email = '$email'");

  if ($account = db_fetch_object($result)) {
    $passwd = account_password();
    $hash = substr(md5("$userid. ". time() .""), 0, 12);
    $status = 1;

    db_query("UPDATE users SET passwd = PASSWORD('$passwd'), hash = '$hash', status = '$status' WHERE userid = '$userid'");

    $link = variable_get(site_url, "http://drupal/") ."account.php?op=confirm&name=$userid&hash=$hash";
    $subject = strtr(t("Account details for %a"), array("%a" => variable_get(site_name, "drupal")));
    $message = strtr(t("%a,\n\n\nyou requested us to e-mail you a new password for your account at %b.  You will need to re-confirm your account or you will not be able to login.  To confirm your account updates visit the URL below:\n\n   %c\n\nOnce confirmed you can login using the following username and password:\n\n   username: %a\n   password: %d\n\n\n-- %b team"), array("%a" => $userid, "%b" => variable_get(site_name, "drupal"), "%c" => $link, "%d" => $passwd));

    watchdog("message", "new password: `$userid' &lt;$email&gt;");

    mail($email, $subject, $message, "From: noreply");

    $output = t("Your password and further instructions have been sent to your e-mail address.");
  }
  else {
    watchdog("warning", "new password: '$userid' and &lt;$email&gt; do not match");
    $output = t("Could not sent password: no match for the specified username and e-mail address.");
  }

  $theme->header();
  $theme->box(t("E-mail new password"), $output);
  $theme->footer();
}

function account_create_submit($userid, $email) {
  global $theme;

  $new[userid] = trim($userid);
  $new[real_email] = trim($email);

  if ($error = account_validate($new)) {
    $theme->header();
    $theme->box(t("Create user account"), account_create($error));
    $theme->footer();
  }
  else {
    $new[passwd] = account_password();
    $new[hash] = substr(md5("$new[userid]. ". time()), 0, 12);

    $user = user_save("", array("userid" => $new[userid], "real_email" => $new[real_email], "passwd" => $new[passwd], "status" => 1, "hash" => $new[hash]));

    $link = variable_get(site_url, "http://drupal/") ."account.php?op=confirm&name=$new[userid]&hash=$new[hash]";
    $subject = strtr(t("Account details for %a"), array("%a" => variable_get(site_name, "drupal")));
    $message = strtr(t("%a,\n\n\nsomeone signed up for a user account on %b and supplied this e-mail address as their contact.  If it wasn't you, don't get your panties in a knot and simply ignore this mail.  If this was you, you will have to confirm your account first or you will not be able to login.  To confirm your account visit the URL below:\n\n   %c\n\nOnce confirmed you can login using the following username and password:\n\n   username: %a\n   password: %d\n\n\n-- %b team\n"), array("%a" => $new[userid], "%b" => variable_get(site_name, "drupal"), "%c" => $link, "%d" => $new[passwd]));

    watchdog("message", "new account: `$new[userid]' &lt;$new[real_email]&gt;");

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
        watchdog("message", "$name: account confirmation successful");
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

function account_password($min_length=6) {
  mt_srand((double)microtime() * 1000000);
  $words = array("foo","bar","guy","neo","tux","moo","sun","asm","dot","god","axe","geek","nerd","fish","hack","star","mice","warp","moon","hero","cola","girl","fish","java","perl","boss","dark","sith","jedi","drop","mojo");
  while(strlen($password) < $min_length) $password .= $words[mt_rand(0, count($words))];
  return $password;
}

function account_track_comments() {
  global $theme, $user;

  $sresult = db_query("SELECT n.nid, n.title, COUNT(n.nid) AS count FROM comments c LEFT JOIN node n ON c.lid = n.nid WHERE c.author = '$user->id' GROUP BY n.nid DESC LIMIT 5");

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
  global $status, $theme, $user;

  $result = db_query("SELECT n.nid, n.type, n.title, n.timestamp, COUNT(c.cid) AS count FROM node n LEFT JOIN comments c ON c.lid = n.nid WHERE n.status = '$status[posted]' AND n.author = '$user->id' GROUP BY n.nid DESC LIMIT 25");

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
  global $nstatus, $status, $theme, $user;

  $period = 259200; // 3 days

  $theme->header();

  $sresult = db_query("SELECT n.title, n.nid, COUNT(c.lid) AS count FROM comments c LEFT JOIN node n ON c.lid = n.nid WHERE n.status = '$status[posted]' AND ". time() ." - n.timestamp < $period GROUP BY c.lid ORDER BY n.timestamp DESC LIMIT 10");
  while ($node = db_fetch_object($sresult)) {
    $output .= "<LI>". format_plural($node->count, "comment", "comments") ." ". t("attached to node") ." '<A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A>':</LI>";

    $cresult = db_query("SELECT c.subject, c.cid, c.pid, u.userid FROM comments c LEFT JOIN users u ON u.id = c.author WHERE c.lid = '$node->nid' ORDER BY c.timestamp DESC LIMIT $node->count");
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
    $output .= " <TR><TD><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></TD><TD ALIGN=\"center\">". format_username($node->userid) ."</TD><TD ALIGN=\"center\">$node->type</TD><TD>". $nstatus[$node->status] ."</TD></TR>";
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
    account_create_submit(check_input($userid), check_input($email));
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

?>