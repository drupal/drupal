<?
include "user.class.php";
include "database.inc";

session_start();

include "config.inc";
$functions = 1;

function dbsave($dbase, $data, $id=0) {
  foreach ($data as $key=>$value) {
    if ($key == "passwd") { $query .= "$key=PASSWORD('". addslashes($value) ."'), "; }
    else { $query .= "$key='". addslashes($value) ."', "; }
  }
  $query = substr($query, 0, -2);
  dbconnect();
  if (!empty($id)) { mysql_query("UPDATE $dbase SET $query WHERE id=$id") or die(mysql_error()); return $id; }
  else { mysql_query("INSERT INTO $dbase SET $query") or die(mysql_error()); return mysql_insert_id(); }
}

function dbconnect() {
  include "config.inc";
  mysql_pconnect($dbhost, $dbuname, $dbpass) or die(mysql_Error());
  mysql_select_db("$dbname") or die ("Unable to select database");
}

function morelink_bytes($theme, $story) {
  global $user;

  ### Compose more-link:
  $morelink = "[ ";
  if ($story->article) {
    $morelink .= "<A HREF=\"discussion.php?id=$story->id";
    $morelink .= ($user->umode) ? "&mode=$user->umode" : "&mode=threaded";
    $morelink .= ($user->uorder) ? "&order=$user->uorder" : "&order=0";
    $morelink .= ($user->thold) ? "&thold=$user->thold" : "&thold=0";
    $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\"><B>read more</B></FONT></A> | $bytes bytes in body | "; 
  }
  $morelink .= "<A HREF=\"discussion.php?id=$story->id";
  $morelink .= ($user->umode) ? "&mode=$user->umode" : "&mode=threaded";
  $morelink .= ($user->uorder) ? "&order=$user->uorder" : "&order=0";
  $morelink .= ($user->thold) ? "&thold=$user->thold" : "&thold=0";
  $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\">$story->comments comments</FONT></A> ]";

  return $morelink;
}

function morelink_words($theme, $story) {
  // todo
}

function morelink_lines($theme, $story) {
  // todo
}

function FixQuotes ($what = "") {
  $what = ereg_replace("'","''",$what);
  while (eregi("\\\\'", $what)) {
    $what = ereg_replace("\\\\'","'",$what);
  }
  return $what;
}

function check_html($message) {
  ## TODO
  return $message;
}

function filter_text($message, $strip="") {
  ### TODO
  return check_html($text, $strip);
}

function formatTimestamp($time) {
  ### Should be removed as soon as possible!
  global $datetime;
  ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})", $time, $datetime);
  $datetime = date("l, F d, Y - h:i A", mktime($datetime[4],$datetime[5],$datetime[6],$datetime[2],$datetime[3],$datetime[1]));
  return($datetime);
}

function addRefer($url) {
  $query = "SELECT * FROM refer WHERE url = '$url'";
  $result = db_query($query);

  if ($site = db_fetch_object($result)) {
    if ($site->status) {
      $site->refers++;
      $query = "UPDATE refer SET refers = '$site->refers', access_dt = '". time() ."' WHERE url = '$url'";
      $result = db_query($query);
    }
  }
  else {
    $query = "INSERT INTO refer (url, name, refers, create_dt, access_dt) VALUES ('$url', '', '1', '". time() ."', '". time() ."')";
    $result = db_query($query);
  }
}

function id2story($id) {
  ### Perform query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = $id");
  return db_fetch_object($result);
}

function displayModerationResults($theme, $story) {
  global $user;

  if ($user->id && $story->id && $vote = $user->getHistory("s$story->id")) {
    $output .= "<P><B>You voted `$vote'.</B></P>\n";
    $output .= "<P>\n";
    $output .= "<B>Other people voted:</B><BR>\n";

    $result = db_query("SELECT * FROM users WHERE history LIKE '%s$story->id%'");
    while ($account = db_fetch_object($result)) {
      $output .= "<A HREF=\"account.php?op=info&uname=$account->userid\">$account->userid</A> voted `". getHistory($account->history, "s$story->id") ."'.<BR>";
    }
    
    $theme->box("Moderation results", $output);
  }
}

function displayRelatedLinks($theme, $story) {
  ### Parse story for <A HREF="">-tags:
  $text = "$story->abstract $story->updates $story->article";
  while ($text = stristr($text, "<A HREF=")) {
    $link = substr($text, 0, strpos(strtolower($text), "</a>") + 4);
    $text = stristr($text, "</A>");
    if (!stristr($link, "mailto:")) $content .= "<LI>$link</LI>";
  }

  ### Default related links: 
  $content .= " <LI>More about <A HREF=\"search.php?category=". urlencode($story->category) ."\">$story->category</A>.</LI>";
  $content .= " <LI>Also by <A HREF=\"search.php?author=". urlencode($story->userid) ."\">$story->userid</A>.</LI>";

  $theme->box("Related links", $content);
}

