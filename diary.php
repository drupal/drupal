<?

include "includes/theme.inc";

function diary_overview($num = 20) {
  global $theme, $user;

  $result = db_query("SELECT d.*, u.userid FROM diaries d LEFT JOIN users u ON d.author = u.id ORDER BY d.timestamp DESC LIMIT $num");

  $output .= "<P>This part of the website is dedicated to providing easy to write and easy to read online diaries or journals filled with daily thoughts, poetry, boneless blather, spiritual theories, intimate details, valuable experiences, cynical rants, semi-coherent comments, writing experiments, artistic babblings, critics on actuality, fresh insights, diverse dreams, chronicles and general madness available for general human consumption.</P>";

  while ($diary = db_fetch_object($result)) {
    if ($time != date("F jS", $diary->timestamp)) {
      $output .= "<B>". date("l, F jS", $diary->timestamp) ."</B>\n";
      $time = date("F jS", $diary->timestamp);
    }
    $output .= "<DL>\n";
    $output .= " <DD><P><B>$diary->userid wrote:</B></P></DD>\n";
    $output .= " <DL>\n";
    $output .= "  <DD><P>". check_output($diary->text, 1) ."</P><P>[ <A HREF=\"diary.php?op=view&name=$diary->userid\">more</A> ]</P></DD>\n";
    $output .= " </DL>\n";
    $output .= "</DL>\n";
  }

  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();

}

function diary_entry($timestamp, $text, $id = 0) {
  if ($id) {
    $output .= "<DL>\n";
    $output .= " <DT><B>". date("l, F jS", $timestamp) .":</B> </DT>\n";
    $output .= " <DD><P>[ <A HREF=\"diary.php?op=edit&id=$id\">edit</A> ]</P><P>". check_output($text) ."</P></DD>\n";
    $output .= "</DL>\n";
  }
  else {
    $output .= "<DL>\n";
    $output .= " <DT><B>". date("l, F jS", $timestamp) .":</B></DT>\n";
    $output .= " <DD><P>". check_output($text, 1) ."</P></DD>\n";
    $output .= "</DL>\n";
  }
  return $output;
}

function diary_display($username) {
  global $theme, $user;

  $result = db_query("SELECT d.*, u.userid FROM diaries d LEFT JOIN users u ON d.author = u.id WHERE u.userid = '$username' ORDER BY timestamp DESC");

  if ($username == $user->userid) {
    $output .= diary_entry(time(), "<BIG><A HREF=\"diary.php?op=add\">Add new diary entry!</A></BIG><P>");
    while ($diary = db_fetch_object($result)) $output .= diary_entry($diary->timestamp, $diary->text, $diary->id);
  }
  else {
    $output .= "<P>". format_username($username) ."'s diary:</P>\n";
    while ($diary = db_fetch_object($result)) $output .= diary_entry($diary->timestamp, $diary->text);
  }

  $theme->header();
  $theme->box("$username's online diary", $output);
  $theme->footer();
}

function diary_add() {
  global $theme, $user, $allowed_html;
  
  $output .= "<FORM ACTION=\"diary.php\" METHOD=\"post\">\n";

  $output .= "<P>\n"; 
  $output .= " <B>Enter new diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"text\" MAXLENGTH=\"20\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview diary entry\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_edit($id) {
  global $theme, $user, $allowed_html;

  $result = db_query("SELECT * FROM diaries WHERE id = $id");
  $diary = db_fetch_object($result);

  $output .= diary_entry($diary->timestamp, $diary->text);

  $output .= "<FORM ACTION=\"diary.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Edit diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"text\">". check_input($diary->text) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$diary->id\">\n";
  $output .= " <INPUT TYPE=\"hidden\" NAME=\"timesamp\" VALUE=\"$diary->timestamp\">\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview diary entry\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Submit diary entry\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_preview($text, $timestamp, $id = 0) {
  global $theme, $user, $allowed_html;

  $output .= diary_entry($timestamp, $text);

  $output .= "<FORM ACTION=\"diary.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Preview diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"text\">". check_output($text) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>Allowed HTML tags: ". htmlspecialchars($allowed_html) .".</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview diary entry\">\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Submit diary entry\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";

  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_submit($text, $id = 0) {
  global $user, $theme;

  if ($id) {
    db_query("UPDATE diaries SET text =  '". check_input($text) ."' WHERE id = $id");
    watchdog(1, "old diary entry updated");
  }
  else {
    db_query("INSERT INTO diaries (author, text, timestamp) VALUES ('$user->id', '". check_input($text) ."', '". time() ."')");
    watchdog(1, "new diary entry added");
  }
  header("Location: diary.php?op=view&name=$user->userid");
}


switch($op) {
  case "add":
    diary_add();
    break;
  case "edit":
    diary_edit($id);
    break;
  case "view":
    diary_display($name);
    break;
  case "Preview diary entry":
    if ($id) diary_preview($text, $timestamp, $id);
    else diary_preview($text, time());
    break;
  case "Submit diary entry":
    if ($id) diary_submit($text, $id);
    else diary_submit($text);
    break;
  default:
    diary_overview();
}

?>