<?

include "includes/theme.inc";

function account_get_user($uname) {
  $result = db_query("SELECT * FROM users WHERE userid = '$uname'");
  return db_fetch_object($result);
}

function account_login() {
  $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
  $output .= " <TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"2\">\n";
  $output .= "  <TR><TH ALIGN=\"right\">Username:</TH><TD><INPUT NAME=\"userid\"></TD></TR>\n";
  $output .= "  <TR><TH ALIGN=\"right\">Password:</TH><TD><INPUT NAME=\"passwd\" TYPE=\"password\"></TD></TR>\n";
  $output .= "  <TR><TD ALIGN=\"right\" COLSPAN=\"2\"><INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"Login\"></TD></TR>\n";
  $output .= " </TABLE>\n";
  $output .= "</FORM>\n";

  return $output;
}

function account_email() {
  $output .= "<P>Lost your password?  Fill out your username and e-mail address, and your password will be mailed to you.</P>\n";
  $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
  $output .= " <TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"2\">\n";
  $output .= "  <TR><TH ALIGN=\"right\">Username:</TH><TD><INPUT NAME=\"userid\"></TD></TR>\n";
  $output .= "  <TR><TH ALIGN=\"right\">E-mail addres:</TH><TD><INPUT NAME=\"email\"></TD></TR>\n";
  $output .= "  <TR><TD ALIGN=\"right\" COLSPAN=\"2\"><INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"E-mail password\"></TD></TR>\n";
  $output .= " </TABLE>\n";
  $output .= "</FORM>\n";

  return $output;
}

function account_create($user = "", $error = "") {
  global $theme;

  if ($error) $output .= "<B><FONT COLOR=\"red\">Failed to register.</FONT>$error</B>\n";
  else $output .= "<P>Registering allows you to comment on stories, to moderate comments and pending stories, to maintain an online diary, to customize the look and feel of the site and generally helps you interact with the site more efficiently.</P><P>To create an account, simply fill out this form an click the `Create account' button below.  An e-mail will then be sent to you with instructions on how to validate your account.</P>\n";

  $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
  $output .= "<P>\n";
  $output .= " <B>Username:</B><BR>\n";
  $output .= " <INPUT NAME=\"userid\" VALUE=\"$userid\"><BR>\n";
  $output .= " <SMALL><I>Enter your desired username: only letters, numbers and common special characters are allowed.</I></SMALL><BR>\n";
  $output .= "</P>\n";
  $output .= "<P>\n";
  $output .= " <B>E-mail address:</B><BR>\n";
  $output .= " <INPUT NAME=\"email\" VALUE=\"$email\"><BR>\n";
  $output .= " <SMALL><I>You will be sent instructions on how to validate your account via this e-mail address - please make sure it is accurate.</I></SMALL><BR>\n";
  $output .= "</P>\n";
  $output .= "<P>\n";
  $output .= " <INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"Create account\">\n";
  $output .= "</P>\n";
  $output .= "</FORM>\n";

  return $output;
}

function account_session_start($userid, $passwd) {
  global $user;

  $user = new User($userid, $passwd);
  if ($user->id) {
    session_start();
    session_register("user");
    watchdog("message", "session opened for user `$user->userid'");
  }
  else {
    watchdog("warning", "failed login for user `$userid'");
  }
}

function account_session_close() {
  global $user;  
  watchdog("message", "session closed for user `$user->userid'");
  session_unset();
  session_destroy();
  unset($user);
}

