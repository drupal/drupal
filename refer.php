<?

### Include global settings:
include "config.inc";

include "functions.inc";
include "authentication.inc";
include "theme.inc";

$theme->header();

/*
function addRefer($url) {
  $query = "SELECT * FROM refer WHERE url = '$url'";
  $result = mysql_query($query);

  if ($site = mysql_fetch_object($result)) {
    if ($site->status) {
      $site->refers++;
      $query = "UPDATE refer SET refers = '$site->refers', access_dt = '". time() ."' WHERE url = '$url'";
      $result = mysql_query($query);
    }
  }
  else {
    $query = "INSERT INTO refer (url, name, refers, create_dt, access_dt) VALUES ('$url', '', '1', '". time() ."', '". time() ."')";
    $result = mysql_query($query);
  }
}
*/

function blockRefer($url) {
  $query = "UPDATE refer SET status = '0' WHERE url = '$url'";
  $result = mysql_query($query);
}

function setReferName($url, $name) {
  $query = "UPDATE refer SET name = '$name' WHERE url = '$url'";
  $result = mysql_query($query);
}

function deleteRefer($url) {
  $query = "DELETE FROM refer WHERE url = '$url'";
  $result = mysql_query($query);
}

function openRefer($url) {
  $query = "UPDATE refer SET status = '1' WHERE url = '$url'";
  $result = mysql_query($query);
}

function getReferArray($number = "") {
  if ($number) {
    $query = "SELECT * FROM refer ORDER BY refers DESC LIMIT $number";
    $result = mysql_query($query);
  }
  else {
    $query = "SELECT * FROM refer ORDER BY refers DESC";
    $result = mysql_query($query);
  }
  
  $index = 0;
  while ($site = mysql_fetch_object($result)) {
    $rval[$index] = $site;
    $index++;
  }
  return $rval;
}

$info = "<P>If you are not familiar with \"top sites\"-lists: we use a script that keeps track of the number of visitor your website referred to our site and we rank you according to that number.  This can be a good, free way of increasing your website traffic: it is our way to give a link back to referring sites.  In order to take advantage of this feature, you have to do is to use the following code when linking to our site:</P><BR><CENTER><FONT COLOR=\"orange\"><CODE>&lt;A HREF=\"http://this-site.com/<B>?url=http://www.your-website.com/</B>\"&gt;&lt;IMG SRC=\"this-site-button.gif\"&gt;&lt/A&gt;</CODE></FONT></CENTER><BR><P>By using the above line of code you will automatically participate in our referring site program.  Note however that it will only work if you applied to above code correctly, that is, make sure you don't forget the <I>?url=http://www.your-website.com/</I> part. The more visitors you refer, the higher your ranking.</P><P>The highest ranked sites will be automatically included in most (if not all) our pages!</P>\n";

function referList($number = "", $detail = "0") {
  $site = getReferArray($number);
  $count = 1;
    
  if ($detail) {
    $rval .= "<TABLE CELLSPACING=\"2\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
    $rval .= " <TR><TD><B>Rank</B></TD><TD><B>Referrals</B></TD><TD><B>URL or name</B></TD><TD NOWRAP><B>Last refer</B></TD></TR>\n";

    for (reset($site); $entry = current($site); next($site)) {

      $last = date("d/m/y - H:i:s", $entry->access_dt) ." &nbsp; <SMALL><I>(". round((time() - $entry->access_dt) / 86400) ." days ago)</I></SMALL>";

      if ($entry->name) $rval .= " <TR><TD>$count</TD><TD>$entry->refers</TD><TD><A HREF=\"$entry->url\">$entry->name</A></TD><TD>$last</TD><TR>\n";
      else $rval .= " <TR><TD>$count</TD><TD>$entry->refers</TD><TD><A HREF=\"$entry->url\">$entry->url</A></TD><TD>$last</TD></TR>\n";
      $count++;
    }
    $rval .= "</TABLE>\n";
  }
  else {
    for (reset($site); $entry = current($site); next($site)) {
      if ($entry->name) $rval .= "$count. <A HREF=\"$entry->url\">$entry->name</A> ($entry->refers)<BR>";
      else $rval .= "$count. <A HREF=\"$entry->url\">$entry->url</A> ($entry->refers)<BR>";
      $count++;
    }
  }
  return $rval;
}

