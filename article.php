<?PHP
 if(!isset($sid) && !isset($tid)) { exit(); }

 include "config.inc";
 include "functions.inc";
 include "theme.inc";

 dbconnect();

 if ($save) {
   mysql_query("UPDATE users SET umode='$mode', uorder='$order', thold='$thold' where id='$user->id'");
   $user->update();
 }

 if($op == "reply") Header("Location: comments.php?op=reply&pid=0&sid=$sid&mode=$mode&order=$order&thold=$thold");

 $result = mysql_query("SELECT * FROM stories WHERE sid = $sid");
 list($sid, $aid, $subject, $time, $abstract, $comments, $article, $category, $informant, $department) = mysql_fetch_row($result);

 $theme->header();

 $reply = "[ <A HREF=\"\"><FONT COLOR=\"$theme->hlcolor2\">home</FONT></A> | <A HREF=\"comments.php?op=reply&pid=0&sid=$sid\"><FONT COLOR=\"$theme->hlcolor2\">add a comment</FONT></A> ]";

 $theme->article($aid, $informant, $time, stripslashes($subject), $department, stripslashes($abstract), stripslashes($comments), stripslashes($article), $reply);

 if ($mode != "nocomments") include "comments.php";

 $theme->footer();
?>