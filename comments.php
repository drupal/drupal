<?

function moderate_1() {
  include "config.inc";
  global $admin;
  echo "<FORM ACTION=\"comments.php\" METHOD=\"post\">";
}

function moderate_2($tid, $reason) {
  include "config.inc";

  echo "<SELECT NAME=\"meta:$tid\">"; 
  for($i = 0; $i < sizeof($comments_meta_reasons); $i++) {
    echo "<OPTION VALUE=\"$i\">$comments_meta_reasons[$i]</OPTION>\n";
  }
  echo "</SELECT>";
}

function moderate_3($sid, $mode, $order, $thold = 0) {
  echo "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\"><INPUT TYPE=\"hidden\" NAME=\"mode\" VALUE=\"$mode\"><INPUT TYPE=\"hidden\" NAME=\"order\" VALUE=\"$order\"><INPUT TYPE=\"hidden\" NAME=\"thold\" VALUE=\"$thold\"><INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Moderate\"></FORM>";
}

function displayKids ($tid, $mode, $order = 0, $thold = 0, $level = 0, $dummy = 0) {
  global $user, $theme;
  include "config.inc";
  $comments = 0;

  $result = mysql_query("SELECT tid, pid, sid, date, name, email, url, host_name, subject, comment, score, reason FROM comments WHERE pid = $tid ORDER BY date, tid");

  if ($mode == 'nested') {
    while (list($r_tid, $r_pid, $r_sid, $r_date, $r_name, $r_email, $r_url, $r_host_name, $r_subject, $r_comment, $r_score, $r_reason) = mysql_fetch_row($result)) {
      if ($r_score >= $thold) {
        if ($level && !$comments) {
           echo "<UL>";
           $tblwidth -= 5;
        }
        $comments++;

        $link = "<A HREF=\"comments.php?op=reply&pid=$r_tid&sid=$r_sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";

        $theme->comment($r_name, $r_subject, $r_tid, $r_date, $r_url, $r_email, $r_score, $r_reason, $r_comment, $link);
        
        displayKids($r_tid, $mode, $order, $thold, $level + 1, $dummy + 1);
      }
    }
  } elseif ($mode == 'flat') {
    while (list($r_tid, $r_pid, $r_sid, $r_date, $r_name, $r_email, $r_url, $r_host_name, $r_subject, $r_comment, $r_score, $r_reason) = mysql_fetch_row($result)) {
      if ($r_score >= $thold) {
        if (!eregi("[a-z0-9]",$r_name)) $r_name = $anonymous;
        if (!eregi("[a-z0-9]",$r_subject)) $r_subject = "[no subject]";

        $link = "<A HREF=\"comments.php?op=reply&pid=$r_tid&sid=$r_sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";

        $theme->comment($r_name, $r_subject, $r_tid, $r_date, $r_url, $r_email, $r_score, $r_reason, $r_comment, $link);
      } 
      displayKids($r_tid, $mode, $order, $thold);
    }
  } else {
    echo "ERROR: we should not get here!";
  }
  
  if ($level && $comments) {
    echo "</UL>";
  }
}

