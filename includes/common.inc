<?php

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

/**
 * Set the breadcrumb trail for the current page.
 *
 * @param $breadcrumb
 *   Array of links, starting with "home" and proceeding up to but not including
 *   the current page.
 */
function drupal_set_breadcrumb($breadcrumb = NULL) {
  static $stored_breadcrumb;

  if (isset($breadcrumb)) {
    $stored_breadcrumb = $breadcrumb;
  }
  return $stored_breadcrumb;
}

/**
 * Get the breadcrumb trail for the current page.
 */
function drupal_get_breadcrumb() {
  $breadcrumb = drupal_set_breadcrumb();

  if (!isset($breadcrumb)) {
    $breadcrumb = menu_get_active_breadcrumb();
  }

  return $breadcrumb;
}

function drupal_add_link($attributes) {
  drupal_set_html_head('<link'. drupal_attributes($attributes) ." />\n");
}

/**
 * Add output to the head tag of the HTML page.
 */
function drupal_set_html_head($data = NULL) {
  static $stored_head = '';

  if (!is_null($data)) {
    $stored_head .= $data ."\n";
  }
  return $stored_head;
}

/**
 * Retrieve output to be displayed in the head tag of the HTML page.
 */
function drupal_get_html_head() {
  global $base_url;

  $output = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
  $output .= "<base href=\"$base_url/\" />\n";
  $output .= theme('stylesheet_import', 'misc/drupal.css');

  return $output . drupal_set_html_head();
}

/**
 * Regenerate the path map from the information in the database.
 */
function drupal_rebuild_path_map() {
  drupal_get_path_map('rebuild');
}

/**
 * Given a path alias, return the internal path it represents.
 */
function drupal_get_normal_path($path) {
  if (($map = drupal_get_path_map()) && isset($map[$path])) {
    return $map[$path];
  }
  elseif (function_exists('conf_url_rewrite')) {
    return conf_url_rewrite($path, 'incoming');
  }
  else {
    return $path;
  }
}

/**
 * Set an HTTP response header for the current page.
 */
function drupal_set_header($header = NULL) {
  // We use an array to guarantee there are no leading or trailing delimiters.
  // Otherwise, header('') could get called when serving the page later, which
  // ends HTTP headers prematurely on some PHP versions.
  static $stored_headers = array();

  if (strlen($header)) {
    header($header);
    $stored_headers[] = $header;
  }
  return implode("\n", $stored_headers);
}

/**
 * Get the HTTP response headers for the current page.
 */
function drupal_get_headers() {
  return drupal_set_header();
}

/**
 * @name HTTP handling
 * @{
 * Functions to properly handle HTTP responses.
 */

/**
 * Send the user to a different Drupal page.
 *
 * This issues an on-site HTTP redirect. The function makes sure the redirected
 * URL is formatted correctly.
 *
 * It is advised to use drupal_goto() instead of PHP's header(), because
 * drupal_goto() will append the user's session ID to the URI when PHP is
 * compiled with "--enable-trans-sid".
 *
 * This function ends the request; use it rather than a print theme('page')
 * statement in your menu callback.
 *
 * @param $path
 *   A Drupal path.
 * @param $query
 *   The query string component, if any.
 * @param $fragment
 *   The destination fragment identifier (named anchor).
 */
function drupal_goto($path = '', $query = NULL, $fragment = NULL) {
  // Translate &amp; to simply & in the absolute URL.
  $url = str_replace('&amp;', '&', url($path, $query, $fragment, TRUE));

  if (ini_get('session.use_trans_sid') && session_id() && !strstr($url, session_id())) {
    $sid = session_name() . '=' . session_id();

    if (strstr($url, '?') && !strstr($url, $sid)) {
      $url = $url .'&'. $sid;
    }
    else {
      $url = $url .'?'. $sid;
    }
  }

  // Before the redirect, allow modules to react to the end of the page request.
  module_invoke_all('exit', $url);

  header('Location: '. $url);

  // The "Location" header sends a REDIRECT status code to the http
  // daemon. In some cases this can go wrong, so we make sure none
  // of the code below the drupal_goto() call gets executed when we redirect.
  exit();
}

/**
 * Generates a 404 error if the request can not be handled.
 */
function drupal_not_found() {
  header('HTTP/1.0 404 Not Found');
  watchdog('httpd', t('404 error: %page not found.', array('%page' => '<em>'. check_query($_GET['q']) .'</em>')));

  $path = drupal_get_normal_path(variable_get('site_404', ''));
  $status = MENU_NOT_FOUND;
  if ($path) {
    menu_set_active_item($path);
    $status = menu_execute_active_handler();
  }

  if ($status != MENU_FOUND) {
    print theme('page', '', t('Page not found'));
  }
}

/**
 * Generates a 403 error if the request is not allowed.
 */
function drupal_access_denied() {
  header('HTTP/1.0 403 Forbidden');

  $path = drupal_get_normal_path(variable_get('site_403', ''));
  $status = MENU_NOT_FOUND;
  if ($path) {
    menu_set_active_item($path);
    $status = menu_execute_active_handler();
  }

  if ($status != MENU_FOUND) {
    print theme('page', message_access(), t('Access denied'));
  }
}

/**
 * Perform an HTTP request.
 *
 * This is a flexible and powerful HTTP client implementation. Correctly handles
 * GET, POST, PUT or any other HTTP requests. Handles redirects.
 *
 * @param $url
 *   A string containing a fully qualified URI.
 * @param $headers
 *   An array containing an HTTP header => value pair.
 * @param $method
 *   A string defining the HTTP request to use.
 * @param $data
 *   A string containing data to include in the request.
 * @param $retry
 *   An integer representing how many times to retry the request in case of a
 *   redirect.
 * @return
 *   An object containing the HTTP request headers, response code, headers,
 *   data, and redirect status.
 */
