<?
include "function.inc";
include "theme.inc";


function diary_entry($timestamp, $text, $id = 0) {
  if ($id) {
    $output .= "<DL>\n";
    $output .= " <DT><B>". date("l, F jS", $timestamp) .":</B> </DT>\n";
    $output .= " <DD><P>[ <A HREF=\"diary.php?op=edit&id=$id\">edit</A> ]</P><P>$text</P></DD>\n";
    $output .= "</DL>\n";
  }
  else {
    $output .= "<DL>\n";
    $output .= " <DT><B>". date("l, F jS", $timestamp) .":</B></DT>\n";
    $output .= " <DD><P>$text</P></DD>\n";
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
    while ($diary = db_fetch_object($result)) $output .= diary_entry($diary->timestamp, $diary->text);
  }

  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_add_enter() {
  global $theme, $user;
  
  ### Submission form:
  $output .= "<FORM ACTION=\"diary.php\" METHOD=\"post\">\n";

  $output .= "<P>\n"; 
  $output .= " <B>Enter new diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"text\" MAXLENGTH=\"20\"></TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview diary entry\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_edit_enter($id) {
  global $theme, $user;

  $result = db_query("SELECT * FROM diaries WHERE id = $id");
  $diary = db_fetch_object($result);

  $output .= diary_entry($diary->timestamp, $diary->text);

  $output .= "<FORM ACTION=\"diary.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Edit diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"text\">". stripslashes($diary->text) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$diary->id\">\n";
  $output .= " <INPUT TYPE=\"hidden\" NAME=\"timestamp\" VALUE=\"$diary->timestamp\">\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview diary entry\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Submit diary entry\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";
  
  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_preview($text, $timestamp, $id = 0) {
  global $theme, $user;

  $output .= diary_entry($timestamp, $text);

  $output .= "<FORM ACTION=\"diary.php\" METHOD=\"post\">\n";

  $output .= "<P>\n";
  $output .= " <B>Preview diary entry:</B><BR>\n";
  $output .= " <TEXTAREA WRAP=\"virtual\" COLS=\"50\" ROWS=\"15\" NAME=\"text\">". stripslashes($text) ."</TEXTAREA><BR>\n";
  $output .= " <SMALL><I>HTML is nice and dandy, but double check those URLs and HTML tags!</I></SMALL>\n";
  $output .= "</P>\n";

  $output .= "<P>\n";
  $output .= " <INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n";
  $output .= " <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Preview diary entry\"> <INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"Submit diary entry\">\n";
  $output .= "</P>\n";

  $output .= "</FORM>\n";

  $theme->header();
  $theme->box("Online diary", $output);
  $theme->footer();
}

function diary_submit($text, $id = 0) {
  global $user, $theme;

  if ($id) {
    db_query("UPDATE diaries SET text =  '".addslashes($text) ."' WHERE id = $id");
    watchdog(1, "old diary entry updated");
  }
  else {
    db_query("INSERT INTO diaries (author, text, timestamp) VALUES ('$user->id', '". addslashes($text) ."', '". time() ."')");
    watchdog(1, "new diary entry added");
  }
  header("Location: diary.php?op=view&name=$user->userid");
}


switch($op) {
  case "add":
    diary_add_enter();
    break;
  case "edit":
    diary_edit_enter($id);
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
    diary_display($user->userid);
}

?>