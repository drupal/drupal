<?

include "function.inc";
include "config.inc";
include "theme.inc";

function account_getUser($uname) {
  $result = db_query("SELECT * FROM users WHERE userid = '$uname'");
  return db_fetch_object($result);
}

function showLogin($userid = "") {
  $output .= "<FORM ACTION=\"account.php\" METHOD=post>\n";
  $output .= " <TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>\n";
  $output .= "  <TR><TH>User ID:</TH><TD><INPUT NAME=userid VALUE=\"$userid\"></TD></TR>\n";
  $output .= "  <TR><TH>Password:</TH><TD><INPUT NAME=passwd TYPE=password></TD></TR>\n";
  $output .= "  <TR><TD ALIGN=center><INPUT NAME=op TYPE=submit VALUE=\"Login\"></TD></TR>\n";
  $output .= "  <TR><TD ALIGN=center><A HREF=\"account.php?op=new\">Register</A> as new user.</A></TD></TR>\n";
  $output .= "  <TR><TD COLSPAN=2>$user->ublock</TD></TR>\n";
  $output .= " </TABLE>\n";
  $output .= "</FORM>\n";
  return $output;
}

function showAccess() {
  global $user, $access;
  foreach ($access as $key=>$value) if ($user->access & $value) $result .= "$key<BR>";
  return $result;
}

function showUser($uname) {
  global $user, $theme;
  
  if ($user && $uname && $user->userid == $uname) {
    $output .= "<P>Welcome $user->userid! This is <B>your</B> user info page. There are many more, but this one is yours. You are probably most interested in editing something, but if you need to kill some time, this place is as good as any other place.</P>\n";
    $output .= "<TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"2\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>User ID:</B></TD><TD>$user->userid</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Name:</B></TD><TD>". format_data($user->name) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>E-mail:</B></TD><TD>". format_email_address($user->femail) ."</A></TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>URL:</B></TD><TD>". format_url($user->url) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Bio:</B></TD><TD>". format_data($user->bio) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Signature:</B></TD><TD>". format_data($user->signature) ."</TD></TR>\n";
    $output .= "</TABLE>\n";

    ### Display account information:
    $theme->header();
    $theme->box("Your user information", $output);
    $theme->footer();
  }
  elseif ($uname && $account = account_getUser($uname)) {
    $box1 .= "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"1\">\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>Username:</B></TD><TD>$account->userid</TD></TR>\n";
    $box1 .= " <TR><TD ALIGN=\"right\"><B>E-mail:</B></TD><TD>". format_email_address($account->femail) ."</TD></TR>\n";
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
    $theme->box("Login", showLogin($userid)); 
    $theme->footer();
  }
}

function newUser($user = "", $error="") {
  global $theme;

  $output .= "<FORM ACTION=\"account.php\" METHOD=post>\n";
  $output .= "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>\n";
  if (!empty($error)) $output .= "<TR><TD COLSPAN=2>$error</TD></TR>\n";
  $output .= "<TR><TH>Name:</TH><TD><INPUT NAME=\"new[name]\" VALUE=\"$new[name]\"></TD></TR>\n";
  $output .= "<TR><TH>User ID:</TR><TD><INPUT NAME=\"new[userid]\" VALUE=\"$new[userid]\"></TD></TR>\n";
  $output .= "<TR><TH>E-mail:</TH><TD><INPUT NAME=\"new[email]\" VALUE=\"$new[email]\"></TD></TR>\n";
  $output .= "<TR><TD ALIGN=right COLSPAN=2><INPUT NAME=op TYPE=submit VALUE=\"Register\"></TD></TR>\n";
  $output .= "</TABLE>\n";
  $output .= "</FORM>\n";

  $theme->header();
  $theme->box("Register as new user", $output);
  $theme->footer();
}

