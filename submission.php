<?

include "includes/submission.inc";
include "includes/common.inc";

function submission_display_main() {
  global $PHP_SELF, $theme, $user;

  // Perform query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.status = 1 ORDER BY s.id");

  $content .= "<P>Anyone who happens by, and has some news or some thoughts they'd like to share, can <A HREF=\"submit.php\">submit</A> new content for consideration.  After someone has submitted something, their story is added to a queue.  All registered users can access this list of pending stories, that is, stories that have been submitted, but do not yet appear on the public front page.  Those registered users can vote whether they think the story should be posted or not.  When enough people vote to post a story, the story is pushed over the threshold and up it goes on the public page.  On the other hand, when too many people voted to drop a story, the story will get trashed.</P><P>Basically, this means that you, the community, are truly the editors of this site as you have the final decision on the content of this site.  It's you judging the overall quality of a story.  But remember, vote on whether the story is interesting, not on whether you agree with it or not.  If the story goes up, you can disagree all you want, but don't vote `no' because you think the ideas expressed are wrong.  Instead, vote `no' when you think the story is plain boring.</P>";
  $content .= "<TABLE BORDER=\"0\" CELLSPACING=\"4\" CELLPADDING=\"4\">\n";
  $content .= " <TR BGCOLOR=\"$bgcolor1\"><TH>Subject</TH><TH>Category</TH><TH>Date</TH><TH>Author</TH><TH>Score</TH></TR>\n";
  while ($submission = db_fetch_object($result)) {
    if (user_getHistory($user->history, "s$submission->id")) $content .= " <TR><TD WIDTH=\"100%\"><A HREF=\"$PHP_SELF?op=view&id=$submission->id\">". stripslashes($submission->subject) ."</A></TD><TD>$submission->category</TD><TD ALIGN=\"center\">". date("Y-m-d", $submission->timestamp) ."<BR>". date("H:m:s", $submission->timestamp) ."</TD><TD ALIGN=\"center\">". format_username($submission->userid) ."</TD><TD ALIGN=\"center\">". submission_score($submission->id) ."</TD></TR>\n";
    else $content .= " <TR><TD WIDTH=\"100%\"><A HREF=\"$PHP_SELF?op=view&id=$submission->id\">". stripslashes($submission->subject) ."</A></TD><TD>$submission->category</TD><TD ALIGN=\"center\">". date("Y-m-d", $submission->timestamp) ."<BR>". date("H:m:s", $submission->timestamp) ."</TD><TD ALIGN=\"center\">". format_username($submission->userid) ."</TD><TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?op=view&id=$submission->id\">vote</A></TD></TR>\n";
  }
  $content .= "</TABLE>\n";

  $theme->header();
  $theme->box("Submission queue - Pending stories", $content);
  $theme->footer();
}

function submission_display_item($id) {
  global $PHP_SELF, $theme, $user, $submission_votes;

  if ($vote = user_getHistory($user->history, "s$id")) {
    header("Location: discussion.php?id=$id");
  }
  else {
    $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = $id");
    $submission = db_fetch_object($result);

    $theme->header();
    $theme->article($submission, "[ <A HREF=\"$PHP_SELF\"><FONT COLOR=\"$theme->hlcolor2\">back</FONT></A> ]");
   
    print "<FORM ACTION=\"$PHP_SELF\" METHOD=\"post\">\n";

    print "<P>\n";
    print " <B>Vote:</B><BR>\n";
    print " <SELECT NAME=\"vote\">\n";
    foreach ($submission_votes as $key=>$value) print "  <OPTION VALUE=\"$value\">". $key ."</OPTION>\n";
    print " </SELECT>\n";
    print "</P>\n";

    print "<P>\n";
    print " <B>Comment:</B><BR>\n";
    print " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"7\" NAME=\"comment\"></TEXTAREA>\n";
    print "</P>\n";

    print "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$submission->id\">\n";
    print "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Vote\">\n";
    print "</FORM>\n";
  }

  $theme->footer(); 
}

// Security check:
if (strstr($id, " ")) {
  watchdog("error", "submission: attempt to provide malicious input through URI");
  exit();
}

if ($user->id) {
  switch($op) {
    case "view":
      submission_display_item($id);
      break;
    case "Vote";
      submission_vote($id, $vote, $comment);
      submission_display_item($id);
      break;
    default:
      submission_display_main();
      break;
  }
}

?>
