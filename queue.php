<?

function displayMain() {
  include "functions.inc";
  include "theme.inc";
  

  dbconnect();

  $result = mysql_query("SELECT * FROM queue");

  $content .= "<P>Anyone who happens by, and has some news or some thoughts they'd like to share, can <A HREF=\"submit.php\">submit</A> new content for consideration.  After someone has submitted something, their story is added to a queue.  All registered users can access this list of pending stories, that is, stories that have been submitted, but do not yet appear on the public front page.  Those registered users can vote whether they think the story should be posted or not.  When enough people vote to post a story, the story is pushed over the threshold and up it goes on the public page.  On the other hand, when too many people voted to drop a story, the story will be trashed.</P><P>Basically, this means that you, the community, are truly the editors of this site as you have the final decision on the content of this site.  It's you judging the overall quality of a story.  But remember, vote on whether the story is interesting, not on whether you agree with it or not.  If the story goes up, you can disagree all you want, but don't vote 'no' because you think the ideas expressed are wrong.  Instead, vote 'no' when you think the story is plain boring.</P>";
  $content .= "<TABLE BORDER=\"0\" CELLSPACING=\"2\" CELLPADDING=\"2\">\n";
  $content .= " <TR BGCOLOR=\"$bgcolor1\"><TD>Subject</TD><TD>Category</TD><TD>Date</TD><TD>Author</TD><TD>Score</TD></TR>\n";

  while ($submission = mysql_fetch_object($result)) {
    $content .= " <TR><TD WIDTH=\"100%\"><A HREF=\"queue.php?op=view&qid=$submission->qid\">$submission->subject</A></TD><TD>$submission->category</TD><TD NOWRAP>". date("Y-m-d h:m:s", $submission->timestamp) ."</TD><TD NOWRAP>$submission->uname</TD><TD>O</TD></TR>\n";
  }

  $content .= "</TABLE>\n";

  $theme->header();
  $theme->box("Pending stories", $content);
  $theme->footer();
}

function displaySubmission($qid) {
  include "functions.inc";
  include "theme.inc";
 
  dbconnect();

  $result = mysql_query("SELECT * FROM queue WHERE qid = $qid");
  $submission = mysql_fetch_object($result);

  $theme->header();
  $theme->article("", $submission->uname, $submission->time, $submission->subject, "", $submission->abstract, "", $submission->article, "[ <A HREF=\"javascript: history.back()\"><FONT COLOR=\"$theme->hlcolor2\">back</FONT></A> ]");
  $theme->footer(); 
}

switch($op) {
  case "view":
    displaySubmission($qid);
    break;
  default:
    displayMain();
    break;
}

?>