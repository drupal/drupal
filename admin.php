<?

/*
 * Account administration:
 */

function account_display($id = "", $order = 1) {
  ### Perform query:
  $result = db_query("SELECT * FROM users");

  ### Generate output:
  print "<H3>Accounts:</H3>\n";
 
  while ($account = db_fetch_object($result)) {
    $output .= "$account->userid<BR>";
  }

  print $output;
}


/*
 * Log administration:
 */
function log_display() {
  global $PHP_SELF, $anonymous, $log_level;

  ### Perform query:
  $result = db_query("SELECT l.*, u.userid FROM logs l LEFT JOIN users u ON l.user = u.id ORDER BY l.id DESC");

  $color = array("#FFFFFF", "#FFFFFF", "#90EE90", "#CD5C5C");
 
  ### Generate output:
  print "<H3>Logs:</H3>\n";
  print "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  print " <TR>\n";
  print "  <TH>Date</TH>\n";
  print "  <TH>User</TH>\n";
  print "  <TH>Message</TH>\n";
  print "  <TH>Operations</TH>\n";
  print " </TR>\n";

  while ($log = db_fetch_object($result)) {
    if ($log->userid) print " <TR BGCOLOR=\"". $color[$log->level] ."\"><TD>". date("D d/m, H:m:s", $log->timestamp) ."</TD><TD ALIGN=\"center\"><A HREF=\"account.php?op=info&uname=$log->userid\">$log->userid</A></TD><TD>". substr($log->message, 0, 44) ."</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=logs&op=view&id=$log->id\">more</A></TD></TR>\n";
    else print " <TR BGCOLOR=\"". $color[$log->level] ."\"><TD>". date("D d/m, H:m:s", $log->timestamp) ."</TD><TD ALIGN=\"center\">$anonymous</TD><TD>". substr($log->message, 0, 44) ."</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=logs&op=view&id=$log->id\">more</A></TD></TR>\n";
  }

  print "</TABLE>\n";
}

function log_view($id) {
  ### Perform query:
  $result = db_query("SELECT l.*, u.userid FROM logs l LEFT JOIN users u ON l.user = u.id WHERE l.id = $id");

  if ($log = db_fetch_object($result)) {
    print "<H3>Logs:</H3>\n";
    print "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
    print " <TR><TD ALIGN=\"right\"><B>Level:</B></TD><TD>$log->level</TD></TR>\n";
    print " <TR><TD ALIGN=\"right\"><B>Date:</B></TD><TD>". date("l, F d, Y - H:i A", $log->timestamp) ."</TD></TR>\n";
    print " <TR><TD ALIGN=\"right\"><B>User:</B></TD><TD><A HREF=\"account.php?op=info&uname=$log->userid\">". username($log->userid) ."</TD></TR>\n";
    print " <TR><TD ALIGN=\"right\"><B>Message:</B></TD><TD>$log->message</TD></TR>\n";
    print " <TR><TD ALIGN=\"right\"><B>Hostname:</B></TD><TD>$log->hostname</TD></TR>\n";
    print "</TABLE>\n";
  }
}

/*
 * Ban administration:
 */

function ban_check($mask, $category) {
  $ban = ban_match($mask, $category);

  print "<H3>Status:</H3>\n";
  print "". ($ban ? "Matched ban '<B>$ban->mask</B>' with reason: <I>$ban->reason</I>.<P>\n" : "No matching bans for '$mask'.<P>\n") ."";
}

function ban_new($mask, $category, $reason) {
  ban_add($mask, $category, $reason, &$message);

  print "<H3>Status:</H3>\n";
  print "$message\n";  
}

