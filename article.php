<?

 include "config.inc";
 include "functions.inc";
 include "theme.inc";

 if ($save) {
   db_query("UPDATE users SET umode='$mode', uorder='$order', thold='$thold' where id='$user->id'");
   $user->rehash();
 }

 if ($op == "reply") Header("Location: comments.php?op=reply&pid=0&sid=$sid&mode=$mode&order=$order&thold=$thold");

 $result = db_query("SELECT stories.*, users.userid FROM stories LEFT JOIN users ON stories.author = users.id WHERE stories.status = 2 AND stories.id = $id");
 $story = db_fetch_object($result);

 $theme->header();
 $theme->article($story, "[ <A HREF=\"\"><FONT COLOR=\"$theme->hlcolor2\">home</FONT></A> | <A HREF=\"comments.php?op=reply&pid=0&sid=$story->id\"><FONT COLOR=\"$theme->hlcolor2\">add a comment</FONT></A> ]");
 include "comments.php";
 $theme->footer();
?>