function validateUser($user) {
  include "ban.inc";

  ### Verify username and e-mail address:
  $user[userid] = trim($user[userid]);
  if (empty($user[email]) || (!eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $user[email]))) $rval = "the specified e-mail address is not valid.<BR>";
  if (empty($user[userid]) || (ereg("[^a-zA-Z0-9_-]", $user[userid]))) $rval = "the specified username '$new[userid]' is not valid.<BR>";
  if (strlen($user[userid]) > 15) $rval = "the specified username is too long: it must be less than 15 characters.";

  ### Check to see whether the username or e-mail address are banned:
  if ($ban = ban_match($user[userid], $type2index[usernames])) $rval = "the specified username is banned  for the following reason: <I>$ban->reason</I>.";
  if ($ban = ban_match($user[email], $type2index[addresses])) $rval = "the specified e-mail address is banned for the following reason: <I>$ban->reason</I>.";

  ### Verify whether username and e-mail address are unique:
  if (db_num_rows(db_query("SELECT userid FROM users WHERE LOWER(userid)=LOWER('$user[userid]')")) > 0) $rval = "the specified username is already taken.";
  if (db_num_rows(db_query("SELECT email FROM users WHERE LOWER(email)=LOWER('$user[email]')")) > 0) $rval = "the specified e-mail address is already registered.";

  return($rval);
}

function account_makePassword($min_length=6) {
  mt_srand((double)microtime() * 1000000);
  $words = array("foo","bar","guy","neo","tux","moo","sun","asm","dot","god","axe","geek","nerd","fish","hack","star","mice","warp","moon","hero","cola","girl","fish","java","perl","boss","dark","sith","jedi","drop","mojo");
  while(strlen($password) < $min_length) $password .= $words[mt_rand(0, count($words))];
  return $password;
}

function account_track_comments() {
  global $user;

  $output .= "<P>This page might be helpful in case you want to keep track of your most recent comments in any of the discussions.  You are given an overview of your comments in each of the stories you participates in along with the number of replies each comment got.\n<P>\n"; 

  ### Perform query:
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
   
  return $output;
}

