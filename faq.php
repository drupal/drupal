<?PHP

function defaultDisplay() {
  include "functions.inc";
  include "theme.inc";
  $theme->header();
  ?>
   <PRE>
    <FONT FACE="courier">
<H3>General</H3>

Jeroen was here.

* Mission statement:
--------------------

   - New generation of weblog systems: mixture of slashdot.org,
     squishdot.org, kuro5hin.org, etc.
   - History


* Frequently asked questions:
-----------------------------

   1. What is this site about, alas what is our 'mission statement'?
        See above.


   2. What kind of news should I submit?
        Anything you find interesting.  Read the site for a bit.
        If the stories that appear on this site interest you, and 
        you come across a story that also interests you, chances 
        are, it will interest us too.
        In general we prefer stories that some meat to them.  We
        encourage submitters to extend their posts, and perhaps
        to offer some insight or explanation as to why they 
        thought their item was interesting, and what it means to 
        us.
        todo: automatically generate a list of the available
              news categories.
   
   3. How to create an account?
        - todo: explanation to write.

   4. What can I do with an account?
        - todo: check completed features (see below) as for now.

   5. What is comment moderation and how does it work?
        After a weblog gains some popularity, an inevitable question
        shows up: "how do we sort the wheat from the chaff?".
        The purpose of comment moderation is two-fold:
          * To bring the really good comments to everyone's attention.
          * To hide or get get rid of spam, flamebait and trolls.
        In the latter, comment moderation provides a technical solution
        to a social problem.

   6. What is story moderation and how does it work?
        Under construction.

   7. Is the source code of this weblog engine available?
        This site is powered by <A HREF="http://www.fsf.org/">Free Software</A>; including <A HREF="http://www.apache.org/">Apache</A>, 
        <A HREF="http://www.php.net/">PHP</A>, <A HREF="http://www.mysql.com/">MySQL</A> and <A HREF="http://www.linux.com/">Linux</A>.  Therefor we have decided to make 
        the software engine of this site available under terms of
        GPL.


* Disclaimer:
-------------

   - todo: general disclaimer
   - Short version: comments are owned by the poster and this site is
                    not responsible for what tey say.

    </FONT>
   </PRE>
  <?php
  $theme->footer();
}

switch($op) {
  default:
    defaultDisplay();
    break;
}

?>