<?php
$bgcolor1 = "#CECECE";
$bgcolor2 = "#486591";
$bgcolor3 = "#CECECE";

function themeindex($editor, $informant, $datetime, $subject, $abstract, $comments, $category, $department, $link) {
  global $bgcolor1, $bgcolor2, $bgcolor3;

  $datetime = date("l, F d, Y - h:i:s A", $datetime);

  include "config.inc";
   ?>
   <TABLE BORDER="0" CELLPADDING="4" WIDTH="100%">
    <TR BGCOLOR="<? echo $bgcolor1; ?>"><TD COLSPAN="2"><FONT COLOR="<? echo $bgcolor2; ?>"><B><? echo $subject; ?></B></FONT></TD></TR>
    <TR BGCOLOR="<? echo $bgcolor2; ?>">
     <TD>
      <?
       if ($informant) {
         print "<FONT SIZE=\"-1\">Posted by <A HREF=\"account.php?op=userinfo&uname=$informant\">$informant</A> on $datetime"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT></TD><TD ALIGN=\"center\" WIDTH=\"80\"><A HREF=\"search.php?category=$category\">$category</A>";
       }
       else {
         print "<FONT SIZE=\"-1\">Posted by $anonymous on $datetime"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT></TD><TD ALIGN=\"center\" WIDTH=\"80\"><A HREF=\"search.php?category=$category\">$category</A>";
       }
      ?>
     </TD>
    </TR>
    <TR BGCOLOR="<? echo $bgcolor3; ?>">
     <TD COLSPAN="2">
      <?
       if ($comments) {
         echo "<P>$abstract</P><P><FONT COLOR=\"$bgcolor1\">Editor's note by <A HREF=\"account.php?op=userinfo&uname=$editor\">$editor</A>:</FONT> $comments</P>";
       }
       else {
         echo $abstract;
       }        
      ?>
     </TD>
    </TR>
    <TR BGCOLOR="<? echo $bgcolor2; ?>"><TD ALIGN="right" COLSPAN="2"><? echo $link ?></TD></TR>
   </TABLE><BR>
  <?	
}

function themearticle($editor, $informant, $datetime, $subject, $department, $abstract, $comments, $article, $reply) {
  global $bgcolor1, $bgcolor2, $bgcolor3;

  $datetime = date("l, F d, Y - h:i:s A", $datetime);

  include "config.inc";
  ?>
   <TABLE BORDER="0" CELLPADDING="4" WIDTH="100%">
    <TR BGCOLOR="<? echo $bgcolor1; ?>"><TD><FONT COLOR="<? echo $bgcolor2; ?>"><B><? echo $subject; ?></B></FONT></TD></TR>
    <TR BGCOLOR="<? echo $bgcolor2; ?>">
     <TD>
      <?
       if ($informant) {
         print "<FONT SIZE=\"-1\">Posted by <A HREF=\"account.php?op=userinfo&uname=$informant\">$informant</A> on $datetime"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT>";
       }
       else {
         print "<FONT SIZE=\"-1\">Posted by $anonymous on $datetime"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT>";
       }
      ?>
     </TD>
    </TR>
    <TR BGCOLOR="<? echo $bgcolor3; ?>">
     <TD>
      <? 
       if ($abstract) echo "<P>$abstract<P>";
       if ($comments) echo "<P><FONT COLOR=\"$bgcolor1\">Editor's note by <A HREF=\"account.php?op=userinfo&uname=$editor\">$editor</A>:</FONT> $comments</P>";
       if ($article) echo "<P>$article</P>";
      ?>
     </TD>
    </TR>
    <TR BGCOLOR="<? echo $bgcolor2; ?>"><TD ALIGN="right"><? echo "$reply"; ?></TD></TR>
   </TABLE><BR>
  <?
}

function themepreview($editor, $informant, $datetime, $subject, $department, $abstract, $comments, $article) {
  global $bgcolor1, $bgcolor2, $bgcolor3;
  include "config.inc";
  ?>
   <TABLE BORDER="0" CELLPADDING="4" WIDTH="100%">
    <TR BGCOLOR="<? echo $bgcolor1; ?>"><TD><FONT COLOR="<? echo $bgcolor2; ?>"><B><? echo $subject; ?></B></FONT></TD></TR>
    <TR BGCOLOR="<? echo $bgcolor2; ?>">
     <TD>
      <?
       if ($informant) {
         print "<FONT SIZE=\"-1\">Posted by <A HREF=\"account.php?op=userinfo&uname=$informant\">$informant</A> on $datetime"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT>";
       }
       else {
         print "<FONT SIZE=\"-1\">Posted by $anonymous on $datetime"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT>";
       }
      ?>
     </TD>
    </TR>
    <TR BGCOLOR="<? echo $bgcolor3; ?>">
     <TD>
      <? 
       if ($abstract) echo "<P>$abstract<P>";
       if ($comments) echo "<P><FONT COLOR=\"$bgcolor1\">Editor's note by <A HREF=\"account.php?op=userinfo&uname=$editor\">$editor</A>:</FONT> $comments</P>";
       if ($article) echo "<P>$article</P>";
      ?>
     </TD>
    </TR>
    <TR BGCOLOR="<? echo $bgcolor2; ?>"><TD ALIGN="right">&nbsp;</TD></TR>
   </TABLE><BR>
  <?
}

function themebox($subject, $content) { 
  global $bgcolor1, $bgcolor2, $bgcolor3;
  include "config.inc";
  print "<TABLE BORDER=\"0\" CELLPADDING=\"3\" CELLSPACING=\"3\" WIDTH=\"100%\">";
  print " <TR><TD ALIGN=\"center\" BGCOLOR=\"$bgcolor1\"><FONT COLOR=\"$bgcolor2\"><B>$subject</B></FONT></TD></TR>";
  print " <TR><TD BGCOLOR=\"$bgcolor2\">$content</TD></TR>";
  print "</TABLE><BR>";
}
?>