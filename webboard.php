<? 

include "functions.inc";
include "theme.inc";

$theme->header();
 
### parameters:
$timeout = 70000;
$width = "100%";

#####
# Syntax.......: text2html(number);
# Description..: Converst ascii text into HTML.
#
function text2html($text) {
  return nl2br(stripslashes(trim($text)));
}

function displayThread($id, $current = 0) {
  global $PHP_SELF, $timeout, $new, $theme;
  
  $query = "SELECT * FROM webboard WHERE topic_id = $id";
  $result = mysql_query($query);

  if (mysql_num_rows($result)) {
    ### fields from MySQL table:
    $author = text2html(mysql_result($result, 0, "author"));
    $subject = text2html(mysql_result($result, 0, "subject"));
    $create_dt = mysql_result($result, 0, "create_dt");
    $date = date("D, M d - H:i:s", $create_dt);

    ### highlight current post:
    if ($id == $current) print "<FONT COLOR=\"$theme->hlcolor2\">";

    print "<UL>\n";

    ### indicate new post:
    if (time() - $create_dt > $timeout) print " <LI><A HREF=\"$PHP_SELF?display=$id\">$subject</A> by <B>$author</B> ($date)</LI>\n";
    else print " <LI><A HREF=\"$PHP_SELF?display=$id\">$subject</A> by <B>$author</B> ($date) &nbsp; <FONT COLOR=\"$theme->hlcolor2\">new</FONT></LI>\n";

    ### highlight current post:
    if ($id == $current)  print "</FONT>";
  }

  $query = "SELECT DISTINCT topic_id FROM webboard WHERE parent_id = $id ORDER BY create_dt ASC";
  $result = mysql_query($query);
  
  ### recursive call to process childeren:
  while ($row = mysql_fetch_object($result)) displayThread($row->topic_id, $current);

  print "</UL>\n";
}


#####
# Syntax.......: displayThreadedOverview(id)
# Description..: Displays all 'child'-posts of the 'root'-post specified by
#                start_id.  The posts are displayed in a typical threaded 
#                style.
#
function displayThreadedOverview($id = 0) {
  global $PHP_SELF, $active, $timeout, $new, $width, $theme;
   

  $current = getCurrentPosts();
  $total = getTotalPosts();

  $query = "SELECT DISTINCT root_id FROM webboard WHERE parent_id = $id ORDER BY create_dt DESC";
  $result = mysql_query($query);

  print "<TABLE BORDER=\"0\" CELLSPACING=\"2\" CELLPADDING=\"4\" WIDTH=\"$width\">\n";
  print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD>\n";
  print "  <TABLE BORDER=\"0\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
  print "   <TR>\n";
  print "    <TD ALIGN=\"left\"><FONT COLOR=\"$theme->fgcolor1\" SIZE=\"+1\"><B>Threaded overview</B></FONT></TD>\n";
  print "    <TD ALIGN=\"right\">[ <A NAME=\"top\"><A HREF=\"#post\"><FONT COLOR=\"$theme->hlcolor1\">post</FONT></A></A> | <A HREF=\"$PHP_SELF?threaded=0\"><FONT COLOR=\"$theme->hlcolor1\">chronological</FONT></A> ]</FONT></TD>\n";
  print "   </TR>\n";
  print "  </TABLE>\n";
  print " </TD></TR>\n";

  while ($row = mysql_fetch_object($result)) {
    print " <TR><TD BGCOLOR=\"$theme->bgcolor2\">\n";
    displayThread($row->root_id);
    print " </TD></TR>\n";
  }

  print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD>\n";
  print "  <TABLE BORDER=\"0\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
  print "   <TR>\n";
  print "    <TD ALIGN=\"left\"><FONT COLOR=\"$theme->fgcolor1\"[ <A HREF=\"javascript: history.back()\"><FONT COLOR=\"$theme->hlcolor1\">back</FONT></A> | <A HREF=\"/\"><FONT COLOR=\"$theme->hlcolor1\">home</FONT></A> | <A NAME=\"post\"><A HREF=\"#top\"><FONT COLOR=\"$theme->hlcolor1\">top</FONT></A></A> ]</FONT></TD>\n";
  print "    <TD ALIGN=\"center\"><FONT COLOR=\"$theme->fgcolor1\">[ current: $current | total: $total ]</FONT></TD>\n";
  print "    <TD ALIGN=\"right\"><FONT COLOR=\"$theme->fgcolor1\">[ <A HREF=\"$PHP_SELF?threaded=0\"><FONT COLOR=\"$theme->hlcolor1\">chronological</FONT></A> | <A HREF=\"$PHP_SELF?threaded=1\"><FONT COLOR=\"$theme->hlcolor1\">threaded</FONT></A> ]</FONT></TD>\n";
  print "   </TR>\n";
  print "  </TABLE>\n";
  print " </TD></TR>\n";
  print "</TABLE>\n"; 
}


