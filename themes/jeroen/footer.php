   </TD>
   <TD VALIGN="top" WIDTH="150">
    <?
     global $PHP_SELF;

     if (strstr($PHP_SELF, "index.php")) {
       global $user;

       ### Display login box:
       displayAccount();

       ### Display voting poll:
       displayPoll();

       ### Display old headlines:
       displayOldHeadlines();
     }
     elseif (strstr($PHP_SELF, "account.php")) {
       ### Display account settings:
       displayAccountSettings();
     }
     elseif (strstr($PHP_SELF, "article.php")) {
       global $sid;

       ### Display related links:
       displayRelatedLinks($sid);

       ### Display new headlines:
       displayNewHeadlines();
     }
     else {
       ### Display new headlines:
       displayNewHeadlines();
     }
    ?>
   </TD>
  </TR>
  <TR>
   <TD ALIGN="center" COLSPAN="3">
    <FONT SIZE="2">[ <A HREF="">home</A> | <A HREF="/faq.php"><IMG BORDER="0" SRC="themes/Jeroen/images/dropfaq.gif" ALT="Frequently Asked Questions"></A> | <A HREF="/search.php">search</A> | <A HREF="/submit.php">submit news</A> | <A HREF="/account.php">user account</A> | <A HREF="/webboard.php">webboard</A> ] </FONT>
   </TD>
  </TR>
 </TABLE>
</BODY>
</HTML>