function displayBabies ($tid, $level = 0, $dummy = 0, $thread) {
  global $datetime, $theme, $user;

  include "config.inc";

  $comments = 0;
  $result = mysql_query("SELECT tid, pid, sid, date, name, email, url, host_name, subject, comment, score, reason FROM comments WHERE pid = $tid ORDER BY date, tid");
  
  if ($level == 0) $thread = "";

  while (list($r_tid, $r_pid, $r_sid, $r_date, $r_name, $r_email, $r_url, $r_host_name, $r_subject, $r_comment, $r_score, $r_reason) = mysql_fetch_row($result)) {
    if ($level && !$comments) {
      $thread .= "<UL>";
    }
  
    $comments++;
    if (!eregi("[a-z0-9]",$r_name)) { $r_name = $anonymous; }
    if (!eregi("[a-z0-9]",$r_subject)) { $r_subject = "[no subject]"; }

    if ($user) {
      ### Make sure to respect the user preferences:
      $thread .= "<LI><A HREF=\"comments.php?op=showreply&tid=$r_tid&pid=$r_pid&sid=$r_sid";
      if (isset($user->umode)) { $thread .= "&mode=$user->umode"; } else { $thread .= "&mode=threaded"; }
      if (isset($user->uorder)) { $thread .= "&order=$user->uorder"; } else { $thread .= "&order=0"; }
      if (isset($user->thold)) { $thread .= "&thold=$user->thold"; } else { $thread .= "&thold=0"; }
      $thread .= "\">$r_subject</A> by $r_name <FONT SIZE=\"2\">(". formatTimestamp($r_date) .")</FONT></LI>";
    }
    else {
      $thread .= "<LI><A HREF=\"comments.php?op=showreply&tid=$r_tid&pid=$r_pid&sid=$r_sid&mode=threaded&order=1&thold=0\">$r_subject</A> by $r_name <FONT SIZE=\"2\">(". formatTimestamp($r_date) .")</FONT></LI>";
    }   
    displayBabies($r_tid, $level + 1, $dummy + 1, &$thread);
  } 

  if ($level && $comments) {
    $thread .= "</UL>";
  }

  return $thread;
}

function displayTopic ($sid, $pid = 0, $tid = 0, $mode = "threaded", $order = 0, $thold = 0, $level = 0, $nokids = 0) {
  global $user, $theme, $functions;

  ### include required files:
  if ($functions) {
    include "config.inc";
  }
  else {
    include "functions.inc";
    include "theme.inc";
    $theme->header();
  }

  ### ensure default value:
  if (!isset($pid)) $pid = 0;

  ### connect to database:
  dbconnect();

  $count_times = 0;

  $q = "SELECT tid, pid, sid, date, name, email, url, host_name, subject, comment, score, reason FROM comments WHERE sid = $sid AND pid = $pid";

  if ($mode == 'threaded' || mode == 'nested') {
    if ($thold != "") {
      $q .= " AND score >= $thold";
    } else {
      $q .= " AND score >= 0";
    }
  }

  if ($order == 1) $q .= " ORDER BY date DESC";
  if ($order == 2) $q .= " ORDER BY score DESC";

  $res = mysql_query("$q");
  
  $num_tid = mysql_num_rows($res);

  $theme->commentControl($sid, $title, $thold, $mode, $order);

  moderate_1();

  while ($count_times < $num_tid) {
    list($tid, $pid, $sid, $date, $name, $email, $url, $host_name, $subject, $comment, $score, $reason) = mysql_fetch_row($res);
    if ($name == "") { $name = $anonymous; }
    if ($subject == "") { $subject = "[no subject]"; }	

    ### Dynamically generate the link:
    if ($pid != 0) {
      list($erin) = mysql_fetch_row(mysql_query("SELECT pid FROM comments WHERE tid=$pid"));
      $link = "<A HREF=\"comments.php?sid=$sid&pid=$erin&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">return to parent</FONT></A> | <A HREF=\"comments.php?op=reply&pid=$tid&sid=$sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A>";
    }
    else {
      $link = "<A HREF=\"comments.php?op=reply&pid=$tid&sid=$sid&mode=$mode&order=$order&thold=$thold\"><FONT COLOR=\"$theme->hlcolor2\">reply to this comment</FONT></A> ";
    }

    if ($mode == "threaded") {
      $thread = displayBabies($tid, $mode, $order, $thold, $level);
      $theme->comment($name, $subject, $tid, $date, $url, $email, $score, $reason, $comment, $link, $thread);
    }
    else {
      $theme->comment($name, $subject, $tid, $date, $url, $email, $score, $reason, $comment, $link);
      displayKids($tid, $mode, $order, $thold, $level);
    }

    echo "</UL>";
    echo "</P>";
    $count_times += 1;
  }

  moderate_3($sid, $mode, $order, $thold);

  if ($pid == 0) return array($sid, $pid, $subject);
  else $theme->footer();
}


