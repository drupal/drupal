<?PHP

function submit_enter() {
  include "functions.inc";
  include "theme.inc";
  
  global $user;

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

  $output .= "<P>\n";
  $output .= " <SMALL><B>Important:</B> remember to include the exact URL of your <U>source</U> in case you refer to a story found on another website or your submission might be rejected!</SMALL>\n";
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

  $output .= "<P>\n";
  $output .= " <SMALL><B>Important:</B> remember to include the exact URL of your <U>source</U> in case you refer to a story found on another website or your submission might be rejected!</SMALL>\n";
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
  
  ### Send notification mail (if required):
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