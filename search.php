<?

include "functions.inc";
include "theme.inc";

$theme->header();

dbconnect();
$terms = stripslashes($terms);

?>

<TABLE WIDTH="100%" BORDER="0">
 <TR VALIGN="center">
  <TD COLSPAN="3">
   <FORM ACTION="<? print basename($GLOBALS[PHP_SELF]); ?>" METHOD="POST"><BR>
   <INPUT SIZE="50" VALUE="<? print "$terms"; ?>" NAME="terms" TYPE="text"><BR>
   <SELECT NAME="category">
   <?
    if ($category != "") print " <OPTION VALUE=\"$category\">$category</OPTION>";
    print "<OPTION VALUE=\"\">All categories</OPTION>";
    for ($i = 0; $i < sizeof($categories); $i++) {
      print " <OPTION VALUE=\"$categories[$i]\">$categories[$i]";
    }
   ?>
   </SELECT>
   <SELECT NAME="author">
   <?
    $result = mysql_query("SELECT aid FROM authors ORDER BY aid");
    if ($author != "") print " <OPTION VALUE=\"$author\">$author";
    print " <OPTION VALUE=\"\">All authors";
    while(list($authors) = mysql_fetch_row($result)) {
      print "   <OPTION VALUE=\"$authors\">$authors";
    }
   ?>		
   </SELECT>
   <SELECT NAME="order">
   <?
    if ($order == "Oldest first") {
      print "<OPTION VALUE=\"Oldest first\">Oldest first";
      print "<OPTION VALUE=\"Newest first\">Newest first";
    }
    else {
      print "<OPTION VALUE=\"Newest first\">Newest first";
      print "<OPTION VALUE=\"Oldest first\">Oldest first";
    }
   ?>
   </SELECT>
   <INPUT TYPE="submit" VALUE="Search">
  </TD>
 </TR>
 <TR>
  <TD>
   <?
    ### Compose query:
    $query = "SELECT DISTINCT s.sid, s.aid, s.subject, s.time FROM stories s, authors a WHERE s.sid != 0 ";
      // Note: s.sid is a dummy clause used to enforce the WHERE-tag.
    if ($terms != "") $query .= "AND (s.subject LIKE '%$terms%' OR s.abstract LIKE '%$terms%' OR s.comments LIKE '%$terms%') ";
    if ($author != "") $query .= "AND s.aid = '$author' ";
    if ($category != "") $query .= "AND s.category = '$category' ";
    if ($order == "Oldest first") $query .= " ORDER BY s.time ASC";
    else $query .= " ORDER BY s.time DESC";
   
    ### Perform query:
    $result = mysql_query("$query");
 
    ### Display search results:
    print "<HR>";
    while (list($sid, $aid, $subject, $time) = mysql_fetch_row($result)) {
      $num++;

      if ($user) {
        $link = "<A HREF=\"article.php?sid=$sid";
        if (isset($user->umode)) { $link .= "&mode=$user->umode"; } else { $link .= "&mode=threaded"; }
        if (isset($user->uorder)) { $link .= "&order=$user->uorder"; } else { $link .= "&order=0"; }
        if (isset($user->thold)) { $link .= "&thold=$user->thold"; } else { $link .= "&thold=0"; }
        $link .= "\">$subject</A>";
      }
      else {
        $link = "<A HREF=\"article.php?sid=$sid&mode=threaded&order=1&thold=0\">$subject</A>";
      }

      print "<P>$num) <B>$link</B><BR><SMALL>by <B><A HREF=\"account.php?op=userinfo&uname=$aid\">$aid</A></B>, posted on ". date("l, F d, Y - H:i A", $time) .".</SMALL></P>\n";
    }

    if ($num == 0) print "<P>Your search did <B>not</B> match any articles in our database: <UL><LI>Try using fewer words.</LI><LI>Try using more general keywords.</LI><LI>Try using different keywords.</LI></UL></P>";
    else print "<P><B>$num</B> results matched your search query.</P>";
  ?>

  </TD>
 </TR>
</TABLE>

<?
 $theme->footer();
?>