function drupal_http_request($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3) {
  // Parse the URL, and make sure we can handle the schema.
  $uri = parse_url($url);
  switch ($uri['scheme']) {
    case 'http':
      $fp = @fsockopen($uri['host'], ($uri['port'] ? $uri['port'] : 80), $errno, $errstr, 15);
      break;
    case 'https':
      // Note: Only works for PHP 4.3 compiled with OpenSSL.
      $fp = @fsockopen('ssl://'. $uri['host'], ($uri['port'] ? $uri['port'] : 443), $errno, $errstr, 20);
      break;
    default:
      $result->error = 'invalid schema '. $uri['scheme'];
      return $result;
  }

  // Make sure the socket opened properly.
  if (!$fp) {
    $result->error = trim($errno .' '. $errstr);
    return $result;
  }

  // Construct the path to act on.
  $path = $uri['path'] ? $uri['path'] : '/';
  if ($uri['query']) {
    $path .= '?'. $uri['query'];
  }

  // Create HTTP request.
  $defaults = array(
    'Host' => 'Host: '. $uri['host'],
    'User-Agent' => 'User-Agent: Drupal (+http://www.drupal.org/)',
    'Content-Length' => 'Content-Length: '. strlen($data)
  );

  foreach ($headers as $header => $value) {
    $defaults[$header] = $header .': '. $value;
  }

  $request = $method .' '. $path ." HTTP/1.0\r\n";
  $request .= implode("\r\n", $defaults);
  $request .= "\r\n\r\n";
  if ($data) {
    $request .= $data ."\r\n";
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
  // the base code in their class.
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
/**
 * @} End of "HTTP handling".
 */

/**
 * Log errors as defined by administrator
 * Error levels:
 *  1 = Log errors to database.
 *  2 = Log errors to database and to screen.
 */
function error_handler($errno, $message, $filename, $line, $variables) {
  if ($errno & E_ALL ^ E_NOTICE) {
    $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning');
    $entry = $types[$errno] .': '. $message .' in '. $filename .' on line '. $line .'.';

    watchdog('error', t('%error: %message in %file on line %line.', array('%error' => $types[$errno], '%message' => $message, '%file' => $filename, '%line' => $line)));

    if (variable_get('error_level', 1) == 1) {
      print '<pre>'. $entry .'</pre>';
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

/**
 * Correct double-escaping problems caused by "magic quotes" in some PHP
 * installations.
 */
function fix_gpc_magic() {
  static $fixed = false;
  if (!$fixed && ini_get('magic_quotes_gpc')) {
    array_walk($_GET, '_fix_gpc_magic');
    array_walk($_POST, '_fix_gpc_magic');
    array_walk($_COOKIE, '_fix_gpc_magic');
    array_walk($_REQUEST, '_fix_gpc_magic');
    $fixed = true;
  }
}

/**
 * @name Conversion
 * @{
 * Converts data structures to different types.
 */

/**
 * Convert an associative array to an anonymous object.
 */
function array2object($array) {
  if (is_array($array)) {
    $object = new stdClass();
    foreach ($array as $key => $value) {
      $object->$key = $value;
    }
  }
  else {
    $object = $array;
  }

  return $object;
}

/**
 * Convert an object to an associative array.
 */
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

/**
 * @} End of "Conversion".
 */

/**
 * @name Messages
 * @{
 * Frequently used messages.
 */

/**
 * Return a string with an "access denied" message.
 *
 * Always consider whether to use drupal_access_denied() instead to return a
 * proper (and customizable) 403 error.
 */
function message_access() {
  return t('You are not authorized to access this page.');
}

/**
 * Return a string with a "not applicable" message.
 */
function message_na() {
  return t('n/a');
}

/**
 * @} End of "Messages".
 */

/**
 * Initialize the localization system.
 */
function locale_initialize() {
  global $user;

  if (function_exists('i18n_get_lang')) {
    return i18n_get_lang();
  }

  if (function_exists('locale')) {
    $languages = locale_supported_languages();
    $languages = $languages['name'];
  }
  else {
    // Ensure the locale/language is correctly returned, even without locale.module.
    // Useful for e.g. XML/HTML 'lang' attributes.
    $languages = array('en' => 'English');
  }
  if ($user->uid && $languages[$user->language]) {
    return $user->language;
  }
  else {
    return key($languages);
  }
}

/**
 * Translate strings to the current locale.
 *
 * When using t(), try to put entire sentences and strings in one t() call.
 * This makes it easier for translators. HTML markup within translation strings
 * is acceptable, if necessary. The suggested syntax for a link embedded
 * within a translation string is:
 * @code
 *   $msg = t('You must log in below or <a href="%url">create a new
 *             account</a> before viewing the next page.', array('%url'
 *             => url('user/register')));
 * @endcode
 * We suggest the same syntax for links to other sites. This makes it easy to
 * change link URLs if needed (which happens often) without requiring updates
 * to translations.
 *
 * @param $string
 *   A string containing the English string to translate.
 * @param $args
 *   An associative array of replacements to make after translation. Incidences
 *   of any key in this array are replaced with the corresponding value.
 * @return
 *   The translated string.
 */
function t($string, $args = 0) {
  global $locale;
  if (function_exists('locale') && $locale != 'en') {
    $string = locale($string);
  }

  if (!$args) {
    return $string;
  }
  else {
    return strtr($string, $args);
  }
}

/**
 * Encode special characters in a string for display as HTML.
 *
 * Note that we'd like to use htmlspecialchars($input, $quotes, 'utf-8')
 * as outlined in the PHP manual, but we can't because there's a bug in
 * PHP < 4.3 that makes it mess up multibyte charsets if we specify the
 * charset. This will be changed later once we make PHP 4.3 a requirement.
 */
function drupal_specialchars($input, $quotes = ENT_NOQUOTES) {
  return htmlspecialchars($input, $quotes);
}

/**
 * @defgroup validation Input validation
 * @{
 * Functions to validate user input.
 */

/**
 * Verify the syntax of the given e-mail address.
 *
 * Empty e-mail addresses are allowed. See RFC 2822 for details.
 *
 * @param $mail
 *   A string containing an email address.
 * @return
 *   TRUE if the address is in a valid format.
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
 * @param $url
 *   The URL to verify.
 * @param $absolute
 *   Whether the URL is absolute (beginning with a scheme such as "http:").
 * @return
 *   TRUE if the URL is in a valid format.
 */
function valid_url($url, $absolute = FALSE) {
  if ($absolute) {
    return preg_match("/^(http|https|ftp):\/\/[a-z0-9\/:_\-_\.\?,~=#&%\+]+$/i", $url);
  }
  else {
    return preg_match("/^[a-z0-9\/:_\-_\.,\+]+$/i", $url);
  }
}

/**
 * @defgroup search Search interface
 * @{
 * The Drupal search interface manages a global search mechanism.
 *
 * Modules may plug into this system to provide searches of different types of
 * data. Most of the system is handled by search.module, so this must be enabled
 * for all of the search features to work.
 */

/**
 * Format a single result entry of a search query.
 *
 * Modules may implement hook_search_item() in order to override this default
 * function to display search results.
 *
 * @param $item
 *   A single search result as returned by hook_search(). The result should be
 *   an array with keys "count", "link", "title", "user", "date", and "keywords".
 * @param $type
 *   The type of item found, such as "user" or "comment".
 */
function search_item($item, $type) {
  if (module_hook($type, 'search_item')) {
    $output = module_invoke($type, 'search_item', $item);
  }
  else {
    $output = ' <dt class="title"><a href="'. $item['link'] .'">'. $item['title'] .'</a></dt>';
    $output .= ' <dd class="small">' . t($type) . ($item['user'] ? ' - '. $item['user'] : '') .''. ($item['date'] ? ' - '. format_date($item['date'], 'small') : '') .'</dd>';
  }

  return $output;
}

/**
 * Render a generic search form.
 *
 * This form must be usable not only within "http://example.com/search", but also
 * as a simple search box (without "Restrict search to", help text, etc.), in the
 * theme's header, and so forth. This means we must provide options to
 * conditionally render certain parts of this form.
 *
 * @param $action
 *   Form action. Defaults to "search".
 * @param $keys
 *   The search string entered by the user, containing keywords for the search.
 * @param $options
 *   Whether to render the optional form fields and text ("Restrict search
 *   to", help text, etc.).
 * @return
 *   An HTML string containing the search form.
 */
function search_form($action = '', $keys = '', $options = FALSE) {
  $edit = $_POST['edit'];

  if (!$action) {
    $action = url('search');
  }

  $output = ' <div class="search-form"><br /><input type="text" class="form-text" size="50" value="'. check_form($keys) .'" name="keys" />';
  $output .= ' <input type="submit" class="form-submit" value="'. t('Search') ."\" />\n";

  if ($options) {
    $output .= '<br />';
    $output .= t('Restrict search to') .': ';

    foreach (module_list() as $name) {
      if (module_hook($name, 'search')) {
        $output .= ' <input type="checkbox" name="edit[type]['. $name .']" '. ($edit['type'][$name] ? ' checked="checked"' : '') .' /> '. t($name);
      }
    }
  }
  $output .= '</div>';

  return form($output, 'post', $action);
}

/**
 * Perform a global search on the given keys, and return the formatted results.
 */
function search_data($keys = NULL) {
  $edit = $_POST['edit'];
  $output = '';

  if (isset($keys)) {
    foreach (module_list() as $name) {
      if (module_hook($name, 'search') && (!$edit['type'] || $edit['type'][$name])) {
        list($title, $results) = module_invoke($name, 'search', $keys);
        if ($results) {
          $output .= '<h2>'. $title .'</h2>';
          $output .= '<dl class="search-results">';
          foreach ($results as $entry) {
            $output .= search_item($entry, $name);
          }
          $output .= '</dl>';
        }
      }
    }
  }

  return $output;
}

/**
 * Display a search form for a particular type of data.
 *
 * @param $type
 *   The type of content to search within.
 * @param $action
 *   Form action. Defaults to "search".
 * @param $keys
 *   The search string entered by the user, containing keywords for the search.
 * @param $options
 *   Whether to render the optional form fields and text ("Restrict search
 *   to", help text, etc.).
 * @return
 *   An HTML string containing the search form and results.
 */
function search_type($type, $action = '', $keys = '', $options = FALSE) {
  $_POST['edit']['type'][$type] = 'on';

  return search_form($action, $keys, $options) . '<br />'. search_data($keys);
}

/**
 * @} End of "defgroup search".
 */

function check_form($text) {
  return drupal_specialchars($text, ENT_QUOTES);
}

function check_file($filename) {
  return is_uploaded_file($filename);
}

/**
 * Prepare a URL for use in an HTML attribute. Strips harmful protocols.
 *
 */
function check_url($uri) {
  return filter_xss_bad_protocol($uri, FALSE);
}

/**
 * Return the URI of the referring page.
 */
function referer_uri() {
  if (isset($_SERVER['HTTP_REFERER'])) {
    return check_url($_SERVER['HTTP_REFERER']);
  }
}

/**
 * @defgroup format Formatting
 * @{
 * Functions to format numbers, strings, dates, etc.
 */

/**
 * Formats an RSS channel.
 *
 * Arbitrary elements may be added using the $args associative array.
 */
function format_rss_channel($title, $link, $description, $items, $language = 'en', $args = array()) {
  // arbitrary elements may be added using the $args associative array

  $output = "<channel>\n";
  $output .= ' <title>'. drupal_specialchars(strip_tags($title)) ."</title>\n";
  $output .= ' <link>'. drupal_specialchars(strip_tags($link)) ."</link>\n";
  $output .= ' <description>'. drupal_specialchars(strip_tags($description)) ."</description>\n";
  $output .= ' <language>'. drupal_specialchars(strip_tags($language)) ."</language>\n";
  foreach ($args as $key => $value) {
    $output .= ' <'. $key .'>'. drupal_specialchars(strip_tags($value)) ."</$key>\n";
  }
  $output .= $items;
  $output .= "</channel>\n";

  return $output;
}

/**
 * Format a single RSS item.
 *
 * Arbitrary elements may be added using the $args associative array.
 */
function format_rss_item($title, $link, $description, $args = array()) {
  $output = "<item>\n";
  $output .= ' <title>'. drupal_specialchars(strip_tags($title)) ."</title>\n";
  $output .= ' <link>'. drupal_specialchars(strip_tags($link)) ."</link>\n";
  $output .= ' <description>'. drupal_specialchars($description) ."</description>\n";
  foreach ($args as $key => $value) {
    $output .= ' <'. $key .'>'. drupal_specialchars(strip_tags($value)) ."</$key>\n";
  }
  $output .= "</item>\n";

  return $output;
}

/**
 * Format a string containing a count of items.
 *
 * This function ensures that the string is pluralized correctly. Since t() is
 * called by this function, make sure not to pass already-localized strings to it.
 *
 * @param $count
 *   The item count to display.
 * @param $singular
 *   The string for the singular case. Please make sure it is clear this is
 *   singular, to ease translation (e.g. use "1 new comment" instead of "1 new").
 * @param $plural
 *   The string for the plural case. Please make sure it is clear this is plural,
 *   to ease translation. Use %count in place of the item count, as in "%count
 *   new comments".
 * @return
 *   A translated string.
 */
function format_plural($count, $singular, $plural) {
  if ($count == 1) return t($singular, array("%count" => $count));

  // get the plural index through the gettext formula
  $index = (function_exists('locale')) ? locale_get_plural($count) : -1;
  if ($index < 0) { // backward compatibility
    return t($plural, array("%count" => $count));
  }
  else {
    switch ($index) {
      case "0":
        return t($singular, array("%count" => $count));
      case "1":
        return t($plural, array("%count" => $count));
      default:
        return t(strtr($plural, array("%count" => '%count['. $index .']')), array('%count['. $index .']' => $count));
    }
  }
}

/**
 * Generate a string representation for the given byte count.
 *
 * @param $size
 *   The size in bytes.
 * @return
 *   A translated string representation of the size.
 */
function format_size($size) {
  $suffix = t('bytes');
  if ($size > 1024) {
    $size = round($size / 1024, 2);
    $suffix = t('KB');
  }
  if ($size > 1024) {
    $size = round($size / 1024, 2);
    $suffix = t('MB');
  }
  return t('%size %suffix', array('%size' => $size, '%suffix' => $suffix));
}

/**
 * Format a time interval with the requested granularity.
 *
 * @param $timestamp
 *   The length of the interval in seconds.
 * @param $granularity
 *   How many different units to display in the string.
 * @return
 *   A translated string representation of the interval.
 */
function format_interval($timestamp, $granularity = 2) {
  $units = array('1 year|%count years' => 31536000, '1 week|%count weeks' => 604800, '1 day|%count days' => 86400, '1 hour|%count hours' => 3600, '1 min|%count min' => 60, '1 sec|%count sec' => 1);
  $output = '';
  foreach ($units as $key => $value) {
    $key = explode('|', $key);
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '') . format_plural(floor($timestamp / $value), $key[0], $key[1]);
      $timestamp %= $value;
      $granularity--;
    }

    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : t('0 sec');
}

/**
 * Format a date with the given configured format or a custom format string.
 *
 * Drupal allows administrators to select formatting strings for 'small',
 * 'medium' and 'large' date formats. This function can handle these formats,
 * as well as any custom format.
 *
 * @param $timestamp
 *   The exact date to format, as a UNIX timestamp.
 * @param $type
 *   The format to use. Can be "small", "medium" or "large" for the preconfigured
 *   date formats. If "custom" is specified, then $format is required as well.
 * @param $format
 *   A PHP date format string as required by date().
 * @param $timezone
 *   Time zone offset in seconds; if omitted, the user's time zone is used.
 * @return
 *   A translated date string in the requested format.
 */
function format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL) {
  if ($timezone === NULL) {
    global $user;
    if (variable_get('configurable_timezones', 1) && $user->uid && strlen($user->timezone)) {
      $timezone = $user->timezone;
    }
    else {
      $timezone = variable_get('date_default_timezone', 0);
    }
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
  $date = '';
  for ($i = 0; $i < $max; $i++) {
    $c = $format{$i};
    if (strpos('AaDFlM', $c) !== false) {
      $date .= t(gmdate($c, $timestamp));
    }
    else if (strpos('BdgGhHiIjLmnsStTUwWYyz', $c) !== false) {
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
 * Format a username.
 *
 * @param $object
 *   The user object to format, usually returned from user_load().
 * @return
 *   A string containing an HTML link to the user's page if the passed object
 *   suggests that this is a site user. Otherwise, only the username is returned.
 */
function format_name($object) {

  if ($object->uid && $object->name) {
    // Shorten the name when it is too long or it will break many tables.
    if (strlen($object->name) > 20) {
      $name = truncate_utf8($object->name, 15) .'...';
    }
    else {
      $name = $object->name;
    }

    $output = l(check_plain($name), 'user/'. $object->uid, array('title' => t('View user profile.')));
  }
  else if ($object->name) {
    // Sometimes modules display content composed by people who are
    // not registered members of the site (e.g. mailing list or news
    // aggregator modules). This clause enables modules to display
    // the true author of the content.
    if ($object->homepage) {
      $output = '<a href="'. check_url($object->homepage) .'">'. check_plain($object->name) .'</a>';
    }
    else {
      $output = check_plain($object->name);
    }

    $output .= ' ('. t('not verified') .')';
  }
  else {
    $output = variable_get('anonymous', 'Anonymous');
  }

  return $output;
}
/**
 * @} End of "defgroup format".
 */

/**
 * @defgroup form Form generation
 * @{
 * Functions to enable output of HTML forms and form elements.
 *
 * Drupal uses these functions to achieve consistency in its form presentation,
 * while at the same time simplifying code and reducing the amount of HTML that
 * must be explicitly generated by modules.
 */

/**
 * Generate a form from a set of form elements.
 *
 * @param $form
 *   An HTML string containing one or more form elements.
 * @param $method
 *   The query method to use ("post" or "get").
 * @param $action
 *   The URL to send the form contents to, if not the current page.
 * @param $attributes
 *   An associative array of attributes to add to the form tag.
 * @result
 *   An HTML string with the contents of $form wrapped in a form tag.
 */
function form($form, $method = 'post', $action = NULL, $attributes = NULL) {
  if (!$action) {
    $action = request_uri();
  }
  return '<form action="'. $action .'" method="'. $method .'"'. drupal_attributes($attributes) .">\n". $form ."\n</form>\n";
}

/**
 * File an error against the form element with the specified name.
 */
function form_set_error($name, $message) {
  $GLOBALS['form'][$name] = $message;
  drupal_set_message($message, 'error');
}

/**
 * Return an associative array of all errors.
 */
function form_get_errors() {
  if (array_key_exists('form', $GLOBALS)) {
    return $GLOBALS['form'];
  }
}

/**
 * Return the error message filed against the form with the specified name.
 */
function _form_get_error($name) {
  if (array_key_exists('form', $GLOBALS)) {
    return $GLOBALS['form'][$name];
  }
}

function _form_get_class($name, $required, $error) {
  return $name. ($required ? ' required' : '') . ($error ? ' error' : '');
}

/**
 * Format a general form item.
 *
 * @param $title
 *   The label for the form item.
 * @param $value
 *   The contents of the form item.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $id
 *   A unique identifier for the form item.
 * @param $required
 *   Whether the user must fill in this form element before submitting the form.
 * @param $error
 *   An error message to display alongside the form element.
 * @return
 *   A themed HTML string representing the form item.
 */
function form_item($title, $value, $description = NULL, $id = NULL, $required = FALSE, $error = FALSE) {
  return theme('form_element', $title, $value, $description, $id, $required, $error);
}

/**
 * Format a group of form items.
 *
 * @param $legend
 *   The label for the form item group.
 * @param $group
 *   The form items within the group, as an HTML string.
 * @param $description
 *   Explanatory text to display after the form item group.
 * @return
 *   A themed HTML string representing the form item group.
 */
function form_group($legend, $group, $description = NULL) {
  return '<fieldset>' . ($legend ? '<legend>'. check_plain($legend) .'</legend>' : '') . $group . ($description ? '<div class="description">'. $description .'</div>' : '') . "</fieldset>\n";
}

/**
 * Format a radio button.
 *
 * @param $title
 *   The label for the radio button.
 * @param $name
 *   The internal name used to refer to the button.
 * @param $value
 *   The value that the form element takes on when selected.
 * @param $checked
 *   Whether the button will be initially selected when the page is rendered.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $attributes
 *   An associative array of HTML attributes to add to the button.
 * @param $required
 *   Whether the user must select this radio button before submitting the form.
 * @return
 *   A themed HTML string representing the radio button.
 */
function form_radio($title, $name, $value = 1, $checked = FALSE, $description = NULL, $attributes = NULL, $required = FALSE) {
  $element = '<input type="radio" class="'. _form_get_class('form-radio', $required, _form_get_error($name)) .'" name="edit['. $name .']" value="'. check_plain($value) .'"'. ($checked ? ' checked="checked"' : '') . drupal_attributes($attributes) .' />';
  if (!is_null($title)) {
    $element = '<label class="option">'. $element .' '. $title .'</label>';
  }
  return theme('form_element', NULL, $element, $description, $name, $required, _form_get_error($name));
}

/**
 * Format a set of radio buttons.
 *
 * @param $title
 *   The label for the radio buttons as a group.
 * @param $name
 *   The internal name used to refer to the buttons.
 * @param $value
 *   The currently selected radio button's key.
 * @param $options
 *   An associative array of buttons to display. The keys in this array are
 *   button values, while the values are the labels to display for each button.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $required
 *   Whether the user must select a radio button before submitting the form.
 * @param $attributes
 *   An associative array of HTML attributes to add to each button.
 * @return
 *   A themed HTML string representing the radio button set.
 */
function form_radios($title, $name, $value, $options, $description = NULL, $required = FALSE, $attributes = NULL) {
  if (count($options) > 0) {
    $choices = '';
    foreach ($options as $key => $choice) {
      $choices .= '<label class="option"><input type="radio" class="form-radio" name="edit['. $name .']" value="'. check_plain($key) .'"'. ($key == $value ? ' checked="checked"' : ''). drupal_attributes($attributes) .' /> '. $choice .'</label><br />';
    }
    return theme('form_element', $title, $choices, $description, NULL, $required, _form_get_error($name));
  }
}

/**
 * Format a checkbox.
 *
 * @param $title
 *   The label for the checkbox.
 * @param $name
 *   The internal name used to refer to the button.
 * @param $value
 *   The value that the form element takes on when selected.
 * @param $checked
 *   Whether the button will be initially selected when the page is rendered.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $attributes
 *   An associative array of HTML attributes to add to the button.
 * @param $required
 *   Whether the user must check this box before submitting the form.
 * @return
 *   A themed HTML string representing the checkbox.
 */
function form_checkbox($title, $name, $value = 1, $checked = FALSE, $description = NULL, $attributes = NULL, $required = FALSE) {
  $element = '<input type="checkbox" class="'. _form_get_class('form-checkbox', $required, _form_get_error($name)) .'" name="edit['. $name .']" id="edit-'. $name .'" value="'. check_plain($value) .'"'. ($checked ? ' checked="checked"' : '') . drupal_attributes($attributes) .' />';
  if (!is_null($title)) {
    $element = '<label class="option">'. $element .' '. $title .'</label>';
  }
  // Note: because unchecked boxes are not included in the POST data, we include
  // a form_hidden() which will be overwritten for a checked box.
  return form_hidden($name, 0) . theme('form_element', NULL, $element, $description, $name, $required, _form_get_error($name));
}

/**
 * Format a set of checkboxes.
 *
 * @param $title
 *   The label for the checkboxes as a group.
 * @param $name
 *   The internal name used to refer to the buttons.
 * @param $values
 *   A linear array of keys of the initially checked boxes.
 * @param $options
 *   An associative array of buttons to display. The keys in this array are
 *   button values, while the values are the labels to display for each button.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $attributes
 *   An associative array of HTML attributes to add to each button.
 * @param $required
 *   Whether the user must check a box before submitting the form.
 * @return
 *   A themed HTML string representing the radio button set.
 */
function form_checkboxes($title, $name, $values, $options, $description = NULL, $attributes = NULL, $required = FALSE) {
  if (count($options) > 0) {
    if (!isset($values) || $values == 0) {
      $values = array();
    }
    $choices = '';
    foreach ($options as $key => $choice) {
      $choices .= '<label class="option"><input type="checkbox" class="form-checkbox" name="edit['. $name .'][]" value="'. check_plain($key) .'"'. (in_array($key, $values) ? ' checked="checked"' : ''). drupal_attributes($attributes) .' /> '. $choice .'</label><br />';
    }
    // Note: because unchecked boxes are not included in the POST data, we
    // include a form_hidden() which will be overwritten as soon as there is at
    // least one checked box.
    return form_hidden($name, 0) . theme('form_element', $title, $choices, $description, NULL, $required, _form_get_error($name));
  }
}

/**
 * Format a single-line text field.
 *
 * @param $title
 *   The label for the text field.
 * @param $name
 *   The internal name used to refer to the field.
 * @param $value
 *   The initial value for the field at page load time.
 * @param $size
 *   A measure of the visible size of the field (passed directly to HTML).
 * @param $maxlength
 *   The maximum number of characters that may be entered in the field.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $attributes
 *   An associative array of HTML attributes to add to the form item.
 * @param $required
 *   Whether the user must enter some text in the field.
 * @return
 *   A themed HTML string representing the field.
 */
function form_textfield($title, $name, $value, $size, $maxlength, $description = NULL, $attributes = NULL, $required = FALSE) {
  $size = $size ? ' size="'. $size .'"' : '';
  return theme('form_element', $title, '<input type="text" maxlength="'. $maxlength .'" class="'. _form_get_class('form-text', $required, _form_get_error($name)) .'" name="edit['. $name .']" id="edit-'. $name .'"'. $size .' value="'. check_form($value) .'"'. drupal_attributes($attributes) .' />', $description, 'edit-'. $name, $required, _form_get_error($name));
}

/**
 * Format a single-line text field that does not display its contents visibly.
 *
 * @param $title
 *   The label for the text field.
 * @param $name
 *   The internal name used to refer to the field.
 * @param $value
 *   The initial value for the field at page load time.
 * @param $size
 *   A measure of the visible size of the field (passed directly to HTML).
 * @param $maxlength
 *   The maximum number of characters that may be entered in the field.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $attributes
 *   An associative array of HTML attributes to add to the form item.
 * @param $required
 *   Whether the user must enter some text in the field.
 * @return
 *   A themed HTML string representing the field.
 */
function form_password($title, $name, $value, $size, $maxlength, $description = NULL, $attributes = NULL, $required = FALSE) {
  $size = $size ? ' size="'. $size .'"' : '';
  return theme('form_element', $title, '<input type="password" class="'. _form_get_class('form-password', $required, _form_get_error($name)) .'" maxlength="'. $maxlength .'" name="edit['. $name .']" id="edit-'. $name .'"'. $size .' value="'. check_form($value) .'"'. drupal_attributes($attributes) .' />', $description, 'edit-'. $name, $required, _form_get_error($name));
}

/**
 * Format a multiple-line text field.
 *
 * @param $title
 *   The label for the text field.
 * @param $name
 *   The internal name used to refer to the field.
 * @param $value
 *   The initial value for the field at page load time.
 * @param $cols
 *   The width of the field, in columns of text.
 * @param $rows
 *   The height of the field, in rows of text.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $attributes
 *   An associative array of HTML attributes to add to the form item.
 * @param $required
 *   Whether the user must enter some text in the field.
 * @return
 *   A themed HTML string representing the field.
 */
function form_textarea($title, $name, $value, $cols, $rows, $description = NULL, $attributes = NULL, $required = FALSE) {
  $output = '';

  // e.g. optionally plug in a WYSIWYG editor
  foreach (module_list() as $module_name) {
    if (module_hook($module_name, 'textarea')) {
      $output .= module_invoke($module_name, 'textarea', 'pre', $name);
    }
  }

  $output .= theme('form_element', $title, '<textarea wrap="virtual" cols="'. check_plain($cols) .'" rows="'. check_plain($rows) .'" name="edit['. $name .']" id="edit-'. $name .'" class="'. _form_get_class('textarea', $required, _form_get_error($name)) .'"'. drupal_attributes($attributes) .'>'. check_form($value) .'</textarea>', $description, 'edit-'. $name, $required, _form_get_error($name));

  // e.g. optionally plug in a WYSIWYG editor
  foreach (module_list() as $module_name) {
    if (module_hook($module_name, 'textarea')) {
      $output .= module_invoke($module_name, 'textarea', 'post', $name);
    }
  }

  return $output;
}

/**
 * Format a dropdown menu or scrolling selection box.
 *
 * @param $title
 *   The label for the form element.
 * @param $name
 *   The internal name used to refer to the form element.
 * @param $value
 *   The key of the currently selected item, or a linear array of keys of all the
 *   currently selected items if multiple selections are allowed.
 * @param $options
 *   An associative array of buttons to display. The keys in this array are
 *   button values, while the values are the labels to display for each button.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $extra
 *   Additional HTML to inject into the select element tag.
 * @param $multiple
 *   Whether the user may select more than one item.
 * @param $required
 *   Whether the user must select a value before submitting the form.
 * @return
 *   A themed HTML string representing the form element.
 *
 * It is possible to group options together; to do this, change the format of
 * $options to an associative array in which the keys are group labels, and the
 * values are associative arrays in the normal $options format.
 */
function form_select($title, $name, $value, $options, $description = NULL, $extra = 0, $multiple = FALSE, $required = FALSE) {
  $select = '';
  foreach ($options as $key => $choice) {
    if (is_array($choice)) {
      $select .= '<optgroup label="'. check_plain($key) .'">';
      foreach ($choice as $key => $choice) {
        $select .= '<option value="'. check_plain($key) .'"'. (is_array($value) ? (in_array($key, $value) ? ' selected="selected"' : '') : ($value == $key ? ' selected="selected"' : '')) .'>'. check_plain($choice) .'</option>';
      }
      $select .= '</optgroup>';
    }
    else {
      $select .= '<option value="'. check_plain($key) .'"'. (is_array($value) ? (in_array($key, $value) ? ' selected="selected"' : '') : ($value == $key ? ' selected="selected"' : '')) .'>'. check_plain($choice) .'</option>';
    }
  }
  return theme('form_element', $title, '<select name="edit['. $name .']'. ($multiple ? '[]' : '') .'"'. ($multiple ? ' multiple="multiple" ' : '') . ($extra ? ' '. $extra : '') .' id="edit-'. $name .'">'. $select .'</select>', $description, 'edit-'. $name, $required, _form_get_error($name));
}

/**
 * Format a file upload field.
 *
 * @param $title
 *   The label for the file upload field.
 * @param $name
 *   The internal name used to refer to the field.
 * @param $size
 *   A measure of the visible size of the field (passed directly to HTML).
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $required
 *   Whether the user must upload a file to the field.
 * @return
 *   A themed HTML string representing the field.
 *
 * For assistance with handling the uploaded file correctly, see the API
 * provided by file.inc.
 */
function form_file($title, $name, $size, $description = NULL, $required = FALSE) {
  return theme('form_element', $title, '<input type="file" class="'. _form_get_class('form-file', $required, _form_get_error($name)) .'" name="edit['. $name .']" id="edit-'. $name .'" size="'. $size ."\" />\n", $description, 'edit-'. $name, $required, _form_get_error($error));
}

/**
 * Store data in a hidden form field.
 *
 * @param $name
 *   The internal name used to refer to the field.
 * @param $value
 *   The stored data.
 * @return
 *   A themed HTML string representing the hidden field.
 *
 * This function can be useful in retaining information between page requests,
 * but be sure to validate the data on the receiving page as it is possible for
 * an attacker to change the value before it is submitted.
 */
function form_hidden($name, $value) {
  return '<input type="hidden" name="edit['. $name .']" value="'. check_form($value) ."\" />\n";
}

/**
 * Format an action button.
 *
 * @param $value
 *   Both the label for the button, and the value passed to the target page
 *   when this button is clicked.
 * @param $name
 *   The internal name used to refer to the button.
 * @param $type
 *   What type to pass to the HTML input tag.
 * @param $attributes
 *   An associative array of HTML attributes to add to the form item.
 * @return
 *   A themed HTML string representing the button.
 */
function form_button($value, $name = 'op', $type = 'submit', $attributes = NULL) {
  return '<input type="'. $type .'" class="form-'. $type .'" name="'. $name .'" value="'. check_form($value) .'" '. drupal_attributes($attributes) ." />\n";
}

/**
 * Format a form submit button.
 *
 * @param $value
 *   Both the label for the button, and the value passed to the target page
 *   when this button is clicked.
 * @param $name
 *   The internal name used to refer to the button.
 * @param $attributes
 *   An associative array of HTML attributes to add to the form item.
 * @return
 *   A themed HTML string representing the button.
 */
function form_submit($value, $name = 'op', $attributes = NULL) {
  return form_button($value, $name, 'submit', $attributes);
}

/**
 * Format a weight selection menu.
 *
 * @param $title
 *   The label for the form element.
 * @param $name
 *   The internal name used to refer to the form element.
 * @param $value
 *   The selected weight value at page load time.
 * @param $delta
 *   The largest in absolute value the weight can be. For example, if set to 10,
 *   weights could range from -10 to 10 inclusive.
 * @param $description
 *   Explanatory text to display after the form item.
 * @param $extra
 *   Additional HTML to inject into the select element tag.
 * @return
 *   A themed HTML string representing the form element.
 */
function form_weight($title = NULL, $name = 'weight', $value = 0, $delta = 10, $description = NULL, $extra = 0) {
  for ($n = (-1 * $delta); $n <= $delta; $n++) {
    $weights[$n] = $n;
  }

  return form_select($title, $name, $value, $weights, $description, $extra);
}

/**
 * @} End of "defgroup form".
 */

/**
 * Generate an internal Drupal URL.
 *
 * @param $path
 *   The Drupal path being linked to, such as "admin/node".
 * @param $query
 *   A query string to append to the link.
 * @param $fragment
 *   A fragment identifier (named anchor) to append to the link.
 * @param $absolute
 *   Whether to force the output to be an absolute link (beginning with http:).
 *   Useful for links that will be displayed outside the site, such as in an RSS feed.
 * @return
 *   an HTML string containing a link to the given path.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 */
function url($path = NULL, $query = NULL, $fragment = NULL, $absolute = FALSE) {
  global $base_url;

  static $script;

  if (empty($script)) {
    // On some web servers, such as IIS, we can't omit "index.php".  So, we
    // generate "index.php?q=foo" instead of "?q=foo" on anything that is not
    // Apache.
    $script = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false) ? 'index.php' : '';
  }

  $path = drupal_get_path_alias($path);

  if (isset($fragment)) {
    $fragment = '#'. $fragment;
  }

  $base = ($absolute ? $base_url . '/' : '');

  if (variable_get('clean_url', '0') == '0') {
    if (isset($path)) {
      if (isset($query)) {
        return $base . $script .'?q='. $path .'&amp;'. $query . $fragment;
      }
      else {
        return $base . $script .'?q='. $path . $fragment;
      }
    }
    else {
      if (isset($query)) {
        return $base . $script .'?'. $query . $fragment;
      }
      else {
        return $base . $fragment;
      }
    }
  }
  else {
    if (isset($path)) {
      if (isset($query)) {
        return $base . $path .'?'. $query . $fragment;
      }
      else {
        return $base . $path . $fragment;
      }
    }
    else {
      if (isset($query)) {
        return $base . $script .'?'. $query . $fragment;
      }
      else {
        return $base . $fragment;
      }
    }
  }
}

/**
 * Format an attribute string to insert in a tag.
 *
 * @param $attributes
 *   An associative array of HTML attributes.
 * @return
 *   An HTML string ready for insertion in a tag.
 */
function drupal_attributes($attributes = array()) {
  if ($attributes) {
    $t = array();
    foreach ($attributes as $key => $value) {
      $t[] = $key .'="'. $value .'"';
    }

    return ' '. implode($t, ' ');
  }
}

/**
 * Format an internal Drupal link.
 *
 * This function correctly handles aliased paths, and allows themes to highlight
 * links to the current page correctly, so all internal links output by modules
 * should be generated by this function if possible.
 *
 * @param $text
 *   The text to be enclosed with the anchor tag.
 * @param $path
 *   The Drupal path being linked to, such as "admin/node".
 * @param $attributes
 *   An associative array of HTML attributes to apply to the anchor tag.
 * @param $query
 *   A query string to append to the link.
 * @param $fragment
 *   A fragment identifier (named anchor) to append to the link.
 * @param $absolute
 *   Whether to force the output to be an absolute link (beginning with http:).
 *   Useful for links that will be displayed outside the site, such as in an RSS feed.
 * @return
 *   an HTML string containing a link to the given path.
 */
function l($text, $path, $attributes = array(), $query = NULL, $fragment = NULL, $absolute = FALSE) {
  if (drupal_get_normal_path($path) == $_GET['q']) {
    if (isset($attributes['class'])) {
      $attributes['class'] .= ' active';
    }
    else {
      $attributes['class'] = 'active';
    }
  }
  return '<a href="'. url($path, $query, $fragment, $absolute) .'"'. drupal_attributes($attributes) .'>'. $text .'</a>';
}

function field_get($string, $name) {
  ereg(",$name=([^,]+)", ",$string", $regs);
  return $regs[1];
}

function field_set($string, $name, $value) {
  $rval = ereg_replace(",$name=[^,]+", "", ",$string");
  if (isset($value)) {
    $rval .= ($rval == ',' ? '' : ',') . $name .'='. $value;
  }
  return substr($rval, 1);
}

/**
 * Fetch a set of global navigation links.
 *
 * The links are gathered by calls to hook_link('page') in each module.
 */
function link_page() {
  global $custom_links;

  if (is_array($custom_links)) {
    return $custom_links;
  }
  else {
    $links = module_invoke_all('link', 'page');
    array_unshift($links, l(t('home'), '', array('title' => t('Return to the main page.'))));
    return $links;
  }
}

/**
 * Fetch a set of links to display after a given node.
 *
 * The links are gathered by calls to hook_link('node') in each module.
 */
function link_node($node, $main = 0) {
  return module_invoke_all('link', 'node', $node, $main);
}

/**
 * Perform end-of-request tasks.
 *
 * This function sets the page cache if appropriate, and allows modules to
 * react to the closing of the page by calling hook_exit().
 */
function drupal_page_footer() {
  if (variable_get('cache', 0)) {
    page_set_cache();
  }

  module_invoke_all('exit');
}

/**
 * Form an associative array from a linear array.
 *
 * This function walks through the provided array and constructs an associative
 * array out of it. The keys of the resulting array will be the values of the
 * input array. The values will be the same as the keys unless a function is
 * specified, in which case the output of the function is used for the values
 * instead.
 *
 * @param $array
 *   A linear array.
 * @param $function
 *   The name of a function to apply to all values before output.
 * @result
 *   An associative array.
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
 * Prepare a new XML parser.
 *
 * This is a wrapper around xml_parser_create() which extracts the encoding from
 * the XML data first and sets the output encoding to UTF-8. This function should
 * be used instead of xml_parser_create(), because PHP's XML parser doesn't check
 * the input encoding itself.
 *
 * This is also where unsupported encodings should be converted.
 * Callers should take this into account: $data might have been changed after
 * the call.
 *
 * @param &$data
 *   The XML data which will be parsed later.
 * @return
 *   An XML parser object.
 */
function drupal_xml_parser_create(&$data) {
  $encoding = 'utf-8';
  if (ereg('^<\?xml[^>]+encoding="([^"]+)"', $data, $match)) {
    $encoding = $match[1];
  }

  // Unsupported encodings are converted here into UTF-8.
  // Requires the iconv, GNU recode or mbstring PHP extension.
  $php_supported = array('utf-8', 'iso-8859-1', 'us-ascii');
  if (!in_array(strtolower($encoding), $php_supported)) {
    if (function_exists('iconv')) {
      $out = @iconv($encoding, 'utf-8', $data);
    }
    else if (function_exists('mb_convert_encoding')) {
      $out = @mb_convert_encoding($data, 'utf-8', $encoding);
    }
    else if (function_exists('recode_string')) {
      $out = @recode_string($encoding . '..utf-8', $data);
    }
    else {
      watchdog('warning', t("Unsupported XML encoding '%s'. Please install iconv, GNU recode or mbstring for PHP.", $encoding));
      return 0;
    }

    if ($out !== false) {
      $data = $out;
      $encoding = 'utf-8';
    }
    else {
      watchdog('warning', t("Could not convert XML encoding '%s' to UTF-8.", $encoding));
      return 0;
    }
  }

  $xml_parser = xml_parser_create($encoding);
  xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
  return $xml_parser;
}

/**
 * Truncate a UTF-8-encoded string safely.
 *
 * If the end position is in the middle of a UTF-8 sequence, it scans backwards
 * until the beginning of the byte sequence.
 *
 * Use this function whenever you want to chop off a string at an unsure
 * location. On the other hand, if you're sure that you're splitting on a
 * character boundary (e.g. after using strpos() or similar), you can safely use
 * substr() instead.
 *
 * @param $string
 *   The string to truncate.
 * @param $len
 *   An upper limit on the returned string length.
 * @return
 *   The truncated string.
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

/**
 * Encodes MIME/HTTP header values that contain non US-ASCII characters.
 *
 * For example, mime_header_encode('tÃ©st.txt') returns "=?UTF-8?B?dMOpc3QudHh0?=".
 *
 * See http://www.rfc-editor.org/rfc/rfc2047.txt for more information.
 *
 * Notes:
 * - Only encode strings that contain non-ASCII characters.
 * - The chunks come in groupings of 4 bytes when using base64 encoding.
 * - trim() is used to ensure that no extra spacing is added by chunk_split() or
 *   preg_replace().
 * - Using \n as the chunk separator may cause problems on some systems and may
 *   have to be changed to \r\n or \r.
 */
function mime_header_encode($string, $charset = 'UTF-8') {
  if (preg_match('/[^\x20-\x7E]/', $string)) {
    $chunk_size = 75 - 7 - strlen($charset);
    $chunk_size -= $chunk_size % 4;
    $string = trim(chunk_split(base64_encode($string), $chunk_size, "\n"));
    $string = trim(preg_replace('/^(.*)$/m', " =?$charset?B?\\1?=", $string));
  }
  return $string;
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
  else {
    // Decimal numerical entity (strip leading zeros to avoid PHP octal notation)
    $codepoint = preg_replace('/^0+/', '', $codepoint);
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
 * Evaluate a string of PHP code.
 *
 * This is a wrapper around PHP's eval(). It uses output buffering to capture both
 * returned and printed text. Unlike eval(), we require code to be surrounded by
 * <?php ?> tags; in other words, we evaluate the code as if it were a stand-alone
 * PHP file.
 *
 * Using this wrapper also ensures that the PHP code which is evaluated can not
 * overwrite any variables in the calling code, unlike a regular eval() call.
 *
 * @param $code
 *   The code to evaluate.
 * @return
 *   A string containing the printed output of the code, followed by the returned
 *   output of the code.
 */
function drupal_eval($code) {
  ob_start();
  print eval('?>'. $code);
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}

include_once 'includes/theme.inc';
include_once 'includes/pager.inc';
include_once 'includes/menu.inc';
include_once 'includes/tablesort.inc';
include_once 'includes/file.inc';
include_once 'includes/xmlrpc.inc';

// Set the Drupal custom error handler.
set_error_handler('error_handler');

// Emit the correct charset HTTP header.
drupal_set_header('Content-Type: text/html; charset=utf-8');

// Initialize $_GET['q'] prior to loading modules and invoking hook_init().
if (!empty($_GET['q'])) {
  $_GET['q'] = drupal_get_normal_path(trim($_GET['q'], '/'));
}
else {
  $_GET['q'] = drupal_get_normal_path(variable_get('site_frontpage', 'node'));
}

// Initialize all enabled modules.
module_init();

// Initialize the localization system.
$locale = locale_initialize();

// Initialize the enabled theme.
$theme = init_theme();

?>