#####
# Syntax.......: displayChronologicalOverview
# Description..: Displays all 'child'-posts of the 'root'-post specified by
#                start_id.  The posts are displayed chronological in a
#                typical mailing-list alike style.
#
function displayChronologicalOverview($id = 0) {
  global $PHP_SELF, $theme, $timeout, $width;

  $query = "SELECT DISTINCT topic_id, subject, message, author, create_dt FROM webboard ORDER BY create_dt DESC";
  $result = mysql_query($query);

  $current = getCurrentPosts();
  $total = getTotalPosts();

  print "<TABLE BORDER=\"0\" CELLSPACING=\"2\" CELLPADDING=\"4\" WIDTH=\"$width\">\n";
  print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD COLSPAN=\"3\">\n";
  print " <TABLE BORDER=\"0\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
  print "  <TR>\n";
  print "   <TD ALIGN=\"left\"><FONT COLOR=\"$theme->fgcolor1\" SIZE=\"+1\"><B>Chronological overview</B></FONT></TD>\n"; 
  print "   <TD ALIGN=\"right\"><FONT COLOR=\"$theme->fgcolor1\"[ <A NAME=\"top\"><A HREF=\"#post\"><FONT COLOR=\"$theme->hlcolor1\">post</FONT></A></A> | <A HREF=\"$PHP_SELF?threaded=1\"><FONT COLOR=\"$theme->hlcolor1\">threaded</FONT></A> ]</FONT></TD>\n";
  print "  </TR>\n";
  print " </TABLE>\n";
  print " </TD></TR>\n";

  while ($row = mysql_fetch_row($result)) {
    list($topic_id, $subject, $message, $author, $create_dt) = $row;
    $subject = text2html($subject);
    $author = text2html($author);
    $date = date("d/m/y - h:i:s", $create_dt);

    ### indicate new post:
    if (time() - $create_dt > $timeout) print "<TR><TD BGCOLOR=\"$theme->bgcolor2\"><A HREF=\"$PHP_SELF?display=$topic_id\">$subject</A></TD><TD BGCOLOR=\"$theme->bgcolor2\">$author</TD><TD BGCOLOR=\"$theme->bgcolor2\" NOWRAP>$date</TD><TD></TD></TR>\n";
    else print "<TR><TD BGCOLOR=\"$theme->bgcolor2\"><A HREF=\"$PHP_SELF?display=$topic_id\">$subject</A></TD><TD BGCOLOR=\"$$theme->bgcolor2\">$author</TD><TD BGCOLOR=\"$theme->bgcolor2\" NOWRAP>$date</TD><TD><FONT COLOR=\"$theme->hlcolor2\">new</FONT></TD></TR>\n";
  }
  
  print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD COLSPAN=\"3\">\n";
  print "  <TABLE BORDER=\"0\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
  print "   <TR>\n";
  print "    <TD ALIGN=\"left\"><FONT COLOR=\"$theme->fgcolor1\"[ <A HREF=\"javascript: history.back()\"><FONT COLOR=\"$theme->hlcolor1\">back</FONT></A> | <A HREF=\"/\"><FONT COLOR=\"$theme->hlcolor1\">home</FONT></A> | <A NAME=\"post\"><A HREF=\"#top\"><FONT COLOR=\"$theme->hlcolor1\">top</FONT></A></A> ]</FONT></TD>\n";
  print "    <TD ALIGN=\"center\"><FONT COLOR=\"$theme->fgcolor1\"[ current: $current | total: $total ]</FONT></TD>\n";
  print "    <TD ALIGN=\"right\"><FONT COLOR=\"$theme->fgcolor1\"[ <A HREF=\"$PHP_SELF?threaded=0\"><FONT COLOR=\"$theme->hlcolor1\">chronological</FONT></A> | <A HREF=\"$PHP_SELF?threaded=1\"><FONT COLOR=\"$theme->hlcolor1\">threaded</FONT></A> ]</FONT></TD>\n";
  print "   </TR>\n";
  print "  </TABLE>\n";
  print " </TD></TR>\n";
  print "</TABLE>\n"; 
}

