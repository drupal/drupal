<?

 ### poll.php.inc specific settings:

 # Use cookie:
 # (1 = enabled, 0 = disabled)
 $cookieUse = 1;          // 1 = Enabled 0=Disabled

 # When should cookie expire:
 // $cookieExpire = 604800;  // Expires in a week
 $cookieExpire = 60;  // Expires in a week

 # Bar image we should use:
 $barImage = "./images/poll.gif"; // Image to use

 # Bar height:
 $barHeight = "15";    // Image height 

 /*
  CREATE TABLE poll (
    id int(11) DEFAULT '0' NOT NULL auto_increment,
    question varchar(150),
    answer1 varchar(100),
    answer2 varchar(100),
    answer3 varchar(100),
    answer4 varchar(100),
    answer5 varchar(100),
    answer6 varchar(100),
    votes1 tinyint(4),
    votes2 tinyint(4),
    votes3 tinyint(4),
    votes4 tinyint(4),
    votes5 tinyint(4),
    votes6 tinyint(4),
    status tinyint(4) DEFAULT '0',
    PRIMARY KEY (id)
  );
 */


function deletePoll($id) {
  $query = "DELETE FROM poll WHERE id = $id";
  $result = mysql_query($query);
}

function enablePoll($id) {
  $query = "UPDATE poll SET status = 0 WHERE status = 1";
  $result = mysql_query($query);

  $query = "UPDATE poll SET status = 1 WHERE id = $id";
  $result = mysql_query($query);
}

function disablePoll($id) {
  $query = "UPDATE poll SET status = 0 WHERE id = $id";
  $result = mysql_query($query);
}

function castVote($vote) {
  $query = "SELECT * FROM poll WHERE status = 1";
  $result = mysql_query($query);
  if ($poll = mysql_fetch_object($result)) {
    $vote = "votes$vote";
    $result = $poll->$vote + 1;
    $query = "UPDATE poll SET $vote = '$result' WHERE id = $poll->id";
    $result = mysql_query($query);
  }
}

function addPoll($question, $answer1, $answer2, $answer3 = "", $answer4 = "", $answer5 = "", $answer6 = "") {
  $query = "INSERT INTO poll (question, answer1, answer2, answer3, answer4, answer5, answer6) VALUES ('$question', '$answer1', '$answer2', '$answer3', '$answer4', '$answer5', '$answer6')";
  $result = mysql_query($query);
}

function updatePoll($id, $question, $answer1, $answer2, $answer3 = "", $answer4 = "", $answer5 = "", $answer6 = "") {
  $query = "UPDATE poll SET question = '$question', answer1 = '$answer1', answer2 = '$answer2', answer3 = '$answer3', answer4 = '$answer4', answer5 = '$answer5', answer6 = '$answer6' WHERE id = $id";
  $result = mysql_query($query);
}

function getPoll($id) {
  $query = "SELECT * FROM poll WHERE id = $id";
  $result = mysql_query($query);
  if ($poll = mysql_fetch_object($result)) return $poll;
}

function getActivePoll() {
  $query = "SELECT * FROM poll WHERE status = 1";
  $result = mysql_query($query);
  if ($poll = mysql_fetch_object($result)) return $poll->id;
}

function getPollArray() {
  $query = "SELECT * FROM poll";
  $result = mysql_query($query);
  
  $index = 0;
  while ($poll = mysql_fetch_object($result)) {
    $rval[$index] = $poll;
    $index++;
  }
 
  return $rval;
}

function displayForm() {
  global $PHP_SELF;

  if ($poll = getPoll(getActivePoll())) {
    $rval = "<P ALIGN=\"center\"><B>$poll->question</B></P>\n";
    $rval .= "<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=vote\">\n";
    if ($poll->answer1) $rval .= " <INPUT TYPE=\"radio\" NAME=\"answer\" VALUE=\"1\"> $poll->answer1<BR>\n";
    if ($poll->answer2) $rval .= " <INPUT TYPE=\"radio\" NAME=\"answer\" VALUE=\"2\"> $poll->answer2<BR>\n";
    if ($poll->answer3) $rval .= " <INPUT TYPE=\"radio\" NAME=\"answer\" VALUE=\"3\"> $poll->answer3<BR>\n";
    if ($poll->answer4) $rval .= " <INPUT TYPE=\"radio\" NAME=\"answer\" VALUE=\"4\"> $poll->answer4<BR>\n";
    if ($poll->answer5) $rval .= " <INPUT TYPE=\"radio\" NAME=\"answer\" VALUE=\"5\"> $poll->answer5<BR>\n";
    if ($poll->answer6) $rval .= " <INPUT TYPE=\"radio\" NAME=\"answer\" VALUE=\"6\"> $poll->answer6<BR>\n";
    $rval .= " <BR><CENTER><INPUT TYPE=\"submit\" NAME=\"vote\" VALUE=\"Vote\"></CENTER>\n";
    $rval .= "</FORM>";
  }
  else {
    $rval = "There is currently no poll available.";
  }
  return $rval;
}