function reply($pid, $sid, $mode, $order, $thold) {
  include "functions.inc";
  include "theme.inc";
  
  global $user;
  dbconnect();

  $theme->header();
  
  if ($pid != 0) {
    list($date, $name, $email, $url, $subject, $comment, $score) = mysql_fetch_row(mysql_query("SELECT date, name, email, url, subject, comment, score FROM comments WHERE tid = $pid"));
  } else {
    list($date, $subject, $comment, $name) = mysql_fetch_row(mysql_query("SELECT time, subject, abstract, informant FROM stories WHERE sid = $sid"));
  }

  ### Pre-process the variables:
  if ($comment == "") $comment = $comment;
  if ($subject == "") $subject = "[no subject]";
  if ($name == "") $name = $anonymous;

  ### Display parent comment:
  echo "<TABLE WIDTH=\"100%\" BORDER=\"0\">";
  if ($email) {
    echo " <TR BGCOLOR=\"$theme->bgcolor1\"><TD><FONT COLOR=\"$theme->hlcolor1\"><B>$subject</B><BR>by <A HREF=\"mailto:$email\">$name</A> <B>($email)</B> on ". formatTimestamp($date) ."</FONT></TD></TR>";
  } 
  else {
    echo " <TR BGCOLOR=\"$theme->bgcolor1\"><TD><FONT COLOR=\"$theme->hlcolor1\"><B>$subject</B><BR>by $name on ". formatTimestamp($date) ."</FONT></TD></TR>";
  }
  echo " <TR BGCOLOR=\"$theme->bgcolor2\"><TD>$comment</TD></TR>";
  echo "</TABLE>";

  if (!isset($pid) || !isset($sid)) { exit(); }
  if ($pid == 0) {
    list($subject) = mysql_fetch_row(mysql_query("SELECT subject FROM stories WHERE sid = $sid"));
  } 
  else {
    list($subject) = mysql_fetch_row(mysql_query("SELECT subject FROM comments WHERE tid = $pid"));
  }

  ### Build reply form:
  echo "<FORM ACTION=\"comments.php\" METHOD=\"post\">";

  echo "<B>Your name:</B><BR> ";
  if ($user) {
    echo "<A HREF=\"account.php\">$user->userid</A> &nbsp; &nbsp; <FONT SIZE=\"2\">[ <A HREF=\"account.php?op=logout\">logout</A> ]</FONT>";
  } 
  else {
    echo "$anonymous"; 
    $postanon = 2;
  }
  echo "<BR><BR>";

  echo "<B>Subject:</B><BR>";
  if (!eregi("Re:",$subject)) $subject = "Re: $subject"; 
    // Only one 'Re:' will just do fine. ;)
  echo "<INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"60\" MAXLENGTH=\"60\" VALUE=\"$subject\">";
  echo "<BR><BR>";

  if ($user) { 
    $userinfo = getusrinfo($user);
    echo "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">$userinfo[signature]</TEXTAREA><BR>";
    echo "<INPUT TYPE=\"checkbox\" NAME=\"postanon\"> Post this comment anonymously.";
    echo "<BR><BR>";
  }
  else {
    echo "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\"></TEXTAREA>";
    echo "<BR><BR>";
  }

  echo "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">";
  echo "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\"><INPUT TYPE=\"hidden\" NAME=\"mode\" VALUE=\"$mode\">";
  echo "<INPUT TYPE=\"hidden\" NAME=\"order\" VALUE=\"$order\"><INPUT TYPE=\"hidden\" NAME=\"thold\" VALUE=\"$thold\">";
  echo "<INPUT TYPE=submit NAME=op VALUE=\"Preview comment\"> <INPUT TYPE=submit NAME=op VALUE=\"Post comment\"> <SELECT NAME=\"posttype\"><OPTION VALUE=\"exttrans\">HTML to text<OPTION VALUE=\"html\">HTML-formatted<OPTION VALUE=\"plaintext\" SELECTED>Plain text</SELECT></FORM>";
 
  echo "<FONT SIZE=\"2\">Allowed HTML-tags:<BR>";
  for ($i=0; $i < sizeof($AllowableHTML); $i++) {
    if (!eregi("/",$AllowableHTML[$i])) echo " &lt;$AllowableHTML[$i]&gt;";
  }
  
  $theme->footer();
}

