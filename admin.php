<?PHP

include "functions.inc";
include "authentication.inc";

function login() {
  include "theme.inc";
  $theme->header();
  $theme->box("Login", "<FORM ACTION=\"admin.php\" METHOD=\"post\"><P>Name: <INPUT TYPE=\"text\" NAME=\"aid\" SIZE=\"20\" MAXLENGTH=\"20\"><P>Password: <INPUT TYPE=\"password\" NAME=\"pwd\" SIZE=\"20\" MAXLENGTH=\"18\"><P><INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"login\"></FORM>");
  $theme->footer();
}

function logout() {
  setcookie("admin");

  include "theme.inc";
  $theme->header();
  ?>
   <BR><BR><BR><BR>
   <P ALIGN="center"><FONT SIZE="+2"><B>You are now logged out!</B></FONT></P>
   <P>You have been logged out of the system.  Since authentication details are stored by using cookies, logging out is only necessary to prevent those who have access to your computer from abusing your account.</P>
  <?  
  $theme->footer();
}

function backup() {
  include "config.inc";
  if ($system == 0) {
    exec("mysqldump -h $dbhost -u $dbuname -p$dbpass $dbname | mail -s \"[$sitename] MySQL backup\" $notify_email");
    exec("mysqldump -h $dbhost -u $dbuname -p$dbpass $dbname > ../$sitename-backup-". date("Ymd", time()).".mysql");
  }
  else print "<P><B>Warning:</B> the backup feature is only supported on UNIX systems.  Check your configuration file if you are using a UNIX system.</P>";
}

function main() {
  include "config.inc";
  include "theme.inc";
  $theme->header();
  dbconnect();

  $result = mysql_query("SELECT qid, subject, timestamp FROM queue order by timestamp");

  echo "<FORM ACTION=\"admin.php\" METHOD=\"post\">";
  echo "<TABLE WIDTH=\"100%\">";

  if (mysql_num_rows($result) != 0) {
    while (list($qid, $subject, $timestamp) = mysql_fetch_row($result)) {
      
      ### format date:
      $datetime = date("F d - h:i:s A", $timestamp);

      ### generate overview:
      echo " <TR>";
      echo "  <TD BGCOLOR=\"#c0c0c0\" WIDTH=\"11\" ALIGN=\"middle\"><INPUT TYPE=\"radio\" NAME=\"qid\" VALUE=\"$qid\"></TD>";
      echo "  <TD BGCOLOR=\"#c0c0c0\"><A HREF=\"admin.php?op=submission&qid=$qid\">$subject</A></TD>";
      echo "  <TD BGCOLOR=\"#c0c0c0\">$datetime</TD>";
      echo " </TR>";
      $dummy++;
    }
  }

  if ($dummy < 1) {
    echo " <TR><TD ALIGN=\"center\" BGCOLOR=\"#c0c0c0\" COLSPAN=\"3\">There are currently <B>no</B> new submissions available.</TD></TR>";
  } 
  else {
    echo " <TR><TD COLSPAN=\"3\"><INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Delete article\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"View article\"></TD></TR>";
  }
  
  echo " <TR><TD COLSPAN=\"3\">Article ID: <INPUT TYPE=\"text\" NAME=\"sid\" SIZE=\"5\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Edit article\"></TD></TR>";
  echo " <TR><TD COLSPAN=\"3\"><A HREF=\"admin.php?op=news_admin_write\">Write and post an article as administrator.</A></TD></TR>";
  echo "</TABLE></FORM>";
 
  mysql_free_result($result);
  ?>
  <HR>
  <LI><A HREF="admin.php?op=blocks">Edit global blocks on main page.</A></LI><BR>
  <I>Allows you to update the content blocks on the main page.</I>
  <HR>
  <LI><A HREF="admin.php?op=user_overview">Edit user accounts.</A></LI><BR>
  <I>Add, delete, block, view and update user accounts.</I>
  <HR>
  <LI><A HREF="admin.php?op=mod_authors">Edit adminstrators accounts.</A></LI><BR>
  <HR>
  <LI><A HREF="admin.php?op=backup">Backup MySQL tables.</A></LI><BR>
  <I>Will mail a backup of the MySQL database to '<? echo $notify_email; ?>'.</I>
  <HR>
  <LI><A HREF="webboard.php?section=webboard">Webboard manager.</A></LI><BR>
  <I>Allows you to delete flamebait post or threads from the webboard.</I>
  <HR>
  <LI><A HREF="poll.php?section=poll">Poll manager.</A></LI><BR>
  <I>Install, delete or update polls.</I>
  <HR>
  <LI><A HREF="refer.php?section=refer">Referring site manager.</A></LI><BR>
  <I>Edit, block or delete sites that participate with the referring site program.</I>
  <HR>
  <LI><A HREF="">Resource manager.</A> (not implemented yet)</LI><BR>
  <I>Allows admins to maintain a list of resources, news sites and other interesting start points to start their search for news.</I>
  <HR>
  <LI><A HREF="admin.php?op=logout">Logout</A></LI>
  <?PHP
  $theme->footer();
}