#####
# Syntax.......: getRecentThreads
# Description..: 
#
function getRecentThreads($number = 5, $filename = "webboard.php") {
  global $timeout, $theme;

  $query = "SELECT * FROM webboard WHERE parent_id = 0 ORDER BY create_dt DESC LIMIT $number";
  $result = mysql_query($query);
  
  $rval = "<UL>\n";

  while ($object = mysql_fetch_object($result)) {
    ### fields from MySQL table:
    $topic_id = $object->topic_id;
    $author = text2html($object->author);
    $subject = text2html($object->subject);
    $create_dt = $object->create_dt;
    $date = date("d/m/y - h:i:s", $create_dt);
    $size = getThreadSize($object->topic_id);

    ### indicate new post:
    if (time() - $create_dt > $timeout) $rval .= " <LI><A HREF=\"$filename?display=$topic_id\">$subject</A> by <B>$author</B> ($date) [$size]</LI>\n";
    else $rval .= " <LI><A HREF=\"$filename?display=$topic_id\">$subject</A> by <B>$author</B> ($date) [$size] &nbsp; <FONT COLOR=\"$theme->hlcolor2\">new</FONT></LI>\n";
  }

  $rval .= "</UL>\n";

  return $rval;
}

#####
# Syntax.......:
# Description..:
#
function displayAdminOverview($id = 0) {
  global $PHP_SELF;

  $query = "SELECT DISTINCT t.topic_id, t.parent_id, t.root_id, t.subject, t.message, t.author, t.hostname, t.create_dt FROM webboard t, webboard r WHERE t.parent_id = $id ORDER BY create_dt DESC";
  $result = mysql_query($query);

  print "<UL>\n";    
  while ($row = mysql_fetch_row($result)) {
    list($topic_id, $parent_id, $root_id, $subject, $message, $author, $hostname, $create_dt) = $row;
    $date = date("D, M d - H:i:s", $create_dt);
    print " <LI><INPUT TYPE=\"checkbox\" NAME=\"delete\" VALUE=\"$topic_id\"> <A HREF=\"$PHP_SELF?display=$topic_id\">$subject</A> by <B>$author</B> ($date)\n";
    displayAdminOverview($topic_id);
  }
  print "</UL>\n";
}