function account_user_edit() {
  global $theme, $user;

  if ($user->id) {
    ### Generate output/content:
    $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
    $output .= "<B>Username:</B><BR>\n";
    $output .= "&nbsp; $user->userid<P>\n";
    $output .= "<I>Required, unique, and can not be changed.</I><P>\n";
    $output .= "<B>Real name:</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[name]\" MAXLENGTH=\"55\" SIZE=\"30\" VALUE=\"$user->name\"><BR>\n";
    $output .= "<I>Optional.</I><P>\n";
    $output .= "<B>Real e-mail address:</B><BR>\n";
    $output .= "&nbsp; $user->real_email<P>\n";
    $output .= "<I>Required, unique, can not be changed and is never displayed publicly: only needed in case you lose your password.</I><P>\n";
    $output .= "<B>Fake e-mail address:</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[fake_email]\" MAXLENGTH=\"55\" SIZE=\"30\" VALUE=\"$user->fake_email\"><BR>\n";
    $output .= "<I>Optional, and displayed publicly. You may spam proof your real e-mail address if you want.</I><P>\n";
    $output .= "<B>URL of homepage:</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[url]\" MAXLENGTH=\"55\" SIZE=\"30\" VALUE=\"$user->url\"><BR>\n";
    $output .= "<I>Optional, but make sure you enter fully qualified URLs only. That is, remember to include \"http://\".</I><P>\n";
    $output .= "<B>Bio:</B> (255 char. limit)<BR>\n";
    $output .= "<TEXTAREA NAME=\"edit[bio]\" COLS=\"35\" ROWS=\"5\" WRAP=\"virtual\">$user->bio</TEXTAREA><BR>\n";
    $output .= "<I>Optional. This biographical information is publicly displayed on your user page.</I><P>\n";
    $output .= "<B>Signature:</B> (255 char. limit)<BR>\n";
    $output .= "<TEXTAREA NAME=\"edit[signature]\" COLS=\"35\" ROWS=\"5\" WRAP=\"virtual\">$user->signature</TEXTAREA><BR>\n";
    $output .= "<I>Optional. This information will be publicly displayed at the end of your comments. </I><P>\n";
    $output .= "<B>Password:</B><BR>\n";
    $output .= "<INPUT TYPE=\"password\" NAME=\"edit[pass1]\" SIZE=\"10\" MAXLENGTH=\"20\"> <INPUT TYPE=\"password\" NAME=\"edit[pass2]\" SIZE=\"10\" MAXLENGTH=\"20\"><BR>\n";
    $output .= "<I>Enter your new password twice if you want to change your current password or leave it blank if you are happy with your current password.</I><P>\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Save user information\"><BR>\n";
    $output .= "</FORM>\n";

    ### Display output/content:
    $theme->header();
    $theme->box("Edit your information", $output);
    $theme->footer();
  }
  else {
    $theme->header();
    $theme->box("Login", account_login()); 
    $theme->box("E-mail password", account_email());
    $theme->box("Create new account", account_create());
    $theme->footer();
  }
}

function account_user_save($edit) {
  global $user;
  if ($user->id) {
    $data[name] = $edit[name];
    $data[fake_email] = $edit[fake_email];
    $data[url] = $edit[url];
    $data[bio] = $edit[bio];
    $data[signature] = $edit[signature];

    if ($edit[pass1] && $edit[pass1] == $edit[pass2]) $data[passwd] = $edit[pass1];

    user_save($data, $user->id);
  }
}

