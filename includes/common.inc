<?php
/* $Id$ */

/**
 * @name drupal_title
 *
 * Functions to get and set the title of the current page.
 * @{
 */
function drupal_set_title($title = NULL) {
  static $stored_title;

  if (isset($title)) {
    $stored_title = ucfirst($title);
  }
  return $stored_title;
}

function drupal_get_title() {
  $title = drupal_set_title();

  if (!isset($title)) {
    $title = menu_get_active_title();
  }

  return $title;
}
// @}

/**
 * @name drupal_message
 *
 * Functions to get and set the message of the current page.
 * @{
 */
function drupal_set_message($message = NULL, $type = "status") {
  static $stored_message;

  if (isset($message)) {
    $stored_message->message = $message;
    $stored_message->type = $type;
  }

  return $stored_message;
}

function drupal_get_message() {
  return drupal_set_message();
}
// @}

/**
 * @name drupal_breadcrumb
 *
 * Functions to get and set the breadcrumb trail of the current page.
 *
 * @param $breadcrumb array of links, starting with "home" and proceeding up
 *   to but not including the current page.
 * @{
 */
function drupal_set_breadcrumb($breadcrumb = NULL) {
  static $stored_breadcrumb;

  if (isset($breadcrumb)) {
    $stored_breadcrumb = $breadcrumb;
  }
  return $stored_breadcrumb;
}

function drupal_get_breadcrumb() {
  $breadcrumb = drupal_set_breadcrumb();

  if (!isset($breadcrumb)) {
    $breadcrumb = menu_get_active_breadcrumb();
    array_pop($breadcrumb);
  }

  return $breadcrumb;
}
// @}

/**
 * Build the alias/path array
 */
function drupal_get_path_map($action = "") {
  static $map = NULL;

  if ($action == "rebuild") {
    $map = NULL;
  }

  if (is_null($map)) {
    $result = db_query("SELECT * FROM {url_alias}");
    while ($data = db_fetch_object($result)) {
      $map[$data->dst] = $data->src;
    }
  }

  return $map;
}

function drupal_rebuild_path_map() {
  drupal_get_path_map("rebuild");
}

function drupal_not_found() {
  header("HTTP/1.0 404 Not Found");
  watchdog("httpd", "404 error: '". check_query($_GET["q"]) ."' not found");

  $path = drupal_get_normal_path(variable_get('site_404', ''));

  if ($path) {
    menu_set_active_item($path);
  }

  if ($path && menu_active_handler_exists()) {
    menu_execute_active_handler();
  }
  else {
    print theme("page", '<h1>'. t('Page not found') .'</h1>');
  }
}

function error_handler($errno, $message, $filename, $line, $variables) {
  $types = array(1 => "error", 2 => "warning", 4 => "parse error", 8 => "notice", 16 => "core error", 32 => "core warning", 64 => "compile error", 128 => "compile warning", 256 => "user error", 512 => "user warning", 1024 => "user notice");
  $entry = $types[$errno] .": $message in $filename on line $line.";

  if ($errno & E_ALL ^ E_NOTICE) {
    watchdog("error", $types[$errno] .": $message in $filename on line $line.");
    if (error_reporting()) {
      print "<pre>$entry</pre>";
    }
  }
}

function throttle($type, $rate) {
  if (!user_access("access administration pages")) {
    if ($throttle = db_fetch_object(db_query("SELECT * FROM {watchdog} WHERE type = '%s' AND hostname = '%s' AND %d - timestamp < %d", $type, $_SERVER['REMOTE_ADDR'], time(), $rate))) {
      watchdog("warning", "throttle: '". $_SERVER['REMOTE_ADDR'] ."' exceeded submission rate - $throttle->type");
      die(message_throttle());
    }
  }
}

function _fix_gpc_magic(&$item, $key) {
  if (is_array($item)) {
    array_walk($item, '_fix_gpc_magic');
  }
  else {
    $item = stripslashes($item);
  }
}

function fix_gpc_magic() {
  static $fixed = false;
  if (!$fixed && ini_get("magic_quotes_gpc")) {
    array_walk($_GET, '_fix_gpc_magic');
    array_walk($_POST, '_fix_gpc_magic');
    array_walk($_COOKIE, '_fix_gpc_magic');
    array_walk($_REQUEST, '_fix_gpc_magic');
    $fixed = true;
  }
}

function array2object($array) {
  if (is_array($array)) {
    foreach ($array as $key => $value) {
      $object->$key = $value;
    }
  }
  else {
    $object = $array;
  }

  return $object;
}

