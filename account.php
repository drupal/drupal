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
  $output .= "You don't have an account yet?  <A HREF=\"account.php?op=register\">Register</A> as new user.\n";

  return $output;
}

function account_session_start($userid, $passwd) {
  global $user;

  $user = new User($userid, $passwd);

  if ($user->id) {
    session_start();
    session_register("user");
    watchdog(1, "session opened for user `$user->userid'");
  }
  else {
    watchdog(2, "failed login for user `$userid'");
  }
}

function account_session_close() {
  global $user;  
  watchdog(1, "session closed for user `$user->userid'");
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
    $output .= "<B>Singature:</B> (255 char. limit)<BR>\n";
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
    ### Generate output/content:
    $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
    $output .= "<B>Theme:</B><BR>\n";

    ### Loop (dynamically) through all available themes:
    foreach ($themes as $key=>$value) { 
      $options .= "<OPTION VALUE=\"$key\"". (($user->theme == $key) ? " SELECTED" : "") .">$key - $value[1]</OPTION>";
    }

    $output .= "<SELECT NAME=\"edit[theme]\">$options</SELECT><BR>\n";
    $output .= "<I>Selecting a different theme will change the look and feel of the site.</I><P>\n";
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

    ### Display output/content:
    $theme->header();
    $theme->box("Customize your page", $output);
    $theme->footer();
  }
  else {
    $theme->header();
    $theme->box("Login", account_login()); 
    $theme->footer();
  }
}

