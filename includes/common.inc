<?php

/**
 * @defgroup common Core functions
 */

/**
 * @name Page title
 * @ingroup common
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
/* @} */

/**
 * @name Page messages
 * @ingroup common
 *
 * Functions to get and set the message of the current page.
 * @{
 */
function drupal_set_message($message = NULL, $type = "status") {
  static $stored_message = array();

  if (isset($message)) {
    $stored_message[] = array($message, $type);
  }

  return $stored_message;
}

function drupal_get_messages() {
  return drupal_set_message();
}
/* @} */

/**
 * @name Page breadcrumbs
 * @ingroup common
 *
 * Functions to get and set the breadcrumb trail of the current page.
 * @{
 */

/**
 * @param $breadcrumb Array of links, starting with "home" and proceeding up
 *   to but not including the current page.
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
/* @} */

/**
 * @name HTML head contents
 * @ingroup common
 *
 * Set and get output that should be in the \<head\> tag.
 * @{
 */

function drupal_set_html_head($data = NULL) {
  static $stored_head;

  if (!is_null($data)) {
    $stored_head .= "$data\n";
  }
  return $stored_head;
}

function drupal_get_html_head() {
  global $base_url;

  $output = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
  $output .= "<base href=\"$base_url/\" />\n";
  $output .= "<style type=\"text/css\" media=\"all\">\n";
  $output .= "@import url(misc/drupal.css);\n";
  $output .= "</style>\n";

  return $output . drupal_set_html_head();
}
/* @} */

/**
 * @name URL path alias
 * @ingroup common
 *
 * Functions to handle path aliases.
 */
