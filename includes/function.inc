<?

$na = "<I>na</I>";

function check_textfield($message) {
  global $allowed_html;
  return strip_tags(str_replace("\"", "&quot;", stripslashes($message)), $allowed_html);
}

function check_textarea($message) {
  global $allowed_html;
  return htmlspecialchars(strip_tags(stripslashes($message), $allowed_html));
}

function check_input($message) {
  global $allowed_html, $submission_size;
  return strip_tags(addslashes(substr($message, 0, $submission_size)), $allowed_html);
}

function check_code($message) {
  return $message;
}

function check_output($message, $nl2br = 0) {
  global $allowed_html, $na;
  if ($message) {
    if ($nl2br == 1) return nl2br(strip_tags(stripslashes($message), $allowed_html));
    else return strip_tags(stripslashes($message), $allowed_html);
  }
  else return $na;
}

function format_plural($count, $singular, $plural) {
  return ($count == 1) ? "$count ". t($singular) : "$count ". t($plural);
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


function format_username($username) {
  global $user;
  if ($username) return (user_permission($user) ? "<A HREF=\"admin.php?mod=account&op=view&name=$username\">$username</A>" : "<A HREF=\"account.php?op=view&name=$username\">$username</A>");
  else { global $anonymous; return $anonymous; }
}

function format_email($address) {
  global $na;
  return ($address) ? "<A HREF=\"mailto:$address\">$address</A>" : $na;
}

function format_url($address, $description = "") {
  global $na;
  $description = ($description) ? $description : $address;
  return ($address) ? "<A HREF=\"$address\">$description</A>" : $na;
}

?>