function account_page_edit() {
  global $theme, $themes, $user;

  if ($user->id) {
    $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
    $output .= "<B>Theme:</B><BR>\n";

    foreach ($themes as $key=>$value) { 
      $options1 .= " <OPTION VALUE=\"$key\"". (($user->theme == $key) ? " SELECTED" : "") .">$key - $value[1]</OPTION>\n";
    }

    $output .= "<SELECT NAME=\"edit[theme]\">\n$options1</SELECT><BR>\n";
    $output .= "<I>Selecting a different theme will change the look and feel of the site.</I><P>\n";
    $output .= "<B>Timezone:</B><BR>\n";

    $date = time() - date("Z");
    for ($zone = -43200; $zone <= 46800; $zone += 3600) {
      $options2 .= " <OPTION VALUE=\"$zone\"". (($user->timezone == $zone) ? " SELECTED" : "") .">". date("l, F dS, Y - h:i A", $date + $zone) ." (GMT ". $zone / 3600 .")</OPTION>\n";
    }

    $output .= "<SELECT NAME=\"edit[timezone]\">\n$options2</SELECT><BR>\n";
    $output .= "<I>Select what time you currently have and your timezone settings will be set appropriate.</I><P>\n";
    $output .= "<B>Maximum number of stories:</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[stories]\" MAXLENGTH=\"3\" SIZE=\"3\" VALUE=\"$user->stories\"><P>\n";
    $output .= "<I>The maximum number of stories that will be displayed on the main page.</I><P>\n";
    $options  = "<OPTION VALUE=\"nested\"". ($user->mode == "nested" ? " SELECTED" : "") .">Nested</OPTION>";
    $options .= "<OPTION VALUE=\"flat\"". ($user->mode == "flat" ? " SELECTED" : "") .">Flat</OPTION>";
    $options .= "<OPTION VALUE=\"threaded\"". ($user->mode == "threaded" ? " SELECTED" : "") .">Threaded</OPTION>";
    $output .= "<B>Comment display mode:</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[mode]\">$options</SELECT><P>\n";
    $options  = "<OPTION VALUE=\"0\"". ($user->sort == 0 ? " SELECTED" : "") .">Oldest first</OPTION>";
    $options .= "<OPTION VALUE=\"1\"". ($user->sort == 1 ? " SELECTED" : "") .">Newest first</OPTION>";
    $options .= "<OPTION VALUE=\"2\"". ($user->sort == 2 ? " SELECTED" : "") .">Highest scoring first</OPTION>";
    $output .= "<B>Comment sort order:</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[sort]\">$options</SELECT><P>\n";
    $options  = "<OPTION VALUE=\"-1\"". ($user->threshold == -1 ? " SELECTED" : "") .">-1: Display uncut and raw comments.</OPTION>";
    $options .= "<OPTION VALUE=\"0\"". ($user->threshold == 0 ? " SELECTED" : "") .">0: Display almost all comments.</OPTION>";
    $options .= "<OPTION VALUE=\"1\"". ($user->threshold == 1 ? " SELECTED" : "") .">1: Display almost no anonymous comments.</OPTION>";
    $options .= "<OPTION VALUE=\"2\"". ($user->threshold == 2 ? " SELECTED" : "") .">2: Display comments with score +2 only.</OPTION>";
    $options .= "<OPTION VALUE=\"3\"". ($user->threshold == 3 ? " SELECTED" : "") .">3: Display comments with score +3 only.</OPTION>";
    $options .= "<OPTION VALUE=\"4\"". ($user->threshold == 4 ? " SELECTED" : "") .">4: Display comments with score +4 only.</OPTION>";
    $options .= "<OPTION VALUE=\"5\"". ($user->threshold == 5 ? " SELECTED" : "") .">5: Display comments with score +5 only.</OPTION>";
    $output .= "<B>Comment threshold:</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[threshold]\">$options</SELECT><BR>\n";
    $output .= "<I>Comments that scored less than this setting will be ignored. Anonymous comments start at 0, comments of people logged on start at 1 and moderators can add and subtract points.</I><P>\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Save page settings\"><BR>\n";
    $output .= "</FORM>\n";

    $theme->header();
    $theme->box("Edit your settings", $output);
    $theme->footer();
  }
  else {
    $theme->header();
    $theme->box("Login", account_login()); 
    $theme->box("E-mail password", account_email());
    $theme->box("E-mail password", account_create());
    $theme->footer();
  }
}

function account_page_save($edit) {
  global $user;
  if ($user->id) {
    $data[theme] = $edit[theme];
    $data[timezone] = $edit[timezone];
    $data[stories] = $edit[stories];
    $data[mode] = $edit[mode];
    $data[sort] = $edit[sort];
    $data[threshold] = $edit[threshold];
    user_save($data, $user->id);
  }
}