function displayOldHeadlines($theme, $num = 10) {
  global $user;
  
  if ($user->storynum) $result = db_query("SELECT id, subject, timestamp FROM stories WHERE status = 2 ORDER BY timestamp DESC LIMIT $user->storynum, $num");
  else $result = db_query("SELECT id, subject, timestamp FROM stories WHERE status = 2 ORDER BY timestamp DESC LIMIT $num, $num");

  while ($story = db_fetch_object($result)) {    
    if ($time != date("F jS", $story->timestamp)) {
      $content .= "<P><B>". date("l, F jS", $story->timestamp) ."</B></P>";
      $time = date("F jS", $story->timestamp);
    }

    if ($user->userid) {
      $content .= "<LI><A HREF=\"discussion.php?id=$story->id";
      $content .= ($user->umode) ? "&mode=$user->umode" : "&mode=threaded";
      $content .= ($user->uorder) ? "&order=$user->uorder" : "&order=0";
      $content .= ($user->thold) ? "&thold=$user->thold" : "&thold=0";
      $content .= "\">$story->subject</A></LI>";
    }
    else {
      $content .= "<LI><A HREF=\"discussion.php?id=$story->id&mode=threaded&order=1&thold=0\">$story->subject</A></LI>";
    }
  }
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";

  $theme->box("Older headlines", $content);
}

function displayNewHeadlines($theme, $num = 10) {
  global $user;

  $content = "";
  $result = db_query("SELECT id, subject FROM stories WHERE status = 2 ORDER BY id DESC LIMIT $num");
  while ($story = db_fetch_object($result)) {    
    if ($user->userid) {
      $content .= "<LI><A HREF=\"discussion.php?id=$story->id";
      $content .= ($user->umode) ? "&mode=$user->umode" : "&mode=threaded";
      $content .= ($user->uorder) ? "&order=$user->uorder" : "&order=0";
      $content .= ($user->thold) ? "&thold=$user->thold" : "&thold=0";
      $content .= "\">$story->subject</A></LI>";
    }
    else {
      $content .= "<LI><A HREF=\"discussion.php?id=$story->id&mode=threaded&order=1&thold=0\">$story->subject</A></LI>";
    }
  }
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";
  $theme->box("Latest headlines", $content);
}

function displayAdminblock($theme) {
  $result = db_query("SELECT title, content FROM blocks");
  while (list($title, $content) = mysql_fetch_array($result)) {
    $theme->box($title, nl2br($content));
  }
}

function displayUserblock($theme) {
  global $user;

  if ($user && $user->ublockon) {
    $content .= "<P ALIGN=\"right\">[ <A HREF=\"account.php?op=edithome\"><FONT COLOR=\"$theme->hlcolor2\">edit</FONT></A> | <A HREF=\"account.php?op=logout\"><FONT COLOR=\"$theme->hlcolor2\">logout</FONT></A> ]</P>";
    $theme->box("$user->userid's box", $user->content);
  }
}

function displayLogin($theme) {
  global $user;
  
  if ($user && $user->userid) { 
    ### Display userblock if any:
    displayUserblock();
  }
  else {
    $content  = "<CENTER><FORM METHOD=\"post\" ACTION=\"account.php\">\n";
    $content .= "<P>Username:<BR><INPUT NAME=userid MAXLENGTH=50 SIZE=12></P>\n";
    $content .= "<P>Password:<BR> <INPUT TYPE=password NAME=passwd MAXLENGTH=25 SIZE=12></P>\n";
    $content .= "<INPUT TYPE=submit NAME=op VALUE=\"Login\">\n";
    $content .= "</FORM>\n";
    $content .= "<P><A HREF=\"account.php?op=new\">Register</A> as new user.<BR><A HREF=\"account.php?op=forgot\">Forgot</A> your password?</P></CENTER>";
    $theme->box("Login", $content);
  }
}

function displayCalendar($theme, $date) {
  include "calendar.class.php";
  $calendar = new calendar($date);
  $theme->box("Browse archives", $calendar->display());
}

function displayAccountSettings($theme) {
  global $user;
  
  if ($user && $user->userid) { 
    ### Display account settings:
    $content  = "<LI><A HREF=\"account.php\">User info</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=edituser\">Edit user info</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=editpage\">Customize page</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=logout\">Logout</A></LI>";

    $theme->box("$user->userid's account", "$content");
  }
}

function displayAccount($theme) {
  global $user;
 
  include "submission.inc";

  if ($user && $user->userid) {
    ### Display account settings:
    $content .= "<LI><A HREF=\"submission.php\">moderate submissions</A> (". submission_count() .")</LI>";
    $theme->box("$user->userid's account", "$content");
  }
}

function displayReferrals($theme, $number = 10) {
  $count = 1;

  if ($number) {
    $query = "SELECT * FROM refer ORDER BY refers DESC LIMIT $number";
    $result = db_query($query);
  }
  else {
    $query = "SELECT * FROM refer ORDER BY refers DESC";
    $result = db_query($query);
  }
  
  while (($site = db_fetch_object($result)) && ($count <= $number)) {
    if ($site->name) $rval .= "$count. <A HREF=\"$site->url\">$site->name</A> ($site->refers)<BR>";
    else $rval .= "$count. <A HREF=\"$site->url\">$site->url</A> ($site->refers)<BR>";
    $count++;
  }

  $theme->box("Referring sites", "$rval <P ALIGN=\"right\">[ <A HREF=\"refer.php#refer-info\"><FONT COLOR=\"$theme->hlcolor2\">info</FONT></A> | <A HREF=\"refer.php#refer-more\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>");
}

function displayPoll($theme) {
  global $answer, $answer1, $answer2, $answer3, $answer4, $answer5, $answer6, $id, $method, $section, $poll, $question;
    // Pass the URI and FORM parameters along to poll.php.
  $box = 1;
  include "poll.php";
}
?>