#####
# Syntax.......: displayMessage(id)
# Description..:
#
function displayMessage($id = 0) {
  global $PHP_SELF, $theme, $width;

  $query = "SELECT * FROM webboard WHERE topic_id = $id";
  $result = mysql_query($query);
 
  if (mysql_num_rows($result)) {
    ### fields from MySQL table:
    $author = text2html(mysql_result($result, 0, "author"));
    $subject = text2html(mysql_result($result, 0, "subject"));
    $message = text2html(mysql_result($result, 0, "message"));
    $hostname = text2html(mysql_result($result, 0, "hostname"));
    $date = date("l, F d - h:i:s A", mysql_result($result, 0, "create_dt"));
    $topic_id = mysql_result($result, 0, "topic_id");
    $root_id = mysql_result($result, 0, "root_id");

    ### previous and next posts:
    $prev_msg = getPrevPost($topic_id);
    $next_msg = getNextPost($topic_id);

    ### previous and next threads:
    $next_thread = getNextThread($root_id);
    $prev_thread = getPrevThread($root_id);

    ### generate output table:
    print "<TABLE BORDER=\"0\" CELLPADDING=\"4\" WIDTH=\"$width\">\n";
    print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD COLSPAN=\"2\"><TABLE BORDER=\"0\" WIDTH=\"100%\"><TR><TD><FONT COLOR=\"$theme->fgcolor1\"><A NAME=\"top\">Current message</A></FONT></TD><TD ALIGN=\"right\"><FONT COLOR=\"$theme->fgcolor1\">[ <A HREF=\"$PHP_SELF?display=$prev_msg\"><FONT COLOR=\"$theme->hlcolor1\">previous message</FONT></A> | <A HREF=\"$PHP_SELF?display=$next_msg\"><FONT COLOR=\"$theme->hlcolor1\">next message</FONT></A> ]</FONT></TD></TR></TABLE></TD></TR>\n";
    print " <TR BGCOLOR=\"$theme->bgcolor2\"><TD COLSPAN=\"2\">Subject: <B>$subject</B></TD></TR>\n";
    print " <TR BGCOLOR=\"$theme->bgcolor2\"><TD><FONT COLOR=\"$theme->hlcolor2\">by <B>$author</B> on $date</FONT></TD><TD ALIGN=\"right\">Hostname/IP: $hostname</TD></TR>\n";
    print " <TR BGCOLOR=\"$theme->bgcolor2\"><TD COLSPAN=\"2\">$message</TD></TR>\n";
    print " <TR><TD COLSPAN=\"2\"></TD></TR>\n";
    print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD COLSPAN=\"2\"><TABLE BORDER=\"0\" WIDTH=\"100%\"><TR><TD><FONT COLOR=\"$theme->fgcolor1\">Current thread</FONT></TD><TD ALIGN=\"right\"><FONT COLOR=\"$theme->fgcolor1\">[ <A HREF=\"$PHP_SELF?display=$prev_thread\"><FONT COLOR=\"$theme->hlcolor1\">previous thread</FONT></A> | <A HREF=\"$PHP_SELF?display=$next_thread\"><FONT COLOR=\"$theme->hlcolor1\">next thread</FONT></A> ]</FONT></TD></TR></TABLE></TD></TR>\n";
    print " <TR BGCOLOR=\"$theme->bgcolor2\"><TD COLSPAN=\"2\">\n";    
    displayThread($root_id, $id);
    print " </TD></TR>\n";
    print " <TR BGCOLOR=\"$theme->bgcolor2\"><TD COLSPAN=\"2\"></TD></TR>\n";    
    print " <TR BGCOLOR=\"$theme->bgcolor1\"><TD COLSPAN=\"2\">\n";
    print "  <TABLE BORDER=\"0\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
    print "   <TR>\n";
    print "    <TD ALIGN=\"left\"><FONT COLOR=\"$theme->fgcolor1\">[ <A HREF=\"javascript: history.back()\"><FONT COLOR=\"$theme->hlcolor1\">back</FONT></A> | <A HREF=\"/\"><FONT COLOR=\"$theme->hlcolor1\">home</FONT></A> | <A HREF=\"#top\"><FONT COLOR=\"$theme->hlcolor1\">top</FONT></A> ]</FONT></TD>\n";
    print "    <TD ALIGN=\"center\">&nbsp;</TD>\n";
    print "    <TD ALIGN=\"right\"><FONT COLOR=\"$theme->fgcolor1\">[ <A HREF=\"$PHP_SELF?threaded=0\"><FONT COLOR=\"$theme->hlcolor1\">chronological</FONT></A> | <A HREF=\"$PHP_SELF?threaded=1\"><FONT COLOR=\"$theme->fgcolor1\">threaded</FONT></A> ]</FONT></TD>\n";
    print "   </TR>\n";
    print "  </TABLE>\n";
    print " </TD></TR>\n";
    print "</TABLE>\n"; 
    print "<BR><BR>\n"; 
  }
  else {
    print "<P><B>Error:</B> no such message in database.  The message you are looking for might have expired and does no longer exsist, or might have been explicitly removed by the webboard administrator.</P>";
  }
}