/*********************************************************/
/* block functions                                       */
/*********************************************************/

function block_overview() {
  include "theme.inc";
  $theme->header();

  dbconnect();
  $result = mysql_query("SELECT id, title, content FROM blocks");

  if (mysql_num_rows($result) > 0) {
    while(list($id, $title, $content) = mysql_fetch_array($result)) {
      echo "<FORM ACTION=\"admin.php\" METHOD=\"post\">";
      echo " <B>Title:</B><BR>";
      echo " <INPUT TYPE=\"text\" NAME=\"title\" SIZE=\"60\" MAXLENGTH=\"60\" VALUE=\"$title\">";
      echo " <BR><BR>";

      echo " <B>Content:</B><BR>";
      echo " <TEXTAREA WRAP=\"virtual\" COLS=\"60\" ROWS=\"8\" NAME=\"content\">$content</TEXTAREA>";
      echo " <BR><BR>";

      echo " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">";
      echo " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update block\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Delete block\">";
      echo "</FORM>";
    }
  } 
  ?>
  <HR>
  <FORM ACTION="admin.php" METHOD="post">
   <B>Title:</B><BR>
   <INPUT TYPE="text" NAME="title" SIZE="60" MAXLENGTH="60">
   <BR><BR>
 
   <B>Content:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="8" NAME="content"></TEXTAREA>
   <BR><BR>
   <INPUT TYPE="submit" NAME="op" VALUE="Add new block">
  </FORM>

  <?php
  $theme->footer();
}

function block_add($title, $content) {
  dbconnect();
  mysql_query("INSERT INTO blocks VALUES (NULL,'$aid','$title','$content')");
  header("Location: admin.php?op=main");
}

function block_update($id, $title, $content) {
  dbconnect();
  mysql_query("update blocks set title='$title', content='$content' where id=$id");
  header("Location: admin.php?op=main");
}

function block_delete($id) {
  dbconnect();
  mysql_query("DELETE FROM blocks WHERE id = '$id'");
  header("Location: admin.php?op=main");
}


/*********************************************************/
/* user account functions                                */
/*********************************************************/

function user_overview() {
  include "theme.inc";
  $theme->header();
  dbconnect();
  $result = mysql_query("SELECT * FROM users");
  while ($account = mysql_fetch_object($result)) {
    $count++;
    print "$count. $account->uname [ <A HREF=\"account.php?op=userinfo&uname=$account->uname\">view</A> | edit | block | delete ]<BR>";
  }
  $theme->footer();
}

/*********************************************************/
/* article functions                                      */
/*********************************************************/
function news_queue_delete($qid) {
  dbconnect();
  $result = mysql_query("DELETE FROM queue WHERE qid = $qid");
  header("Location: admin.php?op=main");
}


