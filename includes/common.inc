<?

$na = "<I>na</I>";

function conf_init() {
  global $HTTP_HOST, $REQUEST_URI;
  $file = strtolower(strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", ".."));
  while ($file && !file_exists("includes/$file.php")) $file = substr($file, 0, strrpos($file, "."));
  return $file ? $file : "conf";
}

function error_handler($errno, $message, $filename, $line, $variables) {
  $types = array(1 => "error", 2 => "warning", 4 => "parse error", 8 => "notice", 16 => "core error", 32 => "core warning", 64 => "compile error", 128 => "compile warning", 256 => "user error", 512 => "user warning", 1024 => "user notice");
  $entry = $types[$errno] .": $message in $filename on line $line.";
  if (($errno == 1 || $errno == 2 || $errno == 4) && error_reporting()) {
    watchdog("error", $types[$errno] .": $message in $filename on line $line.");
    print $entry;
  }
}

function watchdog($type, $message) {
  global $user;
  db_query("INSERT INTO watchdog (user, type, message, location, hostname, timestamp) VALUES ('$user->id', '". check_input($type) ."', '". check_input($message) ."', '". check_input(getenv("REQUEST_URI")) ."', '". check_input(getenv("REMOTE_ADDR")) ."', '". time() ."')");
}

function throttle($type, $rate) {
  global $user;
  if (!user_access($user)) {
    if ($throttle = db_fetch_object(db_query("SELECT * FROM watchdog WHERE type = '$type' AND hostname = '". getenv("REMOTE_ADDR") ."' AND ". time() ." - timestamp < $rate"))) {
      watchdog("warning", "throttle: '". getenv("REMOTE_ADDR") ."' exceeded submission rate - $throttle->type");
      die(message_throttle());
    }
    else {
      watchdog($type, "throttle");
    }
  }
}

function path_uri() {
  global $HTTP_HOST, $REQUEST_URI;
  return "http://". $HTTP_HOST . substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")) ."/";
}

function path_img() {
  // use "http://your-image-server.com/ if you want to host images on a seperate server.
  return "./images/";
}

function message_account() {
  return t("This page requires a valid user account.  Please <A HREF=\"account.php\">create a user account</A> and <A HREF=\"account.php\">login</A> prior to accessing it.");
}

function message_throttle() {
  return t("You exceeded the submission rate exceeded.  Please wait a few minutes and try again.");
}

function check_form($text) {
  return htmlspecialchars(stripslashes($text));
}

function check_export($text) {
  return htmlspecialchars(stripslashes($text));
}

function check_code($text) {
  return $text;
}

function check_preview($text) {
  return check_output(check_input($text));
}

function check_query($text) {
  return addslashes(stripslashes($text));
}

function check_input($text) {
  foreach (module_list() as $module) $text = module_invoke($module, "filter", $text);
  return addslashes(stripslashes(substr($text, 0, variable_get("max_input_size", 10000))));
}

function check_output($text, $nl2br = 0) {
  global $na;
  return ($text) ? ($nl2br ? nl2br(stripslashes($text)) : stripslashes($text)) : $na;
}

function format_plural($count, $singular, $plural) {
  return ($count == 1) ? "$count ". t($singular) : "$count ". t($plural);
}

function format_interval($timestamp) {
  $units = array("year|years" => 31536000, "week|weeks" => 604800, "day|days" => 86400, "hour|hours" => 3600, "min|min" => 60, "sec|sec" => 1);
  foreach ($units as $key=>$value) {
    $key = explode("|", $key);
    if ($timestamp >= $value) {
      $output .= ($output ? " " : "") . format_plural(floor($timestamp / $value), $key[0], $key[1]);
      $timestamp %= $value;
    }
  }
  return ($output) ? $output : "0 sec";
}

function format_date($timestamp, $type = "medium", $format = "") {
  global $user;

  $timestamp += ($user->timezone) ? $user->timezone - date("Z") : 0;

  switch ($type) {
    case "small":
      $date = date("m/d/y - H:i", $timestamp);
      break;
    case "medium":
      $date = t(date("l", $timestamp)) .", ". date("m/d/Y - H:i", $timestamp);
      break;
    case "large":
      $date = t(date("l", $timestamp)) .", ". t(date("F", $timestamp)) ." ". date("d, Y - H:i", $timestamp);
      break;
    case "custom":
      for ($i = strlen($format); $i >= 0; $c = $format[--$i]) {
        if (strstr("DFlMSw", $c)) {
          $date=t(date($c, $timestamp)).$date;
        }
        else if (strstr("AaBdgGhHiIjLmnrstTUYyZz", $c)) {
          $date = date($c, $timestamp).$date;
        }
        else {
          $date = $c.$date;
        }
      }
      break;
    default:
      $date = t(date("l", $timestamp)) .", ". date("m/d/Y - H:i", $timestamp);
  }
  return $date;
}

function format_username($username) {
  global $user;
  if ($username) return (user_access($user, "account") ? "<A HREF=\"admin.php?mod=account&op=view&name=". urlencode($username) ."\">$username</A>" : "<A HREF=\"account.php?op=view&name=". urlencode($username) ."\">$username</A>");
  else return variable_get(anonymous, "Anonymous");
}

function format_email($address) {
  global $na;
  return ($address) ? "<a href=\"mailto:$address\">$address</A>" : $na;
}

function format_url($address, $description = "") {
  global $na;
  $description = ($description) ? $description : $address;
  return ($address) ? "<a href=\"$address\">". check_output($description) ."</a>" : $na;
}

function format_tag($link, $text) {
  return "'<a href=\"node.php?title='. urlencode('$link') .'\">'. ('$text' ? '$text' : '$link') .'</a>'";
}

function form($action, $form, $method = "post", $options = 0) {
  return "<form action=\"$action\" method=\"$method\"". ($options ? " $options" : "") .">\n$form</form>\n";
}

function form_item($title, $value, $description = 0) {
  return ($description) ? "<b>$title:</b><br />$value<br /><small><i>$description</i></small><p />\n" : "<b>$title:</b><br />$value<p />\n";
}

function form_textfield($title, $name, $value, $size, $maxlength, $description = 0) {
  return form_item($title, "<input maxlength=\"$maxlength\" name=\"edit[$name]\" size=\"$size\" value=\"". check_form($value) ."\" />", $description);
}

function form_textarea($title, $name, $value, $cols, $rows, $description = 0) {
  return form_item($title, "<textarea wrap=\"virtual\" cols=\"$cols\" rows=\"$rows\" name=\"edit[$name]\">". check_form($value) ."</textarea>", $description);
}

function form_select($title, $name, $value, $options, $description = 0, $extra = 0) {
  if (count($options) > 0) {
    foreach ($options as $key=>$choice) $select .= "<option value=\"$key\"". (is_array($value) ? (in_array($key, $value) ? " selected" : "") : ($key == $value ? " selected" : "")) .">". check_form($choice) ."</option>";
    return form_item($title, "<select name=\"edit[$name]\"". ($extra ? " $extra" : "") .">$select</select>", $description);
  }
}

function form_file($title, $name, $size, $description = 0) {
  return form_item($title, "<input type=\"file\" name=\"edit[$name]\" size=\"$size\" />\n", $description);
}

function form_hidden($name, $value) {
  return "<input type=\"hidden\" name=\"edit[$name]\" value=\"". check_form($value) ."\" />\n";
}

function form_submit($value) {
  return "<input type=\"submit\" name=\"op\" value=\"". check_form($value) ."\" />\n";
}

function field_get($string, $name) {
  foreach (explode(",", $string) as $data) {
    $entry = explode("=", $data);
    if ($entry[0] == $name) return $entry[1];
  }
}

function field_set($string, $name, $value) {
  if (!$value) {
    // remove entry:
    foreach (explode(",", $string) as $data) {
      $entry = explode("=", $data);
      if ($entry[0] != $name) $rval .= "$entry[0]=$entry[1],";
    }
  }
  else if (strstr($string, "$name=")) {
    // found: update exsisting entry:
    foreach (explode(",", $string) as $data) {
      $entry = explode("=", $data);
      if ($entry[0] == $name) $entry[1] = $value;
      $rval .= "$entry[0]=$entry[1],";
    }
  }
  else {
    // not found:
    $rval = "$string$name=$value,";
  }

  return $rval;
}

function field_merge($a, $b) {
  foreach (explode(",", $b) as $data) {
    $entry = explode("=", $data);
    $a = field_set($a, $entry[0], $entry[1]);
  }
  return $a;
}

function timer_start() {
  global $timer;
  $timer = explode(" ", microtime());
}

function timer_print() {
  global $timer;
  $stop = explode(" ", microtime());
  $diff = $stop[0] - $timer[0];
  print "<PRE>execution time: $diff ms</PRE>";
}

function page_header() {
  global $user;

  if (variable_get("dev_timer", 0)) {
    timer_start();
  }
}

function page_footer() {
  if (variable_get("dev_timer", 0)) {
    timer_print();
  }
}

$conf = conf_init();

include_once "includes/$conf.php";
include_once "includes/database.inc";
include_once "includes/variable.inc";
include_once "includes/comment.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/search.inc";
include_once "includes/theme.inc";
include_once "includes/user.inc";
include_once "includes/node.inc";

// initialize user session:
user_init();

// initialize installed modules:
module_init();

// initialize localization system:
$locale = locale_init();

// initialize configuration variables:
$conf = variable_init();

// initialize theme:
$theme = theme_init();

// set error handler:
set_error_handler("error_handler");

?>