#####
# Syntax.......: printForm(id)
# Description..:
#
function printForm($id = 0, $author = "", $signature = "") {
  global $PHP_SELF;

  ### initialize variables:
  $parent_id = 0;
  $root_id = 0;
 
  if ($id) {
    $query = "SELECT * FROM webboard WHERE topic_id = $id";
    $result = mysql_query($query);

    if (mysql_num_rows($result)) {
      ### fields from MySQL table:
      $subject = text2html(mysql_result($result, 0, "subject"));
      $subject = "Re: $subject";
      $parent_id = $id;
      $root_id = mysql_result($result, 0, "root_id");
    }
  }

  print "<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?post\">\n";
  print " <TABLE BORDER=\"0\" CELLSPACING=\"10\">\n";
  print "  <TR><TD ALIGN=\"right\" VALIGN=\"top\">Author:</TD><TD>$author</TD></TR>\n";
  print "  <TR><TD ALIGN=\"right\" VALIGN=\"top\">Subject:</TD><TD><INPUT TYPE=\"text\" NAME=\"subject\" MAXLENGTH=\"75\" SIZE=\"50\" VALUE=\"$subject\"></TD></TR>\n";
  print "  <TR><TD ALIGN=\"right\" VALIGN=\"top\">Message:</TD><TD><TEXTAREA NAME=\"message\" COLS=\"45\" ROWS=\"10\" WRAP=\"virtual\">$signature</TEXTAREA></TR>\n";
  print "  <TR>\n";
  print "   <TD ALIGN=\"center\" COLSPAN=\"2\">\n";
  print "    <INPUT TYPE=\"hidden\" NAME=\"author\" VALUE=\"$author\">\n";
  print "    <INPUT TYPE=\"hidden\" NAME=\"parent_id\" VALUE=\"$parent_id\">\n";
  print "    <INPUT TYPE=\"hidden\" NAME=\"root_id\" VALUE=\"$root_id\">\n";
  print "    <INPUT TYPE=\"submit\" NAME=\"post\" VALUE=\"Post message\">\n";
  print "   </TD>\n";
  print "  </TR>\n";
  print " </TABLE>\n";
  print "</FORM>\n";
}

/*
#####
# Syntax.......: fixQuotes(text)
# Description..:
#
function fixQuotes ($what = "") {
  $what = ereg_replace("'","''",$what);
  $counter = 0;
  while (eregi("\\\\'", $what) && $counter < 10) { $what = ereg_replace("\\\\'","'",$what); }
  return $what;
}
*/