function displayResult($id) {
  global $PHP_SELF, $barImage;

  if ($poll = getPoll($id)) {
    # total number of votes:
    $total = $poll->votes1 + $poll->votes2 + $poll->votes3 + $poll->votes4 + $poll->votes5 + $poll->votes6;
    $rval = "<P ALIGN=\"center\"><B>$poll->question</B></P>\n";
 
    if ($total) {
      # percentage:
      if ($poll->answer1) {
        $per1 = round($poll->votes1 / $total * 100);
        $wid1 = ($per1) ? $per1 : 1;
        $rval .= "<P>$poll->answer1<BR><IMG SRC=\"$barImage\" HEIGHT=\"8\" WIDTH=\"$wid1\"> $poll->votes1 ($per1 %)</P>";
      }
      if ($poll->answer2) {
        $per2 = round($poll->votes2 / $total * 100);
        $wid2 = ($per2) ? $per2 : 1;
        $rval .= "<P>$poll->answer2<BR><IMG SRC=\"$barImage\" HEIGHT=\"8\" WIDTH=\"$wid2\"> $poll->votes2 ($per2 %)</P>";
      }
      if ($poll->answer3) {
        $per3 = round($poll->votes3 / $total * 100);
        $wid3 = ($per3) ? $per3 : 1;
        $rval .= "<P>$poll->answer3<BR><IMG SRC=\"$barImage\" HEIGHT=\"8\" WIDTH=\"$wid3\"> $poll->votes3 ($per3 %)</P>";
      }
      if ($poll->answer4) {
        $per4 = round($poll->votes4 / $total * 100);
        $wid4 = ($per4) ? $per4 : 1;
        $rval .= "<P>$poll->answer4<BR><IMG SRC=\"$barImage\" HEIGHT=\"8\" WIDTH=\"$wid4\"> $poll->votes4 ($per4 %)</P>";
      }
      if ($poll->answer5) {
        $per5 = round($poll->votes5 / $total * 100);
        $wid5 = ($per5) ? $per5 : 1;
        $rval .= "<P>$poll->answer5<BR><IMG SRC=\"$barImage\" HEIGHT=\"8\" WIDTH=\"$wid5\"> $poll->votes5 ($per5 %)</P>";
      }
      if ($poll->answer6) {
        $per6 = round($poll->votes6 / $total * 100);
        $wid6 = ($per6) ? $per6 : 1;
        $rval .= "<P>$poll->answer6<BR><IMG SRC=\"$barImage\" HEIGHT=\"8\" WIDTH=\"$wid6\"> $poll->votes6 ($per6 %)</P>";
      }
    } 
    $rval .= "<BR><P>Total votes: $total</P>";
  }
  else {
    $rval = "There is currently no poll available.";
  }
  return $rval;
}

function adminPolls() {
  global $PHP_SELF;

  $polls = getPollArray();
  $rval = "<TABLE WIDTH=\"100%\">\n";
  for (reset($polls); $poll = current($polls); next($polls)) {
    $status = ($poll->status) ? "<TD WIDTH=\"20\"><FONT COLOR=\"blue\" SIZE=\"+2\">*</FONT></TD><TD WIDTH=\"40\"><A HREF=\"$PHP_SELF?section=poll&method=disable&id=$poll->id\">disable</A></TD>" : "<TD WIDTH=\"20\"><FONT COLOR=\"yellow\" SIZE=\"+2\">*</FONT></TD><TD WIDTH=\"40\" ><A HREF=\"$PHP_SELF?section=poll&method=enable&id=$poll->id\">enable</A></TD>";
    $rval .= " <TR><TD WIDTH=\"50%\" >$poll->question</TD>$status<TD WIDTH=\"40\"><A HREF=\"$PHP_SELF?section=poll&method=result&id=$poll->id\">view</A></TD><TD WIDTH=\"40\"><A HREF=\"$PHP_SELF?section=poll&method=edit&id=$poll->id\">edit</A></TD><TD WIDTH=\"40\"><A HREF=\"$PHP_SELF?section=poll&method=delete&id=$poll->id\">delete</A></TD></TR>\n";
  }
  $rval .= "</TABLE>";
    
  return $rval;
}

