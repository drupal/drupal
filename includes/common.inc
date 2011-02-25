<?php

function conf_init() {
  global $HTTP_HOST;

  /*
  ** Try finding a matching configuration file by stripping the website's
  ** URI from left to right.  If no configuration file is found, return a
  ** default value 'conf'.
  */

  $file = strtolower(strtr($HTTP_HOST . substr(request_uri(), 0, strrpos(request_uri(), "/")), "/:", ".."));

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
  if (($errno == 1 || $errno == 2 || $errno == 4 || $errno == 256) && error_reporting()) {
    watchdog("error", $types[$errno] .": $message in $filename on line $line.");
    print "<pre>$entry</pre>";
  }
}

function watchdog($type, $message) {
  global $user;
  db_query("INSERT INTO watchdog (uid, type, message, location, hostname, timestamp) VALUES ('%d', '%s', '%s', '%s', '%s', '%s')", $user->uid, $type, $message, request_uri(), getenv("REMOTE_ADDR"), time());
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

function check_php_setting($name, $value) {
  if (ini_get($name) != $value) {
    print "<p>Note that the value of PHP's configuration option <code><b>$name</b></code> is incorrect.  It should be set to '$value' for Drupal to work properly.  Either configure your webserver to support <code>.htaccess</code> files so Drupal's <code>.htaccess</code> file can set it to the proper value, or edit your <code>php.ini</code> file directly.  This message will automatically dissapear when the problem has been fixed.</p>";
  }
}

function array2object($node) {

  if (is_array($node)) {
    foreach ($node as $key => $value) {
      $object->$key = $value;
    }
  }
  else {
    $object = $node;
  }

  return $object;
}

function object2array($node) {

  if (is_object($node)) {
    foreach ($node as $key => $value) {
      $array[$key] = $value;
    }
  }
  else {
    $array = $node;
  }

  return $array;
}

function path_uri($brief = 0) {
  global $HTTP_HOST;
  $path = $HTTP_HOST . substr(request_uri(), 0, strrpos(request_uri(), "/")) ."/";
  if (!$brief) {
    $path = "http://". $path;
  }
  return $path;
}

function request_uri() {
  // since request_uri() is only available on apache, we generate equivalent using other environment vars.

  global $REQUEST_URI, $PATH_INFO, $QUERY_STRING;

  if ($REQUEST_URI) {
    $uri = $REQUEST_URI;
  }
  else {
    $uri = $PATH_INFO ."?". $QUERY_STRING;
  }

  /*
  ** We pipe the request URI through htmlspecialchars() to prevent
  ** XSS attacks.
  */

  $uri = htmlspecialchars($uri, ENT_QUOTES);

  /*
  ** We replace ( and ) with their entity equivalents to prevent XSS
  ** attacks.
  */

  $uri = strtr($uri, array("(" => "&040;", ")" => "&041;"));


  return $uri;
}

function message_access() {
  return t("You are not authorized to access this page.");
}

function message_na() {
  return t("n/a");
}

function message_throttle() {
  return t("You exceeded the maximum submission rate.  Please wait a few minutes and try again.");
}

function locale_init() {
  global $languages, $user;
  return ($languages ? (($user->uid && $user->language) ? $user->language : key($languages)) : 0);
}

function t($string, $args = 0) {
  global $languages;

  /*
  ** About the usage of t().  We try to keep strings whole as much as
  ** possible and are unafraid of HTML markup within translation strings
  ** if necessary.  The suggested syntax for a link embedded within a
  ** translation string is for example:
  **
  ** $msg = t("You must login below or <a href=\"%url\">create a new
  **           account</a> before viewing the next page.", array ("%url"
  **           => drupal_url(array ("mod" => "user", "op" => "register"),
  **           "module")));
  */

  $string = ($languages && function_exists("locale") ? locale($string) : $string);

  if (!$args) {
    return $string;
  }
  else {
    return strtr($string, $args);
  }
}

function variable_init($conf = array()) {
  $result = db_query("SELECT * FROM variable");
  while ($variable = db_fetch_object($result)) {
    if (!isset($conf[$variable->name])) {
      $conf[$variable->name] = unserialize($variable->value);
    }
  }

  return $conf;
}

function variable_get($name, $default, $object = 0) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}

function variable_set($name, $value) {
  global $conf;

  db_query("DELETE FROM variable WHERE name = '%s'", $name);
  db_query("INSERT INTO variable (name, value) VALUES ('%s', '%s')", $name, serialize($value));

  $conf[$name] = $value;
}