function referAdmin($number = "") {
  global $PHP_SELF, $bgcolor1, $bgcolor2;

  $site = getReferArray($number);
  $count = 1;
  $rval .= "<TABLE CELLSPACING=\"2\" CELLPADDING=\"4\" WIDTH=\"100%\">\n";
  $rval .= "<TR BGCOLOR=\"$bgcolor2\"><TD>#</TD><TD COLSPAN=\"2\">URL or name</TD><TD NOWRAP>First refer</TD><TD NOWRAP>Last refer</TD><TD>&nbsp;</TD><TD COLSPAN=\"3\">Commands</TD></TR>\n";
    
  for (reset($site); $entry = current($site); next($site)) {
    if ($entry->status) {
      $delete = "delete";
      $block = "<A HREF=\"$PHP_SELF?section=refer&method=block&url=$entry->url\">block</A>";
      $status = "<FONT COLOR=\"orange\" SIZE=\"+2\">*</FONT>";
    }
    else {
      $delete = "<A HREF=\"$PHP_SELF?section=refer&method=delete&url=$entry->url\">delete</A>";
      $block = "<A HREF=\"$PHP_SELF?section=refer&method=open&url=$entry->url\">open</A>";
      $status = "<FONT COLOR=\"red\" SIZE=\"+2\">*</FONT>";
    }
         
    $first = date("d/m/y - H:i:s", $entry->create_dt) ."<BR><FONT SIZE=\"-1\"><I>(". round((time() - $entry->create_dt) / 86400) ." days ago)</I></FONT>";
    $last = date("d/m/y - H:i:s", $entry->access_dt) ."<BR><FONT SIZE=\"-1\"><I>(". round((time() - $entry->access_dt) / 86400) ." days ago)</I></FONT>";

    if ($entry->name) $rval .= "<TR BGCOLOR=\"$bgcolor1\"><TD>$count</TD><TD><A HREF=\"$entry->url\">$entry->name</A></TD><TD>$entry->refers</TD><TD>$first</TD><TD>$last</TD><TD>$status</TD><TD>$block</TD><TD>$delete</TD><TD><A HREF=\"$PHP_SELF?section=refer&method=edit&url=$entry->url\">edit</A></TD></TR>";
    else $rval .= "<TR BGCOLOR=\"$bgcolor1\"><TD>$count</TD><TD><A HREF=\"$entry->url\">$entry->url</A></TD><TD>$entry->refers</TD><TD>$first</TD><TD>$last</TD><TD>$status</TD><TD>$block</TD><TD>$delete</TD><TD><A HREF=\"$PHP_SELF?section=refer&method=edit&url=$entry->url\">edit</A></TD></TR>";
    $count++;
  }
  $rval .= "</TABLE>\n";
  return $rval;
}

/*
### log valid refers:
if (($url) && ($section != "refer") && (strstr(getenv("HTTP_REFERER"), $url))) {
  addRefer($url);
}
*/

### parse URI:
if ($section == "refer") {
  if ($admin) {
    if ($method == "block") {
      blockRefer($url);
      print referAdmin();
    }
    else if ($method == "open") {
      openRefer($url);
      print referAdmin();
    }
    else if ($method == "delete") {
      deleteRefer($url);
      print referAdmin();
    } 
    else if ($method == "edit") {
      print "<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=refer&method=update&url=$url\">\nEnter a description for $url:<BR><INPUT TYPE=\"text\" LENGTH=\"40\" NAME=\"name\">\n<INPUT TYPE=\"submit\" NAME=\"update\" VALUE=\"Update\">\n</FORM>";
    }
    else if ($method == "update") {
      setReferName($url, $name);
      print referAdmin();
    }
    else {
      print referAdmin();
    }
  }
}
else {
  $theme->box("Referring sites", "<P><U><A NAME=\"#refer-info\">Information:</A></U></P><P>$info</P><BR><BR><P><U><A NAME=\"#refer-more\">Complete list:</A></U></P>". referList("", 1));
}

$theme->footer();
?>