switch ($op) {
  case "Login":
    session_start();
    $user = new User($userid, $passwd);
    if ($user && user_valid()) {
      session_register("user");
      watchdog(1, "session opened for user `$user->userid'.");
    }
    else {
      watchdog(2, "failed login for user `$userid'.");
    }
    showUser($user->userid);
    break;
  case "new":
    newUser();
    break;
  case "view":
    showUser($name);
    break;
  case "discussion":
    $theme->header();
    $theme->box("Track your comments", account_track_comments());
    $theme->footer();
    break;
  case "logout":
    watchdog(1, "session closed for user `$user->userid'.");
    session_unset();
    session_destroy();
    unset($user);
    showUser();
    break;
  case "Register":
    if ($rval = validateUser($new)) { newUser($new, "<B>Error: $rval</B>"); }
    else {
      ### Generate new password:
      $new[passwd] = account_makePassword();
      dbsave("users", $new);

      if ($system == 1) {
        ### Display account information:
        $theme->header();
        $theme->box("Account details", "Your password is: <B>$new[passwd]</B><BR><A HREF=\"account.php?op=Login&userid=$new[userid]&passwd=$new[passwd]\">Login</A> to change your personal settings.");
        $theme->footer();
      } else {
        ### Send e-mail with account details:
        mail($new[email], "Account details for $sitename", "$user->name,\n\nyour $sitename member account has been created succesfully.  To be able to use it, you must login using the information below.  Please save this mail for further reference.\n\n   username: $new[userid]\n     e-mail: $new[email]\n   password: $new[passwd]\n\nThis password is generated by a randomizer.  It is recommended that you change this password immediately.\n\n$contact_signature", "From: $contact_email\nX-Mailer: PHP/" . phpversion());

        ### Display account information:
        $theme->header();
        $theme->box("Account details", "Your member account has been created and the details necessary to login have been sent to your e-mail account <B>$new[email]</B>.  Once you received the account confirmation, hit <A HREF=\"account.php\">this link</A> to login.");
        $theme->footer();
      }

      watchdog(1, "new user `$new[userid]' registered with e-mail address `$new[email]'");
    }
    break;
  case "user":
    if ($user->id && user_valid()) {
      ### Generate output/content:
      $output .= "<FORM ACTION=\"account.php\" METHOD=post>\n";
      $output .= "<B>Real name:</B><BR>\n";
      $output .= "<INPUT NAME=\"edit[name]\" MAXLENGTH=55 SIZE=30 VALUE=\"$user->name\"><BR>\n";
      $output .= "<I>Optional.</I><P>\n";
      $output .= "<B>Real e-mail address:</B><BR>\n";
      $output .= "<INPUT NAME=\"edit[email]\" MAXLENGTH=55 SIZE=30 VALUE=\"$user->email\"><BR>\n";
      $output .= "<I>Required, but never displayed publicly: needed in case you lose your password.</I><P>\n";
      $output .= "<B>Fake e-mail address:</B><BR>\n";
      $output .= "<INPUT NAME=\"edit[femail]\" MAXLENGTH=55 SIZE=30 VALUE=\"$user->femail\"><BR>\n";
      $output .= "<I>Optional, and displayed publicly by your comments. You may spam proof it if you want.</I><P>\n";
      $output .= "<B>URL of homepage:</B><BR>\n";
      $output .= "<INPUT NAME=\"edit[url]\" MAXLENGTH=55 SIZE=30 VALUE=\"$user->url\"><BR>\n";
      $output .= "<I>Optional, but make sure you enter fully qualified URLs only. That is, remember to include \"http://\".</I><P>\n";
      $output .= "<B>Bio:</B> (255 char. limit)<BR>\n";
      $output .= "<TEXTAREA NAME=\"edit[bio]\" COLS=35 ROWS=5 WRAP=virtual>$user->bio</TEXTAREA><BR>\n";
      $output .= "<I>Optional. This biographical information is publicly displayed on your user page.</I><P>\n";
      $output .= "<B>User block:</B> (255 char. limit)<BR>\n";
      $output .= "<TEXTAREA NAME=\"edit[ublock]\" COLS=35 ROWS=5 WRAP=virtual>$user->ublock</TEXTAREA><BR>\n";
      $output .= "<INPUT NAME=\"edit[ublockon]\" TYPE=checkbox". ($user->ublockon == 1 ? " CHECKED" : "") ."> Enable user block<BR>\n";
      $output .= "<I>Enable the checkbox and whatever you enter below will appear on your costum main page.</I><P>\n";
      $output .= "<B>Password:</B><BR>\n";
      $output .= "<INPUT TYPE=password NAME=\"edit[pass1]\" SIZE=10 MAXLENGTH=20> <INPUT TYPE=password NAME=edit[pass2] SIZE=10 MAXLENGTH=20><BR>\n";
      $output .= "<I>Enter your new password twice if you want to change your current password or leave it blank if you are happy with your current password.</I><P>\n";
      $output .= "<INPUT TYPE=submit NAME=op VALUE=\"Save user information\"><BR>\n";
      $output .= "</FORM>\n";

      ### Display output/content:
      $theme->header();
      $theme->box("Edit your information", $output);
      $theme->footer();
    }
    else {
      $theme->header();
      $theme->box("Login", showLogin($userid)); 
      $theme->footer();
    }
    break;
  case "page":
    if ($user && user_valid()) {
      ### Generate output/content:
      $output .= "<FORM ACTION=\"account.php\" METHOD=post>\n";
      $output .= "<B>Theme:</B><BR>\n";

      ### Loop (dynamically) through all available themes:
      foreach ($themes as $key=>$value) { 
        $options .= "<OPTION VALUE=\"$key\"". (($user->theme == $key) ? " SELECTED" : "") .">$key - $value[1]</OPTION>";
      }

      $output .= "<SELECT NAME=\"edit[theme]\">$options</SELECT><BR>\n";
      $output .= "<I>Selecting a different theme will change the look and feel of the site.</I><P>\n";
      $output .= "<B>Maximum number of stories:</B><BR>\n";
      $output .= "<INPUT NAME=\"edit[storynum]\" MAXLENGTH=3 SIZE=3 VALUE=\"$user->storynum\"><P>\n";
      $output .= "<I>The maximum number of stories that will be displayed on the main page.</I><P>\n";
      $options  = "<OPTION VALUE=\"nested\"". ($user->umode == 'nested' ? " SELECTED" : "") .">Nested</OPTION>";
      $options .= "<OPTION VALUE=\"flat\"". ($user->umode == 'flat' ? " SELECTED" : "") .">Flat</OPTION>";
      $options .= "<OPTION VALUE=\"threaded\"". ($user->umode == 'threaded' ? " SELECTED" : "") .">Threaded</OPTION>";
      $output .= "<B>Comment display mode:</B><BR>\n";
      $output .= "<SELECT NAME=\"edit[umode]\">$options</SELECT><P>\n";
      $options  = "<OPTION VALUE=0". ($user->uorder == 0 ? " SELECTED" : "") .">Oldest first</OPTION>";
      $options .= "<OPTION VALUE=1". ($user->uorder == 1 ? " SELECTED" : "") .">Newest first</OPTION>";
      $options .= "<OPTION VALUE=2". ($user->uorder == 2 ? " SELECTED" : "") .">Highest scoring first</OPTION>";
      $output .= "<B>Comment sort order:</B><BR>\n";
      $output .= "<SELECT NAME=\"edit[uorder]\">$options</SELECT><P>\n";
      $options  = "<OPTION VALUE=\"-1\"". ($user->thold == -1 ? " SELECTED" : "") .">-1: Display uncut and raw comments.</OPTION>";
      $options .= "<OPTION VALUE=0". ($user->thold == 0 ? " SELECTED" : "") .">0: Display almost all comments.</OPTION>";
      $options .= "<OPTION VALUE=1". ($user->thold == 1 ? " SELECTED" : "") .">1: Display almost no anonymous comments.</OPTION>";
      $options .= "<OPTION VALUE=2". ($user->thold == 2 ? " SELECTED" : "") .">2: Display comments with score +2 only.</OPTION>";
      $options .= "<OPTION VALUE=3". ($user->thold == 3 ? " SELECTED" : "") .">3: Display comments with score +3 only.</OPTION>";
      $options .= "<OPTION VALUE=4". ($user->thold == 4 ? " SELECTED" : "") .">4: Display comments with score +4 only.</OPTION>";
      $options .= "<OPTION VALUE=5". ($user->thold == 5 ? " SELECTED" : "") .">5: Display comments with score +5 only.</OPTION>";
      $output .= "<B>Comment threshold:</B><BR>\n";
      $output .= "<SELECT NAME=\"edit[thold]\">$options</SELECT><BR>\n";
      $output .= "<I>Comments that scored less than this setting will be ignored. Anonymous comments start at 0, comments of people logged on start at 1 and moderators can add and subtract points.</I><P>\n";
      $output .= "<B>Singature:</B> (255 char. limit)<BR>\n";
      $output .= "<TEXTAREA NAME=\"edit[signature]\" COLS=35 ROWS=5 WRAP=virtual>$user->signature</TEXTAREA><BR>\n";
      $output .= "<I>Optional. This information will be publicly displayed at the end of your comments. </I><P>\n";
      $output .= "<INPUT TYPE=submit NAME=op VALUE=\"Save page settings\"><BR>\n";
      $output .= "</FORM>\n";

      ### Display output/content:
      $theme->header();
      $theme->box("Customize your page", $output);
      $theme->footer();
    }
    else {
      $theme->header();
      $theme->box("Login", showLogin($userid)); 
      $theme->footer();
    }
    break;
  case "Save user information":
    if ($user && user_valid()) {
      $data[name] = $edit[name];
      $data[email] = $edit[email];
      $data[femail] = $edit[femail];
      $data[url] = $edit[url];
      $data[bio] = $edit[bio];
      $data[ublock] = $edit[ublock];
      $data[ublockon] = $edit[ublockon];
      if ($edit[pass1] == $edit[pass2] && !empty($edit[pass1])) { $data[passwd] = $edit[pass1]; }
      dbsave("users", $data, $user->id);
      user_rehash();
    }
    showUser($user->userid);
    break;
  case "Save page settings":
    if ($user && user_valid()) {
      $data[theme] = $edit[theme];
      $data[storynum] = $edit[storynum];
      $data[umode] = $edit[umode];
      $data[uorder] = $edit[uorder];
      $data[thold] = $edit[thold];
      $data[signature] = $edit[signature];
      dbsave("users", $data, $user->id);
      user_rehash();
    }
    showUser($user->userid);
    break;
  default: 
    showUser($user->userid);
}

?>