function variable_del($name) {
  global $conf;

  db_query("DELETE FROM variable WHERE name = '%s'", $name);

  unset($conf[$name]);
}

/**
 * Format a single result entry of a search query:
 *
 * @param $item  a single search result as returned by <module>_search of type
 *               array("count" => ..., "link" => ..., "title" => ...,
 *               "user" => ..., "date" => ..., "keywords" => ...)
 * @param $type  module type of this item
 */
function search_item($item, $type) {

  /*
  ** Modules may implement the "search_item" hook in order to overwrite
  ** the default function to display search results.
  */

  if (module_hook($type, "search_item")) {
    $output = module_invoke($type, "search_item", $item);
  }
  else {
    $output .= " <b>". $item["count"] ."&nbsp;&nbsp;<u><a href=\"". $item["link"] ."\">". $item["title"] ."</a></u></b><br />";
    $output .= " <small>$type ". ($item["user"] ? " - ". $item["user"] : "") ."". ($item["date"] ? " - ". format_date($item["date"], "small") : "") ."</small>";
    $output .= "<br /><br />";
  }

  return $output;
}

/**
 * Render a generic search form.
 *
 * "Generic" means "universal usable" - that is, usable not only from
 * module.php?mod=search, but also as a simple seach box (without
 * "Restrict search to", help text, etc) from theme's header etc.
 * This means: provide options to only conditionally render certain
 * parts of this form.
 *
 * @param $action  Form action. Defaults to module.php?mod=search.
 * @param $query   Query string. Defaults to global $keys.
 * @param $options != 0: Render additional form fields/text
 *                 ("Restrict search to", help text, etc).
 */
function search_form($action = 0, $query = 0, $options = 0) {
  global $keys;

  if (!$action) {
    $action = drupal_url(array("mod" => "search"), "module");
  }

  if (!$query) {
    $query = $keys;
  }

  $output .= " <br /><input type=\"text\" size=\"50\" value=\"". check_form($keys) ."\" name=\"keys\" />";
  $output .= " <input type=\"submit\" value=\"". t("Search") ."\" />\n";

  if ($options != 0) {
    $output .= "<br />";
    $output .= t("Restrict search to") .": ";

    foreach (module_list() as $name) {
      if (module_hook($name, "search")) {
        $output .= " <input type=\"checkbox\" name=\"edit[type][$name]\" ". ($edit["type"][$name] ? " checked=\"checked\"" : "") ." /> ". t($name);
      }
    }

    // TODO: (link to) search hints
  }

  $form .= "<br />";

  return form($output, "post", $action);
}

/*
 * Collect the search results:
 */
function search_data() {
  global $keys, $edit;

  $keys = check_input($keys);

  if ($keys) {
    foreach (module_list() as $name) {
      if (module_hook($name, "search") && (!$edit["type"] || $edit["type"][$name]) && ($result = module_invoke($name, "search", check_query($keys)))) {
        if ($name == "node" || $name == "comment") {
          $output .= "<p><b>". t("Matching ". $name ."s ranked in order of relevance") .":</b></p>";
        }
        else {
          $output .= "<p><b>". t("Matching ". $name ."s") .":</b></p>";
        }
        foreach ($result as $entry) {
          $output .= search_item($entry, $name);
        }
      }
    }
    if (!$output) {
      $output .= t("Your search yielded no results.");
    }
  }

  return $output;
}

/**
 * Display the search form and the resulting data.
 *
 * @param $type    If set, search only nodes of this type.
 *                 Otherwise, search all types.
 * @param $action  Form action. Defaults to module.php?mod=search.
 * @param $query   Query string. Defaults to global $keys.
 * @param $options != 0: Render additional form fields/text
 *                 ("Restrict search to", help text, etc).
 */
function search_type($type = 0, $action = 0, $query = 0, $options = 0) {
  global $edit;

  if ($type) {
    $edit["type"][$type] = "on";
  }

  return search_form($action, $query, $options) . search_data();
}


function drupal_goto($url) {

  /*
  ** Translate &amp; to simply &
  */

  $url = str_replace ("&amp;", "&", $url);

  /*
  ** It is advised to use "drupal_goto()" instead of PHP's "header()" as
  ** "drupal_goto()" will append the user's session ID to the URI when PHP
  ** is compiled with "--enable-trans-sid".
  */
  if (!ini_get("session.use_trans_sid") || !session_id() || strstr($url, session_id())) {
    header("Location: $url");
  }
  else {
    $sid = session_name() . "=" . session_id();

    if (strstr($url, "?") && !strstr($url, $sid)) {
      header("Location: $url&". $sid);
    }
    else {
      header("Location: $url?". $sid);
    }
  }

  /*
  ** The "Location" header sends a REDIRECT status code to the http
  ** deamon.  In some cases this can go wrong, so we make sure none
  ** of the code /below/ gets executed when we redirect.
  */

  exit();
}