function news_display($qid) {
  global $user, $subject, $article;
  
  include "config.inc";
  include "header.inc";
  
  dbconnect();
  
  if (isset($qid)) $result = mysql_query("SELECT qid, uid, uname, timestamp, subject, abstract, article, category FROM queue WHERE qid = $qid");
  else $result = mysql_query("SELECT qid, uid, uname, timestamp, subject, abstract, article, category FROM queue LIMIT 1");
  
  list($qid, $uid, $uname, $timestamp, $subject, $abstract, $article, $category) = mysql_fetch_row($result);
  mysql_free_result($result);

  $subject = stripslashes($subject);
  $abstract = stripslashes($abstract);
  $article = stripslashes($article);

  $theme->preview("", $uname, $timestamp, $subject, "", $abstract, "", $article);
  ?>

  <FORM ACTION="admin.php" METHOD="post">

  <P>
   <B>Author or poster:</B><br>
   <INPUT TYPE="text" NAME="author" SIZE="50" VALUE="<?PHP echo "$uname"; ?>">
  </P>

  <P>
   <B>Subject:</B><BR>
   <INPUT TYPE="text" NAME="subject" SIZE="50" VALUE="<?PHP echo"$subject"; ?>">
  </P>

  <P>
   <B>Department:</B><BR>
   <INPUT TYPE="text" NAME="department" SIZE="50" VALUE=""> dept.<BR>
   <I>
    <FONT SIZE="2"> 
     Example departments: 
     <UL>
      <LI>we-saw-it-coming dept.</LI>
      <LI>don't-get-your-panties-in-a-knot dept.</LI>
      <LI>brain-melt dept.</LI>
      <LI>beats-the-heck-out-of-me dept.</LI>
     </UL>
    </FONT>
   </I>   
  </P>

  <P>
   <B>Category:</B><BR>
   <SELECT NAME="category">
   <?PHP
   for ($i = 0; $i < sizeof($categories); $i++) {
     echo "<OPTION VALUE=\"$categories[$i]\" ";
     if ($category == $categories[$i]) echo "SELECTED";
     echo ">$categories[$i]\n";
   }
  ?>
   </SELECT>
  </P>

  <P>
   <B>Author's abstract:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="8" NAME="abstract"><?PHP echo "$abstract"; ?></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page!</I></FONT>  
  </P>

  <P>
   <B>Editor's comments:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="5" NAME="comments"></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page after the abstract.</I></FONT>
  </P>

  <P>
   <B>Extended article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="12" NAME="article"><?PHP echo "$article"; ?></TEXTAREA><BR>
   <FONT SIZE="2">Will be displayed on the article's page when following the 'read more'-link.</FONT></I>
  </P>

  <INPUT TYPE="hidden" NAME="qid" VALUE="<?PHP echo "$qid"; ?>">
  <INPUT TYPE="hidden" NAME="uid" VALUE="<?PHP echo "$uid"; ?>">
  <INPUT TYPE="submit" NAME="op" VALUE="Delete article">
  <INPUT TYPE="submit" NAME="op" VALUE="Preview article"> 
  <INPUT TYPE="submit" NAME="op" VALUE="Post article">
  </FORM>

  <?PHP
  $theme->footer();
}

function news_preview($qid, $uid, $author, $subject, $department, $category, $abstract, $comments, $article) {
  global $user, $boxstuff, $aid;
  include "config.inc";
  include "theme.inc";

  $theme->header();

  $subject = stripslashes($subject);
  $agstract = stripslashes($abstract);
  $comments = stripslashes($comments);
  $article = stripslashes($article);

  $theme->preview($aid, $author, time(), $subject, $department, $abstract, $comments, $article);
  $theme->footer();
  ?>
  

  <FORM ACTION="admin.php" METHOD="post">

  <P>
   <B>Author or poster:</B><br>
   <INPUT TYPE="text" NAME="author" SIZE="50" VALUE="<?PHP echo "$author"; ?>">
  </P>

  <P>
   <B>Subject:</B><BR>
   <INPUT TYPE="text" NAME="subject" SIZE="50" VALUE="<?PHP echo"$subject"; ?>">
  </P>

  <P>
   <B>Department:</B><BR>
   <INPUT TYPE="text" NAME="department" SIZE="50" VALUE="<?PHP echo"$department"; ?>"> dept.<BR>
   <I><FONT SIZE="2"> 
    Example departments: 
    <UL>
     <LI>we-saw-it-coming dept.</LI>
     <LI>don't-get-your-panties-in-a-knot dept.</LI>
     <LI>brain-melt dept.</LI>
     <LI>beats-the-heck-out-of-me dept.</LI>
    </UL>
   </FONT></I>   
  </P>

  <P>
   <B>Category:</B><BR>
   <SELECT NAME="category">
   <?PHP
   for ($i = 0; $i < sizeof($categories); $i++) {
     echo "<OPTION VALUE=\"$categories[$i]\" ";
     if ($category == $categories[$i]) echo "SELECTED";
     echo ">$categories[$i]\n";
   }
  ?>
   </SELECT>
  </P>

  <P>
   <B>Author's abstract:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="8" NAME="abstract"><?PHP echo "$abstract"; ?></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page!</I></FONT>  
  </P>

  <P>
   <B>Editor's comments:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="5" NAME="comments"><? echo "$comments"; ?></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page after the abstract.</I></FONT>
  </P>

  <P>
   <B>Extended article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="12" NAME="article"><? echo "$article"; ?></TEXTAREA><BR>
   <I><FONT SIZE="2">Will be displayed on the article's page when following the 'read more'-link.</FONT></I>
  </P>

  <INPUT TYPE="hidden" NAME="qid" VALUE="<?PHP echo "$qid"; ?>">
  <INPUT TYPE="hidden" NAME="uid" VALUE="<?PHP echo "$uid"; ?>">
  <INPUT TYPE="submit" NAME="op" VALUE="Delete article">
  <INPUT TYPE="submit" NAME="op" VALUE="Preview article"> 
  <INPUT TYPE="submit" NAME="op" VALUE="Post article">
  </FORM>
 
  <?PHP
   $theme->footer();
}

