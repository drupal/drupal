<?php

$na = "<I>na</I>";

function conf_init() {
  global $HTTP_HOST, $REQUEST_URI;
  $file = strtolower(strtr($HTTP_HOST ."". substr($REQUEST_URI, 0, strrpos($REQUEST_URI, "/")), "/:", ".."));
  return file_exists("includes/$file.php") ? $file : "setting";
}

function error_handler($errno, $message, $filename, $line, $variables) {
  $types = array(1 => "error", 2 => "warning", 4 => "parse error", 8 => "notice", 16 => "core error", 32 => "core warning", 64 => "compile error", 128 => "compile warning", 256 => "user error", 512 => "user warning", 1024 => "user notice");
  $entry = $types[$errno] .": $message in $filename on line $line.";
  if ($errno == 1 || $errno == 2 || $errno == 4) {
    watchdog("error", $types[$errno] .": $message in $filename on line $line.");
    print $entry;
  }
}

function watchdog($type, $message) {
  global $user, $watchdog, $PHP_SELF;
  $link = ($mod) ? $mod : substr(strrchr($PHP_SELF, "/"), 1, strrchr($PHP_SELF, "/") - 4);
  db_query("INSERT INTO watchdog (user, type, link, message, location, hostname, timestamp) VALUES ('$user->id', '". check_input($type) ."', '". check_input($link) ."', '". check_input($message) ."', '". check_input(getenv("REQUEST_URI")) ."', '". check_input(getenv("REMOTE_ADDR")) ."', '". time() ."')");
}

function throttle($type, $rate) {
  global $user;
  if (!(user_access($user, "watchdog") || user_access($user, "comment") || user_access($user, "node"))) {
    if ($throttle = db_fetch_object(db_query("SELECT * FROM watchdog WHERE type = '$type' AND hostname = '". getenv("REMOTE_ADDR") ."' AND ". time() ." - timestamp < $rate"))) {
      watchdog("warning", "throttle: '". getenv("REMOTE_ADDR") ."' exceeded submission rate - $throttle->type");
      header("Location: error.php?op=throttle");
      die("submission rate exceeded");
    }
    else {
      watchdog($type, "throttle control");
    }
  }
}


function check_textfield($message) {
  return strip_tags(str_replace("\"", "&quot;", stripslashes($message)));
}

function check_select($message) {
  return check_textfield($message);
}

function check_export($message) {
  return check_textfield($message);
}

function check_textarea($message) {
  global $allowed_html;
  return htmlspecialchars(strip_tags(stripslashes($message), $allowed_html));
}

function check_input($message) {
  global $allowed_html;
  return strip_tags(addslashes(stripslashes(substr($message, 0, variable_get(max_input_size, 10000)))), $allowed_html);
}

function check_code($message) {
  return $message;
}

function check_mail($mail) {
  return eregi("^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $mail) ? 1 : 0;
}

function check_name($name) {
  return ereg("[^a-zA-Z0-9_-]", $name) ? 0 : 1;
}

function check_output($message, $nl2br = 0) {
  global $allowed_html, $na;
  $var = strip_tags(stripslashes(format_text($message)), $allowed_html);
  return ($var) ? (($nl2br) ? nl2br($var) : $var) : $na;
}

function format_plural($count, $singular, $plural) {
  return ($count == 1) ? "$count ". t($singular) : "$count ". t($plural);
}

function format_interval($timestamp) {
  if ($timestamp >= 86400) {
    $output .= format_plural(floor($timestamp / 86400), "day", "days");
    $timestamp = $timestamp % 86400;
  }
  if ($timestamp >= 3600) {
    $output .= " ". format_plural(floor($timestamp / 3600), "hour", "hours");
    $timestamp = $timestamp % 3600;
  }
  if ($timestamp >= 60) {
    $output .= " ". floor($timestamp / 60) ." min";
    $timestamp = $timestamp % 60;
  }
  if ($timestamp > 0) {
    $output .= " $timestamp sec";
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
      $date = date($format, $timestamp);
      break;
    default:
      $date = t(date("l", $timestamp)) .", ". date("m/d/Y - H:i", $timestamp);
  }
  return $date;
}

function format_username($username) {
  global $user;
  if ($username) return (user_access($user, "account") ? "<A HREF=\"admin.php?mod=account&op=view&name=$username\">$username</A>" : "<A HREF=\"account.php?op=view&name=$username\">$username</A>");
  else return variable_get(anonymous, "Anonymous");
}

function format_email($address) {
  global $na;
  return ($address) ? "<A HREF=\"mailto:$address\">$address</A>" : $na;
}

function format_url($address, $description = "") {
  global $na;
  $description = ($description) ? $description : $address;
  return ($address) ? "<A HREF=\"$address\">". check_output($description) ."</A>" : $na;
}

function format_tag($link, $text) {
  return "'<a href=\"node.php?title='. urlencode('$link') .'\">'. ('$text' ? '$text' : '$link') .'</a>'";
}

function format_text($text) {

  $src = array( // "/(<\/?)(\w+)([^>]*>)/e",             // convert HTML to lower case
               "/\[\[(([^\|]*?)(\|([^\|]*?))?)\]\]/e");  // [link|description]
  $dst = array( // "'\\1'. strtolower('\\2') .'\\3'",    // convert HTML to lower case
               format_tag('\\2', '\\4'));                // [link|description]

  return preg_replace($src, $dst, $text);
}

function form($action, $form, $method = "post") {
  return "<FORM ACTION=\"$action\" METHOD=\"$method\">\n$form</FORM>\n";
}

function form_item($title, $value, $description = 0) {
  return ($description) ? "<B>$title:</B><BR>$value<BR><SMALL><I>$description</I></SMALL><P>\n" : "<B>$title:</B><BR>$value<P>\n";
}

function form_textfield($title, $name, $value, $size, $maxlength, $description = 0) {
  return form_item($title, "<INPUT MAXLENGTH=\"$maxlength\" NAME=\"edit[$name]\" SIZE=\"$size\" VALUE=\"". check_textfield($value) ."\">", $description);
}

function form_textarea($title, $name, $value, $cols, $rows, $description = 0) {
  return form_item($title, "<TEXTAREA WRAP=\"virtual\" COLS=\"$cols\" ROWS=\"$rows\" NAME=\"edit[$name]\">". check_textarea($value) ."</TEXTAREA>", $description);
}

function form_select($title, $name, $value, $options, $description = 0) {
  foreach ($options as $key=>$choice) $select .= "<OPTION VALUE=\"$key\"". ($key == $value ? " SELECTED" : "") .">". check_select($choice) ."</OPTION>";
  return form_item($title, "<SELECT NAME=\"edit[$name]\">$select</SELECT>", $description);
}

function form_hidden($name, $value) {
  return "<INPUT TYPE=\"hidden\" NAME=\"edit[$name]\" VALUE=\"". check_textfield($value) ."\">\n";
}

function form_submit($value) {
  return "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". check_textfield($value) ."\">\n";
}

$conf = conf_init();

include_once "includes/$conf.php";
include_once "includes/structure.inc";
include_once "includes/database.inc";
include_once "includes/variable.inc";
include_once "includes/comment.inc";
include_once "includes/module.inc";
include_once "includes/locale.inc";
include_once "includes/search.inc";
include_once "includes/timer.inc";
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