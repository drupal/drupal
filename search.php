<?

 include "functions.inc";
 include "theme.inc";

 $theme->header();

 $terms = stripslashes($terms);

 $output .= "<TABLE WIDTH=\"100%\" BORDER=\"0\">";
 $output .= " <TR VALIGN=\"center\">";
 $output .= "  <TD COLSPAN=3>";
 $output .= "   <FORM ACTION=\"". basename($GLOBALS[PHP_SELF]) ."\" METHOD=\"POST\">";
 $output .= "    <INPUT SIZE=\"50\" VALUE=\"$terms\" NAME=\"terms\" TYPE=\"text\"><BR>";

 ### category:
 $output .= "    <SELECT NAME=\"category\">";
 if ($category != "") $output .= " <OPTION VALUE=\"$category\">$category</OPTION>";
 $output .= "<OPTION VALUE=\"\">All categories</OPTION>";
 for ($i = 0; $i < sizeof($categories); $i++) {
   $output .= " <OPTION VALUE=\"$categories[$i]\">$categories[$i]";
 }
 $output .= "</SELECT>";

 ### order:
 $output .= "<SELECT NAME=\"order\">";
 if ($order == "Oldest first") {
   $output .= "<OPTION VALUE=\"Oldest first\">Oldest first";
   $output .= "<OPTION VALUE=\"Newest first\">Newest first";
 }
 else {
   $output .= "<OPTION VALUE=\"Newest first\">Newest first";
   $output .= "<OPTION VALUE=\"Oldest first\">Oldest first";
 }
 $output .= "</SELECT>";

 $output .= "   <INPUT TYPE=\"submit\" VALUE=\"Search\">";
 $output .= "  </TD>";
 $output .= " </TR>";
 $output .= " <TR>";
 $output .= "  <TD>";
   
 ### Compose query:
 $query = "SELECT DISTINCT s.id, s.subject, u.userid, s.timestamp FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.status = 2 ";
 if ($terms != "") $query .= "AND (s.subject LIKE '%$terms%' OR s.abstract LIKE '%$terms%' OR s.comments LIKE '%$terms%') ";
 if ($category != "") $query .= "AND s.category = '$category' ";
 if ($order == "Oldest first") $query .= " ORDER BY s.timestamp ASC";
 else $query .= " ORDER BY s.timestamp DESC";
   
 ### Perform query:
 $result = db_query("$query");
 
 ### Display search results:
 $output .= "<HR>";
 while ($entry = db_fetch_object($result)) {
   $num++;

   if ($user) {
     $link = "<A HREF=\"article.php?id=$entry->id";
     if (isset($user->umode)) { $link .= "&mode=$user->umode"; } else { $link .= "&mode=threaded"; }
     if (isset($user->uorder)) { $link .= "&order=$user->uorder"; } else { $link .= "&order=0"; }
     if (isset($user->thold)) { $link .= "&thold=$user->thold"; } else { $link .= "&thold=0"; }
     $link .= "\">$entry->subject</A>";
   }
   else {
     $link = "<A HREF=\"article.php?id=$entry->id&mode=threaded&order=1&thold=0\">$entry->subject</A>";
   }
 
   $output .= "<P>$num) <B>$link</B><BR><SMALL>by <B><A HREF=\"account.php?op=userinfo&uname=$entry->userid\">$entry->userid</A></B>, posted on ". date("l, F d, Y - H:i A", $entry->timestamp) .".</SMALL></P>\n";
 }

 if ($num == 0) $output .= "<P>Your search did <B>not</B> match any articles in our database: <UL><LI>Try using fewer words.</LI><LI>Try using more general keywords.</LI><LI>Try using different keywords.</LI></UL></P>";
 else $output .= "<P><B>$num</B> results matched your search query.</P>";
 

 $output .= "  </TD>";
 $output .= " </TR>";
 $output .= "</TABLE>";

 $theme->box("Search", $output);
 $theme->footer();
?>