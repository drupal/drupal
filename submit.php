<?PHP

function submit_enter() {
  include "functions.inc";
  include "theme.inc";
  
  global $user;

  ### Guidlines:
  $output .= "<P>Got some news or some thoughts you would like to share?  Fill out this form and they will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it.  Every registered user is automatically a moderator and can vote whether or not your sumbission should be carried to the front page for discussion.</P>\n";
  $output .= "<P>Note that we do not revamp or extend your submission so it is totally up to you to make sure it is well-written: if you don't care enough to be clear and complete, your submission is likely to be moderated down by our army of moderators.  Try to be complete, aim for clarity, organize and structure your text, and try to carry out your statements with examples.  It is also encouraged to extend your submission with arguments that flow from your unique intellectual capability and experience: offer some insight or explanation as to why you think your submission is interesting.  Make sure your submission has some meat on it!</P>\n";
  $output .= "<P>However, if you have bugs to report, complaints, personal questions or anything besides a public submission, we would prefer you to mail us instead, or your message is likely to get lost.</P><BR>\n";

  ### Submission form:
  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<P>\n <B>Your name:</B><BR>\n";
  if ($user) $output .= " <A HREF=\"account.php\">$user->userid</A> &nbsp; &nbsp; <SMALL>[ <A HREF=\"account.php?op=logout\">logout</A> ]</SMALL>\n";
  else $output .= " $anonymous &nbsp; &nbsp; <SMALL>[ <A HREF=\"account.php\">login</A> | <A HREF=\"account.php\">create an account</A> ]</SMALL>\n"; 
  $output .= "</P>\n";
 
  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\"><BR>\n";
  $output .= " <SMALL><I>Bad subjects are 'Check this out!' or 'An article'.  Be descriptive, clear and simple!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P><B>Category:</B><BR>\n";
  $output .= " <SELECT NAME=\"category\">\n";
    
  for ($i = 0; $i < sizeof($categories); $i++) {
    $output .= "  <OPTION VALUE=\"$categories[$i]\">$categories[$i]</OPTION>\n";
  }
  
  $output .= " </SELECT>\n";
  $output .= "</P>\n";

  $output .= "<P>\n"; 
  $output .= " <B>Abstract:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n"; 
  $output .= " <B>Extended story:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"story\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";
 
  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\"> (You must preview at least once before you can submit.)\n";
  $output .= "</P>\n";
 
  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->box("Submit a story", $output);
  $theme->footer();
}

function submit_preview($name, $address, $subject, $abstract, $story, $category) {
  global $user;

  include "functions.inc";
  include "theme.inc";

  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Your name:</B><BR>\n";
  if ($user) $output .= " <A HREF=\"account.php\">$user->userid</A> &nbsp; &nbsp; <SMALL> [ <A HREF=\"account.php?op=logout\">logout</A> ]</SMALL>\n";
  else $output .= " $anonymous &nbsp; &nbsp; <SMALL>[ <A HREF=\"$account.php\">login</A> | <A HREF=\"account.php\">create an account</A> ]</SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" VALUE=\"". stripslashes($subject) ."\"><BR>\n";
  $output .= " <SMALL><I>Bad subjects are 'Check this out!' or 'An article'.  Be descriptive, clear and simple!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P><B>Category:</B><BR>\n";
  $output .= " <SELECT NAME=\"category\">\n";
  for ($i = 0; $i < sizeof($categories); $i++) {
    $output .= "  <OPTION VALUE=\"$categories[$i]\" ";
    if ($category == $categories[$i]) $output .= "SELECTED";
    $output .= ">$categories[$i]</OPTION>\n";
  }
  $output .= "</SELECT>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= "<B>Abstract:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\">". stripslashes($abstract) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Extended story:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"story\">". stripslashes($story) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";
 
  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Submit submission\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->preview("", $user->userid, date("l, F d, Y - H:i A", time()), stripslashes($subject), "we-hate-typoes", stripslashes($abstract), "", stripslashes($story));
  $theme->box("Submit a story", $output);
  $theme->footer();
}

function submit_submit($name, $address, $subject, $abstract, $article, $category) {
  global $user;

  include "functions.inc";
  include "theme.inc";

  ### Display confirmation message:

  $theme->header(); 
  $theme->box("Thanks for your submission.", "Thanks for your submission.  The submission moderators in our basement will frown at it, poke at it, and vote for it!");
  $theme->footer();

  ### Add submission to queue:
  if ($user) {
    $uid = $user->id;
    $name = $user->userid;
  }
  else {
    $uid = -1;
    $name = $anonymous;
  }

  db_query("INSERT INTO submissions (uid, uname, subject, article, timestamp, category, abstract, score, votes) VALUES ('$uid', '$name', '$subject', '$article', '". time() ."', '$category', '$abstract', '0', '0')");
  
  ### Send e-mail notification (if enabled):
  if ($notify) {
    $message = "New submission:\n\nsubject...: $subject\nauthor....: $name\ncategory..: $category\nabstract..:\n$abstract\n\narticle...:\n$article";
    mail($notify_email, "$notify_subject $subject", $message, "From: $notify_from\nX-Mailer: PHP/" . phpversion());
  }
}

switch($op) {
  case "Preview submission":
    submit_preview($name, $address, $subject, $abstract, $story, $category);
    break;
  case "Submit submission":
    submit_submit($name, $address, $subject, $abstract, $story, $category);
    break;
  default:
    submit_enter();
    break;
}

?>