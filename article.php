<?

 include "config.inc";
 include "functions.inc";
 include "theme.inc";

 if ($save) {
   db_query("UPDATE users SET umode='$mode', uorder='$order', thold='$thold' where id='$user->id'");
   $user->rehash();
 }

 if ($op == "reply") Header("Location: comments.php?op=reply&pid=0&sid=$sid&mode=$mode&order=$order&thold=$thold");

 $result = db_query("SELECT * FROM stories WHERE id = $id");
 $story = db_fetch_object($result);

 $theme->header();
 $reply = "[ <A HREF=\"\"><FONT COLOR=\"$theme->hlcolor2\">home</FONT></A> | <A HREF=\"comments.php?op=reply&pid=0&sid=$story->sid\"><FONT COLOR=\"$theme->hlcolor2\">add a comment</FONT></A> ]";
 $theme->article($story, $reply);

 // if ($mode != "nocomments") include "comments.php";
 // 21/06/2000 - temporary disabled commnents

 $theme->footer();
?>