#####
# Syntax.......: postMessage(subject, author, message, parent_id, root_id, html_tags)
# Description..:
#
function postMessage ($subject="[no subject]", $author = "Anonymous Chicken", $message, $parent_id = 0, $root_id = 0, $html_tags = "0") {

  if ($html_tags) {
    $subject = fixQuotes($subject);
    $message = fixQuotes($message);
    $author = fixQuotes($author);
    $date = time();
  }
  else {
    $subject = fixQuotes(strip_tags($subject));
    $message = fixQuotes(strip_tags($message));
    $author = fixQuotes(strip_tags($author));
    $date = time();
  }

  ### [NT]-feature:
  if (!$message) $subject .= "&nbsp; [NT]";

  ### 'Anonymous Chicken'-feature:
  if (!$author) $author = "Anonymous Chicken";

  ### insert new post:
  $query = "INSERT INTO webboard (subject, message, parent_id, root_id, author, hostname, create_dt) VALUES ('$subject', '$message', $parent_id, $root_id, '$author', '".getenv("REMOTE_ADDR")."', $date)";

  $result = mysql_query($query);

  if (!$result) {
    print "<P><B>Error:</B> failed to perform query!</P>\n";
  }

  $result = mysql_query("select last_insert_id()");
  list($topic_id) = mysql_fetch_array($result);

  if (isset($topic_id) && ($topic_id > 0)) {
    if ($root_id == 0) {
       $root_id = $topic_id;
       mysql_query("UPDATE webboard SET root_id=$topic_id WHERE topic_id = $topic_id AND root_id=0");
    }
  }
  else {
    print "<P><B>Error:</B> failed to perform query!</P>\n";
  }

  return $topic_id;
}

#####
# Syntax.......: getTotalPosts()
# Description..: Returns the total number of posts that have passed the
#                weboard.
#
function getTotalPosts() {
  $query = "SELECT MAX(topic_id) FROM webboard";
  $result = mysql_query($query);
  if ($result) return mysql_result($result, 0);
}


function getThreadSize($id) {
  $query = "SELECT * FROM webboard WHERE parent_id = $id";
  $result = mysql_query($query);
  $size = 1;
  while ($thread = mysql_fetch_object($result)) { 
    $size += getThreadSize($thread->topic_id);
  }
  return $size;
}

#####
# Syntax.......: getNextPost()
# Description..: Returns the next post.
#
function getNextPost($id) {
  ### Resolve root_id of $id:
  $query = "SELECT root_id FROM webboard WHERE topic_id = $id";
  $result = mysql_query($query);
  $root_id = mysql_result($result, 0);
  
  ### Resolve next message:
  $query = "SELECT topic_id FROM webboard WHERE root_id = $root_id AND topic_id > $id ORDER BY create_dt";
  $result = mysql_query($query);
  if (mysql_num_rows($result)) return mysql_result($result, 0);
  else return 0;
}

#####
# Syntax.......: getPrevPost()
# Description..: Returns the next post.
#
function getPrevPost($id) {
  ### Resolve root_id of $id:
  $query = "SELECT root_id FROM webboard WHERE topic_id = $id";
  $result = mysql_query($query);
  $root_id = mysql_result($result, 0);
  
  ### Resolve next message:
  $query = "SELECT topic_id FROM webboard WHERE root_id = $root_id AND topic_id < $id ORDER BY create_dt DESC";
  $result = mysql_query($query);
  if (mysql_num_rows($result)) return mysql_result($result, 0);
  else return 0;
}

#####
# Syntax.......: getNextThread(root_id)
# Description..: Returns the next thread.
#
function getNextThread($root_id) {
  $query = "SELECT root_id FROM webboard WHERE root_id > $root_id ORDER BY root_id";  
  $result = mysql_query($query);
  if (mysql_num_rows($result)) return mysql_result($result, 0);
  else return 0;
}

#####
# Syntax.......: getPrevThread(root_id)
# Description..: Returns the previous thread.
#
function getPrevThread($root_id) {
  $query = "SELECT root_id FROM webboard WHERE root_id < $root_id ORDER BY root_id DESC";
  $result = mysql_query($query);
  if (mysql_num_rows($result)) return mysql_result($result, 0);
  else return 0;
}

#####
# Syntax.......: getCurrentPosts()
# Description..: Returns the total number of current/active posts.
#
function getCurrentPosts() {
  $query = "SELECT COUNT(topic_id) FROM webboard";
  $result = mysql_query($query);
  if ($result) return mysql_result($result, 0);
}