/*
** Stores the referer in a persistent variable:
*/

function referer_save() {
  global $referer, $HTTP_REFERER;

  if (!strstr($HTTP_REFERER, request_uri())) {
    $referer = $HTTP_REFERER;
    session_register("referer");
  }
}

/*
** Restores the referer from a persistent variable:
*/

function referer_load() {
  global $referer;

  if (session_is_registered("referer")) {
    return $referer;
  }
  else {
    return 0;
  }
}

function check_form($text) {
  return htmlspecialchars($text);
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
  return addslashes($text);
}

function filter($text) {
  foreach (module_list() as $name) {
    if (module_hook($name, "filter")) {
      $text = module_invoke($name, "filter", $text);
    }
  }

  return $text;
}

function check_input($text) {
  return check_query($text);
}

function check_output($text, $nl2br = 0) {
  return ($text) ? ($nl2br ? str_replace("\r", "", str_replace("\n", "<br />", stripslashes($text))) : stripslashes($text)) : message_na();
}

function check_file($filename) {
  if (is_uploaded_file($filename)) {
    return 1;
  }
  else {
    return 0;
  }
}

function format_rss_channel($title, $link, $description, $items, $language = "en", $args = array()) {
  // arbitrary elements may be added using the $args associative array

  $output .= "<channel>\n";
  $output .= " <title>". htmlentities(strip_tags($title)) ."</title>\n";
  $output .= " <link>". htmlentities(strip_tags($link)) ."</link>\n";
  $output .= " <description>". htmlentities($description) ."</description>\n";
  $output .= " <language>". htmlentities(strip_tags($language)) ."</language>\n";
  foreach ($args as $key => $value) {
    $output .= "<$key>". htmlentities(strip_tags($value)) ."</$key>";
  }
  $output .= $items;
  $output .= "</channel>\n";

  return $output;
}

function format_rss_item($title, $link, $description, $args = array()) {
  // arbitrary elements may be added using the $args associative array

  $output .= "<item>\n";
  $output .= " <title>". htmlentities(strip_tags($title)) ."</title>\n";
  $output .= " <link>". htmlentities(strip_tags($link)) ."</link>\n";
  $output .= " <description>". htmlentities($description) ."</description>\n";
  foreach ($args as $key => $value) {
    $output .= "<$key>". htmlentities(strip_tags($value)) ."</$key>";
  }
  $output .= "</item>\n";

  return $output;
}

function format_plural($count, $singular, $plural) {
  return ($count == 1) ? "$count ". t($singular) : "$count ". t($plural);
}

function format_size($size) {
  $suffix = t("bytes");
  if ($size > 1024) {
    $size = round($size / 1024, 2);
    $suffix = t("KB");
  }
  if ($size > 1024) {
    $size = round($size / 1024, 2);
    $suffix = t("MB");
  }
  return t("%size %suffix", array("%size" => $size, "%suffix" => $suffix));
}

function cache_get($key) {
  $cache = db_fetch_object(db_query("SELECT data FROM cache WHERE cid = '%s'", $key));
  return $cache->data ? $cache->data : 0;
}

function cache_set($cid, $data, $expire = 0) {
  if (db_fetch_object(db_query("SELECT cid FROM cache WHERE cid = '%s'", $cid))) {
    db_query("UPDATE cache SET data = '%s' WHERE cid = '%s'", $data, $cid);
  }
  else {
    db_query("INSERT INTO cache (cid, data, expire) VALUES('%s', '%s', '%s')", $cid, $data, $expire);
  }
}

function cache_clear_all($cid = NULL) {
  if (empty($cid)) {
    db_query("DELETE FROM cache WHERE expire <> 0");
  }
  else {
    db_query("DELETE FROM cache WHERE cid = '%s'", $cid);
  }
}

function cache_clear_old($cid = NULL) {
  if (empty($cid)) {
    db_query("DELETE FROM cache WHERE expire < ". time() ." AND expire > 0");
  }
  else {
    db_query("DELETE FROM cache WHERE cid = '%s' AND expire < %s AND expire > 0", $cid, time());
  }
}

