<?

function submit_enter() {
  global $anonymous, $allowed_html, $theme, $user;

  // Guidlines:
  $output .= "<P>". t("Got some news or some thoughts you would like to share? Fill out this form and they will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it. Every registered user is automatically a moderator and can vote whether or not your sumbission should be carried to the front page for discussion.") ."</P>\n";
  $output .= "<P>". t("Note that we do not revamp or extend your submission so it is up to you to make sure your submission is well-written: if you don't care enough to be clear and complete, your submission is likely to be moderated down by our army of moderators. Try to be complete, aim for clarity, organize and structure your text, and try to carry out your statements with examples. It is also encouraged to extend your submission with arguments that flow from your unique intellectual capability and experience: offer some insight or explanation as to why you think your submission is interesting. Make sure your submission has some meat on it!") ."</P>\n";
  $output .= "<P>". t("However, if you have bugs to report, complaints, personal questions or anything besides a public submission, we would prefer you to mail us instead, or your message is likely to get lost.") ."</P>\n";

  // Submission form:
  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Your name") .":</B><BR>\n";
  $output .= format_username($user->userid);
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Subject") .":</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\"><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Section") .":</B><BR>\n";
  $output .= " <SELECT NAME=\"section\">\n";
  foreach ($sections = section_get() as $value) $output .= "  <OPTION VALUE=\"$value\">$value</OPTION>\n";
  $output .= " </SELECT>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Abstract") .":</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\" MAXLENGTH=\"20\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Extended story") .":</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <SMALL><I>". t("You must preview at least once before you can submit") .":</I></SMALL><BR>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";

  $theme->header();
  $theme->box(t("Submit a story"), $output);
  $theme->footer();
}

function submit_preview($subject, $abstract, $article, $section) {
  global $allowed_html, $theme, $user;

  include "includes/story.inc";

  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Your name") .":</B><BR>\n";
  $output .= format_username($user->userid);
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Subject") .":</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"". check_output(check_textfield($subject)) ."\"><BR>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Section") .":</B><BR>\n";
  $output .= " <SELECT NAME=\"section\">\n";
  foreach ($sections = section_get() as $value) $output .= "  <OPTION VALUE=\"$value\"". ($section == $value ? " SELECTED" : "") .">$value</OPTION>\n";
  $output .= "</SELECT>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= "<B>". t("Abstract") .":</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\">". check_textarea($abstract) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>". t("Extended story") .":</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\">". check_textarea($article) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  if (empty($subject)) {
    $output .= "<P><FONT COLOR=\"red\">". t("Warning: you did not supply a subject.") ."</FONT></P>\n";
    $output .= "<P>\n";
    $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\">\n";
    $output .= "</P>\n";
  }
  else if (empty($abstract)) {
    $output .= "<P>\n";
    $output .= " <FONT COLOR=\"red\">". t("Warning: you did not supply an abstract.") ."\n";
    $outout .= "</P>\n";
    $output .= "<P>\n";
    $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\">\n";
    $output .= "</P>\n";
  }
  else {
    $output .= "<P>\n";
    $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\">\n";
    $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Submit submission\">\n";
    $output .= "</P>\n";
  }
  $output .= "</FORM>\n";

  $theme->header();
  $theme->article(new Story($user->userid, $subject, $abstract, $article, $section, time()));
  $theme->box(t("Submit a story"), $output);
  $theme->footer();
}

function submit_submit($subject, $abstract, $article, $section) {
  global $user, $theme;

  // Add log entry:
  watchdog("story", "story: added '$subject'");

  // Add submission to SQL table:
  db_query("INSERT INTO stories (author, subject, abstract, article, section, timestamp) VALUES ('$user->id', '". check_input($subject) ."', '". check_input($abstract) ."', '". check_input($article) ."', '". check_input($section) ."', '". time() ."')");

  // Display confirmation message:
  $theme->header();
  $theme->box("Thank you for your submission.", "Thank you for your submission. Your submission has been whisked away to our submission queue where our registered users will frown at it, poke at it and hopefully carry it to the front page for discussion.");
  $theme->footer();
}

include_once "includes/common.inc";

switch($op) {
  case "Preview submission":
    submit_preview($subject, $abstract, $article, $section);
    break;
  case "Submit submission":
    submit_submit($subject, $abstract, $article, $section);
    break;
  default:
    submit_enter();
    break;
}

?>
