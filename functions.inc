<?

include "config.inc";
$functions = 1;

function dbconnect() {
  include "config.inc";
  mysql_pconnect($dbhost, $dbuname, $dbpass);
  @mysql_select_db("$dbname") or die ("Unable to select database");
}

function counter() {
  dbconnect();
  mysql_query("UPDATE vars SET value=value+1 where name='totalhits'");
}

function cookiedecode($user) {
  global $cookie;
  $user = base64_decode($user);
  $cookie = explode(":", $user);
  return $cookie;
}

function getusrinfo($user) {
  global $userinfo;
  $user2 = base64_decode($user);
  dbconnect();
  $user3 = explode(":", $user2);
  $result = mysql_query("SELECT uid, name, uname, email, femail, url, pass, storynum, umode, uorder, thold, noscore, bio, ublockon, ublock, theme, signature FROM users WHERE uname = '$user3[1]' AND pass = '$user3[2]'");
  if(mysql_num_rows($result)==1) {
    $userinfo = mysql_fetch_array($result);
  } else {
    echo "<b>A problem occured</b><br>";
  }
  return $userinfo;
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
  global $user, $cookie;
  
  if ($cookie[3]) $result = mysql_query("SELECT sid, subject, time FROM stories ORDER BY time DESC LIMIT $cookie[3], 10");
  else $result = mysql_query("SELECT sid, subject, time FROM stories ORDER BY time DESC LIMIT 10, 10");

  while ($story = mysql_fetch_object($result)) {        
    
    if ($time != date("F jS", $story->time)) {
      $content .= "<P><B>". date("l, F jS", $story->time) ."</B></P>";
      $time = date("F jS", $story->time);
    }

    if ($user) {
      $content .= "<LI><A HREF=\"article.php?sid=$story->sid";
      if (isset($cookie[4])) { $content .= "&mode=$cookie[4]"; } else { $content .= "&mode=threaded"; }
      if (isset($cookie[5])) { $content .= "&order=$cookie[5]"; } else { $content .= "&order=0"; }    
      if (isset($cookie[6])) { $content .= "&thold=$cookie[6]"; } else { $content .= "&thold=0"; }
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
  global $user, $cookie;

  dbconnect();

  $content = "";
  $result = mysql_query("SELECT sid, subject FROM stories ORDER BY time DESC LIMIT $num");
  while(list($sid, $subject) = mysql_fetch_row($result)) {
    if ($user) {
      $content .= "<LI><A HREF=\"article.php?sid=$sid";
      if (isset($cookie[4])) { $content .= "&mode=$cookie[4]"; } else { $content .= "&mode=threaded"; }
      if (isset($cookie[5])) { $content .= "&order=$cookie[5]"; } else { $content .= "&order=0"; }    
      if (isset($cookie[6])) { $content .= "&thold=$cookie[6]"; } else { $content .= "&thold=0"; }
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
  global $cookie;
  dbconnect();
  if ($cookie[8]) {
    $block = mysql_query("SELECT ublock FROM users WHERE uid = '$cookie[0]'");
    list($content) = mysql_fetch_row($block);
    $content .= "<P ALIGN=\"right\">[ <A HREF=\"account.php?op=edithome\"><FONT COLOR=\"$theme->hlcolor2\">edit</FONT></A> | <A HREF=\"account.php?op=logout\"><FONT COLOR=\"$theme->hlcolor2\">logout</FONT></A> ]</P>";
    $theme->box("$cookie[1]'s box", $content);
  }
}

function displayAccount($theme) {
  global $user, $cookie;
  
  if ($user) { 
    ### Display userblock if any:
    displayUserblock();
  }
  else {
    $content = "<CENTER><FORM METHOD=\"post\" ACTION=\"account.php\">\n <P>Username:<BR><INPUT TYPE=\"text\" NAME=\"uname\" MAXLENGTH=\"50\" SIZE=\"12\"></P>\n<P>Password:<BR> <INPUT TYPE=\"password\" NAME=\"pass\" MAXLENGTH=\"25\" SIZE=\"12\"></P>\n<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Login\">\n</FORM>\n<P><A HREF=\"account.php\">Register</A> as new user.<BR><A HREF=\"account.php\">Forgot</A> your password?</P></CENTER>";
    $theme->box("Login", $content);
  }
}


function displayAccountSettings($theme) {
  global $user;
  
  if ($user) { 
    ### Display account settings:
    $content .= "<LI><A HREF=\"account.php\">User info</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=edituser\">Edit user info</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=edithome\">Customize homepage</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=editcomm\">Customize comments</A></LI>";
    $content .= "<LI><A HREF=\"account.php?op=logout\">Logout</A></LI>";

    ### Decode user cookie to extract name:
    $cookie = cookiedecode($user);

    $theme->box("$cookie[1]'s acount", "$content");
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