function object2array($object) {
  if (is_object($object)) {
    foreach ($object as $key => $value) {
      $array[$key] = $value;
    }
  }
  else {
    $array = $object;
  }

  return $array;
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

function drupal_specialchars($input, $quotes = ENT_NOQUOTES) {

  /*
  ** Note that we'd like to go 'htmlspecialchars($input, $quotes, "utf-8")'
  ** like the PHP manual tells us to, but we can't because there's a bug in
  ** PHP <4.3 that makes it mess up multibyte charsets if we specify the
  ** charset.  Change this later once we make PHP 4.3 a requirement.
  */

  return htmlspecialchars($input, $quotes);
}

/**
 * Verify the syntax of the given e-mail address. Empty e-mail addresses are
 * allowed. See RFC 2822 for details.
 *
 * @param $mail a email address
 */
function valid_email_address($mail) {
  $user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
  $domain = '(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]\.?)+';
  $ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
  $ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

  return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
}

/**
 * Verify the syntax of the given URL.
 *
 * @param $url an URL
 */
function valid_url($url) {
  return preg_match("/^[a-zA-z0-9\/:_\-_\.,]+$/", $url);
}

/**
 * Format a single result entry of a search query:
 *
 * @param $item a single search result as returned by <i>module</i>_search of
 *   type array("count" => ..., "link" => ..., "title" => ..., "user" => ...,
 *   "date" => ..., "keywords" => ...)
 * @param $type module type of this item
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
    $output = " <b><u><a href=\"". $item["link"] ."\">". $item["title"] ."</a></u></b><br />";
    $output .= " <small>" . t($type) . ($item["user"] ? " - ". $item["user"] : "") ."". ($item["date"] ? " - ". format_date($item["date"], "small") : "") ."</small>";
    $output .= "<br /><br />";
  }

  return $output;
}

/**
 * Render a generic search form.
 *
 * "Generic" means "universal usable" - that is, usable not only from
 * 'site.com/search', but also as a simple seach box (without "Restrict search
 * to", help text, etc) from theme's header etc. This means: provide options to
 * only conditionally render certain parts of this form.
 *
 * @param $action Form action. Defaults to 'site.com/search'.
 * @param $keys string containing keywords for the search.
 * @param $options != 0: Render additional form fields/text ("Restrict search
 *   to", help text, etc).
 */
function search_form($action = NULL, $keys = NULL, $options = NULL) {
  if (!$action) {
    $action = url("search");
  }

  $output = " <br /><input type=\"text\" class=\"form-text\" size=\"50\" value=\"". check_form($keys) ."\" name=\"keys\" />";
  $output .= " <input type=\"submit\" class=\"form-submit\" value=\"". t("Search") ."\" />\n";

  if ($options) {
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
 * @param $type If set, search only nodes of this type. Otherwise, search all
 *   types.
 * @param $action Form action. Defaults to 'site.com/search'.
 * @param $keys Query string. Defaults to global $keys.
 * @param $options != 0: Render additional form fields/text ("Restrict search
 *   to", help text, etc).
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

function valid_input_data($data) {
  if (is_array($data) || is_object($data)) {
    /*
    ** Form data can contain a number of nested arrays.
    */

    foreach ($data as $key => $value) {
      if (!valid_input_data($value)) {
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

function check_form($text) {
  return drupal_specialchars($text, ENT_QUOTES);
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

    /*
    ** If only inline elements are used and no block level elements, we
    ** replace all newlines with HTML line breaks.
    */
    if (strip_tags($text, '<a><br><span><bdo><map><object><img><tt><i><b><big><small><em><strong><dfn><code><q><samp><kbd><var><cite><abbr><acronym><sub><sup><input><select><textarea><label><button><ins><del><script>') == $text) {
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

  $output = "<channel>\n";
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

  $output = "<item>\n";
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
 * correctly. format_plural calls t() by itself, make sure not to pass already
 * localized strings to it.
 *
 * @param $count The item count to display.
 * @param $singular The string for the singular case. Please make sure it's
 *   clear this is singular, to ease translation. ("1 new comment" instead of "1
 *   new").
 * @param $plural The string for the plrual case. Please make sure it's clear
 *   this is plural, to ease translation. Use %count in places of the item
 *   count, as in "%count new comments".
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

function format_interval($timestamp) {
  $units = array("1 year|%count years" => 31536000, "1 week|%count weeks" => 604800, "1 day|%count days" => 86400, "1 hour|%count hours" => 3600, "1 min|%count min" => 60, "1 sec|%count sec" => 1);
  foreach ($units as $key => $value) {
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

function form_item($title, $value, $description = NULL, $id = NULL) {
  return theme("form_element", $title, $value, $description, $id);
}

function form_group($legend, $group, $description = NULL) {
  return "<fieldset>" . ($legend ? "<legend>$legend</legend>" : "") . $group . ($description ? "<div class=\"description\">$description</div>" : "") . "</fieldset>\n";
}

function form_radio($title, $name, $value = 1, $checked = 0, $description = NULL, $attributes = NULL) {
  return theme("form_element", 0, "<input type=\"radio\" class=\"form-radio\" name=\"edit[$name]\" id=\"$name\" value=\"$value\"". ($checked ? " checked=\"checked\"" : "") . drupal_attributes($attributes) ." /> $title", $description, $name);
}

function form_radios($title, $name, $value, $options, $description = NULL) {
  if (count($options) > 0) {
    foreach ($options as $key => $choice) {
      $choices .= "<input type=\"radio\" class=\"form-radio\" name=\"edit[$name]\" value=\"$key\"". ($key == $value ? " checked=\"checked\"" : "") ." /> $choice<br />";
    }
    return theme("form_element", $title, $choices, $description, $name);
  }
}

function form_checkbox($title, $name, $value = 1, $checked = 0, $description = NULL, $attributes = NULL) {
  return form_hidden($name, 0) . theme("form_element", NULL, "<input type=\"checkbox\" class=\"form-checkbox\" name=\"edit[$name]\" id=\"$name\" value=\"". $value ."\"". ($checked ? " checked=\"checked\"" : "") . drupal_attributes($attributes) ." /> $title", $description, $name);
}

function form_textfield($title, $name, $value, $size, $maxlength, $description = NULL, $attributes = NULL) {
  $size = $size ? " size=\"$size\"" : "";
  return theme("form_element", $title, "<input type=\"text\" maxlength=\"$maxlength\" class=\"form-text\" name=\"edit[$name]\" id=\"$name\"$size value=\"". check_form($value) ."\"". drupal_attributes($attributes) ." />", $description, $name);
}

function form_password($title, $name, $value, $size, $maxlength, $description = NULL, $attributes = NULL) {
  $size = $size ? " size=\"$size\"" : "";
  return theme("form_element", $title, "<input type=\"password\" class=\"form-password\" maxlength=\"$maxlength\" name=\"edit[$name]\" id=\"$name\"$size value=\"". check_form($value) ."\"". drupal_attributes($attributes) ." />", $description, $name);
}

function form_textarea($title, $name, $value, $cols, $rows, $description = NULL, $attributes = NULL) {
  $cols = $cols ? " cols=\"$cols\"" : "";
  module_invoke_all("textarea", $name);  // eg. optionally plug in a WYSIWYG editor
  return theme("form_element", $title, "<textarea wrap=\"virtual\"$cols rows=\"$rows\" name=\"edit[$name]\" id=\"$name\"". drupal_attributes($attributes) .">". check_form($value) ."</textarea>", $description, $name);
}

function form_select($title, $name, $value, $options, $description = NULL, $extra = 0, $multiple = 0) {
  foreach ($options as $key => $choice) {
    $select .= "<option value=\"$key\"". (is_array($value) ? (in_array($key, $value) ? " selected=\"selected\"" : "") : ($value == $key ? " selected=\"selected\"" : "")) .">". check_form($choice) ."</option>";
  }
  return theme("form_element", $title, "<select name=\"edit[$name]". ($multiple ? "[]" : "") ."\"". ($multiple ? " multiple " : "") . ($extra ? " $extra" : "") ." id=\"$name\">$select</select>", $description, $name);
}

function form_file($title, $name, $size, $description = NULL) {
  return theme("form_element", $title, "<input type=\"file\" class=\"form-file\" name=\"edit[$name]\" id=\"$name\" size=\"$size\" />\n", $description, $name);
}

function form_hidden($name, $value) {
  return "<input type=\"hidden\" name=\"edit[$name]\" value=\"". check_form($value) ."\" />\n";
}

function form_button($value, $name = "op", $type = "submit", $attributes = NULL) {
  return "<input type=\"$type\" class=\"form-$type\" name=\"$name\" value=\"". check_form($value) ."\" ". drupal_attributes($attributes) ." />\n";
}

function form_submit($value, $name = "op", $attributes = NULL) {
  return form_button($value, $name, "submit", $attributes);
}

function form_weight($title = NULL, $name = "weight", $value = 0, $delta = 10, $description = NULL, $extra = 0) {
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

  return $map[$path] ? $map[$path] : $path;
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

function drupal_attributes($attributes = NULL) {
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

include_once "includes/theme.inc";
include_once "includes/pager.inc";
include_once "includes/menu.inc";
include_once "includes/xmlrpc.inc";
include_once "includes/tablesort.inc";
include_once "includes/file.inc";

// set error handler:
set_error_handler("error_handler");

// spit out the correct charset http header
header("Content-Type: text/html; charset=utf-8");

// initialize the _GET["q"] prior to loading the modules and invoking their 'init' hook:
if (!empty($_GET["q"])) {
  $_GET["q"] = drupal_get_normal_path(trim($_GET["q"], "/"));
}
else {
  $_GET["q"] = drupal_get_normal_path(variable_get("site_frontpage", "node"));
}

// initialize installed modules:
module_init();

if ($_REQUEST && !user_access("bypass input data check")) {
  if (!valid_input_data($_REQUEST)) {
    die("terminated request because of suspicious input data");
  }
}

// initialize localization system:
$locale = locale_init();

// initialize theme:
$theme = init_theme();

?>
