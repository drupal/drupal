<?

 class Theme {
   ### color set #1:
   var $bgcolor1 = "#6C6C6C";   // header color
   var $fgcolor1 = "#000000";   // link  color
   var $hlcolor1 = "#";         // ? color

   ### color set #2:
   var $bgcolor2 = "#E7E7E7";   // color
   var $fgcolor2 = "#FEFEFE";   // header-text color
   var $hlcolor2 = "#E09226";   // rust color


   ######
   # Syntax.......: header($title);
   # Description..: a function to draw the page header.
   function header($title) {
    global $user;
    ?>
     <HTML>
      <HEAD>
       <TITLE><? include "config.inc"; echo $sitename; ?> - Tears of technology</TITLE>
       <META NAME="description" CONTENT="drop.org">
       <META NAME="keywords" CONTENT="drop, weblog, portal, community, news, article, announcements, stories, story, computer, science, space, hype, cult, geek, nerd, foo, bar">
      </HEAD>
      <STYLE type="text/css"> 
       <!--
        BODY,P,DIV,LI,UL,TD,EM, FONT,BLOCKQUOTE,FORM{font-size: 10pt; font-family:Helvetica,Lucida,sans-serif;}
	SMALL {font-size: 8pt;}
       -->
      </STYLE>
      <BODY TEXT="#202020" BGCOLOR="#FEFEFE" BACKGROUND="themes/Jeroen/images/background.gif" ALINK="#000000" LINK="#000000" VLINK="#000000">
       <TABLE WIDTH="770" ALIGN="left" BORDER="0" CELLPADDING="0" CELLSPACING="6">
        <TR>
         <TD COLSPAN="2">
	  <? if (rand(0,150) == 75) $img = "logo2.gif"; else $img = "logo.gif"; ?>
	  <IMG SRC="themes/Jeroen/images/<? echo $img; ?>" ALT="drop.org logo"><BR><BR>
	 </TD>
         <TD WIDTH="160" VALIGN="top" ALIGN="left">
          <?  
           $this->box("Status", "<SMALL>Real name: $user->name<BR>User name: $user->name<BR>E-mail: $user->email<BR>Mojo: infinite<BR>Status: Logged in<BR>Since: 04/06/00, 12:20</SMALL>");
          ?>
         </TD>
        </TR>
        <TR>
         <TD WIDTH="160" VALIGN="top" ALIGN="right">
          <?
           dbconnect();

           ### Display admin blocks:
           displayAdminblock($this);
          ?>
         </TD>
         <TD WIDTH="430" VALIGN="top" ALIGN="left">
    <?
   }

   ######
   # Syntax.......: abstract(...);
   # Description..: a function to draw an abstract story box, that is the
   #                boxes displayed on the main page.
   function abstract($story) {
     include "config.inc";

      $story->timestamp = date("l, F d, Y - h:i:s A", $story->timestamp);
     ?>
         <TABLE WIDTH="100%" CELLPADDING="0" CELLSPACING="0" BORDER="0">
          <TR>
           <TD ALIGN="left" VALIGN="bottom" HEIGHT="20">
            <? if (rand(0,1) == 0) $img = "news1.gif"; else $img = "news3.gif"; ?><IMG SRC="themes/Jeroen/images/<? echo $img; ?>" WIDTH="20" HEIGHT="20" ALT=""></TD>
           <TD colspan="3" ALIGN="center" VALIGN="center" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsmiddle.gif">
            <B><? echo $story->subject; ?></B>
           </TD>
           <TD ALIGN="right" VALIGN="bottom" HEIGHT="20">
            <? if (rand(0,1) == 0) $img = "news2.gif"; else $img = "news4.gif"; if (rand(0,100) == 50) $img = "news5.gif"; ?><IMG SRC="themes/Jeroen/images/<? echo $img; ?>" WIDTH="20" HEIGHT="20" ALT=""></TD>
          </TR>
          <TR>
           <TD ALIGN="left" VALIGN="bottom" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxleft.gif">
            &nbsp;
           </TD>
           <TD COLSPAN="2" ALIGN="left" WIDTH="100%" BGCOLOR="#6C6C6C" HEIGHT="20" BACKGROUND="themes/Jeroen/images/menutitle.gif">
            &nbsp;<FONT COLOR="<? echo $this->fgcolor2; ?>">
             <?
              switch (rand(0,12)) {
		case 0: $how = "Yelled at us"; break;
		case 1: $how = "Whispered"; break;
		case 2: $how = "Reported"; break;
		case 3: $how = "Posted"; break;
		case 4: $how = "Beamed through"; break;
		case 5: $how = "Faxed"; break;
		case 6: $how = "Tossed at us"; break;
		case 7: $how = "Morsed"; break;
		case 8: $how = "Flagged"; break;
		case 9: $how = "Written to us"; break;
		case 10: $how = "Made up"; break;
		case 11: $how = "Uploaded"; break;
		default: $how = "Sneaked through";
              }

              if ($story->userid) {
               print "<FONT SIZE=\"-1\">$how by <A HREF=\"account.php?op=userinfo&uname=$story->userid\">$story->userid</A> on $story->timestamp"; ?><? print "</FONT>
           </TD>
           <TD ALIGN=\"right\" BGCOLOR=\"#6C6C6C\" BACKGROUND=\"themes/Jeroen/images/menutitle.gif\">
            <B><A HREF=\"search.php?category=$story->category\"><FONT COLOR=\"<? $this->fgcolor3; ?>\">$story->category</FONT></A></B>&nbsp;";
              }
              else {
               print "<FONT SIZE=\"-1\">Reported to us by $anonymous on $story->timestamp"; ?><? print "</FONT>
           </TD>
           <TD ALIGN=\"right\" WIDTH=\"65\"><A HREF=\"search.php?category=$story->category\"><FONT COLOR=\"<? $this->fgcolor3; ?>\">$story->category</FONT></A>";
              }
             ?></FONT>
           </TD>
           <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxright.gif">
            &nbsp;
           </TD>
          </TR>
          <TR>
           <TD ALIGN="left" BACKGROUND="themes/Jeroen/images/newsboxleft.gif" WIDTH="20">
            &nbsp;
           </TD>
           <TD COLSPAN="3" ALIGN="center" VALIGN="top" WIDTH="100%" BGCOLOR="#E7E7E7" BACKGROUND="themes/Jeroen/images/sketch.gif">
            <TABLE WIDTH="100%">
             <TR>
              <TD>
               <?
                if ($story->updates) {
                 echo "<P>$story->abstract</P><P><FONT COLOR=\"$this->hlcolor1\">Editor's note by <A HREF=\"account.php?op=userinfo&uname=$story->editor\">$story->editor</A>:</FONT>$story->updates</P>";
                }
                else {
                 echo $story->abstract;
                }
               ?>
               <TR>
                <TD ALIGN="right">
                 <? echo $link; ?>
                </TD>
               </TR>
              </TD>
             </TR>
            </TABLE>
           </TD>
           <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxright.gif">
            <IMG SRC="themes/Jeroen/images/newsboxright.gif" WIDTH="20" HEIGHT="20" ALT="">
           </TD>
          </TR>
          <TR>
           <TD ALIGN="left" VALIGN="top" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxbottomleft.gif">&nbsp;</TD>
           <TD WIDTH="100%" COLSPAN="3" ALIGN="center" HEIGHT="20" VALIGN="top" BACKGROUND="themes/Jeroen/images/newsboxbottom.gif">&nbsp;</TD>
           <TD ALIGN="right" VALIGN="top" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxbottomright.gif">&nbsp;</TD>
          </TR>
         </TABLE>
    <?	
   }

   ######
   # Syntax.......: article(...);
   # Description..: a function to dispay a complete article (without user 
   #                comments).  It's what you get when you followed for
   #                instance one of read-more links on the main page.
   function article($story, $reply) {
     include "config.inc";

     $story->timestamp = date("l, F d, Y - h:i:s A", $story->timestamp);
      ?>
         <TABLE WIDTH="100%" CELLPADDING="0" CELLSPACING="0" BORDER="0">
          <TR>
           <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20">
            <? if (rand(0,1) == 0) $img = "news1.gif"; else $img = "news3.gif"; ?><IMG SRC="themes/Jeroen/images/<? echo $img; ?>" WIDTH="20" HEIGHT="20" ALT=""></TD>
           <TD colspan="3" ALIGN="center" VALIGN="center" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsmiddle.gif">
            <IMG SRC="themes/Jeroen/images/newsmiddle.gif" width="1" height="1" alt=""><B><? echo $story->subject; ?></B></TD>
           <TD ALIGN="left" VALIGN="bottom" WIDTH="20" HEIGHT="20">
            <? if (rand(0,1) == 0) $img = "news2.gif"; else $img = "news4.gif"; ?><IMG SRC="themes/Jeroen/images/<? echo $img; ?>" WIDTH="20" HEIGHT="20" ALT=""></TD>
          </TR>
          <TR>
           <TD ALIGN="left" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxleft.gif" >
            <IMG SRC="themes/Jeroen/images/newsboxleft.gif" WIDTH="20" HEIGHT="20" alt="">
           </TD>
           <TD COLSPAN="2" ALIGN="left" WIDTH="100%" BGCOLOR="#6C6C6C" HEIGHT="20"  BACKGROUND="themes/Jeroen/images/menutitle.gif" NOWRAP>
            &nbsp;<FONT COLOR="<? echo $this->fgcolor2; ?>">
            <?
             switch (rand(0,12)) {
	     case 0: $how = "Yelled at us"; break;
	     case 1: $how = "Whispered"; break;
	     case 2: $how = "Reported"; break;
	     case 3: $how = "Posted"; break;
	     case 4: $how = "Beamed through"; break;
	     case 5: $how = "Faxed"; break;
	     case 6: $how = "Tossed at us"; break;
	     case 7: $how = "Morsed"; break;
	     case 8: $how = "Flagged"; break;
	     case 9: $how = "Written to us"; break;
	     case 10: $how = "Made up"; break;
	     case 11: $how = "Uploaded"; break;
	     default: $how = "Sneaked through";
             }

             if ($story->userid) {
              print "<FONT SIZE=\"-1\">$how by <A HREF=\"account.php?op=userinfo&uname=$story->userid\">$story->userid</A> on $story->timestamp"; ?><? print "</FONT>
           </TD>
           <TD ALIGN=\"right\" WIDTH=\"80\" BGOLOR=\"6C6C6C\" BACKGROUND=\"themes/Jeroen/images/menutitle.gif\">
            <B><A HREF=\"search.php?category=$story->category\"><FONT COLOR=\"<? $this->fgcolor3; ?>\">$story->category</FONT></A></B>&nbsp;";
             }
             else {
              print "<FONT SIZE=\"-1\">Reported to us by $anonymous on $story->timestamp"; ?><? print "</FONT>
           </TD>
           <TD ALIGN=\"center\" WIDTH=\"80\" BGOLOR=\"6C6C6C\" BACKGROUND=\"themes/Jeroen/images/menutitle.gif\">
            <A HREF=\"search.php?category=$category\"><FONT COLOR=\"<? $this->fgcolor3; ?>\">$story->category</FONT></A>";
             }
            ?></FONT>
           </TD>
           <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxright.gif">
            <IMG SRC="themes/Jeroen/images/newsboxright.gif" width="20" height="20" alt="">
           </TD>
          </TR>
          <TR>
           <TD ALIGN="left" BACKGROUND="themes/Jeroen/images/newsboxleft.gif" WIDTH="20">&nbsp;
           </TD>
           <TD COLSPAN="3" VALIGN="top" width="100%" BGCOLOR="#E7E7E7" BACKGROUND="themes/Jeroen/images/sketch.gif">
            <TABLE WIDTH="100%">
             <TR>
              <TD>
               <?
                if ($story->updates) {
                 echo "<P>$story->abstract</P><P><FONT COLOR=\"$this->hlcolor1\">Editor's note by <A HREF=\"account.php?op=userinfo&uname=$story->editor\">$story->editor</A>:</FONT>$story->updates</P>";
                }
                else {
                 echo $story->abstract;
                }
	        if ($story->article) echo "<P>$story->article</P>";
               ?>
               <TR>
                <TD ALIGN="right">
                 <? echo $reply; ?>
                </TD>
               </TR>
              </TD>
             </TR>
            </TABLE>
           </TD>
           <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxright.gif">
            <IMG SRC="themes/Jeroen/images/newsboxright.gif" WIDTH="20" HEIGHT="20" ALT="">
           </TD>
          </TR>
          <TR>
           <TD ALIGN="left" VALIGN="top" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxbottomleft.gif">&nbsp;</TD>
           <TD COLSPAN="3" ALIGN="center" HEIGHT="20" VALIGN="top" BACKGROUND="themes/Jeroen/images/newsboxbottom.gif">&nbsp;</TD>
           <TD ALIGN="right" VALIGN="top" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsboxbottomright.gif">&nbsp;</TD>
          </TR>
         </TABLE>
    <?	
   }

   ######
   # Syntax.......: commentControl(...);
   # Description..: this function is used to theme the comment control box.
   function commentControl($sid, $title, $thold, $mode, $order) {
     global $user;
     dbconnect();
     $query = mysql_query("SELECT sid FROM comments WHERE sid = $sid");

     if (!$query) $count = 0; else $count = mysql_num_rows($query);
     if (!isset($thold)) $thold = 0; 

    ?>
         <TABLE WIDTH="100%" BORDER="0" CELLSPACING="0" CELLPADDING="0">
          <TR>
           <TD ALIGN="left" VALIGN="bottom" WIDTH="20" HEIGHT="20">
	    <IMG SRC="themes/Jeroen/images/news1.gif" WIDTH="20" HEIGHT="20" ALT=""></TD>
	   <TD ALIGN="center" VALIGN="center" WIDTH="100%" HEIGHT="20" BACKGROUND="themes/Jeroen/images/newsmiddle.gif">
	    <B>Comment control</B></TD>
           <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20">
            <IMG SRC="themes/Jeroen/images/news4.gif" WIDTH="20" HEIGHT="20" ALT=""></TD>
          </TR>
          <TR>
           <TD COLSPAN="3" ALIGN="center" BGCOLOR="#E7E7E7" BACKGROUND="themes/Jeroen/images/sketch.gif">
            <FORM METHOD="get" ACTION="article.php">
             <FONT SIZE="2"> 
             <SELECT NAME="thold">
              <OPTION VALUE="-1" <? if ($thold == -1) { echo "SELECTED"; } ?>>Threshold: -1
              <OPTION VALUE="0" <? if ($thold == 0) { echo "SELECTED"; } ?>>Threshold: 0
              <OPTION VALUE="1" <? if ($thold == 1) { echo "SELECTED"; } ?>>Threshold: 1
	      <OPTION VALUE="2" <? if ($thold == 2) { echo "SELECTED"; } ?>>Threshold: 2
              <OPTION VALUE="3" <? if ($thold == 3) { echo "SELECTED"; } ?>>Threshold: 3
              <OPTION VALUE="4" <? if ($thold == 4) { echo "SELECTED"; } ?>>Threshold: 4
	      <OPTION VALUE="5" <? if ($thold == 5) { echo "SELECTED"; } ?>>Threshold: 5
             </SELECT> 
	     <SELECT NAME="mode">
              <OPTION VALUE="nocomments" <? if ($mode == 'nocomments') { echo "SELECTED"; } ?>>No comments
              <OPTION VALUE="nested" <? if ($mode == 'nested') { echo "SELECTED"; } ?>>Nested
              <OPTION VALUE="flat" <? if ($mode == 'flat') { echo "SELECTED"; } ?>>Flat
              <OPTION VALUE="threaded" <? if (!isset($mode) || $mode=='threaded' || $mode=="") { echo "SELECTED"; } ?>>Threaded
             </SELECT> 
             <SELECT NAME="order">
              <OPTION VALUE="0" <? if (!$order) { echo "SELECTED"; } ?>>Oldest first
              <OPTION VALUE="1" <? if ($order==1) { echo "SELECTED"; } ?>>Newest first
              <OPTION VALUE="2" <? if ($order==2) { echo "SELECTED"; } ?>>Highest scoring first
             </SELECT> 
             <INPUT TYPE="hidden" NAME="sid" VALUE="<? echo "$sid"; ?>"> <INPUT TYPE="submit" VALUE="Refresh">
             <?
              if (isset($user)) echo "<BR><CENTER><INPUT TYPE=\"checkbox\" NAME=\"save\"> Save preferences</CENTER>"; 
             ?>
             </FONT>
            </FORM>
           </TD>
          </TR>
      <?
       $result = mysql_query("SELECT COUNT(cid) FROM comments WHERE sid = $sid AND score < $thold");
       if ($result && $number = mysql_result($result, 0)) {
      ?>
          <TR>
           <TD ALIGN="center" BGCOLOR="<? echo "$this->bgcolor2"; ?>">
            <SMALL><FONT COLOR="<? echo "$this->fgcolor2"; ?>">There are at least <? echo $number; ?> comments below your threshold.</FONT></SMALL>
           </TD>
          </TR>
           <?
            }
           ?> 
         </TABLE>
    <?
   }

   ######
   # Syntax.......: comment(...);
   # Description..: this function is used to theme user comments.
   function comment($poster, $subject, $cid, $date, $url, $email, $score, $reason, $comment, $link, $thread = "") {
     include "config.inc";

     if (!eregi("[a-z0-9]",$poster)) $poster = $anonymous;
     if (!eregi("[a-z0-9]",$subject)) $subject = "[no subject]";
     echo "<A NAME=\"$cid\">";

     ### Create comment header:
     echo "<TABLE BORDER=\"0\" CELLPADDING=\"4\" CELLSPACING=\"2\" WIDTH=\"100%\">";
     echo " <TR BGCOLOR=\"#E7E7E\" BACKGROUND=\"themes/Jeroen/images/sketch.gif\">";
     echo "  <TD>";
     echo "   <TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"2\"WIDTH=\"100%\">";

     ### Subject:
     echo "    <TR>";
     echo "     <TD ALIGN=\"right\" WIDTH=\"5%\"><B>Subject:</B></TD><TD WIDTH=\"80%\">";
     echo "      <B>$subject</B>";
     echo " &nbsp; <FONT SIZE=\"2\"> [Score: $score";
     if (isset($reason)) echo ", $comments_meta_reasons[$reason]";
     echo "]</FONT>";
     echo "     </TD>";

     ### Moderation:
     echo "     <TD ALIGN=\"right\" ROWSPAN=\"3\" VALIGN=\"middle\" WIDTH=\"15%\">";
     echo "      <SELECT NAME=\"meta:$cid\">";
     echo "         <OPTION VALUE=\"-1\">Moderate</OPTION>\n";
     for ($i = 0; $i < sizeof($comments_meta_reasons); $i++) {
       echo "       <OPTION VALUE=\"$i\">$comments_meta_reasons[$i]</OPTION>\n";
     }
     echo "      </SELECT>";
     echo "     </TD>";
     echo "    </TR>";

     ### Author:
     echo "    <TR>";
     echo "     <TD ALIGN=\"right\" VALIGN=\"top\">Author:</TD><TD><B>$poster</B> ";
     if ($poster != $anonymous) {
       ### Display extra information line:
       $info .= "<A HREF=\"account.php?op=userinfo&uname=$poster\">user info</A>";
       if ($email) $info .= " | <A HREF=\"mailto:$email\">$email</A>";
       if (eregi("http://",$url)) $info .= " | <A HREF=\"$url\" TARGET=\"_new\">$url</A>";
       echo "<BR><FONT SIZE=\"2\">[ $info ]</FONT>";
     }
     echo "     </TD>";
     echo "    </TR>";

     ### Date
     echo "    <TR><TD ALIGN=\"right\">Date:</TD><TD>". formatTimestamp($date) ."</TD></TR>";

     echo "   </TABLE>";
     echo "  </TD>";
     echo " </TR>";

     ### Print body of comment:
     if ($comment) echo " <TR><TD BGCOLOR=\"#E7E7E7\" BACKGROUND=\"themes/Jeroen/images/sketch.gif\">$comment</TD></TR>";

     ### Print thread (if any):
     if ($thread) echo " <TR><TD BGCOLOR=\"#E7E7E7\" BACKGROUND=\"themes/Jeroen/images/sketch.gif\">$thread</TD></TR>";

     ### Print bottom link(s):
     echo " <TR><TD ALIGN=\"right\" BGCOLOR=\"#E7E7E7\" BACKGROUND=\"themes/Jeroen/images/sketch.gif\">[ $link ]</TD></TR>";
     echo " </TABLE>";
   }

   ######
   # Syntax.......: preview(...);
   # Description..: this function is used to preview a story and is used at
   #                different parts of the homepage: when a visitors sumbits
   #                news, when an editor wants to post news, when people
   #                check the entries in the sumbission queue, etc.
   function preview($editor, $informant, $timestamp, $subject, $department, $abstract, $comments, $article) {
     include "config.inc";
     ?>
      <TABLE BORDER="0" CELLPADDING="4" WIDTH="100%">
       <TR BGCOLOR="<? echo $this->bgcolor1; ?>">
        <TD><FONT COLOR="<? echo $this->bgcolor2; ?>">
         <B><? echo $subject; ?></B></FONT>
        </TD>
       </TR>
       <TR BGCOLOR="<? echo $this->bgcolor2; ?>">
        <TD>
         <?
          if ($informant) {
           print "<FONT SIZE=\"-1\">Posted by <A HREF=\"account.php?op=userinfo&uname=$informant\">$informant</A> on $timestamp"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT>";
          }
          else {
           print "<FONT SIZE=\"-1\">Posted by $anonymous on $timestamp"; ?><? if ($department) echo "<BR>from the $department dept."; ?><? print "</FONT>";
          }
         ?>
        </TD>
       </TR>
       <TR BGCOLOR="<? echo $this->bgcolor3; ?>">
        <TD>
         <? 
          if ($abstract) echo "<P>$abstract<P>";
          if ($comments) echo "<P><FONT COLOR=\"$this->bgcolor1\">Editor's note by <A HREF=\"account.php?op=userinfo&uname=$editor\">$editor</A>:</FONT> $comments</P>";
          if ($article) echo "<P>$article</P>";
         ?>
        </TD>
       </TR>
       <TR BGCOLOR="<? echo $this->bgcolor2; ?>"><TD ALIGN="right">&nbsp;</TD></TR>
      </TABLE>
     <?
   }
 
   ######
   # Syntax.......: box($title, $body);
   # Description..: a function to draw a box/block.
   function box($subject, $content) { 
     include "config.inc";
     if (rand(0,50) == 25) $img = "boxbottomright2.gif";
     else $img = "boxbottomright1.gif";
     $width = rand(10,200);
     if (rand(0,100) == 50) $img2 = "boxtopleftside2.gif";
     else $img2 ="boxtopleftside1.gif";
    ?>
    <TABLE WIDTH="100%" ALIGN="center" CELLPADDING="0" CELLSPACING="0" BORDER="0">
     <TR>
      <TD ALIGN="left" VALIGN="bottom" WIDTH="20" HEIGHT="20">
       <IMG SRC="themes/Jeroen/images/boxtopleft.gif" WIDTH="20" HEIGHT="20" ALT=""></TD>
      <TD HEIGHT="20" WIDTH="<? echo $width; ?>" BACKGROUND="themes/Jeroen/images/boxtop.gif">&nbsp;</TD>
      <TD HEIGHT="20" WIDTH="20" BACKGROUND="themes/Jeroen/images/boxtopmiddle.gif">
       &nbsp;
      </TD>
      <TD VALIGN="bottom" HEIGHT="20" BACKGROUND="themes/Jeroen/images/boxtop.gif">&nbsp;</TD>
      <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/boxtopright.gif">
      &nbsp;
      </TD>
     </TR>
     <TR>
      <TD ALIGN="left" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/<? echo $img2; ?>">
       &nbsp;
      </TD>
      <TD COLSPAN="3" ALIGN="center" BGCOLOR="#6C6C6C" HEIGHT="20" BACKGROUND="themes/Jeroen/images/menutitle.gif">
       <FONT COLOR="<? echo $this->fgcolor2; ?>"><?  echo $subject; ?></FONT>
      </TD>
      <TD ALIGN="right" VALIGN="bottom" WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/boxtoprightside.gif">
       &nbsp;
      </TD>
     </TR>
     <TR>
      <TD ALIGN="left" BACKGROUND="themes/Jeroen/images/boxleft.gif" WIDTH="20">&nbsp;</TD>
      <TD COLSPAN="3" ALIGN="center" VALIGN="top" WIDTH="100%" BGCOLOR="#E7E7E7" BACKGROUND="themes/Jeroen/images/sketch.gif">
       <TABLE WIDTH="100%">
        <TR>
         <TD>
          <? echo $content; ?>
         </TD>
        </TR>
       </TABLE>
      </TD>
      <TD ALIGN="right" BACKGROUND="themes/Jeroen/images/boxright.gif" WIDTH="20">&nbsp;</TD>
     </TR>
     <TR>
      <TD ALIGN="left" VALIGN=TOP WIDTH="20" HEIGHT="20" BACKGROUND="themes/Jeroen/images/boxbottomleft.gif">
       &nbsp;
      </TD>
      <TD COLSPAN="3" ALIGN="center" HEIGHT="20" VALIGN="top" BACKGROUND="themes/Jeroen/images/boxbottom.gif">
       &nbsp;</TD>
      <TD ALIGN="right" VALIGN="top" WIDTH="20" HEIGHT="20">
       <IMG SRC="themes/Jeroen/images/<? echo $img; ?>" WIDTH="20" HEIGHT="20" ALT=""></TD>
     </TR>
    </TABLE>
    <BR>
    <?
   }

   ######
   # Syntax.......: footer();
   # Description..: a function to draw the page footer.
   function footer() {
    ?>
        </TD>
        <TD WIDTH="180" VALIGN="top" ALIGN="right">
         <?
         global $PHP_SELF; 
 
	 $this->box("Drop where?", "<TD ALIGN=\"left\" VALIGN=\"top\"><A HREF=\"index.php\">home</A><BR><A HREF=\"faq.php\">faq</A><BR><A HREF=\"search.php\">search</A></TD><TD ALIGN=\"right\" VALIGN=\"top\"><A HREF=\"submit.php\">submit news</A><BR><A HREF=\"account.php\">your account</A></TD>");

         if (strstr($PHP_SELF, "index.php")) {
           global $user, $date;

           ### Display account:
           displayAccount($this);

           ### Display calendar:
           displayCalendar($this, $date);

           ### Display calendar:
           displayOldHeadlines($this);
 
           ### Display voting poll:
           displayPoll($this);
         }
         elseif (strstr($PHP_SELF, "account.php")) {
           ### Display account settings:
           displayAccountSettings($this);

           ### Display account:
           displayAccount($this);
         }
         elseif (strstr($PHP_SELF, "submit.php")) {
           ### Display account:
           displayAccount($this);

           ### Display new headlines:
           displayNewHeadlines($this);
         }
         elseif (strstr($PHP_SELF, "discussion.php")) {
           global $id;
           
           if ($id && $story = id2story($id)) {
             if ($story->status == 2) {
               ### Display account:
               displayAccount($this);
      
               ### Display related links:
               displayRelatedLinks($this, $story);

               ### Display new headlines:
               displayNewHeadlines($this);
             }
             else {
               ### Display results of moderation:
               displayModerationResults($this, $story);
             }
           }
           else {
             ### Display account:
             displayAccount($this);

             ### Display new headlines:
             displayNewHeadlines($this);
           }
         }
         else {
           ### Display new headlines:
           displayNewHeadlines($this);
         }
         
         ?>
        </TD>
       </TR>
       <TR>
        <TD WIDTH="160 ALIGN="right" VALIGN="bottom" HEIGHT="20">
         <IMG SRC="themes/Jeroen/images/footerleft.gif" WIDTH="20" HEIGHT="20" ALT="">
        </TD>
        <TD WIDTH="100%" BACKGROUND="themes/Jeroen/images/footer.gif" ALIGN="center" VALIGN="center" HEIGHT="20">
         <FONT COLOR="<? echo $this->hlcolor2; ?>" SIZE="2">[ <A HREF="">home</A> | <A HREF="faq.php">faq</A> | <A HREF="search.php">search</A> | <A HREF="submit.php">submit news</A> | <A HREF="account.php">your account</A> ] </FONT>
        </TD>
        <TD WIDTH="160" ALIGN="left" VALIGN="bottom" HEIGHT="20">
         <IMG SRC="themes/Jeroen/images/footerright.gif" WIDTH="20" HEIGHT="20" ALT="">
        </TD>
       </TR>
      </TABLE>
     </BODY> 
    </HTML>
    <?
   }
 }

?>
