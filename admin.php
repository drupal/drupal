<?

/*
 * Account administration:
 */

function account_display($order = "username") {
  global $PHP_SELF;

  $sort = array("ID" => "id", "fake e-mail address" => "femail", "homepage" => "url", "hostname" => "last_host", "last access date" => "last_access", "real e-mail address" => "email", "real name" => "name", "status" => "status", "theme" => "theme", "username" => "userid");
  $show = array("ID" => "id", "username" => "userid", "$order" => "$sort[$order]", "status" => "status");

  ### Perform query:
  $result = db_query("SELECT u.id, u.userid, u.$sort[$order], u.status FROM users u ORDER BY $sort[$order]");
  
  ### Generate output:
  $output .= "<H3>Accounts:</H3>\n";
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"". (sizeof($show) + 1) ."\">\n";
  $output .= "   <FORM ACTION=\"$PHP_SELF?section=accounts\" METHOD=\"post\">\n";
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
	case "email":
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
          $output .= "  <TD>". format_availability($account[$value]) ."</TD>\n";
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
    $output .= "<H3>Accounts:</H3>\n";
    $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>ID:</B></TD><TD>$account->id</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Username:</B></TD><TD>$account->userid</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Real name:</B></TD><TD>". format_availability($account->name) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Real e-mail address:</B></TD><TD>". format_email_address($account->email) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Fake e-mail address:</B></TD><TD>". format_availability($account->femail) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>URL of homepage:</B></TD><TD>". format_url($account->url) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Last access:</B></TD><TD>". format_date($account->last_access) ." from $account->last_host</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Bio information:</B></TD><TD>". format_availability($account->bio) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Signature:</B></TD><TD>". format_availability($account->signature) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Theme:</B></TD><TD>$account->theme</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Submitted stories:</B></TD><TD>". format_availability(account_stories($account->id)) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\" VALIGN=\"top\"><B>Submitted comments:</B></TD><TD>". format_availability(account_comments($account->id)) ."</TD></TR>\n";
    $output .= "</TABLE>\n";
    print "$output";
  }
}

/*
 * Log administration:
 */
function log_display($order = "date") {
  global $PHP_SELF, $anonymous;

  $colors = array("#FFFFFF", "#FFFFFF", "#90EE90", "#CD5C5C");
  $fields = array("date" => "id DESC", "username" => "user", "message" => "message DESC", "level" => "level DESC");

  ### Perform query:
  $result = db_query("SELECT l.*, u.userid FROM logs l LEFT JOIN users u ON l.user = u.id ORDER BY l.$fields[$order]");
 
  ### Generate output:
  $output .= "<H3>Logs:</H3>\n";
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"4\">\n";
  $output .= "   <FORM ACTION=\"$PHP_SELF?section=logs\" METHOD=\"post\">\n";
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
    $output .= " <TR BGCOLOR=\"". $colors[$log->level] ."\"><TD>". date("D d/m, H:m:s", $log->timestamp) ."</TD><TD ALIGN=\"center\">". format_username($log->userid, 1) ."</A></TD><TD>". substr($log->message, 0, 44) ."</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=logs&op=view&id=$log->id\">more</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";

  print $output;
}

