<?php

function conf_init() {

  /*
  ** Try finding a matching configuration file by stripping the website's
  ** URI from left to right.  If no configuration file is found, return a
  ** default value 'conf'.
  */

  $uri = $_SERVER["PHP_SELF"];

  $file = strtolower(strtr($_SERVER["HTTP_HOST"] . substr($uri, 0, strrpos($uri, "/")), "/:", ".."));

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

/**
 * Build the alias/path array
 */
function drupal_get_path_map($action = "") {

  static $cache;
  static $map;

  if ($action == "rebuild") {
    $map = NULL;
    $cache = 0;
  }

  if (!$cache) {
    $result = db_query("SELECT * FROM {url_alias}");
    while ($data = db_fetch_object($result)) {
      $map[$data->dst] = $data->src;
    }

    $cache = 1;
  }

  return $map;
}

function drupal_rebuild_path_map() {
  drupal_get_path_map("rebuild");
}

function error_handler($errno, $message, $filename, $line, $variables) {
  $types = array(1 => "error", 2 => "warning", 4 => "parse error", 8 => "notice", 16 => "core error", 32 => "core warning", 64 => "compile error", 128 => "compile warning", 256 => "user error", 512 => "user warning", 1024 => "user notice");
  $entry = $types[$errno] .": $message in $filename on line $line.";

  if ($errno & E_ALL ^ E_NOTICE) {
    watchdog("error", $types[$errno] .": $message in $filename on line $line.");
    print "<pre>$entry</pre>";
  }
}

function watchdog($type, $message, $link = NULL) {
  global $user;
  db_query("INSERT INTO {watchdog} (uid, type, message, link, location, hostname, timestamp) VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d)", $user->uid, $type, $message, $link, request_uri(), getenv("REMOTE_ADDR"), time());
}

function throttle($type, $rate) {
  if (!user_access("access administration pages")) {
    if ($throttle = db_fetch_object(db_query("SELECT * FROM {watchdog} WHERE type = '$type' AND hostname = '". getenv("REMOTE_ADDR") ."' AND ". time() ." - timestamp < $rate"))) {
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

function arg($index) {

  static $arguments;

  if (empty($arguments)) {
    $arguments = explode("/", $_GET["q"]);
  }

  return $arguments[$index];
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

function referer_uri() {

  if (isset($_SERVER["HTTP_REFERER"])) {
    $uri = $_SERVER["HTTP_REFERER"];
    return check_url($uri);
  }
}

function request_uri() {
  /*
  ** Since request_uri() is only available on Apache, we generate
  ** equivalent using other environment vars.
  */

  if (isset($_SERVER["REQUEST_URI"])) {
    $uri = $_SERVER["REQUEST_URI"];
  }
  else {
    $uri = $_SERVER["PHP_SELF"] ."?". $_SERVER["QUERY_STRING"];
  }

  return check_url($uri);
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
  if ($user->uid && $languages[$user->language]) {
    return $user->language;
  }
  else {
    return key($languages);
  }
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
  **           account</a> before viewing the next page.", array("%url"
  **           => url("user/register")));
  */

  $string = ($languages && module_exist("locale") ? locale($string) : $string);

  if (!$args) {
    return $string;
  }
  else {
    return strtr($string, $args);
  }
}

function variable_init($conf = array()) {
  $result = db_query("SELECT * FROM {variable} ");
  while ($variable = db_fetch_object($result)) {
    if (!isset($conf[$variable->name])) {
      $conf[$variable->name] = unserialize($variable->value);
    }
  }

  return $conf;
}

function variable_get($name, $default) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}

function variable_set($name, $value) {
  global $conf;

  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);
  db_query("INSERT INTO {variable} (name, value) VALUES ('%s', '%s')", $name, serialize($value));

  $conf[$name] = $value;
}

function variable_del($name) {
  global $conf;

  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);

  unset($conf[$name]);
}

function drupal_specialchars($input, $quotes = ENT_NOQUOTES) {

  /*
  ** Note that we'd like to go 'htmlspecialchars($input, $quotes, "utf-8")'
  ** like the PHP manual tells us to, but we can't because there's a bug in
  ** PHP <4.3 that makes it mess up multibyte charsets if we specify the
  ** charset.  Change this later once we make PHP 4.3 a requirement.
  */

  return htmlspecialchars($input, $quotes);
}

