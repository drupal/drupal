<?

include_once "includes/common.inc";

function submit_enter() {
  global $anonymous, $allowed_html, $theme, $user;

  // Guidlines:
  $output .= "<P>". t("Got some news or some thoughts you would like to share? Fill out this form and they will automatically get whisked away to our submission queue where our moderators will frown at it, poke at it and hopefully post it. Every registered user is automatically a moderator and can vote whether or not your sumbission should be carried to the front page for discussion.") ."</P>\n";
  $output .= "<P>". t("Note that we do not revamp or extend your submission so it is up to you to make sure your submission is well-written: if you don't care enough to be clear and complete, your submission is likely to be moderated down by our army of moderators. Try to be complete, aim for clarity, organize and structure your text, and try to carry out your statements with examples. It is also encouraged to extend your submission with arguments that flow from your unique intellectual capability and experience: offer some insight or explanation as to why you think your submission is interesting. Make sure your submission has some meat on it!") ."</P>\n";
  $output .= "<P>". t("However, if you have bugs to report, complaints, personal questions or anything besides a public submission, we would prefer you to mail us instead, or your message is likely to get lost.") ."</P>\n";

  // Submission form:
  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<B>". t("Your name") .":</B><BR>\n";
  $output .= format_username($user->userid) ."<P>\n";

  $output .= "<B>". t("Subject") .":</B><BR>\n";
  $output .= "<INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\"><P>\n";

  $output .= "<B>". t("Section") .":</B><BR>\n";
  foreach ($sections = section_get() as $value) $options .= "  <OPTION VALUE=\"$value\">$value</OPTION>\n";
  $output .= "<SELECT NAME=\"section\">$options</SELECT><P>\n";

  $output .= "<B>". t("Abstract") .":</B><BR>\n";
  $output .= "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\" MAXLENGTH=\"20\"></TEXTAREA><BR>\n";
  $output .= "<SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL><P>\n";

  $output .= "<B>". t("Extended story") .":</B><BR>\n";
  $output .= "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\"></TEXTAREA><BR>\n";
  $output .= "<SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL><P>\n";

  $output .= "<SMALL><I>". t("You must preview at least once before you can submit") .":</I></SMALL><BR>\n";
  $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview submission") ."\"><P>\n";

  $output .= "</FORM>\n";

  $theme->header();
  $theme->box(t("New submission"), $output);
  $theme->footer();
}

function submit_preview($subject, $abstract, $article, $section) {
  global $allowed_html, $theme, $user;

  include "includes/story.inc";

  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<B>". t("Your name") .":</B><BR>\n";
  $output .= format_username($user->userid) ."<P>";

  $output .= "<B>". t("Subject") .":</B><BR>\n";
  $output .= "<INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"". check_textfield($subject) ."\"><P>\n";

  $output .= "<B>". t("Section") .":</B><BR>\n";
  foreach ($sections = section_get() as $value) $options .= "  <OPTION VALUE=\"$value\"". ($section == $value ? " SELECTED" : "") .">$value</OPTION>\n";
  $output .= "<SELECT NAME=\"section\">$options</SELECT><P>\n";

  $output .= "<B>". t("Abstract") .":</B><BR>\n";
  $output .= "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\">". check_textarea($abstract) ."</TEXTAREA><BR>\n";
  $output .= "<SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL><P>\n";

  $output .= "<B>". t("Extended story") .":</B><BR>\n";
  $output .= "<TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\">". check_textarea($article) ."</TEXTAREA><BR>\n";
  $output .= "<SMALL><I>". t("Allowed HTML tags") .": ". htmlspecialchars($allowed_html) .".</I></SMALL><P>\n";

  $duplicate = db_result(db_query("SELECT COUNT(id) FROM stories WHERE subject = '$subject'"));

  if (empty($subject)) {
    $output .= "<FONT COLOR=\"red\">". t("Warning: you did not supply a subject.") ."</FONT><P>\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview submission") ."\">\n";
  }
  else if (empty($abstract)) {
    $output .= "<FONT COLOR=\"red\">". t("Warning: you did not supply an abstract.") ."</FONT><P>\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview submission") ."\">\n";
  }
  else if ($duplicate) {
    $output .= "<FONT COLOR=\"red\">". t("Warning: there is already a story with that subject.") ."</FONT><P>\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview submission") ."\">\n";
  }
  else {
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Preview submission") ."\">\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Submit submission") ."\">\n";
  }
  $output .= "</FORM>\n";

  $theme->header();
  $theme->story(new Story($user->userid, $subject, $abstract, $article, $section, time()));
  $theme->box(t("Submit a story"), $output);
  $theme->footer();
}

function submit_submit($subject, $abstract, $article, $section) {
  global $user, $theme;

  // Add log entry:
  watchdog("story", "story: added '$subject'");

  // Add submission to SQL table:
  db_query("INSERT INTO stories (author, subject, abstract, article, section, timestamp) VALUES ('$user->id', '$subject', '$abstract', '$article', '$section', '". time() ."')");

  // Display confirmation message:
  $theme->header();
  $theme->box(t("Submission completed"), t("Thank you for your submission. Your submission has been whisked away to our submission queue where our registered users will frown at it, poke at it and hopefully carry it to the front page for discussion."));
  $theme->footer();
}

switch($op) {
  case t("Preview submission"):
    submit_preview(check_input($subject), check_input($abstract), check_input($article), check_input($section));
    break;
  case t("Submit submission"):
    submit_submit(check_input($subject), check_input($abstract), check_input($article), check_input($section));
    break;
  default:
    submit_enter();
    break;
}

?>
