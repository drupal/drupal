<?

 include "includes/theme.inc";

 $theme->header();

 $terms = stripslashes($terms);

 $output .= "<TABLE WIDTH=\"100%\" BORDER=\"0\">\n";
 $output .= " <TR VALIGN=\"center\">\n";
 $output .= "  <TD COLSPAN=3>\n";
 $output .= "   <FORM ACTION=\"search.php\" METHOD=\"POST\">\n";
 $output .= "    <INPUT SIZE=\"50\" VALUE=\"$terms\" NAME=\"terms\" TYPE=\"text\"><BR>\n";

 ### category:
 $output .= "<SELECT NAME=\"category\">\n";
 if ($category) $output .= " <OPTION VALUE=\"$category\">$category</OPTION>\n";
 $output .= " <OPTION VALUE=\"\">All categories</OPTION>\n";
 for ($i = 0; $i < sizeof($categories); $i++) {
   $output .= " <OPTION VALUE=\"$categories[$i]\">$categories[$i]</OPTION>\n";
 }
 $output .= "</SELECT>\n";

 ### order:
 $output .= "<SELECT NAME=\"order\">\n";
 if ($order == "Oldest first") {
   $output .= " <OPTION VALUE=\"Oldest first\">Oldest first</OPTION>\n";
   $output .= " <OPTION VALUE=\"Newest first\">Newest first</OPTION>\n";
 }
 else {
   $output .= " <OPTION VALUE=\"Newest first\">Newest first</OPTION>\n";
   $output .= " <OPTION VALUE=\"Oldest first\">Oldest first</OPTION>\n";
 }
 $output .= "</SELECT>\n";

 $output .= "   <INPUT TYPE=\"submit\" VALUE=\"Search\">\n";
 $output .= "  </TD>\n";
 $output .= " </TR>\n";
 $output .= " <TR>\n";
 $output .= "  <TD>\n";
   
 ### Compose and perform query:
 $query = "SELECT DISTINCT s.id, s.subject, u.userid, s.timestamp, COUNT(c.cid) AS comments FROM comments c, stories s LEFT JOIN users u ON s.author = u.id WHERE s.status = 2 AND s.id = c.sid ";
 $query .= ($author) ? "AND u.userid = '$author' " : "";
 $query .= ($terms) ? "AND (s.subject LIKE '%$terms%' OR s.abstract LIKE '%$terms%' OR s.updates LIKE '%$terms%') " : "";
 $query .= ($category) ? "AND s.category = '$category' GROUP BY c.sid " : "GROUP BY c.sid ";
 $query .= ($order == "Oldest first") ? "ORDER BY s.timestamp ASC" : "ORDER BY s.timestamp DESC";
 $result = db_query("$query");
 
 ### Display search results:
 $output .= "<HR>\n";

 while ($entry = db_fetch_object($result)) {
   $num++;
   $output .= "<P>$num) <B><A HREF=\"discussion.php?id=$entry->id\">$entry->subject</A></B> (". format_plural($entry->comments, "comment", comments) .")<BR><SMALL>by ". format_username($entry->userid) ."</B>, posted on ". format_date($entry->timestamp) .".</SMALL></P>\n";
 }

 if ($num == 0) $output .= "<P>Your search did <B>not</B> match any articles in our database: <UL><LI>Try using fewer words.</LI><LI>Try using more general keywords.</LI><LI>Try using different keywords.</LI></UL></P>\n";
 else $output .= "<P><B>$num</B> results matched your search query.</P>\n";
 
 $output .= "  </TD>\n";
 $output .= " </TR>\n";
 $output .= "</TABLE>\n";

 $theme->box("Search", $output);
 $theme->footer();
?>