function log_view($id) {
  ### Perform query:
  $result = db_query("SELECT l.*, u.userid FROM logs l LEFT JOIN users u ON l.user = u.id WHERE l.id = $id");

  if ($log = db_fetch_object($result)) {
    $output .= "<H3>Logs:</H3>\n";
    $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Level:</B></TD><TD>$log->level</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>Date:</B></TD><TD>". date("l, F d, Y - H:i A", $log->timestamp) ."</TD></TR>\n";
    $output .= " <TR><TD ALIGN=\"right\"><B>User:</B></TD><TD>". format_username($log->userid, 1) ."</TD></TR>\n";
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
  $output .= "<H3>Status:</H3>\n";
  $output .= "". ($ban ? "Matched ban '<B>$ban->mask</B>' with reason: <I>$ban->reason</I>.<P>\n" : "No matching bans for '$mask'.<P>\n") ."";
  print $output;
}

function ban_new($mask, $category, $reason) {
  ban_add($mask, $category, $reason, &$message);
  $output .= "<H3>Status:</H3>\n";
  $output .= "$message\n";
  print $output;
}

function ban_display($category = "") {
  global $PHP_SELF, $type2index;

  ### initialize variable: 
  $category = $category ? $category : 1;

  ### Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = $category ORDER BY mask");
 
  ### Generate output:
  $output .= "<H3>Bans:</H3>\n";
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH COLSPAN=\"3\">\n";
  $output .= "   <FORM ACTION=\"$PHP_SELF?section=bans\" METHOD=\"post\">\n";
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
    $output .= "  <TR><TD>$ban->mask</TD><TD>$ban->reason</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=bans&op=delete&category=$category&id=$ban->id\">delete</A></TD></TR>\n";
  }
  
  $output .= " <TR><TD COLSPAN=\"3\"><SMALL>%: matches any number of characters, even zero characters.<BR>_: matches exactly one character.</SMALL></TD></TR>\n";
  $output .= "</TABLE>\n";
  $output .= "<BR><HR>\n";

  $output .= "<H3>Add new ban:</H3>\n";
  $output .= "<FORM ACTION=\"$PHP_SELF?section=bans\" METHOD=\"post\">\n";
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
  $output .= "<FORM ACTION=\"$PHP_SELF?section=bans\" METHOD=\"post\">\n";
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
 * Story administration:
 */

function story_edit($id) {
  global $PHP_SELF, $anonymous, $categories;

  $result = db_query("SELECT stories.*, users.userid FROM stories LEFT JOIN users ON stories.author = users.id WHERE stories.id = $id");
  $story = db_fetch_object($result);

  $output .= "<FORM ACTION=\"$PHP_SELF?section=stories&op=save&id=$id\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Author:</B><BR>\n";
  if ($story->userid) $output .= " <A HREF=\"admin.php?section=accounts&op=view&id=$story->author\">$story->userid</A>\n";
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
  db_query("UPDATE stories SET subject = '". addslashes($subject) ."', abstract = '". addslashes($abstract) ."', updates = '". addslashes($updates) ."', article = '". addslashes($article) ."', category = '". addslashes($category) ."', status = '$status' WHERE id = $id");

  ### Add log entry:
  watchdog(1, "modified story `$subject'.");
}

function story_display($order = "date") {
  global $PHP_SELF;

  ### Initialize variables:
  $status = array("deleted", "pending", "public");
  $fields = array("author" => "author", "category" => "category", "date" => "timestamp DESC", "status" => "status DESC");

  ### Perform SQL query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON u.id = s.author ORDER BY s.$fields[$order]");
  
  ### Display stories:
  $output .= "<H3>Stories:</H3>\n";
  $output .= "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  $output .= " <TR>\n";
  $output .= "  <TH ALIGN=\"right\" COLSPAN=\"5\">\n";
  $output .= "   <FORM ACTION=\"$PHP_SELF?section=stories\" METHOD=\"post\">\n";
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
    $output .= " <TR><TD><A HREF=\"discussion.php?id=$story->id\">$story->subject</A></TD><TD>". format_username($story->userid, 1) ."</TD><TD>$story->category</TD><TD ALIGN=\"center\">". $status[$story->status] ."</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?section=stories&op=edit&id=$story->id\">edit</A></TD></TR>\n";
  }

  $output .= "</TABLE>\n";
 
  print $output;
}

include "function.inc";
include "admin.inc";

admin_header();

switch ($section) {
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
      case "Update":
        log_display($order);
        break;
      default:
        log_display();
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
      case "Update":
        story_display($order);
        break;
      default:
        story_display();
    }
    break;
  default:
    print "Welcome to the adminstration page!";
}

admin_footer();

?>