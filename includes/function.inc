<?

function load_theme() {
  global $user, $themes;

  if ($user->theme && file_exists($themes[$user->theme][0])) {
    include_once $themes[$user->theme][0];     
  }
  else {
    include_once $themes[key($themes)][0];
  }
  return new Theme();
}

function discussion_score($comment) {
  $value = ($comment->votes) ? ($comment->score / $comment->votes) : (($comment->score) ? $comment->score : 0);
  return (strpos($value, ".")) ? substr($value ."00", 0, 4) : $value .".00";
}

function check_field($message) {
  return str_replace("\"", "&quot;", stripslashes($message));
}

function check_input($message) {
  global $allowed_html, $submission_size;
  return strip_tags(addslashes(substr($message, 0, $submission_size)), $allowed_html);
}

function check_code($message) {
  return $message;
}

function check_output($message, $nl2br = 0) {
  global $allowed_html;
  if ($nl2br == 1) return nl2br(strip_tags(stripslashes($message), $allowed_html));
  else return strip_tags(stripslashes($message), $allowed_html);
}

function discussion_num_replies($id, $count = 0) {
  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = $id");
  return ($result) ? db_result($result, 0) : 0;
}

function discussion_num_filtered($sid, $pid) {
  global $user;

  $threshold = ($user->id) ? $user->threshold  : "0"; 
  $pid = ($pid) ? $pid : 0;

  $result = db_query("SELECT COUNT(cid) FROM comments WHERE sid = $sid AND pid = $pid AND (votes != 0 AND score / votes < $threshold)");
  return ($result) ? db_result($result, 0) : 0;
}

function format_plural($count, $singular, $plural) {
  return ($count == 1) ? "$count $singular" : "$count $plural";
}

function format_interval($timestamp) {
  if ($timestamp >= 86400) {
    $output .= format_plural(floor($timestamp / 86400), "day ", "days ");
    $timestamp = $timestamp % 86400;
  }
  if ($timestamp >= 3600) {
    $output .= format_plural(floor($timestamp / 3600), "hour ", "hours ");
    $timestamp = $timestamp % 3600;
  }
  if ($timestamp >= 60) {
    $output .= floor($timestamp / 60) ." min ";
    $timestamp = $timestamp % 60;
  }
  if ($timestamp > 0) {
    $output .= "$timestamp sec";
  }
  return ($output) ? $output : "0 sec";
}

function format_date($timestamp, $type = "medium") {
  global $user;

  $timestamp += ($user->timezone) ? $user->timezone - date("Z") : 0;

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
  if ($username) return ($admin) ? "<A HREF=\"admin.php?mod=account&op=view&name=$username\">$username</A>" : "<A HREF=\"account.php?op=view&name=$username\">$username</A>";
  else { global $anonymous; return $anonymous; }
}

function format_email($address) {
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