#####
# Syntax.......: deleteThread(id)
# Description..: Deletes a thread including all child threads.
#
function deleteThread($id) {
  ### delete thread:
  $query = "SELECT topic_id FROM webboard WHERE parent_id = $id";
  $result = mysql_query($query);

  while ($post = mysql_fetch_object($result)) {
    deleteThread($post->topic_id);
  }

  ### delete individual post:
  $query = "DELETE FROM webboard WHERE topic_id = $id";
  $result = mysql_query($query);
}

#####
# Syntax.......: expireThread(timout)
# Description..: Checks for expired threads and automatically deletes 
#                them (if any).
#
function expireThread($expire, $number = 40) {
  $query = "SELECT root_id, MAX(create_dt) FROM webboard GROUP BY root_id";
  $result = mysql_query($query);

  while (getCurrentPosts() > $number && $result && $row = mysql_fetch_row($result)) {
    list($root_id, $create_dt) = $row;
    if (time() - $create_dt > $expire) deleteThread($root_id);
  }  
}


function displayForm($id = "0") {
  global $anonymous, $login, $support, $subscribe, $bgcolor2, $bgcolor3, $cookie;

  if ($cookie[1]) printForm($id, $cookie[1]);
  else printForm($id, $anonymous);
}



$id = $display;

if ($id) {
  displayMessage($id);
  displayForm($id);
}
else if ($section == "policy") {
  themebox("Webboard policy", "<P>Webboards are normally used to post notices, hints, questions and such.  Messages ideally should be written such that others can read them and get some value in them.</P><P>Everyday we see posts from people who choose to swear, insult and threaten users on the webboard.  Therefor we track all IP addresses of people posting: we know who comes, what they look at, how long they stay and - last but not least - we have a valid e-mail address.  Please do not use profanity.  Everyone is entitled to their opinion, but refrain from posting insults.</P><P>If you are a webboard user and see an offensive post or are being victimed by someone on the webboard, please contact us immediately at <A HREF=\"mailto:info@projectx.mx.dk\">info@projectx.mx.dk</A>.</P><P>We do take our webboard policy serious and we won't hesitate to e-mail the internet provider of the abuser to advise them of the situation.  In addition we will block an abusers ProjectX account, his IP or even its entire ISP: as each internet user is assigned a unique IP address on the net, we can track people down and then 'screen' them out when they try to return.  In most cases it means blocking entire address blocks or even ISPs.</P><P>Think before you post.</P><P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>", 500);
}
else if ($post) {
  $id = postMessage($subject, $author, $message, $parent_id, $root_id, 1); 
  print "<P><FONT SIZE=\"+1\">Your message has been posted:</FONT></P>\n";
  displayMessage($id);
  displayForm($id);
}
else if (isset($threaded) && ($threaded == 0)) {
  displayChronologicalOverview();
  displayForm(); 
}
else if ($delete) {
  ### check permissions:
  if ($admin) {
    ### delete thread:
    deleteThread($delete);
    print "<FORM ACTION=\"$PHP_SELF?delete=1\" METHOD=\"POST\">\n";
    displayAdminOverview(0);
    print "<INPUT TYPE=\"submit\" VALUE=\"Delete\">\n";
    print "</FORM>\n";
  }
  else displayBox("Failed", "You don't have permission to access this section.");
}
else if ($section == "webboard") {
  ### display administrator overview:
  print "<FORM ACTION=\"$PHP_SELF?delete=1\" METHOD=\"POST\">\n";
  displayAdminOverview(0);
  print "<INPUT TYPE=\"submit\" VALUE=\"Delete\">\n";     
  print "</FORM>\n";
}
else {
  displayThreadedOverview();
  displayForm();
}

### Check to see if a certain thread has expired:
if (time() % 20 == 0) { 
  expireThread(302400);  // 604800 = 7 days
}
 
### Close connection with MySQL server/database:
mysql_close();

$theme->footer();

?>