function page_set_cache() {
  global $user, $REQUEST_METHOD;

  if (!$user->uid && $REQUEST_METHOD == "GET") {
    if ($data = ob_get_contents()) {
      cache_set(request_uri(), $data, (time() + variable_get("cache_clear", 120)));
    }
  }
}

function page_get_cache() {
  global $user, $REQUEST_METHOD;

  if (!$user->uid && $REQUEST_METHOD == "GET") {
    if ($cache = cache_get(request_uri())) {
      cache_clear_old();
    }
    else {
      ob_start();
    }
  }

  return $cache ? $cache : 0;
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
  return ($output) ? $output : t("0 sec");
}

function format_date($timestamp, $type = "medium", $format = "") {
  global $user;

  $timestamp += ($user->timezone) ? $user->timezone - date("Z") : 0;

  switch ($type) {
    case "small":
      $date = date(variable_get("date_format", "m/d/Y - H:i"), $timestamp);
      break;
    case "medium":
      $date = t(date("l", $timestamp)) .", ". date(variable_get("date_format", "m/d/Y - H:i"), $timestamp);
      break;
    case "large":
      $date = t(date("l", $timestamp)) .", ". t(date("F", $timestamp)) ." ". date("d, Y - H:i", $timestamp);
      break;
    case "custom":
      for ($i = strlen($format); $i >= 0; $c = $format[--$i]) {
        if (strstr("DFlMSw", $c)) {
          $date = t(date($c, $timestamp)) . $date;
        }
        else if (strstr("AaBdgGhHiIjLmnrstTUWYyZz", $c)) {
          $date = date($c, $timestamp) . $date;
        }
        else {
          $date = $c.$date;
        }
      }
      break;
    default:
      $date = t(date("l", $timestamp)) .", ". date(variable_get("date_format", "m/d/Y - H:i"), $timestamp);
  }
  return $date;
}

function format_name($object) {
  global $PHP_SELF;

  if ($object->uid && $object->name) {
    if (strstr($PHP_SELF, "admin.php")) {
      $output = la($object->name, array("mod" => "user", "op" => "edit", "id" => $object->uid), "", array("title" => t("Administer user profile.")));
    }
    else {
      $output = lm($object->name, array("mod" => "user", "op" => "view", "id" => $object->uid), "", array("title" => t("View user profile.")));
    }
  }
  else {
    $output = t(variable_get(anonymous, "Anonymous"));
  }

  return $output;
}

function format_tag($link, $text) {
  return l(($text ? $text : $link), array("title" => $link));
}

function form($form, $method = "post", $action = 0, $options = 0) {
  return "<form action=\"". ($action ? $action : request_uri()) ."\" method=\"$method\"". ($options ? " $options" : "") .">\n$form</form>\n";
}

function form_item($title, $value, $description = 0) {
  return "<p>". ($title ? "<b>$title:</b><br />" : "") . $value . ($description ? "<br /><small><i>$description</i></small>" : "") ."</p>\n";
}

