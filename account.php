<?
include "config.inc";
include "functions.inc";

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
  global $user;

  include "theme.inc";
  
  if ($user && $uname && $user->userid == $uname) {
    $output .= "<P>Welcome $user->userid! This is <B>your</B> user info page. There are many more, but this one is yours. You are probably most interested in editing something, but if you need to kill some time, this place is as good as any other place.</P>\n";
    $output .= "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>\n";
    $output .= " <TR><TD><B>User ID:</B></TD><TD>$user->userid</TD></TR>\n";
    $output .= " <TR><TD><B>Name:</B></TD><TD>$user->name</TD></TR>\n";
    $output .= " <TR><TD><B>E-mail:</B></TD><TD><A HREF=\"mailto:$user->femail\">$user->femail</A></TD></TR>\n";
    $output .= " <TR><TD><B>URL:</B></TD><TD><A HREF=\"$user->url\">$user->url</A></TD></TR>\n";
    if ($user->access > 0) $output .= "<TR><TD VALIGN=top><B>Access:</B></TD><TD>". showAccess() ."</TD></TR>\n";
    $output .= " <TR><TD><B>Bio:</B></TD><TD>$user->bio</TD></TR>\n";
    $output .= " <TR><TD><B>Signature:</B></TD><TD>$user->signature</TD></TR>\n";
    $output .= "</TABLE>\n";

    ### Display account information:
    $theme->header();
    $theme->box("User information", $output);
    $theme->footer();
  }
  elseif ($uname && $account = account_getUser($uname)) {
    $output .= "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>\n";
    $output .= " <TR><TD><B>User ID:</B></TD><TD>$account->userid</TD></TR>\n";
    $output .= " <TR><TD><B>E-mail:</B></TD><TD><A HREF=\"mailto:$account->femail\">$account->femail</A></TD></TR>\n";
    $output .= " <TR><TD><B>URL:</B></TD><TD><A HREF=\"$account->url\">$account->url</A></TD></TR>\n";
    $output .= " <TR><TD><B>Bio:</B></TD><TD>$account->bio</TD></TR>\n";
    $output .= " <TR><TD><B>Signature:</B></TD><TD>$account->signature</TD></TR>\n";
    $output .= "</TABLE>\n";

    ### Display account information:
    $theme->header();
    $theme->box("User information", $output);
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
  include "theme.inc";
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
  if ($ban = ban_match($user[userid], $type[usernames])) $rval = "the specified username is banned  for the following reason: <I>$ban->reason</I>.";
  if ($ban = ban_match($user[email], $type[addresses])) $rval = "the specified e-mail address is banned for the following reason: <I>$ban->reason</I>.";

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

switch ($op) {
  case "Login":
    session_start();
    $user = new User($userid,$passwd);
    if ($user && $user->valid()) { session_register("user"); }
    showUser($user->userid);
    break;
  case "new":
    newUser();
    break;
  case "info":
    showUser($uname);
    break;
  case "logout":
    // session_start();
    session_unset();
    session_destroy();
    unset($user);
    showUser();
    break;
  case "Register":
    if ($rval = validateUser($new)) { newUser($new, "<B>Error: $rval</B>"); }
    else {
      include"theme.inc";

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
    }
    break;
  case "edituser":
    if ($user && $user->valid()) {
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
      $output .= "<B>Bio:</B> (255 char limit)<BR>\n";
      $output .= "<TEXTAREA NAME=\"edit[bio]\" COLS=35 ROWS=5 WRAP=virtual>$user->bio</TEXTAREA><BR>\n";
      $output .= "<I>Optional. This biographical information is publicly displayed on your user page.</I><P>\n";
      $output .= "<B>User block:</B> (255 char limit)<BR>\n";
      $output .= "<TEXTAREA NAME=\"edit[ublock]\" COLS=35 ROWS=5 WRAP=virtual>$user->ublock</TEXTAREA><BR>\n";
      $output .= "<INPUT NAME=\"edit[ublockon]\" TYPE=checkbox". ($user->ublockon == 1 ? " CHECKED" : "") .">Enable user block<BR>\n";
      $output .= "<I>Enable the checkbox and whatever you enter below will appear on your costum main page.</I><P>\n";
      $output .= "<B>Password:</B><BR>\n";
      $output .= "<INPUT TYPE=password NAME=\"edit[pass1]\" SIZE=10 MAXLENGTH=20> <INPUT TYPE=password NAME=edit[pass2] SIZE=10 MAXLENGTH=20><BR>\n";
      $output .= "<I>Enter your new password twice if you want to change your current password or leave it blank if you are happy with your current password.</I><P>\n";
      $output .= "<INPUT TYPE=submit NAME=op VALUE=\"Save user information\"><BR>\n";
      $output .= "</FORM>\n";

      ### Display output/content:
      include "theme.inc";
      $theme->header();
      $theme->box("Edit user information", $output);
      $theme->footer();
    }
    else {
      include "theme.inc";
      $theme->header();
      $theme->box("Login", showLogin($userid)); 
      $theme->footer();
    }
    break;
  case "editpage":
    ### Generate output/content:
    $output .= "<FORM ACTION=\"account.php\" METHOD=post>\n";
    $output .= "<B>Theme:</B><BR>\n";

    ### Loop (dynamically) through all available themes:
    $handle = opendir("themes");
    while ($file = readdir($handle)) if(!ereg("^\.",$file) && file_exists("themes/$file/theme.class.php")) $options .= "<OPTION VALUE=\"$file\"". (((!empty($userinfo[theme])) && ($file == $cfg_theme)) || ($user->theme == $file) ? " SELECTED" : "") .">$file</OPTION>";
    closedir($handle);

    if ($userinfo[theme]=="") $userinfo[theme] = $cfg_theme;
    $output .= "<SELECT NAME=\"edit[theme]\">$options</SELECT><BR>\n";
    $output .= "<I>Changes the look and feel of the site.</I><P>\n";
    $output .= "<B>Maximum number of stories:</B><BR>\n";
    $output .= "<INPUT NAME=\"edit[storynum]\" MAXLENGTH=3 SIZE=3 VALUE=\"$user->storynum\"><P>\n";
    $options  = "<OPTION VALUE=\"nested\"". ($user->umode == 'nested' ? " SELECTED" : "") .">Nested</OPTION>";
    $options .= "<OPTION VALUE=\"flat\"". ($user->umode == 'flat' ? " SELECTED" : "") .">Flat</OPTION>";
    $options .= "<OPTION VALUE=\"threaded\"". ($user->umode == 'threaded' ? " SELECTED" : "") .">Threaded</OPTION>";
    $output .= "<B>Display mode:</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[umode]\">$options</SELECT><P>\n";
    $options  = "<OPTION VALUE=0". ($user->uorder == 0 ? " SELECTED" : "") .">Oldest first</OPTION>";
    $options .= "<OPTION VALUE=1". ($user->uorder == 1 ? " SELECTED" : "") .">Newest first</OPTION>";
    $options .= "<OPTION VALUE=2". ($user->uorder == 2 ? " SELECTED" : "") .">Highest scoring first</OPTION>";
    $output .= "<B>Sort order:</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[uorder]\">$options</SELECT><P>\n";
    $options  = "<OPTION VALUE=\"-1\"". ($user->thold == -1 ? " SELECTED" : "") .">-1: Display uncut and raw comments.</OPTION>";
    $options .= "<OPTION VALUE=0". ($user->thold == 0 ? " SELECTED" : "") .">0: Display almost all comments.</OPTION>";
    $options .= "<OPTION VALUE=1". ($user->thold == 1 ? " SELECTED" : "") .">1: Display almost no anonymous comments.</OPTION>";
    $options .= "<OPTION VALUE=2". ($user->thold == 2 ? " SELECTED" : "") .">2: Display comments with score +2 only.</OPTION>";
    $options .= "<OPTION VALUE=3". ($user->thold == 3 ? " SELECTED" : "") .">3: Display comments with score +3 only.</OPTION>";
    $options .= "<OPTION VALUE=4". ($user->thold == 4 ? " SELECTED" : "") .">4: Display comments with score +4 only.</OPTION>";
    $options .= "<OPTION VALUE=5". ($user->thold == 5 ? " SELECTED" : "") .">5: Display comments with score +5 only.</OPTION>";
    $output .= "<B>Threshold:</B><BR>\n";
    $output .= "<SELECT NAME=\"edit[thold]\">$options</SELECT><BR>\n";
    $output .= "<I>Comments that scored less than this setting will be ignored. Anonymous comments start at 0, comments of people logged on start at 1 and moderators can add and subtract points.</I><P>\n";
    $output .= "<B>Singature:</B> (255 char limit)<BR>\n";
    $output .= "<TEXTAREA NAME=\"edit[signature]\" COLS=35 ROWS=5 WRAP=virtual>$user->signature</TEXTAREA><BR>\n";
    $output .= "<I>Optional. This information will be publicly displayed at the end of your comments. </I><P>\n";
    $output .= "<INPUT TYPE=submit NAME=op VALUE=\"Save page settings\"><BR>\n";
    $output .= "</FORM>\n";

    ### Display output/content:
    include "theme.inc";
    $theme->header();
    $theme->box("Customize page", $output);
    $theme->footer();

    break;
  case "Save user information":
    if ($user && $user->valid()) {
      $data[name] = $edit[name];
      $data[email] = $edit[email];
      $data[femail] = $edit[femail];
      $data[url] = $edit[url];
      $data[bio] = $edit[bio];
      $data[ublock] = $edit[ublock];
      $data[ublockon] = $edit[ublockon];
      if ($edit[pass1] == $edit[pass2] && !empty($edit[pass1])) { $data[passwd] = $edit[pass1]; }
      dbsave("users", $data, $user->id);
      $user->rehash();
    }
    showUser($user->userid);
    break;
  case "Save page settings":
    if ($user && $user->valid()) {
      $data[theme] = $edit[theme];
      $data[storynum] = $edit[storynum];
      $data[umode] = $edit[umode];
      $data[uorder] = $edit[uorder];
      $data[thold] = $edit[thold];
      $data[signature] = $edit[signature];
      dbsave("users", $data, $user->id);
      $user->rehash();
    }
    showUser($user->userid);
    break;
  default: 
    showUser($user->userid);
}
?>