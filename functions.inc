<?
include("user.class.php");

session_start();

include "config.inc";
$functions = 1;

function dbconnect() {
  include "config.inc";
  mysql_pconnect($dbhost, $dbuname, $dbpass) or die(mysql_Error());
  mysql_select_db("$dbname") or die ("Unable to select database");
}

function counter() {
  dbconnect();
  mysql_query("UPDATE vars SET value=value+1 where name='totalhits'");
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
  dbconnect();

  $query = "SELECT * FROM refer WHERE url = '$url'";
  $result = mysql_query($query);

  if ($site = mysql_fetch_object($result)) {
    if ($site->status) {
      $site->refers++;
      $query = "UPDATE refer SET refers = '$site->refers', access_dt = '". time() ."' WHERE url = '$url'";
      $result = mysql_query($query);
    }
  }
  else {
    $query = "INSERT INTO refer (url, name, refers, create_dt, access_dt) VALUES ('$url', '', '1', '". time() ."', '". time() ."')";
    $result = mysql_query($query);
  }
}

function displayRelatedLinks($theme, $sid = 0) {
  dbconnect();

  $result = mysql_query("SELECT * FROM stories WHERE sid = $sid");

  if ($story = mysql_fetch_object($result)) {
    ### parse story for A HREF-tags:
    $text = "$story->abstract $story->comments $story->article";
    while ($text = stristr($text, "<A HREF=")) {
      $link = substr($text, 0, strpos(strtolower($text), "</a>") + 4);
      $text = stristr($text, "</A>");
      if (!stristr($link, "mailto:")) $content .= "<LI>$link</LI>";
    }

    ### default related links: 
    $content .= " <LI>More about <A HREF=\"search.php?category=$story->category\">$story->category</A>.</LI>";
    $content .= " <LI>Also by <A HREF=\"search.php?author=$story->aid\">$story->aid</A>.</LI>";

    $theme->box("Related links", $content);
  }
}

function displayOldHeadlines($theme) {
  global $user;
  
  if ($user->storynum) $result = mysql_query("SELECT sid, subject, time FROM stories ORDER BY time DESC LIMIT $user->storynum, 10");
  else $result = mysql_query("SELECT sid, subject, time FROM stories ORDER BY time DESC LIMIT 10, 10");

  while ($story = mysql_fetch_object($result)) {        
    
    if ($time != date("F jS", $story->time)) {
      $content .= "<P><B>". date("l, F jS", $story->time) ."</B></P>";
      $time = date("F jS", $story->time);
    }

    if ($user) {
      $content .= "<LI><A HREF=\"article.php?sid=$story->sid";
      if (isset($user->umode)) { $content .= "&mode=$user->umode"; } else { $content .= "&mode=threaded"; }
      if (isset($user->uorder)) { $content .= "&order=$user->uorder"; } else { $content .= "&order=0"; }    
      if (isset($user->thold)) { $content .= "&thold=$user->thold"; } else { $content .= "&thold=0"; }
      $content .= "\">$story->subject</A></LI>";
    }
    else {
      $content .= "<LI><A HREF=\"article.php?sid=$story->sid&mode=threaded&order=1&thold=0\">$story->subject</A></LI>";
    }
  }
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";

  $theme->box("Older headlines", $content);
}

function displayNewHeadlines($theme, $num = 10) {
  global $user;

  dbconnect();

  $content = "";
  $result = mysql_query("SELECT sid, subject FROM stories ORDER BY time DESC LIMIT $num");
  while(list($sid, $subject) = mysql_fetch_row($result)) {
    if ($user) {
      $content .= "<LI><A HREF=\"article.php?sid=$sid";
      if (isset($user->umode)) { $content .= "&mode=$user->umode"; } else { $content .= "&mode=threaded"; }
      if (isset($user->uorder)) { $content .= "&order=$user->uorder"; } else { $content .= "&order=0"; }    
      if (isset($user->thold)) { $content .= "&thold=$user->thold"; } else { $content .= "&thold=0"; }
      $content .= "\">$subject</A></LI>";
    }
    else {
      $content .= "<LI><A HREF=\"article.php?sid=$sid&mode=threaded&order=1&thold=0\">$subject</A></LI>";
    }
  }
  $content .= "<P ALIGN=\"right\">[ <A HREF=\"search.php\"><FONT COLOR=\"$theme->hlcolor2\">more</FONT></A> ]</P>";
  $theme->box("Latest headlines", $content);
}

function displayAdminblock($theme) {
  $result = mysql_query("SELECT title, content FROM blocks");
  while (list($title, $content) = mysql_fetch_array($result)) {
    $theme->box($title, nl2br($content));
  }
}

function displayUserblock($theme) {
  global $user;
  dbconnect();
  if ($user->ublockon) {
    $content .= "<P ALIGN=\"right\">[ <A HREF=\"account.php?op=edithome\"><FONT COLOR=\"$theme->hlcolor2\">edit</FONT></A> | <A HREF=\"account.php?op=logout\"><FONT COLOR=\"$theme->hlcolor2\">logout</FONT></A> ]</P>";
    $theme->box("$user->userid's box", $user->content);
  }
}

function displayAccount($theme) {
  global $user;
  
  if ($user) { 
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
  
  if ($user) { 
    ### Display account settings:
    $content  = "<LI><A HREF=\"account.php\">User info</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=edituser\">Edit user info</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=editpage\">Customize page</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=logout\">Logout</A></LI>";

    $theme->box("$user->userid's account", "$content");
  }
}

function displayReferrals($theme, $number = 10) {
  $count = 1;

  dbconnect();
  if ($number) {
    $query = "SELECT * FROM refer ORDER BY refers DESC LIMIT $number";
    $result = mysql_query($query);
  }
  else {
    $query = "SELECT * FROM refer ORDER BY refers DESC";
    $result = mysql_query($query);
  }
  
  while (($site = mysql_fetch_object($result)) && ($count <= $number)) {
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