function account_user($uname) {
  global $user, $theme;

  if ($user->id && $user->userid == $uname) {
    $output .= "<TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"2\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>User ID:</B></TD><TD>$user->userid</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Name:</B></TD><TD>". format_data($user->name) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>E-mail:</B></TD><TD>". format_email($user->fake_email) ."</A></TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>URL:</B></TD><TD>". format_url($user->url) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Bio:</B></TD><TD>". format_data($user->bio) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Signature:</B></TD><TD>". format_data($user->signature) ."</TD></TR>\n";
    $output .= "</TABLE>\n";

    ### Display account information:
    $theme->header();
    $theme->box("View your information", $output);
    $theme->footer();
  }
  elseif ($uname && $account = account_get_user($uname)) {
    $box1 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Username:</B></TD><TD>$account->userid</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>E-mail:</B></TD><TD>". format_email($account->fake_email) ."</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>URL:</B></TD><TD>". format_url($account->url) ."</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Bio:</B></TD><TD>". format_data($account->bio) ."</TD></TR>\n";
    $box1 .= "</TABLE>\n";

    $result = db_query("SELECT c.cid, c.pid, c.sid, c.subject, c.timestamp, s.subject AS story FROM comments c LEFT JOIN users u ON u.id = c.author LEFT JOIN stories s ON s.id = c.sid WHERE u.userid = '$uname' AND s.status = 2 AND s.timestamp > ". (time() - 1209600) ." ORDER BY cid DESC LIMIT 10");
    while ($comment = db_fetch_object($result)) {
      $box2 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
      $box2 .= " <TR><TD ALIGN=\"right\"><B>Comment:</B></TD><TD><A HREF=\"discussion.php?id=$comment->sid&cid=$comment->cid&pid=$comment->pid#$comment->cid\">". check_output($comment->subject) ."</A></TD></TR>\n";
      $box2 .= " <TR><TD ALIGN=\"right\"><B>Date:</B></TD><TD>". format_date($comment->timestamp) ."</TD></TR>\n";
      $box2 .= " <TR><TD ALIGN=\"right\"><B>Story:</B></TD><TD><A HREF=\"discussion.php?id=$comment->sid\">". check_output($comment->story) ."</A></TD></TR>\n";
      $box2 .= "</TABLE>\n";
      $box2 .= "<P>\n";
      $comments++;
    }

    $result = db_query("SELECT d.* FROM diaries d LEFT JOIN users u ON u.id = d.author WHERE u.userid = '$uname' AND d.timestamp > ". (time() - 1209600) ."  ORDER BY id DESC LIMIT 2");
    while ($diary = db_fetch_object($result)) {
      $box3 .= "<DL><DT><B>". date("l, F jS", $diary->timestamp) .":</B></DT><DD><P>". check_output($diary->text) ."</P><P>[ <A HREF=\"module.php?mod=diary&op=view&name=$uname\">more</A> ]</P></DD></DL>\n";
      $diaries++;
    }
    
    ### Display account information:
    $theme->header();
    if ($box1) $theme->box("User information for $uname", $box1);
    if ($box2) $theme->box("$uname has posted ". format_plural($comments, "comment", "comments") ." recently", $box2);
    if ($box3) $theme->box("$uname has posted ". format_plural($diaries, "diary entry", "diary entries") ." recently", $box3);
    $theme->footer();
  }
  else { 
    ### Display login form:
    $theme->header();
    $theme->box("Login", account_login()); 
    $theme->box("E-mail password", account_email());
    $theme->box("Create new account", account_create());
    $theme->footer();
  }
}

function account_validate($user) {
  include "includes/ban.inc";

  ### Verify username and e-mail address:
  if (empty($user[real_email]) || (!eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $user[real_email]))) $error .= "<LI>the specified e-mail address is not valid.</LI>\n";
  if (empty($user[userid]) || (ereg("[^a-zA-Z0-9_-]", $user[userid]))) $error .= "<LI>the specified username is not valid.</LI>\n";
  if (strlen($user[userid]) > 15) $error .= "<LI>the specified username is too long: it must be less than 15 characters.</LI>\n";

  ### Check to see whether the username or e-mail address are banned:
  if ($ban = ban_match($user[userid], $type2index[usernames])) $error .= "<LI>the specified username is banned  for the following reason: <I>$ban->reason</I>.</LI>\n";
  if ($ban = ban_match($user[real_email], $type2index[addresses])) $error .= "<LI>the specified e-mail address is banned for the following reason: <I>$ban->reason</I>.</LI>\n";

  ### Verify whether username and e-mail address are unique:
  if (db_num_rows(db_query("SELECT userid FROM users WHERE LOWER(userid) = LOWER('$user[userid]')")) > 0) $error .= "<LI>the specified username is already taken.</LI>\n";
  if (db_num_rows(db_query("SELECT real_email FROM users WHERE LOWER(real_email)=LOWER('$user[real_email]')")) > 0) $error .= "<LI>the specified e-mail address is already registered.</LI>\n";

  return $error;
}