function table_cell($cell, $header = 0) {
  if (is_array($cell)) {
    $data = $cell["data"];
    foreach ($cell as $key => $value) {
      if ($key != "data") {
        $attributes .= " $key=\"$value\"";
      }
    }
  }
  else {
    $data = $cell;
  }

  if ($header) {
    $output = "<th$attributes>$data</th>";
  }
  else {
    $output = "<td$attributes>$data</td>";
  }

  return $output;
}

function table($header, $rows) {

  $output = "<table>\n";

  /*
  ** Emit the table header:
  */

  if (is_array($header)) {
    $output .= " <tr>";
    foreach ($header as $cell) {
      if (is_array($cell) && $cell["field"]) {
        $cell = tablesort($cell, $header);
      }
      $output .= table_cell($cell, 1);
    }
    $output .= " </tr>\n";
  }

  /*
  ** Emit the table rows:
  */

  if (is_array($rows)) {
    foreach ($rows as $number => $row) {
      if ($number % 2 == 1) {
        $output .= " <tr class=\"light\">";
      }
      else {
        $output .= " <tr class=\"dark\">";
      }

      foreach ($row as $cell) {
        $output .= table_cell($cell, 0);
      }
      $output .= " </tr>\n";
    }
  }

  $output .= "</table>\n";

  return $output;
}

/**
 * Verify the syntax of the given e-mail address.  Empty e-mail addresses
 * are allowed.  See RFC 2822 for details.
 *
 * @param $mail  a email address
 */
function valid_email_address($mail) {
  $user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
  $domain = '(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]\.?)+';
  $ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
  $ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

  if (preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail)) {
    return 1;
  }
  else {
    return 0;
  }
}

/**
 * Verify the syntax of the given URL.
 *
 * @param $url  an URL
 */
function valid_url($url) {

  if (preg_match("/^[a-zA-z0-9\/:_\-_\.,]+$/", $url)) {
    return 1;
  }
  else {
    return 0;
  }
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
    $output .= " <b><u><a href=\"". $item["link"] ."\">". $item["title"] ."</a></u></b><br />";
    $output .= " <small>" . t($type) . ($item["user"] ? " - ". $item["user"] : "") ."". ($item["date"] ? " - ". format_date($item["date"], "small") : "") ."</small>";
    $output .= "<br /><br />";
  }

  return $output;
}

/**
 * Render a generic search form.
 *
 * "Generic" means "universal usable" - that is, usable not only from
 * 'site.com/search', but also as a simple seach box (without
 * "Restrict search to", help text, etc) from theme's header etc.
 * This means: provide options to only conditionally render certain
 * parts of this form.
 *
 * @param $action  Form action. Defaults to 'site.com/search'.
 * @param $keys   string containing keywords for the search.
 * @param $options != 0: Render additional form fields/text
 *                 ("Restrict search to", help text, etc).
 */
function search_form($action = NULL, $keys = NULL, $options = NULL) {

  $edit = $_POST["edit"];

  if (!$action) {
    $action = url("search");
  }

  $output .= " <br /><input type=\"text\" class=\"form-text\" size=\"50\" value=\"". check_form($keys) ."\" name=\"keys\" />";
  $output .= " <input type=\"submit\" class=\"form-submit\" value=\"". t("Search") ."\" />\n";

  if ($options != 0) {
    $output .= "<br />";
    $output .= t("Restrict search to") .": ";

    foreach (module_list() as $name) {
      if (module_hook($name, "search")) {
        $output .= " <input type=\"checkbox\" name=\"edit[type][$name]\" ". ($edit["type"][$name] ? " checked=\"checked\"" : "") ." /> ". t($name);
      }
    }
  }

  $form .= "<br />";

  return form($output, "post", $action);
}

/*
 * Collect the search results:
 */
