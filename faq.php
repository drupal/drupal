<?

include "functions.inc";
include "theme.inc";

$output = "
  <DL>
   <DT><B>What is a FAQ?</B></DT>
   <DD>
    The <I>Online Jargon Files</I> written by Eric Raymond define a FAQ as:
    <P><B>FAQ</B> /F-A-Q/ or /fak/ n.<BR>[Usenet] 1. A Frequently Asked Question. 2. A compendium of accumulated lore, posted periodically to high-volume newsgroups in an attempt to forestall such questions.  Some people prefer the term FAQ list or FAQL /fa'kl/, reserving FAQ' for sense 1.</P>
    <P><B>RTFAQ</B> /R-T-F-A-Q/ imp.<BR>[Usenet: primarily written, by analogy with <A HREF=\"#RTFM\">RTFM</A>] Abbreviation for \"Read The FAQ!\", an exhortation that the person addressed ought to read the newsgroup's FAQ list before posting questions.</P>
    <P><B><A NAME=\"RTFM\">RTFM</A></B> /R-T-F-M/ imp.<BR>[Unix] Abbreviation for \"Read The Fucking Manual\". 1. Used by gurus to brush off questions they consider trivial or annoying.  2. Used when reporting a problem to indicate that you aren't just asking out of randomness. \"No, I can't figure out how to interface Unix to my toaster, and yes, I have RTFM.\"  Unlike sense 1, this use is considered polite.</P>
    <P><B>user</B> n.<BR>1. Someone doing `real work' with the computer, using it as a means rather than an end. Someone who pays to use a computer.  2. A programmer who will believe anything you tell him.  One who asks silly questions. [GLS observes: This is slightly unfair.  It is true that users ask questions (of necessity). Sometimes they are thoughtful or deep.  Very often they are annoying or downright stupid, apparently because the user failed to think for two seconds or look in the documentation before bothering the maintainer.]  3. Someone who uses a program from the outside, however skillfully, without getting into the internals of the program.  One who reports bugs instead of just going ahead and fixing them.</P>
   </DD>

   <DT><B>What is this site all about?</B></DT>
   <DD>under construction<P></DD>

   <DT><B><A NAME=\"moderation\">How does submission moderation work?</A></B></DT>
   <DD>under construction<P></DD>

   <DT><B>How does comment moderation work?</B></DT>
   <DD>under construction<P></DD>

   <DT><B>Why would I want to create a user account?</B></DT>
   <DD>under construction<P></DD>

   <DT><B>I forgot my password, what do I do?</B></DT>
   <DD>under construction<P></DD>

   <DT><B>I have a cool story that you should post, what do I do?</B></DT>
   <DD>Check out the <A HREF=\"submit.php\">submission form</A>.  If you fill out that form, your contribution gets shipped off to the submission queue for evaluation, <A HREF=\"#moderation\">moderation</A>, and possibly even posting.<P></DD>

   <DT><B>Why did my comment get deleted?</B></DT>
   <DD>It probably didn't.  It probably just got moderated down by our army of moderators. Try browsing at a lower threshold and see if your comment becomes visible.<P></DD>

   <DT><B>Can I syndicate content from this site?</B></DT>
   <DD>under construction<P></DD>

   <DT><B>Is the source code of this site available?</B></DT>
   <DD>Not yet, but it will be released as soon we have a first, well-rounded source tree that has proven to be stable.<BR>This site is powered by <A HREF=\"http://www.fsf.org/\">Free Software</A>; including <A HREF=\"http://www.apache.org/\">Apache</A>, <A HREF=\"http://www.php.net/\">PHP</A>, <A HREF=\"http://www.mysql.com/\">MySQL</A> and <A HREF=\"http://www.linux.com/\">Linux</A>, and is inspired by several <A HREF=\"http://www.fsf.org/\">Free Software</A> projects.  Therefor we have decided to make the software engine of this site available under terms of GPL.<P></DD>

   <DT><B>What is your privacy policy?</B></DT>
   <DD>under construction<P></DD>
  </DL>
  ";

$theme->header();
$theme->box("Frequently Asked Questions", $output);
$theme->footer();

?>