function account_email_submit($userid, $email) {
  global $theme, $site_name, $site_url; 

  $result = db_query("SELECT id FROM users WHERE userid = '". check_output($userid) ."' AND real_email = '". check_output($email) ."'");
  
  if ($account = db_fetch_object($result)) {
    $passwd = account_password();
    $status = 1;
    $hash = substr(md5("$userid. ". time() .""), 0, 12);

    db_query("UPDATE users SET passwd = PASSWORD('$passwd'), hash = '$hash', status = '$status' WHERE userid = '$userid'");

    $link = $site_url ."account.php?op=confirm&name=$userid&hash=$hash";
    $message = "$userid,\n\n\nyou requested us to e-mail you a new password for your $site_name account.  Note that you will need to re-activate your account before you can login.  You can do so simply by visiting the URL below:\n\n    $link\n\nVisiting this URL will automatically re-activate your account.  Once activated you can login using the following information:\n\n    username: $userid\n    password: $passwd\n\n\n-- $site_name crew\n";

    watchdog("message", "new password: `$userid' &lt;$email&gt;");

    mail($email, "Account details for $site_name", $message, "From: noreply");

    $output = "Your password and further instructions have been sent to your e-mail address.";
  }
  else {
    watchdog("warning", "new password: '$userid' and &lt;$email&gt; do not match");
    $output = "Could not sent password: no match for the specified username and e-mail address.";
  }

  $theme->header();
  $theme->box("E-mail password", $output);
  $theme->footer();
}

function account_create_submit($userid, $email) {
  global $theme, $site_name, $site_url;
  
  $new[userid] = $userid;
  $new[real_email] = $email;
  
  if ($rval = account_validate($new)) { 
    $theme->header();
    $theme->box("Create new account", account_create($new, $rval));
    $theme->footer();
  }
  else {
    $new[passwd] = account_password();
    $new[status] = 1;
    $new[hash] = substr(md5("$new[userid]. ". time() .""), 0, 12);

    user_save($new);

    $link = $site_url ."account.php?op=confirm&name=$new[userid]&hash=$new[hash]";
    $message = "$new[userid],\n\n\nsomeone signed up for a user account on $site_name and supplied this email address as their contact.  If it wasn't you, don't get your panties in a knot and simply ignore this mail.\n\nIf this was you, you have to activate your account first before you can login.  You can do so simply by visiting the URL below:\n\n    $link\n\nVisiting this URL will automatically activate your account.  Once activated you can login using the following information:\n\n    username: $new[userid]\n    password: $new[passwd]\n\n\n-- $site_name crew\n";

    watchdog("message", "new account: `$new[userid]' &lt;$new[real_email]&gt;");

    mail($new[real_email], "Account details for $site_name", $message, "From: noreply");

    $theme->header();
    $theme->box("Create new account", "Congratulations!  Your member account has been sucessfully created and further instructions on how to activate your account have been sent to your e-mail address.");
    $theme->footer();
  }
}

