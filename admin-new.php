<?
//////////////////////////////////////////////////
// This code should go in the admin pages and is only a temporary
// placeholder untill we are going to rewrite the admin pages. If
// you have the sudden urge to integrate it into admin.php or if 
// you have some time to kill ... I won't stop you. A rewrite of
// admin.php is sheduled for v0.20 anyway ... 
// Like this the ban.php code I just queued it to be included into 
// the new admin pages.  After proper integration, this file can 
// be removed.
//
// -- Dries
//////////////////////////////////////////////////

include "database.inc";
include "ban.inc";

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
  global $PHP_SELF, $type;

  ### initialize variable: 
  $category = $category ? $category : 1;

  ### Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = $category ORDER BY mask");
 
  ### Generate output:
  print "<H3>Active bans:</H3>\n";
  print "<TABLE BORDER=\"1\" CELLPADDING=\"3\" CELLSPACING=\"0\">\n";
  print " <TR>\n";
  print "  <TH COLSPAN=\"2\" >Active bans</TH>\n";
  print "  </TH>\n";
  print "  <TH>\n";
  print "   <FORM ACTION=\"$PHP_SELF\" METHOD=\"post\">\n";
  print "    <SELECT NAME=\"category\">\n";
  for (reset($type); $cur = current($type); next($type)) {
    print "     <OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type) ."</OPTION>\n";
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
    print "  <TR><TD>$ban->mask</TD><TD>$ban->reason</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?op=delete&category=$category&id=$ban->id\">delete</A></TD></TR>\n";
  }
  
  print " <TR><TD COLSPAN=\"3\"><SMALL>%: matches any number of characters, even zero characters.<BR>_: matches exactly one character.</SMALL></TD></TR>\n";
  print "</TABLE>\n";
  print "<BR><HR>\n";

  print "<H3>Add new ban:</H3>\n";
  print "<FORM ACTION=\"$PHP_SELF\" METHOD=\"post\">\n";
  print "<B>Banmask:</B><BR>\n";
  print "<INPUT TYPE=\"text\" NAME=\"mask\" SIZE=\"35\"><P>\n";
  print "<B>Type:</B><BR>\n";
  print "<SELECT NAME=\"category\"\">\n";
  for (reset($type); $cur = current($type); next($type)) {
    print "<OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type) ."</OPTION>\n";
  }
  print "</SELECT><P>\n";
  print "<B>Reason:</B><BR>\n";
  print "<TEXTAREA NAME=\"reason\" COLS=\"35\" ROWS=\"5\"></TEXTAREA><P>\n";
  print "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Add ban\"><BR>\n";
  print "</FORM>\n";
  print "<BR><HR>\n";

  print "<H3>Ban check:</H3>\n";
  print "<FORM ACTION=\"$PHP_SELF\" METHOD=\"post\">\n";
  print "<B>Banmask:</B><BR>\n";
  print "<INPUT TYPE=\"text\" NAME=\"mask\" SIZE=\"35\"><P>\n";
  print "<B>Type:</B><BR>\n";
  print "<SELECT NAME=\"category\"\">\n";
  for (reset($type); $cur = current($type); next($type)) {
    print "<OPTION VALUE=\"$cur\"". ($cur == $category ? " SELECTED" : "") .">". key($type) ."</OPTION>\n";
  }
  print "</SELECT><P>\n";
  print "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Check ban\"><BR>\n";
  print "</FORM>\n";
}

include "admin.inc";
admin_header();

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

admin_footer();

?>
