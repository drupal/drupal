<?

include "includes/database.inc";
include "includes/watchdog.inc";

function id2story($id) {
  ### Perform query:
  $result = db_query("SELECT s.*, u.userid FROM stories s LEFT JOIN users u ON s.author = u.id WHERE s.id = $id");
  return db_fetch_object($result);
}

function load_theme() {
  global $user, $themes;

  if ($user->theme && file_exists($themes[$user->theme][0])) {
    include $themes[$user->theme][0];     
  }
  else {
    include $themes[key($themes)][0];
  }
 
  return new Theme();
}

function check_input($message) {
  return str_replace("\"", "&quot;", stripslashes($message));
}

function check_output($message) {
  global $allowed_html;
  return nl2br(strip_tags(stripslashes($message), $allowed_html));
}

function discussion_num_replies($id, $count = 0) {
  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = $id");
  return ($result) ? db_result($result, 0) : 0;
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

function format_data($field, $replacement = "<I>na</I>") {
  return ($field) ? $field : $replacement;
}

function format_username($username, $admin = 0) {
  if ($username) return ($admin) ? "<A HREF=\"admin.php?section=accounts&op=view&name=$username\">$username</A>" : "<A HREF=\"account.php?op=view&name=$username\">$username</A>";
  else { global $anonymous; return $anonymous; }
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

?>