function news_post($qid, $uid, $author, $subject, $department, $category, $abstract, $comments, $article) {
  global $aid;
  dbconnect();
  
  if ($uid == -1) $author = "";

  $subject = stripslashes(FixQuotes($subject));
  $abstract = stripslashes(FixQuotes($abstract));
  $comments = stripslashes(FixQuotes($comments));
  $article = stripslashes(FixQuotes($article));

  $result = mysql_query("INSERT INTO stories (sid, aid, subject, time, abstract, comments, article, category, informant, department) VALUES (NULL, '$aid', '$subject', '". time() ."', '$abstract', '$comments', '$article', '$category', '$author', '$department')");

  ### remove article from queue:
  news_queue_delete($qid);
}

function news_edit($sid) {
  global $user, $subject, $abstract, $comments, $article;

  include "theme.inc";
  include "config.inc";
  
  $theme->header();

  dbconnect();

  $result = mysql_query("SELECT * FROM stories where sid = $sid");
  $article = mysql_fetch_object($result); 
  mysql_free_result($result);

  $theme->preview($article->author, $article->informant, $article->time, $article->subject, $article->department, $article->abstract, $article->comments, $article->article);

  ?>

  <FORM ACTION="admin.php" METHOD="post">

  <P>
   <B>Author or poster:</B><BR>
   <INPUT TYPE="text" NAME="author" SIZE="50" VALUE="<?PHP echo "$article->aid"; ?>">
  </P>

  <P>
   <B>Subject:</B><BR>
   <INPUT TYPE="text" NAME="subject" SIZE="50" VALUE="<?PHP echo"$article->subject"; ?>">
  </P>

  <P>
   <B>Department:</B><BR>
   <INPUT TYPE="text" NAME="department" SIZE="50" VALUE="<?PHP echo"$article->department"; ?>"> dept.<BR>
   <I><FONT SIZE="2"> 
    Example departments: 
    <UL>
     <LI>we-saw-it-coming dept.</LI>
     <LI>don't-get-your-panties-in-a-knot dept.</LI>
     <LI>brain-melt dept.</LI>
     <LI>beats-the-heck-out-of-me dept.</LI>
    </UL>
   </FONT></I>   
  </P>

  <P>
   <B>Category:</B><BR>
   <SELECT NAME="category">
   <?PHP
   for ($i = 0; $i < sizeof($categories); $i++) {
     echo "<OPTION VALUE=\"$categories[$i]\" ";
     if ($article->category == $categories[$i]) echo "SELECTED";
     echo ">$categories[$i]\n";
   }
  ?>
   </SELECT>
  </P>

  <P>
   <B>Author's abstract:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="8" NAME="abstract"><?PHP echo "$article->abstract"; ?></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page!</I></FONT>  
  </P>

  <P>
   <B>Editor's comments:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="5" NAME="comments"><? echo "$article->comments"; ?></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page after the abstract.</I></FONT>
  </P>

  <P>
   <B>Extended article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="12" NAME="article"><? echo "$article->article"; ?></TEXTAREA><BR>
   <I><FONT SIZE="2">Will be displayed on the article's page when following the 'read more'-link.</FONT></I>
  </P>


  <INPUT TYPE="hidden" NAME="sid" SIZE=60 VALUE="<?PHP echo"$sid"; ?>">
  <INPUT TYPE="submit" NAME="op" VALUE="Update article"></FORM>

  <?PHP
  $theme->footer();
}

