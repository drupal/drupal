<HTML>
 <HEAD>
  <TITLE><? include "config.inc"; echo $sitename; ?></TITLE>
  <META NAME="description" CONTENT="geek-village: release your inner geek">
  <META NAME="keywords" CONTENT="geek, nerd, weblog, portal, computer, sience, news, announcements, hype, cult, irc, foo, bar">
 </HEAD>
 <STYLE type="text/css"> 
   <!--
    BODY,TD,P,UL,LI,DIV,FORM,EM,BLOCKQUOTE { font-size: 8pt; font-family: verdana,helvetica,arial; }
   -->
 </STYLE>
 <BODY TEXT="#000000" BGCOLOR="#FEFEFE" ALINK="#D5AE83" LINK="#CECECE" VLINK="#FEFEFE">
 <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
  <TR>
   <TD COLSPAN="3"><IMG SRC="images/logo.gif" ALT="drop.org logo"></TD>
  </TR>
  <TR><TD ALIGN="right" COLSPAN="3"><FONT SIZE="2"><A HREF="">home</A> | <A HREF="/faq.php"><IMG BORDER="0" SRC="themes/Jeroen/images/dropfaq.gif" ALT="Frequently Asked Questions"></A> | <A HREF="/search.php">search</A> | <A HREF="/submit.php">submit news</A> | <A HREF="/account.php">user account</A> | <A HREF="/webboard.php">webboard</A></FONT><HR></TD></TR>
  <TR>
   <TD VALIGN="top" WIDTH="120">
    <?
      dbconnect();

      ### Display admin blocks:
      displayAdminblock();

      ### Display referring sites:
      displayReferrals();
    ?>
   <TD VALIGN="top" WIDTH="440">
