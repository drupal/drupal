<?

function conf_init() {
  global $HTTP_HOST, $REQUEST_URI;

  /*
  ** Try finding a matching configuration file by stripping the website's
  ** URI from left to right.  If no configuration file is found, return a
  ** default value 'conf'.
  */

  $file = strtolower(strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", ".."));

  while (strlen($file) > 4) {
    if (file_exists("includes/$file.php")) {
      return $file;
    }
    else {
      $file = substr($file, strpos($file, ".") + 1);
    }
  }

  return "conf";
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
  db_query("INSERT INTO watchdog (user, type, message, location, hostname, timestamp) VALUES ('$user->uid', '". check_input($type) ."', '". check_input($message) ."', '". check_input(getenv("REQUEST_URI")) ."', '". check_input(getenv("REMOTE_ADDR")) ."', '". time() ."')");
}

function throttle($type, $rate) {
  if (!user_access("access administration pages")) {
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

function message_access() {
  return t("You are not authorized to access to this page.");
}

function message_na() {
  return t("n/a");
}

function message_throttle() {
  return t("You exceeded the submission rate exceeded.  Please wait a few minutes and try again.");
}

function drupal_goto($url) {
 
  /*
  ** It is advised to use "drupal_goto()" instead of PHP's "header()" as 
  ** "drupal_goto()" will append the user's session ID to the URI when PHP 
  ** is compiled with "--enable-trans-sid".
  */

  if (strstr($url, "?")) {
    header("Location: $url&". SID);
  }
  else {
    header("Location: $url?". SID);
  }
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
  foreach (module_list() as $name) {
    if (module_hook($name, "filter")) $text = module_invoke($name, "filter", $text);
  }
  return addslashes(stripslashes(substr($text, 0, variable_get("max_input_size", 10000))));
}

function check_output($text, $nl2br = 0) {
  return ($text) ? ($nl2br ? nl2br(stripslashes($text)) : stripslashes($text)) : message_na();
}

function format_info($body, $block) {
  return "<table><tr><td><table align=\"right\" border=\"1\" width=\"180\"><tr><td>$block</td></tr></table>$body</td></tr></table>\n";
}

function format_rss_channel($title, $link, $description, $items, $language = "en") {
  $output .= "<channel>\n";
  $output .= " <title>". htmlentities(strip_tags($title)) ."</title>\n";
  $output .= " <link>". htmlentities(strip_tags($link)) ."</link>\n";
  $output .= " <description>". htmlentities($description) ."</description>\n";
  $output .= " <language>". htmlentities(strip_tags($language)) ."</language>\n";
  $output .= $items;
  $output .= "</channel>\n";

  return $output;
}

function format_rss_item($title, $link, $description) {
  $output .= "<item>\n";
  $output .= " <title>". htmlentities(strip_tags($title)) ."</title>\n";
  $output .= " <link>". htmlentities(strip_tags($link)) ."</link>\n";
  $output .= " <description>". htmlentities($description) ."</description>\n";
  $output .= "</item>\n";

  return $output;
}

function format_plural($count, $singular, $plural) {
  return ($count == 1) ? "$count ". t($singular) : "$count ". t($plural);
}

function format_size($size) {
  $suffix = "bytes";
  if ($size > 1024) {
    $size = round($size / 1024, 2);
    $suffix = "KB";
  }
  if ($size > 1024) {
    $size = round($size / 1024, 2);
    $suffix = "MB";
  }
  return "$size $suffix";
}

function cache_clear($interval = 0) {
  db_query("DELETE FROM cache WHERE ". time() ." - timestamp > $interval");
}

function cache_get() {
  global $user, $REQUEST_URI, $REQUEST_METHOD;

  if (!$user->uid && $REQUEST_METHOD == "GET") {
    if ($cache = db_fetch_object(db_query("SELECT * FROM cache WHERE url = '". check_input($REQUEST_URI) ."'"))) {
      cache_clear(variable_get("cache_clear", 30));
    }
    else {
      ob_start();
    }
  }

  return $cache->data ? $cache->data : 0;
}

function cache_set() {
  global $user, $REQUEST_URI, $REQUEST_METHOD;

  if (!$user->uid && $REQUEST_METHOD == "GET") {
    if ($data = ob_get_contents()) {
      db_query("INSERT INTO cache (url, data, timestamp) VALUES('". addslashes($REQUEST_URI) ."', '". addslashes($data) ."', '". time() ."')");
    }
  }
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
          $date = t(date($c, $timestamp)).$date;
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

function format_name($object) {

  if ($object->uid && $object->name) {
    return "<a href=\"module.php?mod=user&op=view&id=$object->uid\">$object->name</a>";
  }
  else {
    return variable_get(anonymous, "Anonymous");
  }

}

function format_email($address) {
  return ($address) ? "<a href=\"mailto:$address\">$address</A>" : message_na();
}

function format_url($address, $description = 0) {
  $description = ($description) ? $description : $address;
  return ($address) ? "<a href=\"$address\">". check_output($description) ."</a>" : message_na();
}

function format_tag($link, $text) {
  return "'<a href=\"node.php?title='. urlencode('$link') .'\">'. ('$text' ? '$text' : '$link') .'</a>'";
}

function form($form, $method = "post", $action = 0, $options = 0) {
  global $REQUEST_URI;

  return "<form action=\"". ($action ? $action : $REQUEST_URI) ."\" method=\"$method\"". ($options ? " $options" : "") .">\n$form</form>\n";
}

function form_item($title, $value, $description = 0) {
  return ($description) ? "<b>$title:</b><br />$value<br /><small><i>$description</i></small><p />\n" : "<b>$title:</b><br />$value<p />\n";
}

function form_textfield($title, $name, $value, $size, $maxlength, $description = 0) {
  return form_item($title, "<input maxlength=\"$maxlength\" name=\"edit[$name]\" size=\"$size\" value=\"". check_form($value) ."\" />", $description);
}

function form_password($title, $name, $value, $size, $maxlength, $description = 0) {
  return form_item($title, "<input type=\"password\" maxlength=\"$maxlength\" name=\"edit[$name]\" size=\"$size\" value=\"". check_form($value) ."\" />", $description);
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
  ereg(",$name=([^,]+)", ",$string", $regs);
  return $regs[1];
}

function field_set($string, $name, $value) {
  $rval = ereg_replace(",$name=[^,]+", "", ",$string");
  if ($value) $rval .= ($rval == "," ? "" : ",") ."$name=$value";
  return substr($rval, 1);
}

function field_merge($a, $b) {
  foreach (explode(",", $b) as $data) {
    $entry = explode("=", $data);
    $a = field_set($a, $entry[0], $entry[1]);
  }
  return $a;
}

function link_page() {

  $links[] = "<a href=\"index.php\">". t("home") ."</a>";
  $links[] = "<a href=\"submit.php\">". t("submit") ."</a>";

  foreach (module_list() as $name) {
    if (module_hook($name, "link")) {
      $links = array_merge($links, module_invoke($name, "link", "page"));
    }
  }


  return $links;
}

function link_node($node) {
  foreach (module_list() as $name) {
    if (module_hook($name, "link")) {
      $links = array_merge($links, module_invoke($name, "link", "node", $node));
    }
  }

  return $links ? $links : array();
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
  if (variable_get("dev_timer", 0)) {
    timer_start();
  }

  if (variable_get("cache", 0)) {
    if ($data = cache_get()) {
      print $data;
      exit();
    }
  }
}

function page_footer() {
  if (variable_get("dev_timer", 0)) {
    timer_print();
  }

  if (variable_get("cache", 0)) {
    cache_set();
  }
}

$conf = conf_init();

include_once "includes/$conf.php";
include_once "includes/database.inc";
include_once "includes/variable.inc";
include_once "includes/comment.inc";
include_once "includes/xmlrpc.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/search.inc";
include_once "includes/theme.inc";
include_once "includes/node.inc";

// initialize configuration variables:
$conf = variable_init();

// initialize installed modules:
module_init();

// initialize localization system:
$locale = locale_init();

// initialize theme:
$theme = theme_init();

// set error handler:
set_error_handler("error_handler");

?>
