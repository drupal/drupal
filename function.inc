<?

include "config.inc";
include "database.inc";
include "log.inc";

function id2story($id) {
  ### Perform query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = $id");
  return db_fetch_object($result);
}

function dbsave($dbase, $data, $id=0) {
  foreach ($data as $key=>$value) {
    if ($key == "passwd") { $query .= "$key=PASSWORD('". addslashes($value) ."'), "; }
    else { $query .= "$key='". addslashes($value) ."', "; }
  }
  $query = substr($query, 0, -2);
  
  if (!empty($id)) { db_query("UPDATE $dbase SET $query WHERE id=$id") or die(mysql_error()); return $id; }
  else { db_query("INSERT INTO $dbase SET $query") or die(mysql_error()); return mysql_insert_id(); }
}

function check_input($message) {
  return str_replace("\"", "&quot;", stripslashes($message));
}

function check_output($message) {
  include "config.inc";
  return nl2br(strip_tags(stripslashes($message), $allowed_html));
}

function discussion_num_replies($id, $count = 0) {
  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = $id");
  return ($result) ? mysql_result($result, 0) : 0;
}

function format_plural($count, $one, $more) {
  return ($count == 1) ? "$count $one" : "$count $more";
}

function format_date($timestamp, $type = "medium") {
  switch ($type) {
    case "small":
      $date = date("D, m/d/y - H:i", $timestamp);
      break;
    case "medium":
      $date = date("l, m/d/Y - H:i", $timestamp);
      break;
    case "large":
      $date = date("D, M d, Y - H:i", $timestamp);
      break;
    case "extra large":
      $date = date("l, F dS, Y - H:i", $timestamp);
      break;
    default:
      $date = date("D, M d, Y - H:i", $timestamp);
  }
  return $date;
}

function format_data($field, $replacement = "<I>n/a</I>") {
  return ($field) ? $field : $replacement;
}

function format_username($username, $admin = 0) {
  if ($username) return ($admin) ? "<A HREF=\"admin.php?section=accounts&op=view&name=$username\">$username</A>" : "<A HREF=\"account.php?op=view&name=$username\">$username</A>";
  else { include "config.inc"; return $anonymous; }
}

function format_email_address($address) {
  return ($address) ? "<A HREF=\"mailto:$address\">$address</A>" : format_data($address);
}

function format_url($address, $description = "") {
   // POSSIBLE EXTENSIONS:
   //   1. add `http://' in case it's missing.
   //   2. add a trailing `/' in case it's missing.
   //   3. remove any parameters in the URI.
  $description = ($description) ? $description : $address;
  return ($address) ? "<A HREF=\"$address\">$description</A>" : format_data($address);
}

function format_story_link($story, $subject = "") {
  global $user;
  $output .= "<A HREF=\"discussion.php?id=$story->id";
  $output .= ($user->umode) ? "&mode=$user->umode" : "&mode=threaded";
  $output .= ($user->uorder) ? "&order=$user->uorder" : "&order=0";
  $output .= ($user->thold) ? "&thold=$user->thold" : "&thold=0";
  $output .= ($subject) ? "\">$subject</A>" : "\">$story->subject</A>";
  return $output;
}

?>
