<?


function admin_icon($name) {
  global $section;
  if ($name == $section) print "    <TD ALIGN=\"center\" BGCOLOR=\"#CCCCCC\"><A HREF=\"admin.php?section=$name\"><IMG SRC=\"images/admin-$name.png\" BORDER=\"0\"></A><BR>$name</TD>\n";
  else print "    <TD ALIGN=\"center\" VALIGN=\"middle\"><A HREF=\"admin.php?section=$name\"><IMG SRC=\"images/admin-$name.png\" BORDER=\"0\"></A><BR>$name</TD>\n";
}

function admin_header() {
  global $sitename, $section, $status;

 ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
  <HTML>
   <HEAD><TITLE><? echo $sitename; ?> administration center</TITLE></HEAD>
   <STYLE>
    body    { font-family: helvetica, arial; }
    h1      { font-size: 14pt; font-weight: bold; color: #006699; }
    h2      { font-family: helvetica, arial; font-size: 12pt; font-weight: bold; }
    h3      { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; }
    th	    { font-family: helvetica, arial; text-align: center; background-color: #CCCCCC; color: #885555; }
    td	    { font-family: helvetica, arial; }
   </STYLE>
   <BODY BGCOLOR="#FFFFFF" LINK="#006699" VLINK="#004499" ALINK="#FF0000">
   <TABLE BORDER="0" CELLPADDING="0" CELLSPACING="2" WIDTH="780">
    <TR><TD COLSPAN="10"><H1><? echo "$sitename"; ?> administration center</H1></TD></TR>
    <TR><TD BGCOLOR="#000000" COLSPAN="10" WIDTH="100%"><IMG SRC="images/pixel.gif" WIDTH="1" HEIGHT="1" ALT=""></TD></TR>
    <TR>
     <?
       admin_icon("stories");
       admin_icon("comments");
       admin_icon("diaries");
       admin_icon("accounts");
       admin_icon("misc");
       admin_icon("bans");
       admin_icon("logs");
       admin_icon("stats");
       admin_icon("info");
       admin_icon("home");
     ?>
    </TR>
    <TR><TD BGCOLOR="#000000" COLSPAN="10" WIDTH="100%"><IMG SRC="images/pixel.gif" WIDTH="1" HEIGHT="0" ALT=""></TD></TR>
    <TR><TD COLSPAN="10">&nbsp;</TD></TR>
     <?  
       if ($status) {
      ?>
    <TR><TD COLSPAN="10"><BIG><B>status:</B> <? echo $status; ?></BIG></TD></TR>
    <TR><TD COLSPAN="10">&nbsp;</TD></TR>
     <?
       }
     ?>
    <TR>
     <TD COLSPAN="10">
     <?
}

function admin_box($title, $body) {
  print "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"2\" WIDTH=\"100%\">\n";
  print " <TR><TD><FONT COLOR=\"#83997A\"><B>$title</B></A></TD></TR>\n";
  print " <TR><TD BGCOLOR=\"#000000\" WIDTH=\"100%\"><IMG SRC=\"images/pixel.gif\" WIDTH=\"1\" HEIGHT=\"0\" ALT=\"\"></TD></TR>\n";
  print " <TR><TD>&nbsp;</TD></TR>\n";
  print " <TR><TD>$body</TD></TR>\n";
  print "</TABLE>\n";
  print "<BR><BR>\n";
}

function admin_footer() {
 ?>
     </TD>
    </TR>
   </TABLE>
   </BODY>
  </HTML>
 <?
}

?>