function account_create_confirm($name, $hash) {
  global $theme;

  $result = db_query("SELECT userid, hash, status FROM users WHERE userid = '$name'");

  if ($account = db_fetch_object($result)) {
    if ($account->status == 1) {
      if ($account->hash == $hash) {
        db_query("UPDATE users SET status = 2, hash = '' WHERE userid = '$name'");
        $output .= "Your account has been sucessfully confirmed.  You can click <A HREF=\"account.php?op=login\">here</A> to login.\n";
        watchdog("message", "$name: account confirmation sucessful");
      }
      else {
        $output .= "Confirmation failed: invalid confirmation hash.\n";
        watchdog("warning", "$name: invalid confirmation hash");
      }
    }
    else {
      $output .= "Confirmation failed: your account has already been confirmed.  You can click <A HREF=\"account.php?op=login\">here</A> to login.\n";
      watchdog("warning", "$name: attempt to re-confirm account");
    }
  }
  else {
    $output .= "Confirmation failed: no such account found.<BR>";
    watchdog("warning", "$name: attempt to confirm non-existing account");
  }

  $theme->header();
  $theme->box("Account confirmation", $output);
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

  $msg = "<P>This page might be helpful in case you want to keep track of your recent comments in any of the current discussions.  You are presented an overview of your comments in each of the stories you participated in along with the number of replies each comment got.\n<P>\n"; 

  $sresult = db_query("SELECT s.id, s.subject, COUNT(s.id) as count FROM comments c LEFT JOIN stories s ON c.sid = s.id WHERE c.author = $user->id GROUP BY s.id DESC LIMIT 5");
  
  while ($story = db_fetch_object($sresult)) {
    $output .= "<LI>". format_plural($story->count, comment, comments) ." attached to story `<A HREF=\"discussion.php?id=$story->id\">". check_output($story->subject) ."</A>`:</LI>\n";
    $output .= " <UL>\n";
   
    $cresult = db_query("SELECT * FROM comments WHERE author = $user->id AND sid = $story->id");
    while ($comment = db_fetch_object($cresult)) {
      $output .= "  <LI><A HREF=\"discussion.php?id=$story->id&cid=$comment->cid&pid=$comment->pid#$comment->cid\">". check_output($comment->subject) ."</A> - replies: ". discussion_num_replies($comment->cid) ." - score: ". discussion_score($comment) ."</LI>\n";
    }
    $output .= " </UL>\n";
  }

  $output = ($output) ? "$msg $output" : "$info <CENTER><B>You have not posted any comments recently.</B></CENTER>\n";

  $theme->header();
  $theme->box("Track your comments", $output);
  $theme->footer();
}

function account_track_stories() {
  global $theme, $user;

  $msg = "<P>This page might be helpful in case you want to keep track of the stories you contributed.  You are presented an overview of your stories along with the number of replies each story got.\n<P>\n"; 

  $result = db_query("SELECT s.id, s.subject, s.timestamp, s.category, COUNT(s.id) as count FROM comments c LEFT JOIN stories s ON c.sid = s.id WHERE s.status = 2 AND s.author = $user->id GROUP BY s.id DESC");
  
  while ($story = db_fetch_object($result)) {
    $output .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Subject:</B></TD><TD><A HREF=\"discussion.php?id=$story->id\">". check_output($story->subject) ."</A> (". format_plural($story->count, "comment", "comments") .")</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Category:</B></TD><TD><A HREF=\"search.php?category=". urlencode($story->category) ."\">". check_output($story->category) ."</A></TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Date:</B></TD><TD>". format_date($story->timestamp) ."</TD></TR>\n";
    $output .= "</TABLE>\n";
    $output .= "<P>\n";
  }

  $output = ($output) ? "$msg $output" : "$info <CENTER><B>You have not posted any stories.</B></CENTER>\n";

  $theme->header();
  $theme->box("Track your stories", $output);
  $theme->footer();
}