function search_data($keys = NULL) {

  $edit = $_POST["edit"];

  if (isset($keys)) {
    foreach (module_list() as $name) {
      if (module_hook($name, "search") && (!$edit["type"] || $edit["type"][$name]) && ($result = module_invoke($name, "search", $keys))) {
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
  }

  return $output;
}

/**
 * Display the search form and the resulting data.
 *
 * @param $type    If set, search only nodes of this type.
 *                 Otherwise, search all types.
 * @param $action  Form action. Defaults to 'site.com/search'.
 * @param $query   Query string. Defaults to global $keys.
 * @param $options != 0: Render additional form fields/text
 *                 ("Restrict search to", help text, etc).
 */
function search_type($type, $action = NULL, $keys = NULL, $options = NULL) {

  $_POST["edit"]["type"][$type] = "on";

  return search_form($action, $keys, $options) . "<br />". search_data($keys);
}


function drupal_goto($url) {

  /*
  ** Translate &amp; to simply &
  */

  $url = str_replace("&amp;", "&", $url);

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
  ** daemon.  In some cases this can go wrong, so we make sure none
  ** of the code /below/ gets executed when we redirect.
  */

  exit();
}

/*
** Stores the referer in a persistent variable:
*/

function referer_save() {
  if (!strstr(referer_uri(), request_uri())) {
    $_SESSION["referer"] = referer_uri();
  }
}

/*
** Restores the referer from a persistent variable:
*/

function referer_load() {
  if (isset($_SESSION["referer"])) {
    return $_SESSION["referer"];
  }
  else {
    return 0;
  }
}

function valid_input_data($data) {

  if (is_array($data) || is_object($data)) {
    /*
    ** Form data can contain a number of nested arrays.
    */

    foreach ($data as $key => $value) {
      if (!valid_input_data($key) || !valid_input_data($value)) {
        return 0;
      }
    }
  }
  else {
    /*
    ** Detect evil input data.
    */

    // check strings:
    $match  = preg_match("/\Wjavascript\s*:/i", $data);
    $match += preg_match("/\Wexpression\s*\(/i", $data);
    $match += preg_match("/\Walert\s*\(/i", $data);

    // check attributes:
    $match += preg_match("/\W(dynsrc|datasrc|data|lowsrc|on[a-z]+)\s*=[^>]+?>/i", $data);


    // check tags:
    $match += preg_match("/<\s*(applet|script|object|style|embed|form|blink|meta|html|frame|iframe|layer|ilayer|head|frameset|xml)/i", $data);

    if ($match) {
      watchdog("warning", "terminated request because of suspicious input data: ". drupal_specialchars($data));
      return 0;
    }
  }

  return 1;
}

function check_url($uri) {
  $uri = htmlspecialchars($uri, ENT_QUOTES);

  /*
  ** We replace ( and ) with their entity equivalents to prevent XSS
  ** attacks.
  */

  $uri = strtr($uri, array("(" => "&040;", ")" => "&041;"));

  return $uri;
}

function check_form($text) {
  return drupal_specialchars($text, ENT_QUOTES);
}

function check_query($text) {
  return addslashes($text);
}

function filter($text) {

  $modules = module_list();

  /*
  ** Make sure the HTML filters that are part of the node module
  ** are run first.
  */

  if (in_array("node", $modules)) {
    $text = module_invoke("node", "filter", $text);
  }

  foreach ($modules as $name) {
    if (module_hook($name, "filter") && $name != "node") {
      $text = module_invoke($name, "filter", $text);
    }
  }

  return $text;
}

function rewrite_old_urls($text) {

  global $base_url;

  $end = substr($base_url, 12);

  /*
  ** This is a *temporary* filter to rewrite old-style URLs to new-style
  ** URLs (clean URLs).  Currently, URLs are being rewritten dynamically
  ** (ie. "on output"), however when these rewrite rules have been tested
  ** enough, we will use them to permanently rewrite the links in node
  ** and comment bodies.
  */

  if (variable_get("clean_url", "0") == "0") {
    /*
    ** Relative URLs:
    */

    // rewrite 'node.php?id=<number>[&cid=<number>]' style URLs:
    $text = eregi_replace("\"(node)\.php\?id=([[:digit:]]+)(&cid=)?([[:digit:]]*)", "\"?q=\\1/view/\\2/\\4", $text);

    // rewrite 'module.php?mod=<name>{&<op>=<value>}' style URLs:
    $text = ereg_replace("\"module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "\"?q=\\2/\\4/\\6" , $text);
    $text = ereg_replace("\"module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "\"?q=\\2/\\4", $text);
    $text = ereg_replace("\"module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))", "\"?q=\\2", $text);

    /*
    ** Absolute URLs:
    */

    // rewrite 'node.php?id=<number>[&cid=<number>]' style URLs:
    $text = eregi_replace("$end/(node)\.php\?id=([[:digit:]]+)(&cid=)?([[:digit:]]*)", "$end/?q=\\1/view/\\2/\\4", $text);

    // rewrite 'module.php?mod=<name>{&<op>=<value>}' style URLs:
    $text = ereg_replace("$end/module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "$end/?q=\\2/\\4/\\6" , $text);
    $text = ereg_replace("$end/module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "$end/?q=\\2/\\4", $text);
    $text = ereg_replace("$end/module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))", "\"$end/?q=\\2", $text);
  }
  else {
    /*
    ** Relative URLs:
    */

    // rewrite 'node.php?id=<number>[&cid=<number>]' style URLs:
    $text = eregi_replace("\"(node)\.php\?id=([[:digit:]]+)(&cid=)?([[:digit:]]*)", "\"\\1/view/\\2/\\4", $text);

    // rewrite 'module.php?mod=<name>{&<op>=<value>}' style URLs:
    $text = ereg_replace("\"module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "\"\\2/\\4/\\6", $text);
    $text = ereg_replace("\"module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "\"\\2/\\4", $text);
    $text = ereg_replace("\"module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))", "\"\\2", $text);

    /*
    ** Absolute URLs:
    */

    // rewrite 'node.php?id=<number>[&cid=<number>]' style URLs:
    $text = eregi_replace("$end/(node)\.php\?id=([[:digit:]]+)(&cid=)?([[:digit:]]*)", "$end/\\1/view/\\2/\\4", $text);

    // rewrite 'module.php?mod=<name>{&<op>=<value>}' style URLs:
    $text = ereg_replace("$end/module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "$end/\\2/\\4/\\6", $text);
    $text = ereg_replace("$end/module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))(&?[[:alpha:]]+=([[:alnum:]]+))", "$end/\\2/\\4", $text);
    $text = ereg_replace("$end/module\.php\?(&?[[:alpha:]]+=([[:alnum:]]+))", "$end/\\2", $text);
}

  return $text;
}

function check_output($text) {
  if (isset($text)) {
    // filter content on output:
    $text = filter($text);

    // get the line breaks right:
    if (strip_tags($text, "<a><i><b><u><tt><code><cite><strong><img>") == $text) {
      $text = nl2br($text);
    }
  }
  else {
    $text = message_na();
  }

  return $text;
}


function check_file($filename) {
  return is_uploaded_file($filename);
}

function format_rss_channel($title, $link, $description, $items, $language = "en", $args = array()) {
  // arbitrary elements may be added using the $args associative array

  $output .= "<channel>\n";
  $output .= " <title>". drupal_specialchars(strip_tags($title)) ."</title>\n";
  $output .= " <link>". drupal_specialchars(strip_tags($link)) ."</link>\n";
  $output .= " <description>". drupal_specialchars($description) ."</description>\n";
  $output .= " <language>". drupal_specialchars(strip_tags($language)) ."</language>\n";
  foreach ($args as $key => $value) {
    $output .= " <$key>". drupal_specialchars(strip_tags($value)) ."</$key>\n";
  }
  $output .= $items;
  $output .= "</channel>\n";

  return $output;
}

function format_rss_item($title, $link, $description, $args = array()) {
  // arbitrary elements may be added using the $args associative array

  $output .= "<item>\n";
  $output .= " <title>". drupal_specialchars(strip_tags($title)) ."</title>\n";
  $output .= " <link>". drupal_specialchars(strip_tags($link)) ."</link>\n";
  $output .= " <description>". drupal_specialchars(check_output($description)) ."</description>\n";
  foreach ($args as $key => $value) {
    $output .= "<$key>". drupal_specialchars(strip_tags($value)) ."</$key>";
  }
  $output .= "</item>\n";

  return $output;
}

/**
 * Formats a string with a count of items so that the string is pluralized
 * correctly.
 * format_plural calls t() by itself, make sure not to pass already localized
 * strings to it.
 *
 * @param $count    The item count to display.
 * @param $singular The string for the singular case. Please make sure it's clear
 *                  this is singular, to ease translation. ("1 new comment" instead of
 *                  "1 new").
 * @param $plural   The string for the plrual case. Please make sure it's clear
 *                  this is plural, to ease translation. Use %count in places of the
 *                  item count, as in "%count new comments".
 */
function format_plural($count, $singular, $plural) {
  return t($count == 1 ? $singular : $plural, array("%count" => $count));
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
  $cache = db_fetch_object(db_query("SELECT data, created FROM {cache} WHERE cid = '%s'", $key));
  return $cache->data ? $cache : 0;
}

function cache_set($cid, $data, $expire = 0) {
  db_query("UPDATE {cache} SET data = '%s', created = %d, expire = %d WHERE cid = '%s'", $data, time(), $expire, $cid);
  if (!db_affected_rows()) {
    db_query("INSERT INTO {cache} (cid, data, created, expire) VALUES('%s', '%s', %d, %d)", $cid, $data, time(), $expire);
  }
}

function cache_clear_all($cid = NULL) {
  if (empty($cid)) {
    db_query("DELETE FROM {cache} WHERE expire <> 0");
  }
  else {
    db_query("DELETE FROM {cache} WHERE cid = '%s'", $cid);
  }
}

function page_set_cache() {
  global $user;

  if (!$user->uid && $_SERVER["REQUEST_METHOD"] == "GET") {
    if ($data = ob_get_contents()) {
      cache_set(request_uri(), $data, 1);
    }
  }
}

function page_get_cache() {
  global $user;

  $cache = NULL;

  if (!$user->uid && $_SERVER["REQUEST_METHOD"] == "GET") {
    $cache = cache_get(request_uri());

    if (empty($cache)) {
      ob_start();
    }
  }

  return $cache;
}

function format_interval($timestamp) {
  $units = array("1 year|%count years" => 31536000, "1 week|%count weeks" => 604800, "1 day|%count days" => 86400, "1 hour|%count hours" => 3600, "1 min|%count min" => 60, "1 sec|%count sec" => 1);
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
      $format = variable_get("date_format_short", "m/d/Y - H:i");
      break;
    case "large":
      $format = variable_get("date_format_long", "l, F j, Y - H:i");
      break;
    case "custom":
      // No change to format
      break;
    case "medium":
    default:
      $format = variable_get("date_format_medium", "D, m/d/Y - H:i");
  }

  for ($i = strlen($format); $i >= 0; $c = $format[--$i]) {
    if (strstr("DFlMSw", $c)) {
      $date = t(date($c, $timestamp)) . $date;
    }
    else if (strstr("AaBdgGhHiIjLmnOrstTUWYyZz", $c)) {
      $date = date($c, $timestamp) . $date;
    }
    else {
      $date = $c.$date;
    }
  }
  return $date;
}

function format_name($object) {

  if ($object->uid && $object->name) {
    /*
    ** Shorten the name when it is too long or it will break many
    ** tables.
    */

    if (strlen($object->name) > 20) {
      $name = substr($object->name, 0, 15) ."...";
    }
    else {
      $name = $object->name;
    }

    if (arg(0) == "admin") {
      $output = l($name, "admin/user/edit/$object->uid", array("title" => t("Administer user profile.")));
    }
    else {
      $output = l($name, "user/view/$object->uid", array("title" => t("View user profile.")));
    }
  }
  else if ($object->name) {
    /*
    ** Sometimes modules display content composed by people who are
    ** not registers members of the site (i.e. mailing list or news
    ** aggregator modules).  This clause enables modules to display
    ** the true author of the content.
    */

    $output = $object->name;
  }
  else {
    $output = t(variable_get("anonymous", "Anonymous"));
  }

  return $output;
}

function form($form, $method = "post", $action = 0, $options = 0) {

  if (!$action) {
    $action = request_uri();
  }
  return "<form action=\"$action\" method=\"$method\"". drupal_attributes($options) .">\n$form\n</form>\n";
}

function form_item($title, $value, $description = 0) {
  return "<div class=\"form-item\">". ($title ? "<div class=\"title\">$title:</div>" : "") . $value . ($description ? "<div class=\"description\">$description</div>" : "") ."</div>\n";
}

function form_radio($title, $name, $value = 1, $checked = 0, $description = 0, $attributes = 0) {
  return form_item(0, "<input type=\"radio\" class=\"form-radio\" name=\"edit[$name]\" value=\"". $value ."\"". ($checked ? " checked=\"checked\"" : "") . drupal_attributes($attributes) ." /> $title", $description);
}

function form_checkbox($title, $name, $value = 1, $checked = 0, $description = 0, $attributes = 0) {
  return form_hidden($name, 0) . form_item(0, "<input type=\"checkbox\" class=\"form-checkbox\" name=\"edit[$name]\" value=\"". $value ."\"". ($checked ? " checked=\"checked\"" : "") . drupal_attributes($attributes) ." /> $title", $description);
}

function form_textfield($title, $name, $value, $size, $maxlength, $description = 0, $attributes = 0) {
  $size = $size ? " size=\"$size\"" : "";
  return form_item($title, "<input type=\"text\" maxlength=\"$maxlength\" class=\"form-text\" name=\"edit[$name]\"$size value=\"". check_form($value) ."\"". drupal_attributes($attributes) ." />", $description);
}

function form_password($title, $name, $value, $size, $maxlength, $description = 0, $attributes = 0) {
  $size = $size ? " size=\"$size\"" : "";
  return form_item($title, "<input type=\"password\" class=\"form-password\" maxlength=\"$maxlength\" name=\"edit[$name]\"$size value=\"". check_form($value) ."\"". drupal_attributes($attributes) ." />", $description);
}

function form_textarea($title, $name, $value, $cols, $rows, $description = 0, $attributes = 0) {
  $cols = $cols ? " cols=\"$cols\"" : "";
  module_invoke_all("textarea", $name);  // eg. optionally plug in a WYSIWYG editor
  return form_item($title, "<textarea wrap=\"virtual\"$cols rows=\"$rows\" name=\"edit[$name]\" id=\"edit[$name]\"". drupal_attributes($attributes) .">". check_form($value) ."</textarea>", $description);
}

function form_select($title, $name, $value, $options, $description = 0, $extra = 0, $multiple = 0) {
  if (count($options) > 0) {
    foreach ($options as $key=>$choice) {
      $select .= "<option value=\"$key\"". (is_array($value) ? (in_array($key, $value) ? " selected=\"selected\"" : "") : ($value == $key ? " selected=\"selected\"" : "")) .">". check_form($choice) ."</option>";
    }
    return form_item($title, "<select name=\"edit[$name]". ($multiple ? "[]" : "") ."\"". ($multiple ? " multiple " : "") . ($extra ? " $extra" : "") .">$select</select>", $description);
  }
}

function form_radios($title, $name, $value, $options, $description = 0) {
  if (count($options) > 0) {
    foreach ($options as $key=>$choice) {
      $output .= form_radio($choice, $name, $key, ($key == $value));
    }
    return form_item($title, $output, $description);
  }
}

function form_file($title, $name, $size, $description = 0) {
  return form_item($title, "<input type=\"file\" class=\"form-file\" name=\"edit[$name]\" size=\"$size\" />\n", $description);
}

function form_hidden($name, $value) {
  return "<input type=\"hidden\" name=\"edit[$name]\" value=\"". check_form($value) ."\" />\n";
}

function form_submit($value, $name = "op", $attributes = 0) {
  return "<input type=\"submit\" class=\"form-submit\" name=\"$name\" value=\"". check_form($value) ."\" />\n";
}

function form_weight($title = NULL, $name = "weight", $value = 0, $delta = 10, $description = 0, $extra = 0) {
  for ($n = (-1 * $delta); $n <= $delta; $n++) {
    $weights[$n] = $n;
  }

  return form_select($title, $name, $value, $weights, $description, $extra);
}

function form_allowed_tags_text() {
  return variable_get("allowed_html", "") ? (t("Allowed HTML tags") .": ". htmlspecialchars(variable_get("allowed_html", ""))) : "";
}

/**
 * Given an old url, return the alias.
 */
function drupal_get_path_alias($path) {
  $map = drupal_get_path_map();

  if ($map) {
    return array_search($path, $map);
  }
}

/**
 * Given an alias, return the default url.
 */
function drupal_get_normal_path($path) {
  $map = drupal_get_path_map();
  return $map[$path];
}

function url($url = NULL, $query = NULL) {
  global $base_url;

  static $script;

  if (empty($script)) {
    /*
    ** On some webservers such as IIS we can't omit "index.php".  As such we
    ** generate "index.php?q=foo" instead of "?q=foo" on anything that is not
    ** Apache.
    */
    $script = (strpos($_SERVER["SERVER_SOFTWARE"], "Apache") === false) ? "index.php" : "";
  }

  if ($alias = drupal_get_path_alias($url)) {
    $url = $alias;
  }

  if (variable_get("clean_url", "0") == "0") {
    if (isset($url)) {
      if (isset($query)) {
        return "$base_url/$script?q=$url&amp;$query";
      }
      else {
        return "$base_url/$script?q=$url";
      }
    }
    else {
      if (isset($query)) {
        return "$base_url/$script?$query";
      }
      else {
        return "$base_url/";
      }
    }
  }
  else {
    if (isset($url)) {
      if (isset($query)) {
        return "$base_url/$url?$query";
      }
      else {
        return "$base_url/$url";
      }
    }
    else {
      if (isset($query)) {
        return "$base_url/$script?$query";
      }
      else {
        return "$base_url/";
      }
    }
  }
}

function drupal_attributes($attributes = 0) {
  if (is_array($attributes)) {
    $t = array();
    foreach ($attributes as $key => $value) {
      $t[] = "$key=\"$value\"";
    }
    return " ". implode($t, " ");
  }
}

function l($text, $url, $attributes = array(), $query = NULL) {
  return "<a href=\"". url($url, $query) ."\"". drupal_attributes($attributes) .">$text</a>";
}

function field_get($string, $name) {
  ereg(",?$name=([^,]+)", ", $string", $regs);
  return $regs[1];
}

function field_set($string, $name, $value) {
  $rval = ereg_replace(",$name=[^,]+", "", ",$string");
  if (isset($value)) {
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
    $links = module_invoke_all("link", "page");
    array_unshift($links, l(t("home"), "", array("title" => t("Return to the main page."))));
    return $links;
  }
}

function link_node($node, $main = 0) {
  return module_invoke_all("link", "node", $node, $main);
}

function timer_start() {
  global $timer;
  list($usec, $sec) = explode(" ", microtime());
  $timer = (float)$usec + (float)$sec;
}

function drupal_page_header() {

  if (variable_get("dev_timer", 0)) {
    timer_start();
  }

  if (variable_get("cache", 0)) {
    if ($cache = page_get_cache()) {

      // Set default values:
      $date = gmdate("D, d M Y H:i:s", $cache->created) ." GMT";
      $etag = '"'. md5($date) .'"';

      // Check http headers:
      $modified_since = isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) ? $_SERVER["HTTP_IF_MODIFIED_SINCE"] == $date : NULL;
      $none_match = isset($_SERVER["HTTP_IF_NONE_MATCH"]) ? $_SERVER["HTTP_IF_NONE_MATCH"] == $etag : NULL;

      // The type checking here is very important, be careful when changing entries.
      if (($modified_since !== NULL || $none_match !== NULL) && $modified_since !== false && $none_match !== false) {
        header("HTTP/1.0 304 Not Modified");
        exit();
      }

      // Send appropriate response:
      header("Last-Modified: $date");
      header("ETag: $etag");
      print $cache->data;

      /*
      ** A hook for modules where modules may take action at the end of a
      ** request good uses include setting a cache, page logging, etc.
      */

      module_invoke_all("exit");

      exit();
    }
  }

  /*
  ** Putting the check here avoids SQL query overhead in case we are
  ** serving cached pages.  The downside, however, is that the init
  ** hooks might use unchecked data.
  */

  if (!user_access("bypass input data check")) {
    if (!valid_input_data($_REQUEST)) {
      die("terminated request because of suspicious input data");
    }
  }
}

function drupal_page_footer() {
  if (variable_get("cache", 0)) {
    page_set_cache();
  }

  /*
  ** A hook for modules where modules may take action at the end of a
  ** request good uses include setting a cache, page logging, etc.
  */

  module_invoke_all("exit");
}

unset($conf);

$config = conf_init();

include_once "includes/$config.php";
include_once "includes/database.inc";
include_once "includes/module.inc";
include_once "includes/theme.inc";
include_once "includes/pager.inc";
include_once "includes/menu.inc";
include_once "includes/xmlrpc.inc";
include_once "includes/tablesort.inc";

// initialize configuration variables, using values from conf.php if available:
$conf = variable_init(isset($conf) ? $conf : array());

// set error handler:
set_error_handler("error_handler");

// spit out the correct charset http header
header("Content-Type: text/html; charset=utf-8");

// initialize the _GET["q"] prior to loading the modules and invoking their 'init' hook:
if (!empty($_GET["q"])) {
  if ($path = drupal_get_normal_path(trim($_GET["q"], "/"))) {
    $_GET["q"] = $path;
  }
}
else {
  if ($path = drupal_get_normal_path(variable_get("site_frontpage", "node"))) {
    $_GET["q"] = $path;
  }
  else {
    $_GET["q"] = variable_get("site_frontpage", "node");
  }
}

// initialize installed modules:
module_init();

// initialize localization system:
$locale = locale_init();

// initialize theme:
$theme = theme_init();

?>