function news_update($sid, $subject, $category, $department, $abstract, $comments, $article) {
  global $aid;
  dbconnect();
  $subject = stripslashes(FixQuotes($subject));
  $department = stripslashes(FixQuotes($department));
  $abstract = stripslashes(FixQuotes($abstract));
  $comments = stripslashes(FixQuotes($comments));
  $article = stripslashes(FixQuotes($article));
  mysql_query("UPDATE stories SET subject = '$subject', category = '$category', department = '$department', abstract = '$abstract', comments = '$comments', article = '$article' WHERE sid = $sid");
  header("Location: admin.php?op=main");
}

function news_admin_write() {
  include "theme.inc";
  include "config.inc";
  dbconnect();

  $theme->header();
  ?>

  <FORM ACTION="admin.php" METHOD="post">

  <P>
   <B>Subject:</B><BR>
   <INPUT TYPE="text" NAME="subject" SIZE="50" VALUE="">
  </P>

  <P>
   <B>Department:</B><BR>
   <INPUT TYPE="text" NAME="department" SIZE="50" VALUE=""> dept.<BR>
   <I>
    <FONT SIZE="2"> 
     Example departments: 
     <UL>
      <LI>we-saw-it-coming dept.</LI>
      <LI>don't-get-your-panties-in-a-knot dept.</LI>
      <LI>brain-melt dept.</LI>
      <LI>beats-the-heck-out-of-me dept.</LI>
     </UL>
    </FONT>
   </I>   
  </P>

  <P>
   <B>Category:</B><BR>
   <SELECT NAME="category">
   <?PHP
   for ($i = 0; $i < sizeof($categories); $i++) {
     echo "<OPTION VALUE=\"$categories[$i]\">$categories[$i]\n";
   }
  ?>
   </SELECT>
  </P>

  <P>
   <B>Introduction of article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="7" NAME="abstract"></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page.</I></FONT>  
  </P>

  <P>
   <B>Rest of article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="8" NAME="article"></TEXTAREA><BR>
   <I><FONT SIZE="2">Will be displayed on the article's page when following the 'read more'-link.</FONT></I>
  </P>

  <INPUT TYPE="submit" NAME="op" VALUE="Preview admin article"> 
  <INPUT TYPE="submit" NAME="op" VALUE="Post admin article">
  </FORM>
  <?
  $theme->footer();
}

function news_admin_preview($subject, $category, $department, $abstract, $article) {
  global $aid;
  include "theme.inc";
  include "config.inc";
  $subject = stripslashes($subject);
  $intro = stripslashes($intro);
  $rest = stripslashes($rest);

  $theme->header();
  $theme->preview("", $aid, $time, $subject, "", $abstract, "", $article);
  ?>

  <FORM ACTION="admin.php" METHOD="post">

  <P>
   <B>Subject:</B><BR>
   <INPUT TYPE="text" NAME="subject" SIZE="50" VALUE="<? echo "$subject"; ?>">
  </P>

  <P>
   <B>Department:</B><BR>
   <INPUT TYPE="text" NAME="department" SIZE="50" VALUE="<? echo "$department"; ?>"> dept.<BR>
   <I>
    <FONT SIZE="2"> 
     Example departments: 
     <UL>
      <LI>we-saw-it-coming dept.</LI>
      <LI>don't-get-your-panties-in-a-knot dept.</LI>
      <LI>brain-melt dept.</LI>
      <LI>beats-the-heck-out-of-me dept.</LI>
     </UL>
    </FONT>
   </I>   
  </P>

  <P>
   <B>Category:</B><BR>
   <SELECT NAME="category">
   <?PHP
   for ($i = 0; $i < sizeof($categories); $i++) {
     echo "<OPTION VALUE=\"$categories[$i]\" ";
     if ($category == $categories[$i]) echo "SELECTED";
     echo ">$categories[$i]\n";
   }
  ?>
   </SELECT>
  </P>

  <P>
   <B>Introduction of article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="7" NAME="abstract"><? echo "$abstract"; ?></TEXTAREA><BR>
   <FONT SIZE="2"><I>Will be displayed on the main page.</I></FONT>  
  </P>

  <P>
   <B>Rest of article:</B><BR>
   <TEXTAREA WRAP="virtual" COLS="60" ROWS="8" NAME="article"><? echo "$article"; ?></TEXTAREA><BR>
   <I><FONT SIZE="2">Will be displayed on the article's page when following the 'read more'-link.</FONT></I>
  </P>

  <INPUT TYPE="submit" NAME="op" VALUE="Preview admin article"> 
  <INPUT TYPE="submit" NAME="op" VALUE="Post admin article">
  </FORM>

  <?
  $theme->footer();
}

