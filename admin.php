<?

// TEMPORARY SECURITY PATCH:
if ($user->userid != "Dries") exit();

/*
 * Account administration:
 */

function account_display($order = "username") {
  $sort = array("ID" => "id", "fake e-mail address" => "fake_email", "homepage" => "url", "hostname" => "last_host", "last access date" => "last_access", "real e-mail address" => "real_email", "real name" => "name", "status" => "status", "theme" => "theme", "username" => "userid");
  $show = array("ID" => "id", "username" => "userid", "$order" => "$sort[$order]", "status" => "status");

  ### Perform query:
  $result = db_query("SELECT u.id, u.userid, u.$sort[$order], u.status FROM users u ORDER BY $sort[$order]");
  
  ### Generate output:
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"". (sizeof($show) + 1) ."\">\n";
  $output .= "   <FORM ACTION=\"admin.php?section=accounts\" METHOD=\"post\">\n";
  $output .= "    <SELECT NAME=\"order\">\n";
  foreach ($sort as $key=>$value) {
    $output .= "     <OPTION VALUE=\"$key\"". ($key == $order ? " SELECTED" : "") .">Sort by $key</OPTION>\n";
  }
  $output .= "    </SELECT>\n";
  $output .= "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update\">\n";
  $output .= "   </FORM>\n";
  $output .= "  </TH>\n";
  $output .= " </TR>\n";
  $output .= " <TR>\n";
  foreach ($show as $key=>$value) {
    $output .= " <TH>$key</TH>\n";
  }
  $output .= "  <TH>operations</TH>\n";
  $output .= " </TR>\n";

  while ($account = db_fetch_array($result)) {
    $output .= " <TR>\n";
    foreach ($show as $key=>$value) {
      switch($value) {
	case "real_email":
          $output .= "  <TD>". format_email_address($account[$value]) ."</TD>\n";
          break;
        case "last_access":
          $output .= "  <TD>". format_date($account[$value]) ."</TD>\n";
          break;
	case "status":         
          $output .= "  <TD ALIGN=\"center\"><I>todo</I></TD>\n";
          break;
        case "url":
          $output .= "  <TD>". format_url($account[$value]) ."</TD>\n";
          break;
	case "userid":
          $output .= "  <TD>". format_username($account[$value], 1) ."</TD>\n";
          break;
        default:
          $output .= "  <TD>". format_data($account[$value]) ."</TD>\n";
      }
    }
    $output .= "  <TD ALIGN=\"center\"><A HREF=\"admin.php?section=accounts&op=view&name=$account[userid]\">view</A></TD>\n";
    $output .= " </TR>\n";
  }
  $output .= "</TABLE>\n";

  print $output;
}

function account_stories($id) {
  $result = db_query("SELECT * FROM stories WHERE author = $id ORDER BY timestamp DESC");
  while ($story = db_fetch_object($result)) {
    $output .= "<LI><A HREF=\"discussion.php?id=$story->id\">$story->subject</A></LI>\n";
  }
  return $output;
}

function account_comments($id) {
  $result = db_query("SELECT * FROM comments WHERE author = $id ORDER BY timestamp DESC");
  while ($comment = db_fetch_object($result)) {
    $output .= "<LI><A HREF=\"discussion.php?id=$comment->sid&cid=$comment->cid&pid=$comment->pid\">$comment->subject</A></LI>\n";
  }
  return $output;
}

function account_view($name) {
  ### Perform query:
  $result = db_query("SELECT * FROM users WHERE userid = '$name'");

  if ($account = db_fetch_object($result)) {
    $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>ID:</B></TD><TD>$account->id</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Username:</B></TD><TD>$account->userid</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Real name:</B></TD><TD>". format_data($account->name) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Real e-mail address:</B></TD><TD>". format_email_address($account->real_email) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Fake e-mail address:</B></TD><TD>". format_data($account->fake_email) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>URL of homepage:</B></TD><TD>". format_url($account->url) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Last access:</B></TD><TD>". format_date($account->last_access) ." from $account->last_host</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Bio information:</B></TD><TD>". format_data($account->bio) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Signature:</B></TD><TD>". format_data($account->signature) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Theme:</B></TD><TD>". format_data($account->theme) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Submitted stories:</B></TD><TD>". format_data(account_stories($account->id)) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Submitted comments:</B></TD><TD>". format_data(account_comments($account->id)) ."</TD></TR>\n";
    $output .= "</TABLE>\n";
    print "$output";
  }
}