function account_page_save($edit) {
  global $user;
  if ($user->id) {
    $data[theme] = $edit[theme];
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
    $output .= "<P>Welcome $user->userid! This is <B>your</B> user info page. There are many more, but this one is yours. You are probably most interested in editing something, but if you need to kill some time, this place is as good as any other place.</P>\n";
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
    $theme->box("Your user information", $output);
    $theme->footer();
  }
  elseif ($uname && $account = account_get_user($uname)) {
    $box1 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Username:</B></TD><TD>$account->userid</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>E-mail:</B></TD><TD>". format_email($account->fake_email) ."</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>URL:</B></TD><TD>". format_url($account->url) ."</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Bio:</B></TD><TD>". format_data($account->bio) ."</TD></TR>\n";
    $box1 .= "</TABLE>\n";

    $result = db_query("SELECT c.cid, c.pid, c.sid, c.subject, c.timestamp, s.subject AS story FROM comments c LEFT JOIN users u ON u.id = c.author LEFT JOIN stories s ON s.id = c.sid WHERE u.userid = '$uname' AND c.timestamp > ". (time() - 1209600) ." ORDER BY cid DESC LIMIT 10");
    while ($comment = db_fetch_object($result)) {
      $box2 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
      $box2 .= " <TR><TD ALIGN=\"right\"><B>Comment:</B></TD><TD><A HREF=\"discussion.php?id=$comment->sid&cid=$comment->cid&pid=$comment->pid\">$comment->subject</A></TD></TR>\n";
      $box2 .= " <TR><TD ALIGN=\"right\"><B>Date:</B></TD><TD>". format_date($comment->timestamp) ."</TD></TR>\n";
      $box2 .= " <TR><TD ALIGN=\"right\"><B>Story:</B></TD><TD><A HREF=\"discussion.php?id=$comment->sid\">$comment->story</A></TD></TR>\n";
      $box2 .= "</TABLE>\n";
      $box2 .= "<BR><BR>\n";
      $comments++;
    }

    $result = db_query("SELECT d.* FROM diaries d LEFT JOIN users u ON u.id = d.author WHERE u.userid = '$uname' AND d.timestamp > ". (time() - 1209600) ."  ORDER BY id DESC LIMIT 2");
    while ($diary = db_fetch_object($result)) {
      $box3 .= "<DL><DT><B>". date("l, F jS", $diary->timestamp) .":</B></DT><DD><P>". check_output($diary->text) ."</P><P>[ <A HREF=\"diary.php?op=view&name=$uname\">more</A> ]</P></DD></DL>\n";
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
    $theme->footer();
  }
}

function account_validate($user) {
  include "includes/ban.inc";

  ### Verify username and e-mail address:
  $user[userid] = trim($user[userid]);
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

function account_register_enter($user = "", $error = "") {
  global $theme;

  if ($error) $output .= "<B><FONT COLOR=\"red\">Failed to register.</FONT>$error</B>\n";
  else $output .= "<P>Registering allows you to comment on stories, to moderate comments and pending stories, to maintain an online diary, to customize the look and feel of the site and generally helps you interact with the site more efficiently.</P><P>To create an account, simply fill out this form an click the `Register' button below.  An e-mail will then be sent to you with instructions on how to validate your account.</P>\n";

  $output .= "<FORM ACTION=\"account.php\" METHOD=\"post\">\n";
  $output .= "<P>\n";
  $output .= " <B>Username:</B><BR>\n";
  $output .= " <INPUT NAME=\"new[userid]\" VALUE=\"$new[userid]\"><BR>\n";
  $output .= " <SMALL><I>Enter your desired username: only letters, numbers and common special characters are allowed.</I></SMALL><BR>\n";
  $output .= "</P>\n";
  $output .= "<P>\n";
  $output .= " <B>E-mail address:</B><BR>\n";
  $output .= " <INPUT NAME=\"new[real_email]\" VALUE=\"$new[real_email]\"><BR>\n";
  $output .= " <SMALL><I>You will be sent instructions on how to validate your account via this e-mail address - please make sure it is accurate.</I></SMALL><BR>\n";
  $output .= "</P>\n";
  $output .= "<P>\n";
  $output .= " <INPUT NAME=\"op\" TYPE=\"submit\" VALUE=\"Register\">\n";
  $output .= "</P>\n";
  $output .= "</FORM>\n";

  $theme->header();
  $theme->box("Register as new user", $output);
  $theme->footer();
}

function account_register_submit($new) {
  global $theme, $mail, $sitename;

  if ($rval = account_validate($new)) { 
    account_register_enter($new, "$rval");
  }
  else {
    $new[passwd] = account_password();
    $new[status] = 1;
    $new[hash] = substr(md5("$new[userid]. ". time() .""), 0, 12);

    user_save($new);

    $link = "http://". getenv("HOSTNAME") ."/account.php?op=confirm&name=$new[userid]&hash=$new[hash]";
    $message = "$new[userid],\n\n\nsomeone signed up for a user account on $sitename and supplied this email address as their contact.  If it wasn't you, don't get your panties in a knot and simply ignore this mail.\n\nIf this was you, you have to activate your account first before you can login.  You can do so simply by visiting the URL below:\n\n    $link\n\nVisiting this URL will automatically activate your account.  Once activated you can login using the following information:\n\n    username: $new[userid]\n    password: $new[passwd]\n\n\n-- $sitename crew\n";

    mail($new[real_email], "Account details for $sitename", $message, "From: noreply@$sitename");

    watchdog(1, "new user `$new[userid]' &lt;$new[real_email]&gt;");

    $theme->header();
    $theme->box("Account details", "Congratulations!  Your member account has been sucessfully created and further instructions on how to activate your account have been sent to your e-mail address.");
    $theme->footer();
  }
}

function account_register_confirm($name, $hash) {
  global $theme;

  $result = db_query("SELECT userid, hash, status FROM users WHERE userid = '$name'");

  if ($account = db_fetch_object($result)) {
    if ($account->status == 1) {
      if ($account->hash == $hash) {
        db_query("UPDATE users SET status = 2, hash = '' WHERE userid = '$name'");
        $output .= "Your account has been sucessfully confirmed.  You can click <A HREF=\"account.php?op=login\">here</A> to login.\n";
        watchdog(1, "$name: account confirmation sucessful");
      }
      else {
        $output .= "Confirmation failed: invalid confirmation hash.\n";
        watchdog(3, "$name: invalid confirmation hash");
      }
    }
    else {
      $output .= "Confirmation failed: your account has already been confirmed.  You can click <A HREF=\"account.php?op=login\">here</A> to login.\n";
      watchdog(3, "$name: attempt to re-confirm account");
    }
  }
  else {
    $output .= "Confirmation failed: no such account found.<BR>";
    watchdog(3, "$name: attempt to confirm non-existing account");
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

function account_comments() {
  global $theme, $user;

  $info = "<P>This page might be helpful in case you want to keep track of your most recent comments in any of the discussions.  You are given an overview of your comments in each of the stories you participates in along with the number of replies each comment got.\n<P>\n"; 

  $sresult = db_query("SELECT s.id, s.subject, COUNT(s.id) as count FROM comments c LEFT JOIN stories s ON c.sid = s.id WHERE c.author = $user->id GROUP BY s.id DESC LIMIT 5");
  
  while ($story = db_fetch_object($sresult)) {
    $output .= "<LI>". format_plural($story->count, comment, comments) ." in story `<A HREF=\"discussion.php?id=$story->id\">$story->subject</A>`:</LI>\n";
    $output .= " <UL>\n";
   
    $cresult = db_query("SELECT * FROM comments WHERE author = $user->id AND sid = $story->id");
    while ($comment = db_fetch_object($cresult)) {
      $output .= "  <LI><A HREF=\"discussion.php?id=$story->id&cid=$comment->cid&pid=$comment->pid\">$comment->subject</A> (<B>". format_plural(discussion_num_replies($comment->cid), "reply", "replies") ."</B>)</LI>\n";
    }
    $output .= " </UL>\n";
  }

  $output = ($output) ? "$info $output" : "$info <CENTER><B>You have not posted any comments recently.</B></CENTER>\n";

  $theme->header();
  $theme->box("Track your comments", $output);
  $theme->footer();
}

switch ($op) {
  case "Login":
    account_session_start($userid, $passwd);
    header("Location: account.php?op=info");
    break;
  case "register":
    account_register_enter();
    break;
  case "confirm":
    account_register_confirm($name, $hash);
    break;
  case "Register":
    account_register_submit($new);
    break;
  case "view":
    account_user($name);
    break;
  case "info":
    account_user($user->userid);
    break;
  case "discussion":
    account_comments();
    break;
  case "logout":
    account_session_close();
    header("Location: account.php");
    break;
  case "Register":
    account_register_submit($new);
    break;
  case "user":
    account_user_edit();
    break;
  case "page":
    account_page_edit();
    break;
  case "Save user information":
    account_user_save($edit);
    account_user($user->userid);
    break;
  case "Save page settings":
    account_page_save($edit);
    header("Location: account.php?op=info");
    break;
  default: 
    account_user($user->userid);
}

?>