function news_admin_post($subject, $category, $department, $abstract, $article, $category) {
  global $aid;
  dbconnect();
  
  $subject = stripslashes(FixQuotes($subject));
  $intro = stripslashes(FixQuotes($intro));
  $rest = stripslashes(FixQuotes($rest));
  
  $result = mysql_query("INSERT INTO stories VALUES (NULL, '$aid', '$subject', '". time() ."', '$abstract', '', '$article', '$category', '$aid', '$department')");
  if (!$result) {
    echo mysql_errno(). ": ".mysql_error(). "<BR>";
    exit();
  }
  header("Location: admin.php?op=main");
}

/*********************************************************/
/* admin admining                                        */
/*********************************************************/

function displayadmins() {
	$titlebar = "<b>current authors</b>";
	include "header.inc";
	dbconnect();
	$result = mysql_query("select aid from authors");
	echo "<table border=1>";
	while(list($a_aid) = mysql_fetch_row($result)) {
		echo "<tr><td>$a_aid</td>";
		echo "<td><a href=\"$that_url/admin.php?op=modifyadmin&chng_aid=$a_aid\">Modify Info</a></td>";
		echo "<td><a href=\"$that_url/admin.php?op=deladmin&del_aid=$a_aid\">Delete Author</a></td></tr>";
	}
	echo "</table>";
	echo "<form action=\"$that_url/admin.php\" method=\"post\">";
	echo "Handle: <INPUT TYPE=\"text\" NAME=\"add_aid\" size=30 maxlength=30><br>";
	echo "Name: 	<INPUT TYPE=\"text\" NAME=\"add_name\" size=30 maxlength=60><br>";
	echo "Email: <INPUT TYPE=\"text\" NAME=\"add_email\" size=30 maxlength=60><br>";
	echo "URL: <INPUT TYPE=\"text\" NAME=\"add_url\" size=30 maxlength=60><br>";
	echo "Password: <INPUT TYPE=\"text\" NAME=\"add_pwd\" size=12 maxlength=12><br>";
	echo "	<INPUT TYPE=submit NAME=op VALUE=\"Add author\"></form>";
	include "footer.inc";
}

function modifyadmin($chng_aid) {
	$titlebar = "<b>update $chng_aid</b>";
	include "header.inc";
	dbconnect();
	$result = mysql_query("select aid, name, url, email, pwd from authors where aid='$chng_aid'");
	list($chng_aid, $chng_name, $chng_url, $chng_email, $chng_pwd) = mysql_fetch_row($result);
	echo "<form action=\"admin.php\" method=\"post\">";
	echo "Name: $chng_name<INPUT TYPE=\"hidden\" NAME=\"chng_name\" VALUE=\"$chng_name\"><br>";
	echo "Handle: <INPUT TYPE=\"text\" NAME=\"chng_aid\" VALUE=\"$chng_aid\"><br>";
	echo "Email: <INPUT TYPE=\"text\" NAME=\"chng_email\" VALUE=\"$chng_email\" size=30 maxlength=60><br>";
	echo "URL: <INPUT TYPE=\"text\" NAME=\"chng_url\" VALUE=\"$chng_url\" size=30 maxlength=60><br>";
	echo "Password: <INPUT TYPE=\"password\" NAME=\"chng_pwd\" VALUE=\"$chng_pwd\" size=12 maxlength=12><br>";
	echo "Retype Password: <INPUT TYPE=\"password\" NAME=\"chng_pwd2\" size=12 maxlength=12> (for changes only)<br>";
	echo "	<INPUT TYPE=submit NAME=op VALUE=\"Update Author\"></form>";
	include "footer.inc";
}