function drupal_get_path_map($action = "") {
  static $map = NULL;

  if ($action == "rebuild") {
    $map = NULL;
  }

  if (is_null($map)) {
    $map = array();  // make $map non-null in case no aliases are defined
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

/**
 * Given an old url, return the alias.
 */
function drupal_get_path_alias($path) {
  if (($map = drupal_get_path_map()) && ($newpath = array_search($path, $map))) {
    return $newpath;
  }
  elseif (function_exists("conf_url_rewrite")) {
    return conf_url_rewrite($path, 'outgoing');
  }
}

/**
 * Given an alias, return the default url.
 */
function drupal_get_normal_path($path) {
  if (($map = drupal_get_path_map()) && isset($map[$path])) {
    return $map[$path];
  }
  elseif (function_exists("conf_url_rewrite")) {
    return conf_url_rewrite($path, 'incoming');
  }
  else {
    return $path;
  }
}
/* @} */

/**
 * @name HTTP headers
 * @ingroup common
 *
 * Functions to get and set the HTTP headers of the current page.
 * @{
 */
function drupal_set_header($header = NULL) {
  static $stored_headers;

  if (!is_null($header)) {
    header($header);
    $stored_headers .= "$header\n";
  }
  return $stored_headers;
}

function drupal_get_headers() {
  return drupal_set_header();
}
/* @} */

/**
 * @name HTTP handling
 * @ingroup common
 *
 * Functions to properly handle HTTP responses.
 * @{
 */

/**
 * HTTP redirects. Makes sure the redirected url is formatted correctly and
 * includes the session ID.
 *
 * @note This function ends the request.
 *
 * @param $url A Drupal URL
 * @param $query Query string component
 * @param $fragment Fragment identifier
 */
function drupal_goto($url = NULL, $query = NULL, $fragment = NULL) {

  /*
  ** Translate &amp; to simply & in the absolute URL
  */

  $url = str_replace("&amp;", "&", url($url, $query, $fragment, TRUE));

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

/**
 * Generates a 404 error if the request can not be handled.
 */
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
    print theme('page', '', t('Page not found'));
  }
}

/**
 * Flexible and powerful HTTP client implementation. Allows to GET, POST, PUT
 * or any other HTTP requests. Handles redirects.
 *
 * @param $url A string containing a fully qualified URI.
 * @param $headers An array containing a HTTP header => value pair.
 * @param $method A string defining the HTTP request to use.
 * @param $data A string containing data to include in the request.
 * @param $retry An integer representing how many times to retry the request
 *   in case of a redirect.
 * @return An object containing the HTTP request headers, response code,
 *   headers, data, and redirect status.
 */
function drupal_http_request($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3) {
  // Parse the URL, and make sure we can handle the schema
  $uri = parse_url($url);
  switch ($uri['scheme']) {
    case 'http':
      $fp = @fsockopen($uri['host'], ($uri['port'] ? $uri['port'] : 80), $errno, $errstr, 15);
      break;
    case 'https':
      // Note: only works for PHP 4.3 compiled with openssl
      $fp = @fsockopen("ssl://$uri[host]", ($uri['port'] ? $uri['port'] : 443), $errno, $errstr, 20);
      break;
    default:
      $result->error = "invalid schema $uri[scheme]";
      return $result;
  }

  // Make sure the socket opened properly
  if (!$fp) {
    $result->error = trim("$errno $errstr");
    return $result;
  }

  // Construct the path to act on
  $path = $uri['path'] ? $uri['path'] : '/';
  if ($uri['query']) {
    $path .= "?$uri[query]";
  }

  // Create http request
  $defaults = array(
    'Host' => "Host: $uri[host]",
    'User-Agent' => 'User-Agent: Drupal (+http://www.drupal.org/)',
    'Content-Length' => 'Content-Length: '. strlen($data)
  );

  foreach ($headers as $header => $value) {
    $defaults[$header] = "$header: $value";
  }

  $request = "$method $path HTTP/1.0\r\n";
  $request .= implode("\r\n", $defaults);
  $request .= "\r\n\r\n";
  if ($data) {
    $request .= "$data\r\n";
  }
  $result->request = $request;

  fwrite($fp, $request);

  // Fetch response.
  $response = '';
  while (!feof($fp) && $data = fread($fp, 1024)) {
    $response .= $data;
  }
  fclose($fp);

  // Parse response.
  list($headers, $result->data) = explode("\r\n\r\n", $response, 2);
  $headers = preg_split("/\r\n|\n|\r/", $headers);

  list($protocol, $code, $text) = explode(' ', trim(array_shift($headers)), 3);
  $result->headers = array();

  // Parse headers.
  while ($line = trim(array_shift($headers))) {
    list($header, $value) = explode(':', $line, 2);
    $result->headers[$header] = trim($value);
  }

  $responses = array(
    100 => 'Continue', 101 => 'Switching Protocols',
    200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content',
    300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect',
    400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed',
    500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported'
  );
  // RFC 2616 states that all unknown HTTP codes must be treated the same as
  // the base code in their class:
  if (!isset($responses[$code])) {
    $code = floor($code / 100) * 100;
  }

  switch ($code) {
    case 200: // OK
    case 304: // Not modified
      break;
    case 301: // Moved permanently
    case 302: // Moved temporarily
    case 307: // Moved temporarily
      $location = $result->headers['Location'];

      if ($retry) {
        $result = drupal_http_request($result->headers['Location'], $headers, $method, $data, --$retry);
        $result->redirect_code = $result->code;
      }
      $result->redirect_url = $location;

      break;
    default:
      $result->error = $text;
  }

  $result->code = $code;
  return $result;
}
/* @} */

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

/**
 * @name Conversion
 * @ingroup common
 *
 * Converts data structures to a different type.
 * @{
 */
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
/* @} */

/**
 * @name Messages
 * @ingroup common
 *
 * Frequently used messages.
 * @{
 */
function message_access() {
  return t("You are not authorized to access this page.");
}

function message_na() {
  return t("n/a");
}

/* @} */

function locale_init() {
  global $languages, $user;
  if ($user->uid && $languages[$user->language]) {
    return $user->language;
  }
  else {
    return key($languages);
  }
}

/**
 * @ingroup common
 *
 * Translates strings to the current locale.
 *
 * When using t(), try to put entire sentences and strings in one t() call.
 * This makes it easier for translators. We are unafraid of HTML markup within
 * translation strings if necessary. The suggested syntax for a link embedded
 * within a translation string is for example:
 * @code
 *   $msg = t("You must login below or \<a href=\"%url\"\>create a new
 *             account\</a\> before viewing the next page.", array("%url"
 *             => url("user/register")));
 * @endcode
 * We suggest the same syntax for links to other sites. This makes it easy to
 * change link URLs if needed (which happens often) without requiring updates
 * to translations.
 *
 * @param $string A string containing the english string to translate.
 * @param $args Array of values to replace in the string.
 * @return Translated string.
 */
function t($string, $args = 0) {
  global $languages;

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
 * @name Validation
 * @ingroup common
 *
 * Functions to validate user input.
 * @{
 */

/**
 * Verify the syntax of the given e-mail address. Empty e-mail addresses are
 * allowed. See RFC 2822 for details.
 *
 * @param $mail A string containing an email address.
 * @return
 */
function valid_email_address($mail) {
  $user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
  $domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
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

    // Decode all normal character entities.
    $data = decode_entities($data, array('<', '&', '"'));

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
/* @} */

/**
 * @defgroup search Search interface
 * @{
 */
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
    $output = " <strong><u><a href=\"". $item["link"] ."\">". $item["title"] ."</a></u></strong><br />";
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
  $edit = $_POST['edit'];

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
      if (module_hook($name, "search") && (!$edit["type"] || $edit["type"][$name])) {
        list($title, $results) = module_invoke($name, "search", $keys);
        if ($results) {
          $output .= "<h2>$title</h2>";
          foreach ($results as $entry) {
            $output .= search_item($entry, $name);
          }
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
/* @} */

function check_form($text) {
  return drupal_specialchars($text, ENT_QUOTES);
}

function check_file($filename) {
  return is_uploaded_file($filename);
}

/**
 * @name Formatting
 * @ingroup common
 *
 * Functions to format numbers, strings, dates, etc.
 * @{
 */

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
 * @param $plural The string for the plural case. Please make sure it's clear
 *   this is plural, to ease translation. Use %count in places of the item
 *   count, as in "%count new comments".
 * @return Translated string
 */
function format_plural($count, $singular, $plural) {
  return t($count == 1 ? $singular : $plural, array("%count" => $count));
}

/**
 * Generates a string representation for the given byte count.
 *
 * @param $size The size in bytes
 * @return Translated string representation of the size
 */
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

/**
 * Formats a time interval with the requested granularity.
 *
 * @param $timestamp The length of the interval in seconds
 * @param $granularity How much units to consider when generating the string
 * @return Translated string representation of the interval
 */
function format_interval($timestamp, $granularity = 2) {
  $units = array("1 year|%count years" => 31536000, "1 week|%count weeks" => 604800, "1 day|%count days" => 86400, "1 hour|%count hours" => 3600, "1 min|%count min" => 60, "1 sec|%count sec" => 1);
  foreach ($units as $key => $value) {
    $key = explode("|", $key);
    if ($timestamp >= $value) {
      $output .= ($output ? " " : "") . format_plural(floor($timestamp / $value), $key[0], $key[1]);
      $timestamp %= $value;
      $granularity--;
    }

    if ($granularity == 0) {
      break;
    }
  }
  return ($output) ? $output : t("0 sec");
}

/**
 * Formats a date with the given configured format or a custom format string.
 * Drupal allows administrators to select formatting strings for 'small',
 * 'medium' and 'large' date formats. This function can handle these formats,
 * as well as any custom format.
 *
 * @param $timestamp The exact date to format
 * @param $type Could be 'small', 'medium' or 'large' for the preconfigured
 *              date formats. If 'custom' is specified, the next parameter
 *              should contain the format string
 * @param $format Format string (as required by the PHP date() function).
 *                Only required if 'custom' date format is requested.
 * @param $timezone Timezone offset in seconds in case the user timezone
 *   should not be used.
 * @return Translated date string in the requested format
 */
function format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL) {
  if ($timezone === NULL) {
    global $user;
    $timezone = $user->uid ? $user->timezone : variable_get('date_default_timezone', 0);
  }

  $timestamp += $timezone;

  switch ($type) {
    case 'small':
      $format = variable_get('date_format_short', 'm/d/Y - H:i');
      break;
    case 'large':
      $format = variable_get('date_format_long', 'l, F j, Y - H:i');
      break;
    case 'custom':
      // No change to format
      break;
    case 'medium':
    default:
      $format = variable_get('date_format_medium', 'D, m/d/Y - H:i');
  }

  $max = strlen($format);
  for ($i = 0; $i <= $max; $c = $format{$i++}) {
    if (strpos('AaDFlM', $c)) {
      $date .= t(gmdate($c, $timestamp));
    }
    else if (strpos('BdgGhHiIjLmnsStTUwWYyz', $c)) {
      $date .= gmdate($c, $timestamp);
    }
    else if ($c == 'r') {
      $date .= format_date($timestamp - $timezone, 'custom', 'D, d M Y H:i:s O', $timezone);
    }
    else if ($c == 'O') {
      $date .= sprintf('%s%02d%02d', ($timezone < 0 ? '-' : '+'), abs($timezone / 3600), abs($timezone % 3600) / 60);
    }
    else if ($c == 'Z') {
      $date .= $timezone;
    }
    else {
      $date .= $c;
    }
  }

  return $date;
}

/**
 * Formats a username.
 *
 * @param $object User object
 * @return String containing a HTML link to the user's page if the
 *         passed object suggests that this is a site user. Otherwise
 *         only the user name is returned.
 */
function format_name($object) {

  if ($object->uid && $object->name) {
    /*
    ** Shorten the name when it is too long or it will break many
    ** tables.
    */

    if (strlen($object->name) > 20) {
      $name = truncate_utf8($object->name, 15) ."...";
    }
    else {
      $name = $object->name;
    }

    if (arg(0) == "admin" and user_access("administer users")) {
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
/* @} */

/**
 * @defgroup from Form generation
 * @{
 */
function form($form, $method = "post", $action = NULL, $attributes = NULL) {
  if (!$action) {
    $action = request_uri();
  }
  return "<form action=\"$action\" method=\"$method\"". drupal_attributes($attributes) .">\n$form\n</form>\n";
}

function form_item($title, $value, $description = NULL, $id = NULL) {
  return theme("form_element", $title, $value, $description, $id);
}

function form_group($legend, $group, $description = NULL) {
  return "<fieldset>" . ($legend ? "<legend>$legend</legend>" : "") . $group . ($description ? "<div class=\"description\">$description</div>" : "") . "</fieldset>\n";
}

function form_radio($title, $name, $value = 1, $checked = 0, $description = NULL, $attributes = NULL) {
  $element = "<input type=\"radio\" class=\"form-radio\" name=\"edit[$name]\" value=\"$value\"". ($checked ? " checked=\"checked\"" : "") . drupal_attributes($attributes) .' />';
  if (!is_null($title)) {
    $element = "<label class=\"option\">$element $title</label>";
  }
  return theme('form_element', NULL, $element, $description);
}

function form_radios($title, $name, $value, $options, $description = NULL) {
  if (count($options) > 0) {
    foreach ($options as $key => $choice) {
      $choices .= "<label class=\"option\"><input type=\"radio\" class=\"form-radio\" name=\"edit[$name]\" value=\"$key\"". ($key == $value ? " checked=\"checked\"" : "") ." /> $choice</label><br />";
    }
    return theme('form_element', $title, $choices, $description);
  }
}

function form_checkbox($title, $name, $value = 1, $checked = 0, $description = NULL, $attributes = NULL) {
  $element = "<input type=\"checkbox\" class=\"form-checkbox\" name=\"edit[$name]\" id=\"edit-$name\" value=\"". $value ."\"". ($checked ? " checked=\"checked\"" : "") . drupal_attributes($attributes) .' />';
  if (!is_null($title)) {
    $element = "<label class=\"option\">$element $title</label>";
  }
  return form_hidden($name, 0) . theme('form_element', NULL, $element, $description);
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
/* @} */

function url($url = NULL, $query = NULL, $fragment = NULL, $absolute = NULL) {
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

  if (isset($fragment)) {
    $fragment = "#$fragment";
  }

  $base = ($absolute ? $base_url . '/' : '');

  if (variable_get("clean_url", "0") == "0") {
    if (isset($url)) {
      if (isset($query)) {
        return "$base$script?q=$url&amp;$query$fragment";
      }
      else {
        return "$base$script?q=$url$fragment";
      }
    }
    else {
      if (isset($query)) {
        return "$base$script?$query$fragment";
      }
      else {
        return "$base$fragment";
      }
    }
  }
  else {
    if (isset($url)) {
      if (isset($query)) {
        return "$base$url?$query$fragment";
      }
      else {
        return "$base$url$fragment";
      }
    }
    else {
      if (isset($query)) {
        return "$base$script?$query$fragment";
      }
      else {
        return "$base$fragment";
      }
    }
  }
}

function drupal_attributes($attributes = array()) {
  if (count($attributes)) {
    $t = array();
    foreach ($attributes as $key => $value) {
      $t[] = "$key=\"$value\"";
    }

    return " ". implode($t, " ");
  }
}

function l($text, $url, $attributes = array(), $query = NULL, $fragment = NULL, $absolute = NULL) {
  if (drupal_get_normal_path($url) == $_GET['q']) {
    if (isset($attributes['class'])) {
      $attributes['class'] .= ' active';
    }
    else {
      $attributes['class'] = 'active';
    }
  }
  return "<a href=\"". url($url, $query, $fragment, $absolute) ."\"". drupal_attributes($attributes) .">$text</a>";
}

function field_get($string, $name) {
  ereg(",$name=([^,]+)", ",$string", $regs);
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

/**
 * Walks through the provided array and constructs an associative
 * array out of it. The keys of the resulting array will be the
 * values of the passed array. The values will either be the same
 * (if no function was specified), or the results of the function
 * given the value.
 *
 * @param $array An array
 * @param $function A name of a function to apply to all values
 */
function drupal_map_assoc($array, $function = NULL) {
  if (!isset($function)) {
    $result = array();
    foreach ($array as $value) {
      $result[$value] = $value;
    }
    return $result;
  }
  elseif (function_exists($function)) {
    $result = array();
    foreach($array as $value) {
      $result[$value] = $function($value);
    }
    return $result;
  }
}

/**
 * Wrapper around xml_parser_create() which extracts the encoding from the XML
 * data first and sets the output encoding to UTF-8. This function should be
 * used instead of xml_parser_create(), because PHP's XML parser doesn't check
 * the input encoding itself.
 *
 * This is also where unsupported encodings should be converted.
 * Callers should take this into account: $data might have been changed after
 * the call.
 *
 * @param $data The XML data which will be parsed later.
 */
function drupal_xml_parser_create(&$data) {
  $encoding = 'utf-8';
  if (ereg('^<\?xml[^>]+encoding="([^"]+)"', $data, $match)) {
    $encoding = $match[1];
  }

  /*
   * Unsupported encodings are converted here into UTF-8.
   * Requires iconv, see http://www.php.net/iconv
   */
  $php_supported = array('utf-8', 'iso-8859-1', 'us-ascii');
  if (!in_array(strtolower($encoding), $php_supported)) {
    if (function_exists('iconv')) {
      $out = iconv($encoding, 'utf-8', $data);
      if ($out !== false) {
        $data = $out;
        $encoding = 'utf-8';
      }
    }
  }

  $xml_parser = xml_parser_create($encoding);
  xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
  return $xml_parser;
}

/**
 * Decode all HTML entities (including numerical ones) to regular UTF-8 bytes.
 *
 * @param $text
 *   The text to decode entities in.
 * @param $exclude
 *   An array of characters which should not be decoded. For example,
 *   array('<', '&', '"'). This affects both named and numerical entities.
 */
function decode_entities($text, $exclude = array()) {
  static $table;
  // We store named entities in a table for quick processing.
  if (!isset($table)) {
    // Get all named HTML entities.
    $table = array_flip(get_html_translation_table(HTML_ENTITIES, $special));
    // PHP gives us ISO-8859-1 data, we need UTF-8.
    $table = array_map('utf8_encode', $table);
  }
  $text = strtr($text, array_diff($table, $exclude));

  // Any remaining entities are numerical. Use a regexp to replace them.
  return preg_replace('/&#(x?)([A-Za-z0-9]+);/e', '_decode_entities("$1", "$2", "$0", $exclude)', $text);
}

/**
 * Helper function for decode_entities
 */
function _decode_entities($hex, $codepoint, $original, $exclude) {
  if ($hex != '') {
    $codepoint = base_convert($codepoint, 16, 10);
  }
  if ($codepoint < 0x80) {
    $str = chr($codepoint);
  }
  else if ($codepoint < 0x800) {
    $str = chr(0xC0 | ($codepoint >> 6))
         . chr(0x80 | ($codepoint & 0x3F));
  }
  else if ($codepoint < 0x10000) {
    $str = chr(0xE0 | ( $codepoint >> 12))
         . chr(0x80 | (($codepoint >> 6) & 0x3F))
         . chr(0x80 | ( $codepoint       & 0x3F));
  }
  else if ($codepoint < 0x200000) {
    $str = chr(0xF0 | ( $codepoint >> 18))
         . chr(0x80 | (($codepoint >> 12) & 0x3F))
         . chr(0x80 | (($codepoint >> 6)  & 0x3F))
         . chr(0x80 | ( $codepoint        & 0x3F));
  }
  if (in_array($str, $exclude)) {
    return $original;
  }
  else {
    return $str;
  }
}

/**
 * UTF-8-safe string truncation
 * If the end position is in the middle of a UTF-8 sequence, it scans backwards
 * until the beginning of the byte sequence.
 *
 * Use this function whenever you want to chop off a string at an unsure
 * location. On the other hand, if you're sure that you're splitting on a
 * character boundary (e.g. after using strpos or similar), you can safely use
 * substr() instead.
 *
 * @param $string The string to truncate
 * @param $len An upper limit on the returned string length.
 */
function truncate_utf8($string, $len) {
  $slen = strlen($string);
  if ($slen <= $len) {
    return $string;
  }
  if ((ord($string[$len]) < 0x80) || (ord($string[$len]) >= 0xC0)) {
    return substr($string, 0, $len);
  }
  while (ord($string[--$len]) < 0xC0) {};
  return substr($string, 0, $len);
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
drupal_set_header("Content-Type: text/html; charset=utf-8");

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