function replyPreview ($pid, $sid, $subject, $comment, $postanon, $mode, $order, $thold, $posttype) {
  include "functions.inc";
  include "theme.inc" ;

  global $user, $bgcolor1, $bgcolor2;

  $subject = stripslashes($subject);
  $comment = stripslashes($comment);

  $theme->header();

  ### Display preview:
  echo "<TABLE WIDTH=\"100%\" BORDER=\"0\">";
  if ($user) {
    echo " <TR BGCOLOR=\"$bgcolor1\"><TD><B>$subject</B><BR>by $user->userid.</TD></TR>";
  } 
  else {
    echo " <TR BGCOLOR=\"$bgcolor1\"><TD><B>$subject</B><BR>by $anonymous.</TD></TR>";
  }

  if ($posttype == "exttrans") {
    echo " <TR BGCOLOR=\"$bgcolor2\"><TD>". nl2br(htmlspecialchars($comment)) ."</TD></TR>";
  }
  elseif ($posttype == "plaintext") {
    echo " <TR BGCOLOR=\"$bgcolor2\"><TD>". nl2br($comment) ."</TD></TR>";
  }
  else {
    echo " <TR BGCOLOR=\"$bgcolor2\"><TD>$comment</TD></TR>";
  }
  echo "</TABLE>";

  ### Build reply form:
  echo "<FORM ACTION=\"comments.php\" METHOD=\"post\">";

  echo "<B>Your name:</B><BR> ";
  if ($user) {
    echo "<A HREF=\"account.php\">$user->userid</A> &nbsp; &nbsp; <FONT SIZE=\"2\">[ <A HREF=\"account.php?op=logout\">logout</A> ]</FONT>";
  } else {
    echo "$anonymous"; 
    $postanon = 2;
  }
  echo "<BR><BR>";

  echo "<B>Subject:</B><BR>";
  if (!eregi("Re:",$subject)) $subject = "Re: $subject"; // one Re: will do ;)
  echo "<INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"60\" MAXLENGTH=\"60\" VALUE=\"$subject\">";
  echo "<BR><BR>";

  $userinfo = getusrinfo($user);
  echo "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">$comment</TEXTAREA>";
  if ($user) { 
   if ($postanon) echo "<BR><INPUT TYPE=\"checkbox\" NAME=\"postanon\" CHECKED> Post this comment anonymously.";
   else echo "<BR><INPUT TYPE=\"checkbox\" NAME=\"postanon\"> Post this comment anonymously.";
  }
  echo "<BR><BR>";

  echo "<INPUT TYPE=\"hidden\" NAME=\"pid\" VALUE=\"$pid\">";
  echo "<INPUT TYPE=\"hidden\" NAME=\"sid\" VALUE=\"$sid\"><INPUT TYPE=\"hidden\" NAME=\"mode\" VALUE=\"$mode\">";
  echo "<INPUT TYPE=\"hidden\" NAME=\"order\" VALUE=\"$order\"><INPUT TYPE=\"hidden\" NAME=\"thold\" VALUE=\"$thold\">";
  echo "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview comment\"> ";
  echo "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Post comment\"> ";
  echo "<SELECT NAME=\"posttype\">";
  if ($posttype == "exttrans") echo " <OPTION VALUE=\"exttrans\" SELECTED>HTML to text";
  else echo " <OPTION VALUE=\"exttrans\">HTML to text";
  if ($posttype == "html") echo " <OPTION VALUE=\"html\" SELECTED>HTML-formatted";
  else echo " <OPTION VALUE=\"html\">HTML-formatted";
  if ($posttype == "plaintext") echo " <OPTION VALUE=\"plaintext\" SELECTED>Plain text";
  else echo " <OPTION VALUE=\"plaintext\">Plain text";
  echo "</SELECT>";
  echo "</FORM>";
 
  echo "<FONT SIZE=\"2\">Allowed HTML-tags:<BR>";
  for ($i=0; $i < sizeof($AllowableHTML); $i++) {
    if (!eregi("/",$AllowableHTML[$i])) echo " &lt;$AllowableHTML[$i]&gt;";
  }

  $theme->footer();
}