function updateadmin($chng_aid, $chng_name, $chng_email, $chng_url, $chng_pwd, $chng_pwd2) {
	if ($chng_pwd2 != "") {
		if($chng_pwd != $chng_pwd2) {
			$titlebar = "<b>bad pass</b>";
			include "header.inc";
			echo "Sorry, the new passwords do not match. Click back and try again";
			include "footer.inc";
			exit;
		}
		dbconnect();
		$result = mysql_query("update authors set aid='$chng_aid', email='$chng_email', url='$chng_url', pwd='$chng_pwd' where NAME='$chng_name'");
		header("Location: admin.php?op=main");
	} else {
		dbconnect();
		$result = mysql_query("update authors set aid='$chng_aid', email='$chng_email', url='$chng_url' where NAME='$chng_name'");
		header("Location: admin.php?op=main");
	}
}


if ($admin) {
  switch($op) {
    case "main":
      main();
      break;
    case "blocks":
      block_overview();
      break;
    case "Add new block":
      block_add($title, $content);
      break;
    case "Delete block":
      block_delete($id);
      break;
    case "Update block":
      block_update($id, $title, $content);
      break;
    case "submission":
      // fall through
    case "View article":
      news_display($qid);
      break;
    case "Preview article":
      news_preview($qid, $uid, $author, $subject, $department, $category, $abstract, $comments, $article);
      break;
    case "Post article":
      news_post($qid, $uid, $author, $subject, $department, $category, $abstract, $comments, $article);
      break;
    case "Edit article":
      news_edit($sid);
      break;
    case "Update article":
      news_update($sid, $subject, $category, $department, $abstract, $comments, $article);
      break;
    case "Delete article":
      news_queue_delete($qid);
      break;
    case "news_admin_write":
      news_admin_write($sid);
      break;
    case "Preview admin article":
      news_admin_preview($subject, $category, $department, $abstract, $article);
      break;
    case "Post admin article":
      news_admin_post($subject, $category, $department, $abstract, $article);
      break;
    case "mod_authors":
      displayadmins();
      break;
    case "modifyadmin":
      modifyadmin($chng_aid);
      break;
    case "Update author":
      updateadmin($chng_aid, $chng_name, $chng_email, $chng_url, $chng_pwd, $chng_pwd2);
      break;
    case "Add author":
      dbconnect();
      $result = mysql_query("INSERT INTO authors VALUES ('$add_aid','$add_name','$add_url','$add_email','$add_pwd')");
      if (!$result) {
        echo mysql_errno(). ": ".mysql_error(). "<br>"; return;
      }
      header("Location: $that_url/admin.php?op=main");
      break;
    case "deladmin":
      include "header.inc";
      echo "Are you sure you want to delete $del_aid?<br>";
      echo "<a href=\"$that_url/admin.php?op=deladminconf&del_aid=$del_aid\">Yes</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"$that_url/admin.php?op=main\">No</a>";
      include "footer.inc";
      break;
    case "deladminconf":
      dbconnect();
      mysql_query("delete from authors where aid='$del_aid'");
      header("Location: $that_url/admin.php?op=main");
      break;
    case "create":
      poll_createPoll();
      break;
    case "createPosted":
      poll_createPosted();
      break;
    case "remove":
      poll_removePoll();
      break;
    case "removePosted":
      poll_removePosted();
      break;
    case "user_overview":
      user_overview();
      break;
    case "backup":
      backup();
      main();
      break;    
    case "view": 
      poll_viewPoll();
      break;
    case "viewPosted":
      poll_viewPosted();
      break;
    case "logout":
      logout();
      break;
    default:
      main();
      break;
  }
} else {
  login();
}
?>