if (!$box) {
  include "functions.inc";
  include "theme.inc";
  $theme->header();
}

if ($section == "poll") {
  if ($method == "add") {
    if ($admin) {
      addPoll($question, $answer1, $answer2, $answer3, $answer4, $answer5, $answer6);
      $theme->box("Poll manager", "<P><B><U>Status:</U></B> new poll added.</P>\n<P><B><U>Overview:</U></B></P>\n". adminPolls() ."<P><B><U>Add poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=add\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Add poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");
  }
  else if ($method == "edit") {
    if ($admin) {
      $poll = getPoll($id);
      $theme->box("Poll manager", "<P><B><U>Edit poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=update&id=$poll->id\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\" VALUE=\"$poll->question\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\" VALUE=\"$poll->answer1\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\" VALUE=\"$poll->answer2\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\" VALUE=\"$poll->answer3\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\" VALUE=\"$poll->answer4\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\" VALUE=\"$poll->answer5\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\" VALUE=\"$poll->answer6\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Update poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");
  }
  else if ($method == "enable") {
    if ($admin) {
      enablePoll($id);
      $theme->box("Poll manager", "<P><B><U>Status:</U></B> poll enabled.</P>\n<P><B><U>Overview:</U></B></P>\n". adminPolls() ."<P><B><U>Add poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=add\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Add poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");
  }
  else if ($method == "delete") {
    if ($admin) {
      deletePoll($id);
      $theme->box("Poll manager", "<P><B><U>Status:</U></B> poll deleted.</P>\n<P><B><U>Overview:</U></B></P>\n". adminPolls() ."<P><B><U>Add poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=add\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Add poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");
  }
  else if ($method == "disable") {
    if ($admin) {
      disablePoll($id);
      $theme->box("Poll manager", "<P><B><U>Status:</U></B> poll disabled.</P>\n<P><B><U>Overview:</U></B></P>\n". adminPolls() ."<P><B><U>Add poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=add\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Add poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");   }
  else if ($method == "update") {
    if ($admin) {
      updatePoll($id, $question, $answer1, $answer2, $answer3, $answer4, $answer5, $answer6);        
      $theme->box("Poll manager", "<P><B><U>Status:</U></B> poll updated.</P>\n<P><B><U>Overview:</U></B></P>\n". adminPolls() ."<P><B><U>Add poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=add\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Add poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");
  }
  else if ($method == "result") {
    if ($id) $theme->box("Voting poll", displayResult($id));
    else $theme->box("Voting poll", displayResult(getActivePoll()));
  }
  else if ($method == "vote") {
    if ($poll) {
      $theme->box("Voting poll", displayResult(getActivePoll()) ."<P><B>Note:</B> you have voted already recently.</P>");
    }
    else {
      castVote($answer);
      $theme->box("Voting poll", displayResult(getActivePoll()));
    }
  }
  else {
    if ($admin) {
      $theme->box("Poll manager", "<P><B><U>Overview:</U></B></P>\n". adminPolls() ."<P><B><U>Add poll:</U></B></P>\n<FORM METHOD=\"post\" ACTION=\"$PHP_SELF?section=poll&method=add\">\n<TABLE>\n <TR><TD>Question:</TD><TD><INPUT TYPE=\"text\" NAME=\"question\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 1:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer1\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 2:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer2\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 3:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer3\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 4:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer4\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 5:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer5\" SIZE=\"35\"></TD></TR>\n <TR><TD>Answer 6:</TD><TD><INPUT TYPE=\"text\" NAME=\"answer6\" SIZE=\"35\"></TD></TR>\n <TR><TD ALIGN=\"center\" COLSPAN=\"2\"><INPUT TYPE=\"submit\" VALUE=\"Add poll\" NAME=\"add\">&nbsp;<INPUT TYPE=\"reset\" VALUE=\"Reset\"></TD></TR>\n</TABLE>\n</FORM>");
    }
    else $theme->box("Failed", "You don't have permission to access this section.<P ALIGN=\"right\">[ <A HREF=\"javascript: history.back()\">back</A> ]</P>");
  }
}
else {
  if ($poll) {
    $theme->box("Voting poll", displayResult(getActivePoll()) ."<P><B>Note:</B> you have voted already recently.</P>");
  }
  else {
    $theme->box("Voting poll", displayForm() ."<P ALIGN=\"right\">[ <A HREF=\"$PHP_SELF?section=poll&method=result\"><FONT COLOR=\"$theme->hlcolor2\">results</FONT></A> ]</P>");
  }
}

if (!$box) $theme->footer(); 
?>