<?


function adminAddChannel() {
  ?>
   <HR>
   <FORM ACTION="backend.php" METHOD="post">
   <P>
    <B>Site name:</B><BR>
    <INPUT TYPE="text" NAME="site" SIZE="50">
   </P>

   <P>
    <B>URL:</B><BR>
    <INPUT TYPE="text" NAME="url" SIZE="50">
   </P>

   <P>
    <B>Backend file:</B><BR>
    <INPUT TYPE="text" NAME="file" SIZE="50">
   </P>

   <P>
    <B>Contact information:</B><BR>
    <INPUT TYPE="text" NAME="contact" SIZE="50">
   </P>
   <INPUT TYPE="submit" NAME="op" VALUE="Add backend"> 
   </FORM>
  <?
}

function displayAll() {
  global $theme;

  ### Get channel info:
  $result = db_query("SELECT * FROM channel ORDER BY id");

  print "<HR>\n";
  print "<TABLE BORDER=\"0\">\n";
  while ($channel = db_fetch_object($result)) {
    if ($state % 3 == 0) print " <TR>\n";

    print "  <TD ALIGN=\"center\" VALIGN=\"top\" WIDTH=\"33%\">\n";
    
    ### Load backend from database:
    $backend = new backend($channel->id);
    
    ### Read headlines from backend class:
    $content = "";
    for (reset($backend->headlines); $headline = current($backend->headlines); next($backend->headlines)) {
      $content .= "<LI>$headline</LI>\n";
    }

    ### Print backend box to screen:
    $theme->box($backend->site, "$content<P ALIGN=\"right\">[ <A HREF=\"$backend->url\">more</A> ]\n");
    print " </TD>\n";

    if ($state % 3 == 2) print " </TR>\n";

    $state += 1;
  }  
  print "</TABLE>\n";
}

function adminMain() {
  global $theme, $PHP_SELF;

  ### Get channel info:
  $result = db_query("SELECT * FROM channel ORDER BY id");

  print "<TABLE BORDER=\"0\" WIDTH=\"100%\" CELLSPACING=\"2\" CELLPADDING=\"4\">";
  print " 
  <TR BGCOLOR=\"$theme->bgcolor1\"><TD ALIGN=\"center\"><B><FONT COLOR=\"$theme->fgcolor1\">Site</FONT></B></TD><TD ALIGN=\"center\"><B><FONT COLOR=\"$theme->fgcolor1\">Contact</FONT></B></TD><TD ALIGN=\"center\"><B><FONT COLOR=\"$theme->fgcolor1\">Last updated</FONT></B></TD><TD ALIGN=\"center\" COLSPAN=\"2\"><B><FONT COLOR=\"$theme->fgcolor1\">Operations</FONT></B></TD></TR>";
  while ($channel = db_fetch_object($result)) {
    ### Load backend from database:
    $backend = new backend($channel->id);
    
    print "<TR BGCOLOR=\"$theme->bgcolor2\">";
    print " <TD><A HREF=\"$backend->url\">$backend->site</A></TD>";
    print " <TD><A HREF=\"mailto:$backend->contact\">$backend->contact</A></TD>";
    print " <TD ALIGN=\"center\">". round((time() - $backend->timestamp) / 60) ." min. ago</TD>";
    print " <TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?op=refresh&id=$backend->id\">refresh</A></TD>";
    print " <TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?op=delete&id=$backend->id\">delete</A></TD>";
    print "</TR>";
  }  
  print "</TABLE>";
  print "<BR><BR>";
}

include "includes/backend.inc";
include "includes/theme.inc";

$theme->header();

switch($op) {
  case "refresh":
    $backend = new backend($id);
    $backend->refresh();
    adminMain();
    displayAll();
    adminAddChannel();
    break;
  case "delete":
    print "ID = $id<BR>";
    $backend = new backend($id);
    $backend->dump();
    $backend->delete();
    adminMain();
    displayAll();
    adminAddChannel();
    break;
  case "Add backend":
    $backend = new backend($id, $site, $url, $file, $contact);
    $backend->add();
    // fall through:
  default:
    adminMain();
    displayAll();
    adminAddChannel();
}

$theme->footer();

?>
