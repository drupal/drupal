<?

function submit_enter() {
  global $anonymous, $categories, $allowed_html, $theme, $user;
  
  ### Guidlines:
  $output .= block_get("submit_information");

  ### Submission form:
  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<P>\n <B>Your name:</B><BR>\n";
  $output .= format_username($user->userid);
  $output .= "</P>\n";
 
  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\"><BR>\n";
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
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\" MAXLENGTH=\"20\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n"; 
  $output .= " <B>Extended story:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";
 
  $output .= "<P>\n";
  $output .= " You must preview at least once before you can submit:<BR>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\">\n";
  $output .= "</P>\n";
 
  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->box("Submit a story", $output);
  $theme->footer();
}

function submit_preview($subject, $abstract, $article, $category) {
  global $categories, $allowed_html, $theme, $user;

  include "includes/story.inc";

  $output .= "<FORM ACTION=\"submit.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Your name:</B><BR>\n";
  $output .= format_username($user->userid);
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Subject:</B><BR>\n";
  $output .= " <INPUT TYPE=\"text\" NAME=\"subject\" SIZE=\"50\" MAXLENGTH=\"60\" VALUE=\"". check_output(check_field($subject)) ."\"><BR>\n";
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
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"10\" NAME=\"abstract\">". check_output($abstract) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <B>Extended story:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"article\">". check_output($article) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  if (empty($subject)) {
    $output .= "<P>\n";
    $output .= " <FONT COLOR=\"red\"><B>Warning:</B></FONT> you did not supply a <U>subject</U>.\n";
    $outout .= "</P>\n";
    $output .= "<P>\n";
    $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview submission\">\n";
    $output .= "</P>\n";
  }
  else if (empty($abstract)) {
    $output .= "<P>\n";
    $output .= " <FONT COLOR=\"red\"><B>Warning:</B></FONT> you did not supply an <U>abstract</U>.\n";
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
  $theme->article(new Story($user->userid, $subject, $abstract, $article, $category, time()));
  $theme->box("Submit a story", $output);
  $theme->footer();
}

function submit_submit($subject, $abstract, $article, $category) {
  global $user, $theme;

  ### Add log entry:
  watchdog("story", "added new story with subject `$subject'");
  
  ### Add submission to SQL table:
  db_query("INSERT INTO stories (author, subject, abstract, article, category, timestamp) VALUES ('$user->id', '". check_input($subject) ."', '". check_input($abstract) ."', '". check_input($article) ."', '". check_input($category) ."', '". time() ."')");
  
  ### Display confirmation message:
  $theme->header(); 
  $theme->box("Thank you for your submission.", block_get("sumbit_confirmation"));
  $theme->footer();
}

include "includes/theme.inc";

switch($op) {
  case "Preview submission":
    submit_preview($subject, $abstract, $article, $category);
    break;
  case "Submit submission":
    submit_submit($subject, $abstract, $article, $category);
    break;
  default:
    submit_enter();
    break;
}

?>