function form_checkbox($title, $name, $value = 1, $checked = 0, $description = 0) {
  return form_item(0, "<input type=\"checkbox\" name=\"edit[$name]\" value=\"". $value ."\"". ($checked ? " checked=\"checked\"" : "") ." /> $title", $description);
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

function form_select($title, $name, $value, $options, $description = 0, $extra = 0, $multiple = 0) {
  if (count($options) > 0) {
    foreach ($options as $key=>$choice) {
      $select .= "<option value=\"$key\"". (is_array($value) ? (in_array($key, $value) ? " selected=\"selected\"" : "") : ($key == $value ? " selected=\"selected\"" : "")) .">". check_form($choice) ."</option>";
    }
    return form_item($title, "<select name=\"edit[$name]". ($multiple ? "[]" : "") ."\"". ($multiple ? " multiple " : "") . ($extra ? " $extra" : "") .">$select</select>", $description);
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

function form_weight($title = NULL, $name = "weight", $value = 0, $delta = 10, $description = 0, $extra = 0) {
  for ($n = (-1*$delta); $n <= $delta; $n++) {
    $weights[$n] = $n;
  }

  return form_select($title, $name, $value, $weights, $description, $extra);
}

/**
 * Build an URL; use this functions when you must write an URL
 * for example in a form or a redirect.
 *
 * @param $args    dictionary of arguments to be passed to the script
 * @param $script  script to be invoked; optional, defaults to node
 * @param $anchor  optional, anchor name
 */
function drupal_url($args = array(), $script = "node", $anchor = 0) {
  $t = array();
  foreach ($args as $key => $value) {
    $t[] = "$key=". urlencode($value);
  }
  if (count($t)) {
    return "$script.php?". implode("&amp;", $t) . ($anchor ? "#$anchor" : "");
  }
  else {
    return "$script.php". ($anchor ? "#$anchor" : "");
  }
}

/**
 * Build a HTML link; use this functions when you must write a link
 * to another drupal page
 *
 * @param $args        dictionary of arguments to be passed to the script
 * @param $text        text of the link
 * @param $anchor      optional, anchor name
 * @param $script      script to be invoked; optional, defaults to node
 * @param $attributes  optional, dictionary of attributes for the <a> tag such as 'target', 'name', 'class', etc.
 */
function l($text, $args = array(), $script = "node", $anchor = "", $attributes = array()) {
  $t = array();
  foreach ($attributes as $key => $value) {
    $t[] = "$key=\"$value\"";
  }
  return "<a href=\"". drupal_url($args, $script, $anchor) ."\" ". implode($t, " ")  .">$text</a>";
}

function la($text, $args = array(), $anchor = "", $attributes = array()) {
  // we don't call l() to avoid another duplication of the array
  $t = array();
  foreach ($attributes as $key => $value) {
    $t[] = "$key=\"$value\"";
  }
  return "<a href=\"". drupal_url($args, "admin", $anchor) ."\" ". implode($t, " ")  .">$text</a>";
}

function lm($text, $args = array(), $anchor = "", $attributes = array()) {
  // we don't call l() to avoid another duplication of the array
  $t = array();
  foreach ($attributes as $key => $value) {
    $t[] = "$key=\"$value\"";
  }
  return "<a href=\"". drupal_url($args, "module", $anchor) ."\" ". implode($t, " ")  .">$text</a>";
}

function field_get($string, $name) {
  ereg(",$name=([^,]+)", ",$string", $regs);
  return $regs[1];
}

function field_set($string, $name, $value) {
  $rval = ereg_replace(",$name=[^,]+", "", ",$string");
  if ($value) {
    $rval .= ($rval == "," ? "" : ",") ."$name=$value";
  }
  return substr($rval, 1);
}

function link_page() {
  global $custom_links;

  if (is_array($custom_links)) {
    return $custom_links;
  }
  else {
    $links[] = l(t("home"), array(), "index", "", array("title" => t("Return to the main page.")));

    foreach (module_list() as $name) {
      if (module_hook($name, "link")) {
        $links = array_merge($links, module_invoke($name, "link", "page"));
      }
    }

    return $links;
  }
}

function link_node($node, $main = 0) {
  $links = array();

  foreach (module_list() as $name) {
    if (module_hook($name, "link")) {
      $links = array_merge($links, module_invoke($name, "link", "node", $node, $main));
    }
  }

  return $links;
}

function timer_start() {
  global $timer;
  list($usec, $sec) = explode(" ", microtime());
  $timer = (float)$usec + (float)$sec;
}

function timer_print() {
  global $timer;
  list($usec, $sec) = explode(" ", microtime());
  $stop = (float)$usec + (float)$sec;
  $diff = $stop - $timer;
  print "<pre>execution time: $diff sec</pre>";
}

function query_print() {
  global $queries;
  print "<pre>";
  print_r($queries);
  print "</pre>";
}

function page_header() {
  if (variable_get("dev_timer", 0)) {
    timer_start();
  }

  if (variable_get("cache", 0)) {
    if ($data = page_get_cache()) {
      print $data;
      exit();
    }
  }
}

function page_footer() {
  if (variable_get("dev_timer", 0)) {
    timer_print();
  }

  if (variable_get("dev_query", 0)) {
    query_print();
  }

  if (variable_get("cache", 0)) {
    page_set_cache();
  }
}

// set error handler:
set_error_handler("error_handler");

unset($conf);

$config = conf_init();

include_once "includes/$config.php";
include_once "includes/database.inc";
// include_once "includes/xmlrpc.inc";
include_once "includes/module.inc";
include_once "includes/theme.inc";
include_once "includes/pager.inc";

// initialize configuration variables, using values from conf.php if available:
$conf = variable_init(isset($conf) ? $conf : array());

// initialize installed modules:
module_init();

// initialize localization system:
$locale = locale_init();

// initialize theme:
$theme = theme_init();

?>