function ban_display($category = "") {
  global $PHP_SELF, $type2index;

  ### initialize variable: 
  $category = $category ? $category : 1;

  ### Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = $category ORDER BY mask");
 
  ### Generate output:
  print "<H3>Bans:</H3>\n";
  print "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  print " <TR>\n";
  print "  <TH COLSPAN=\"2\" >Active bans</TH>\n";
  print "  </TH>\n";
  print "  <TH>\n";
  print "   <FORM ACTION=\"$PHP_SELF?section=bans\" METHOD=\"post\">\n";
  print "    <SELECT NAME=\"category\">\n";
  for (reset($type2index); $cur = current($type2index); next($type2index)) {
    print "     <OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type2index) ."</OPTION>\n";
  }
  print "    </SELECT>\n";
  print "    <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Refresh\">\n";
  print "   </FORM>\n";
  print "  </TH>\n";
  print " </TR>\n";
  print " <TR>\n";
  print "  <TH>Mask</TH>\n";
  print "  <TH>Reason</TH>\n";
  print "  <TH>Operations</TH>\n";
  print " </TR>\n";

  while ($ban = db_fetch_object($result)) {
    print "  <TR><TD>$ban->mask</TD><TD>$ban->reason</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=bans&op=delete&category=$category&id=$ban->id\">delete</A></TD></TR>\n";
  }
  
  print " <TR><TD COLSPAN=\"3\"><SMALL>%: matches any number of characters, even zero characters.<BR>_: matches exactly one character.</SMALL></TD></TR>\n";
  print "</TABLE>\n";
  print "<BR><HR>\n";

  print "<H3>Add new ban:</H3>\n";
  print "<FORM ACTION=\"$PHP_SELF?section=bans\" METHOD=\"post\">\n";
  print "<B>Banmask:</B><BR>\n";
  print "<INPUT TYPE=\"text\" NAME=\"mask\" SIZE=\"35\"><P>\n";
  print "<B>Type:</B><BR>\n";
  print "<SELECT NAME=\"category\"\">\n";
  for (reset($type2index); $cur = current($type2index); next($type2index)) {
    print "<OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type2index) ."</OPTION>\n";
  }
  print "</SELECT><P>\n";
  print "<B>Reason:</B><BR>\n";
  print "<TEXTAREA NAME=\"reason\" COLS=\"35\" ROWS=\"5\"></TEXTAREA><P>\n";
  print "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Add ban\"><BR>\n";
  print "</FORM>\n";
  print "<BR><HR>\n";

  print "<H3>Ban check:</H3>\n";
  print "<FORM ACTION=\"$PHP_SELF?section=bans\" METHOD=\"post\">\n";
  print "<B>Banmask:</B><BR>\n";
  print "<INPUT TYPE=\"text\" NAME=\"mask\" SIZE=\"35\"><P>\n";
  print "<B>Type:</B><BR>\n";
  print "<SELECT NAME=\"category\"\">\n";
  for (reset($type2index); $cur = current($type2index); next($type2index)) {
    print "<OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type2index) ."</OPTION>\n";
  }
  print "</SELECT><P>\n";
  print "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Check ban\"><BR>\n";
  print "</FORM>\n";
}

/*
 * Story administration:
 */

function story_edit($id) {
  global $PHP_SELF, $anonymous, $categories;

  $result = db_query("SELECT stories.*, users.userid FROM stories LEFT JOIN users ON stories.author = users.id WHERE stories.id = $id");
  $story = db_fetch_object($result);

  $output .= "<FORM ACTION=\"$PHP_SELF?section=stories&op=save&id=$id\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Author:</B><BR>\n";
  if ($story->userid) $output .= " <A HREF=\"account.php?op=info&uname=$story->userid\">$story->userid</A>\n";
  else $output .= " $anonymous\n";
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
  global $PHP_SELF;

  ### Add submission to SQL table:
  db_query("UPDATE stories SET subject = '$subject', abstract = '$abstract', updates = '$updates', article = '$article', category = '$category', status = '$status' WHERE id = $id");

  ### Add log entry:
  watchdog(1, "modified story `$subject'.");
}

function story_display($category = "") {
  global $PHP_SELF;

  ### Initialize variables:
  $status = array("deleted", "pending", "public");

  ### Perform SQL query:
  $result = db_query("SELECT * FROM stories");
  
  ### Display stories:
  $output .= "<H3>Stories:</H3>\n";
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH>Subject</TH>\n";
  $output .= "  <TH>Status</TH>\n";
  $output .= "  <TH>Operations</TH>\n";
  $output .= " </TR>\n";

  while ($story = db_fetch_object($result)) {
    $output .= " <TR><TD><A HREF=\"discussion.php?id=$story->id\">$story->subject</A></TD><TD ALIGN=\"center\">". $status[$story->status] ."</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=stories&op=edit&id=$story->id\">edit</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";
 
  print $output;
}


include "functions.inc";
include "function.inc";
include "admin.inc";

admin_header();

switch ($section) {
  case "accounts":
    switch ($op) {
      default:
        account_display(); 
    }
    break;
  case "bans":
    include "ban.inc";
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
      default:
        log_display($category);
    }
    break;
  case "stories":
    switch ($op) {
      case "edit":
        story_edit($id);
        break;
      case "Save story":
        story_save($id, $subject, $abstract, $updates, $article, $category, $status);
        story_edit($id);
        break;
      default:
        story_display($category);
    }
    break;
  default:
    print "Bad visitor!  Bad, bad visitor!  What are you looking for?  Maybe it's <A HREF=\"\">here</A>?";
}

admin_footer();

?>