function postComment($postanon, $subject, $comment, $pid, $sid, $host_name, $mode, $order, $thold, $posttype) {
  global $user, $userinfo;
  include "functions.inc";
  include "config.inc";
  dbconnect();

  $subject = FixQuotes($subject);
  $comment = FixQuotes($comment);
  $author = FixQuotes($author);

  if ($posttype == "exttrans") $comment = nl2br(htmlspecialchars($comment));
  elseif($posttype == "plaintext") $comment = nl2br($comment);
  else $comment = $comment;

  if (($user) && (!$postanon)) {
    getusrinfo($user);
    $name = $userinfo[uname];
    $email = $userinfo[femail];
    $url = $userinfo[url];
    $score = 1;
  } else {
    $name = ""; 
    $email = ""; 
    $url = "";
    $score = 0;
  }
  $ip = getenv("REMOTE_ADDR");

  ### Check for fake threads:
  $fake = mysql_result(mysql_query("SELECT COUNT(*) FROM stories WHERE sid = $sid"), 0);

  ### Check for duplicate comments:
  $duplicate = mysql_result(mysql_query("SELECT COUNT(*) FROM comments WHERE pid = '$pid' AND sid = '$sid' AND subject = '$subject' AND comment = '$comment'"), 0);
  
  if ($fake != 1) {
    include "theme.inc";
    $theme->header();
    $theme->box("fake comment", "fake comment: $fake");
    $theme->footer();
  }
  elseif ($duplicate != 0) {
    include "theme.inc";
    $theme->header();
    $theme->box("duplicate comment", "duplicate comment: $duplicate");
    $theme->footer();
  }
  else {
    ### Add comment to table:
    $reason = (int) sizeof($comments_meta_reasons) / 2;
    mysql_query("INSERT INTO comments (tid, pid, sid, date, name, email, url, host_name, subject, comment, score, reason) VALUES (NULL, '$pid', '$sid', now(), '$name', '$email', '$url', '$ip', '$subject', '$comment', '$score', '$reason')");
  
    ### Compose header:
    if ($user) {
      $header = "article.php?sid=$sid";
      if (isset($user->umode)) { $header .= "&mode=$user->umode"; } else { $header .= "&mode=threaded"; }
      if (isset($user->uorder)) { $header .= "&order=$user->uorder"; } else { $header .= "&order=0"; }    
      if (isset($user->thold)) { $header .= "&thold=$user->thold"; } else { $header .= "&thold=1"; }
    }
    else {
      $header .= "article.php?sid=$sid&mode=threaded&order=1&thold=0";
    }
    header("Location: $header");
  }
}

function moderate($tid, $meta_value = 0) {
  include "config.inc";
  if ($meta_value != -1) {
    ### Compose query:
    $query = "UPDATE comments SET";
    if ($meta_value > (sizeof($comments_meta_reasons) / 2)) {
      $query .= " score = score + 1, reason = $meta_value WHERE tid = $tid";
    } 
    elseif ($meta_value < ((sizeof($comments_meta_reasons) / 2) - 1)) {
      $query .= " score = score - 1, reason = $meta_value WHERE tid = $tid";
    }
    else {
      $query .= " reason = $meta_value WHERE tid = $tid";
    }

    ### Perform query:
    mysql_query("$query");
  }
}

switch($op) {
  case "reply":
    reply($pid, $sid, $mode, $order, $thold);
    break;
  case "Preview comment":
    replyPreview($pid, $sid, $subject, $comment, $postanon, $mode, $order, $thold, $posttype);
    break;
  case "Post comment":
    postComment($postanon, $subject, $comment, $pid, $sid, $host_name, $mode, $order, $thold, $posttype);
    break;
  case "Moderate":
    include "functions.inc";	
    dbconnect();

    while (list($name, $value) = each($HTTP_POST_VARS)) {
      if (eregi("meta", $name)) {
        ### extract comment id (tid):
        $info = explode(":", $name);
        moderate($info[1], $value);
      }
    }

    Header("Location: article.php?sid=$sid&mode=$mode&order=$order&thold=$thold");
    break;
  case "showreply":
    displayTopic($sid, $pid, $tid, $mode, $order, $thold);
    break;
  default:
    displayTopic($sid, $pid, $tid, $mode, $order, $thold);
}

?>