function account_track_site() {
  global $theme, $user, $site_name;

  $result1 = db_query("SELECT c.cid, c.pid, c.sid, c.subject, u.userid, s.subject AS story FROM comments c LEFT JOIN users u ON u.id = c.author LEFT JOIN stories s ON s.id = c.sid WHERE s.status = 2 ORDER BY cid DESC LIMIT 10");

  while ($comment = db_fetch_object($result1)) {
    $box1 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Comment:</B></TD><TD><A HREF=\"discussion.php?id=$comment->sid&cid=$comment->cid&pid=$comment->pid#$comment->cid\">". check_output($comment->subject) ."</A></TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Author:</B></TD><TD>". format_username($comment->userid) ."</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Story:</B></TD><TD><A HREF=\"discussion.php?id=$comment->sid\">". check_output($comment->story) ."</A></TD></TR>\n";
    $box1 .= "</TABLE>\n";
    $box1 .= "<P>\n";
  }

  $users_total = db_result(db_query("SELECT COUNT(id) FROM users"));
  
  $stories_posted  = db_result(db_query("SELECT COUNT(id) FROM stories WHERE status = 2"));
  $stories_queued  = db_result(db_query("SELECT COUNT(id) FROM stories WHERE status = 1"));
  $stories_dumped = db_result(db_query("SELECT COUNT(id) FROM stories WHERE status = 0"));

  $result = db_query("SELECT u.userid, COUNT(s.author) AS count FROM stories s LEFT JOIN users u ON s.author = u.id GROUP BY s.author ORDER BY count DESC LIMIT 10");
  while ($poster = db_fetch_object($result)) $stories_posters .= format_username($poster->userid) .", ";

  $comments_total = db_result(db_query("SELECT COUNT(cid) FROM comments")); 
  $comments_score = db_result(db_query("SELECT TRUNCATE(AVG(score / votes), 2) FROM comments WHERE votes > 0"));

  $result = db_query("SELECT u.userid, COUNT(c.author) AS count FROM comments c LEFT JOIN users u ON c.author = u.id GROUP BY c.author ORDER BY count DESC LIMIT 10");
  while ($poster = db_fetch_object($result)) $comments_posters .= format_username($poster->userid) .", ";

  $diaries_total = db_result(db_query("SELECT COUNT(id) FROM diaries"));

  $result = db_query("SELECT u.userid, COUNT(d.author) AS count FROM diaries d LEFT JOIN users u ON d.author = u.id GROUP BY d.author ORDER BY count DESC LIMIT 10");
  while ($poster = db_fetch_object($result)) $diaries_posters .= format_username($poster->userid) .", ";
  
  $box2 .= "<TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"1\">\n";
  $box2 .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Users:</B></TD><TD>$users_total users</TD></TR>\n";
  $box2 .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Stories:</B></TD><TD>$stories_posted posted, $stories_queued queued, $stories_dumped dumped<BR><I>[most frequent posters: $stories_posters ...]</I></TD></TR>\n";
  $box2 .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Comments:</B></TD><TD>$comments_total comments with an average score of $comments_score<BR><I>[most frequent posters: $comments_posters ...]</I></TD></TR>\n";
  $box2 .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Diaries:</B></TD><TD>$diaries_total diary entries<BR><I>[most frequent posters: $diaries_posters ...]</I></TD></TR>\n";
  $box2 .= "</TABLE>\n";

  $theme->header();
  $theme->box("Recent comments", $box1);
  $theme->box("Site statistics", $box2);
  $theme->footer();
}

### Security check:
if (strstr($name, " ") || strstr($hash, " ")) {
  watchdog("error", "account: attempt to provide malicious input through URI");
  exit();
}

switch ($op) {
  case "Login":
    account_session_start($userid, $passwd);
    header("Location: account.php?op=info");
    break;
  case "E-mail password":
    account_email_submit($userid, $email);
    break;
  case "Create account":
    account_create_submit($userid, $email);
    break;
  case "confirm":
    account_create_confirm($name, $hash);
    break;
  case "Save user information":
    account_user_save($edit);
    account_user($user->userid);
    break;
  case "Save page settings":
    account_page_save($edit);
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
      case "diary":
        header("Location: module.php?mod=diary&op=view&name=$user->userid");
        break;        
      default:
        account_user($name);
    }
    break;
  case "track":
    switch ($topic) {
      case "site":
        account_track_site();
        break;
      case "stories":
        account_track_stories();
        break;
      default:
        account_track_comments();
    }
    break;
  case "edit":
    switch ($topic) {
      case "user":
        account_user_edit();
        break;
      case "page":
        account_page_edit();
        break;
      default:
        header("Location: module.php?mod=diary&op=add&name=$user->userid");
    }
    break;
  default: 
    account_user($user->userid);
}

?>