/*
 * Log administration:
 */
function log_display($order = "date") {
  $colors = array("#FFFFFF", "#FFFFFF", "#90EE90", "#CD5C5C");
  $fields = array("date" => "id DESC", "username" => "user", "location" => "location", "message" => "message DESC", "level" => "level DESC");

  ### Perform query:
  $result = db_query("SELECT l.*, u.userid FROM watchdog l LEFT JOIN users u ON l.user = u.id ORDER BY l.$fields[$order]");
 
  ### Generate output:
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"4\">\n";
  $output .= "   <FORM ACTION=\"admin.php?section=logs\" METHOD=\"post\">\n";
  $output .= "    <SELECT NAME=\"order\">\n";
  foreach ($fields as $key=>$value) {
    $output .= "     <OPTION VALUE=\"$key\"". ($key == $order ? " SELECTED" : "") .">Sort by $key</OPTION>\n";
  }
  $output .= "    </SELECT>\n";
  $output .= "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update\">\n";
  $output .= "   </FORM>\n";
  $output .= "  </TH>\n";
  $output .= " </TR>\n";
  $output .= " <TR>\n";
  $output .= "  <TH>date</TH>\n";
  $output .= "  <TH>user</TH>\n";
  $output .= "  <TH>message</TH>\n";
  $output .= "  <TH>operations</TH>\n";
  $output .= " </TR>\n";

  while ($log = db_fetch_object($result)) {
    $output .= " <TR BGCOLOR=\"". $colors[$log->level] ."\"><TD>". date("D d/m, H:m:s", $log->timestamp) ."</TD><TD ALIGN=\"center\">". format_username($log->userid, 1) ."</A></TD><TD>". substr($log->message, 0, 44) ."</TD><TD ALIGN=\"center\"><A HREF=\"admin.php?section=logs&op=view&id=$log->id\">more</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";

  print $output;
}

function log_view($id) {
  $result = db_query("SELECT l.*, u.userid FROM watchdog l LEFT JOIN users u ON l.user = u.id WHERE l.id = $id");

  if ($log = db_fetch_object($result)) {
    $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Level:</B></TD><TD>$log->level</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Date:</B></TD><TD>". format_date($log->timestamp, "extra large") ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>User:</B></TD><TD>". format_username($log->userid, 1) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Location:</B></TD><TD>$log->location</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Message:</B></TD><TD>$log->message</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Hostname:</B></TD><TD>$log->hostname</TD></TR>\n";
    $output .= "</TABLE>\n";
    print $output;
  }
}

/*
 * Ban administration:
 */

function ban_check($mask, $category) {
  $ban = ban_match($mask, $category);
  $output .= "". ($ban ? "Matched ban '<B>$ban->mask</B>' with reason: <I>$ban->reason</I>.<P>\n" : "No matching bans for '$mask'.<P>\n") ."";
  print $output;
}

function ban_new($mask, $category, $reason) {
  ban_add($mask, $category, $reason, &$message);
  $output .= "$message\n";
  print $output;
}

function ban_display($category = "") {
  global $type2index;

  ### initialize variable: 
  $category = $category ? $category : 1;

  ### Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = $category ORDER BY mask");
 
  ### Generate output:
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH COLSPAN=\"3\">\n";
  $output .= "   <FORM ACTION=\"admin.php?section=bans\" METHOD=\"post\">\n";
  $output .= "    <SELECT NAME=\"category\">\n";
  for (reset($type2index); $cur = current($type2index); next($type2index)) {
    $output .= "     <OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">Sort by ". key($type2index) ."</OPTION>\n";
  }
  $output .= "    </SELECT>\n";
  $output .= "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update\">\n";
  $output .= "   </FORM>\n";
  $output .= "  </TH>\n";
  $output .= " </TR>\n";
  $output .= " <TR>\n";
  $output .= "  <TH>mask</TH>\n";
  $output .= "  <TH>reason</TH>\n";
  $output .= "  <TH>operations</TH>\n";
  $output .= " </TR>\n";

  while ($ban = db_fetch_object($result)) {
    $output .= "  <TR><TD>$ban->mask</TD><TD>$ban->reason</TD><TD ALIGN=\"center\"><A HREF=\"admin.php?section=bans&op=delete&category=$category&id=$ban->id\">delete</A></TD></TR>\n";
  }
  
  $output .= " <TR><TD COLSPAN=\"3\"><SMALL>%: matches any number of characters, even zero characters.<BR>_: matches exactly one character.</SMALL></TD></TR>\n";
  $output .= "</TABLE>\n";
  $output .= "<BR><HR>\n";

  $output .= "<H3>Add new ban:</H3>\n";
  $output .= "<FORM ACTION=\"admin.php?section=bans\" METHOD=\"post\">\n";
  $output .= "<B>Banmask:</B><BR>\n";
  $output .= "<INPUT TYPE=\"text\" NAME=\"mask\" SIZE=\"35\"><P>\n";
  $output .= "<B>Type:</B><BR>\n";
  $output .= "<SELECT NAME=\"category\"\">\n";
  for (reset($type2index); $cur = current($type2index); next($type2index)) {
    $output .= "<OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type2index) ."</OPTION>\n";
  }
  $output .= "</SELECT><P>\n";
  $output .= "<B>Reason:</B><BR>\n";
  $output .= "<TEXTAREA NAME=\"reason\" COLS=\"35\" ROWS=\"5\"></TEXTAREA><P>\n";
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Add ban\"><BR>\n";
  $output .= "</FORM>\n";
  $output .= "<BR><HR>\n";

  $output .= "<H3>Ban check:</H3>\n";
  $output .= "<FORM ACTION=\"admin.php?section=bans\" METHOD=\"post\">\n";
  $output .= "<B>Banmask:</B><BR>\n";
  $output .= "<INPUT TYPE=\"text\" NAME=\"mask\" SIZE=\"35\"><P>\n";
  $output .= "<B>Type:</B><BR>\n";
  $output .= "<SELECT NAME=\"category\"\">\n";
  for (reset($type2index); $cur = current($type2index); next($type2index)) {
    $output .= "<OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type2index) ."</OPTION>\n";
  }
  $output .= "</SELECT><P>\n";
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Check ban\"><BR>\n";
  $output .= "</FORM>\n";

  print $output;
}

/*
 * Comments administration:
 */

function comment_edit($id) {
  $result = db_query("SELECT c.*, u.userid FROM comments c LEFT JOIN users u ON c.author = u.id WHERE c.cid = $id");

  $comment = db_fetch_object($result);

  $output .= "<FORM ACTION=\"admin.php?section=comments&op=save&id=$id\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Author:</B><BR>\n";
  $output .= " ". format_username($comment->userid, 1) ."\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" VALUE=\"". stripslashes($comment->subject) ."\"><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= "<B>Comment:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"comment\">". stripslashes($comment->comment) ."</TEXTAREA><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Save comment\">\n";
  $output .= "</P>\n";
  $output .= "</FORM>\n";
  
  print $output;
}

function comment_save($id, $subject, $comment) {
  db_query("UPDATE comments SET subject = '". addslashes($subject) ."', comment = '". addslashes($comment) ."' WHERE cid = $id");
  watchdog(1, "modified comment `$subject'.");
}

function comment_display($order = "date") {
  ### Initialize variables:
  $fields = array("author" => "author", "date" => "timestamp DESC", "subject" => "subject");

  ### Perform SQL query:
  $result = db_query("SELECT c.*, u.userid FROM comments c LEFT JOIN users u ON u.id = c.author ORDER BY c.$fields[$order] LIMIT 50");
   
  ### Display stories:
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"5\">\n";
  $output .= "   <FORM ACTION=\"admin.php?section=comments\" METHOD=\"post\">\n";
  $output .= "    <SELECT NAME=\"order\">\n";
  foreach ($fields as $key=>$value) {
    $output .= "     <OPTION VALUE=\"$key\"". ($key == $order ? " SELECTED" : "") .">Sort by $key</OPTION>\n";
  }
  $output .= "    </SELECT>\n";
  $output .= "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update\">\n";
  $output .= "   </FORM>\n";
  $output .= "  </TH>\n";
  $output .= " </TR>\n";

  $output .= " <TR>\n";
  $output .= "  <TH>subject</TH>\n";
  $output .= "  <TH>author</TH>\n";
  $output .= "  <TH>operations</TH>\n";
  $output .= " </TR>\n";

  while ($comment = db_fetch_object($result)) {
    $output .= " <TR><TD><A HREF=\"discussion.php?id=$comment->sid&cid=$comment->cid&pid=$comment->pid\">$comment->subject</A></TD><TD>". format_username($comment->userid, 1) ."</TD><TD ALIGN=\"center\"><A HREF=\"admin.php?section=comments&op=edit&id=$comment->cid\">edit</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";
 
  print $output;
}

/*
 * Statistics administration:
 */
function stats_display() {
 #
  # Story statistics:
  #
  $result = db_query("SELECT s.subject, c.sid, COUNT(c.sid) AS count, u.userid FROM comments c, stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = c.sid GROUP BY c.sid ORDER BY count DESC LIMIT 20;");
  while ($stat = db_fetch_object($result)) $output1 .= "<I><A HREF=\"discussion.php?id=$stat->sid\">$stat->subject</A></I> by ". format_username($stat->userid, 1) .": ". format_plural($stat->count, "comment", "comments") ."<BR>\n";
  admin_box("Story statistics", $output1);

  #
  # Poster statistics:
  #
  $result = db_query("SELECT u.userid, COUNT(s.author) AS count FROM stories s LEFT JOIN users u ON s.author = u.id GROUP BY s.author ORDER BY count DESC LIMIT 20");
  while ($stat = db_fetch_object($result)) $output2 .= "". format_username($stat->userid) .": ". format_plural($stat->count, "story", "stories") ."<BR>\n";
  admin_box("Poster statistics", $output2);

  #
  # Category statistics:
  #
  $result = db_query("SELECT category, COUNT(category) AS count FROM stories GROUP by category ORDER BY count DESC");
  while ($stat = db_fetch_object($result)) $output3 .= "$stat->category: ". format_plural($stat->count, "story", "stories") ."<BR>\n";
  admin_box("Category statistics", $output3);

  #
  # Theme statistics:
  #
  $result = db_query("SELECT theme, COUNT(id) AS count FROM users GROUP BY theme ORDER BY count DESC");
  while ($stat = db_fetch_object($result)) $output4 .= "<I>$stat->theme</I>-theme: ". format_plural($stat->count, "user", "users") ."<BR>\n";
  admin_box("Theme statistics", $output4);
}

/*
 * Diary administration:
 */
function diary_edit($id) {
  $result = db_query("SELECT d.*, u.userid FROM diaries d LEFT JOIN users u ON d.author = u.id WHERE d.id = $id");

  $diary = db_fetch_object($result);

  $output .= "<FORM ACTION=\"admin.php?section=diaries&op=save&id=$id\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Author:</B><BR>\n";
  $output .= " ". format_username($diary->userid, 1) ."\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= "<B>Diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"text\">". stripslashes($diary->text) ."</TEXTAREA><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Save diary entry\">\n";
  $output .= "</P>\n";
  $output .= "</FORM>\n";
  
  print $output;
}

function diary_save($id, $text) {
  db_query("UPDATE diaries SET text = '". addslashes($text) ."' WHERE id = $id");
  watchdog(1, "modified diary entry #$id.");
}

function diary_display($order = "date") {
  ### Initialize variables:
  $fields = array("author" => "author", "date" => "timestamp DESC");

  ### Perform SQL query:
  $result = db_query("SELECT d.*, u.userid FROM diaries d LEFT JOIN users u ON u.id = d.author ORDER BY d.$fields[$order] LIMIT 50");
   
  ### Display stories:
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"5\">\n";
  $output .= "   <FORM ACTION=\"admin.php?section=diaries\" METHOD=\"post\">\n";
  $output .= "    <SELECT NAME=\"order\">\n";
  foreach ($fields as $key=>$value) {
    $output .= "     <OPTION VALUE=\"$key\"". ($key == $order ? " SELECTED" : "") .">Sort by $key</OPTION>\n";
  }
  $output .= "    </SELECT>\n";
  $output .= "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update\">\n";
  $output .= "   </FORM>\n";
  $output .= "  </TH>\n";
  $output .= " </TR>\n";

  $output .= " <TR>\n";
  $output .= "  <TH>subject</TH>\n";
  $output .= "  <TH>author</TH>\n";
  $output .= "  <TH>operations</TH>\n";
  $output .= " </TR>\n";

  while ($diary = db_fetch_object($result)) {
    $output .= " <TR><TD><A HREF=\"diary.php?op=view&name=$diary->userid\">$diary->userid on ". format_date($diary->date, "small") ."</A></TD><TD>". format_username($diary->userid, 1) ."</TD><TD ALIGN=\"center\"><A HREF=\"admin.php?section=diaries&op=edit&id=$diary->id\">edit</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";
 
  print $output;
}

/*
 * Home administration:
 */
function home_display() {
  print "<BR><BR><BIG><CENTER><A HREF=\"\">home</A></CENTER></BIG>\n";
}

/*
 * Misc administration:
 */
function misc_display() {
  print "<BIG>Upcoming features:</BIG>";
  print "<UL>\n";
  print " <LI>backup functionality</LI>\n";
  print " <LI>thresholds settings</LI>\n";
  print " <LI>...</LI>\n";
  print "</UL>\n";
}


/*
 * Story administration:
 */

function story_edit($id) {
  global $categories;

  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = $id");
  $story = db_fetch_object($result);

  $output .= "<FORM ACTION=\"admin.php?section=stories&op=save&id=$id\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Author:</B><BR>\n";
  $output .= " ". format_username($story->userid) ."\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" VALUE=\"". stripslashes($story->subject) ."\"><BR>\n";
  $output .= "</P>\n";

  $output .= "<P><B>Category:</B><BR>\n";
  $output .= " <SELECT NAME=\"category\">\n";
  for ($i = 0; $i < sizeof($categories); $i++) {
    $output .= "  <OPTION VALUE=\"$categories[$i]\" ";
    if ($story->category == $categories[$i]) $output .= "SELECTED";
    $output .= ">$categories[$i]</OPTION>\n";
  }
  $output .= "</SELECT>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= "<B>Abstract:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\">". stripslashes($story->abstract) ."</TEXTAREA><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= "<B>Editor's note/updates:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"updates\">". stripslashes($story->updates) ."</TEXTAREA><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Extended story:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\">". stripslashes($story->article) ."</TEXTAREA><BR>\n";
  $output .= "</P>\n";

  $output .= "<P><B>Status:</B><BR>\n";
  $output .= " <SELECT NAME=\"status\">\n";
  $output .= ($story->status == 0) ? "  <OPTION VALUE=\"0\" SELECTED>Deleted story</OPTION>\n" : "  <OPTION VALUE=\"0\">Deleted story </OPTION>\n";
  $output .= ($story->status == 1) ? "  <OPTION VALUE=\"1\" SELECTED>Pending story</OPTION>\n" : "  <OPTION VALUE=\"1\">Pending story</OPTION>\n";
  $output .= ($story->status == 2) ? "  <OPTION VALUE=\"2\" SELECTED>Public story</OPTION>\n" : "  <OPTION VALUE=\"2\">Public story</OPTION>\n";
  $output .= "</SELECT>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Save story\">\n";
  $output .= "</P>\n";
  $output .= "</FORM>\n";
  
  print $output;
}

function story_save($id, $subject, $abstract, $updates, $article, $category, $status) {
  db_query("UPDATE stories SET subject = '". addslashes($subject) ."', abstract = '". addslashes($abstract) ."', updates = '". addslashes($updates) ."', article = '". addslashes($article) ."', category = '". addslashes($category) ."', status = '$status' WHERE id = $id");
  watchdog(1, "modified story `$subject'.");
}

function story_display($order = "date") {
  ### Initialize variables:
  $status = array("deleted", "pending", "public");
  $fields = array("author" => "author", "category" => "category", "date" => "timestamp DESC", "status" => "status DESC");

  ### Perform SQL query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON u.id = s.author ORDER BY s.$fields[$order]");
  
  ### Display stories:
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"5\">\n";
  $output .= "   <FORM ACTION=\"admin.php?section=stories\" METHOD=\"post\">\n";
  $output .= "    <SELECT NAME=\"order\">\n";
  foreach ($fields as $key=>$value) {
    $output .= "     <OPTION VALUE=\"$key\"". ($key == $order ? " SELECTED" : "") .">Sort by $key</OPTION>\n";
  }
  $output .= "    </SELECT>\n";
  $output .= "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Update\">\n";
  $output .= "   </FORM>\n";
  $output .= "  </TH>\n";
  $output .= " </TR>\n";

  $output .= " <TR>\n";
  $output .= "  <TH>subject</TH>\n";
  $output .= "  <TH>author</TH>\n";
  $output .= "  <TH>category</TH>\n";
  $output .= "  <TH>status</TH>\n";
  $output .= "  <TH>operations</TH>\n";
  $output .= " </TR>\n";

  while ($story = db_fetch_object($result)) {
    $output .= " <TR><TD><A HREF=\"discussion.php?id=$story->id\">$story->subject</A></TD><TD>". format_username($story->userid, 1) ."</TD><TD>$story->category</TD><TD ALIGN=\"center\">". $status[$story->status] ."</TD><TD ALIGN=\"center\"><A HREF=\"admin.php?section=stories&op=edit&id=$story->id\">edit</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";
 
  print $output;
}

function info_display() {
  include "includes/config.inc";

  $output .= "sitename: $sitename<BR>\n";
  $output .= "e-mail address: $contact_email<BR>\n";
  $output .= "send e-mail notifications: $notify<BR>\n";
  $output .= "allowed HTML tags: <I>". htmlspecialchars($allowed_html) ."</I><BR>\n";
  $output .= "anonymous user: $anonymous<BR>\n";
  $output .= "submission post threshold: $submission_post_threshold<BR>\n";
  $output .= "submission dump threshold: $submission_dump_threshold<BR>\n";

  admin_box("$sitename settings", $output);
}

include "includes/config.inc";
include "includes/function.inc";
include "includes/admin.inc";

admin_header();

switch ($section) {
  case "stories":
    switch ($op) {
      case "edit":
        story_edit($id);
        break;
      case "Save story":
        story_save($id, $subject, $abstract, $updates, $article, $category, $status);
        story_edit($id);
        break;
      case "Update":
        story_display($order);
        break;
      default:
        story_display();
    }
    break;
  case "comments":
    switch ($op) {
      case "edit":
        comment_edit($id);
        break;
      case "Save comment":
        comment_save($id, $subject, $comment);
        comment_edit($id);
        break;
      case "Update":
        comment_display($order);
        break;
      default:
        comment_display();
    }
    break;
  case "diaries":
    switch ($op) {
      case "edit":
        diary_edit($id);
        break;
      case "Save diary entry":
        diary_save($id, $text);
        diary_edit($id);
        break;
      case "Update":
        diary_display($order);
        break;
      default:
        diary_display();
    }
    break;
  case "accounts":
    switch ($op) {
      case "view":
        account_view($name);
        break;
      case "Update":
        account_display($order);
        break;
      default:
        account_display(); 
    }
    break;
  case "misc":
    misc_display();
    break;
  case "bans":
    include "includes/ban.inc";
    switch ($op) {
      case "Add ban":
        ban_new($mask, $category, $reason);
        ban_display($category);
        break;
      case "Check ban":
        ban_check($mask, $category);
        ban_display($category);
        break;
      case "delete":
        ban_delete($id);
        ban_display($category);
        break;
      default:
        ban_display($category);
    }
    break;
  case "logs":
    switch ($op) {
      case "view":
        log_view($id);
        break;
      case "Update":
        log_display($order);
        break;
      default:
        log_display();
    }
    break;
  case "stats":
    stats_display();
    break;
  case "info":
    info_display();
    break;
  case "home":
    home_display();
    break;
  default:
    print "<BR><BR><CENTER>Welcome to the adminstration center!</CENTER>\n";
}

admin_footer();

?>