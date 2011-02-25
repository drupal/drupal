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

/**
 * Add output to the head tag of the HTML page.
 * This function can be called as long the headers aren't sent.
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
 * Prepare a destination query string for use in combination with
 * drupal_goto().  Used to direct the user back to the referring page
 * after completing a form.
 *
 * @see drupal_goto()
 */
function drupal_get_destination() {
  $destination[] = $_GET['q'];
  $params = array('from', 'sort', 'order');
  foreach ($params as $param) {
    if (isset($_GET[$param])) {
      $destination[] = "$param=". $_GET[$param];
    }
  }
  return 'destination='. urlencode(implode('&', $destination));
}

/**
 * Send the user to a different Drupal page.
 *
 * This issues an on-site HTTP redirect. The function makes sure the redirected
 * URL is formatted correctly.
 *
 * Usually the redirected URL is constructed from this function's input
 * parameters.  However you may override that behavior by setting a
 * <em>destination</em> in either the $_REQUEST-array (i.e. by using
 * the query string of an URI) or the $_REQUEST['edit']-array (i.e. by
 * using a hidden form field).  This is used to direct the user back to
 * the proper page after completing a form.  For example, after editing
 * a post on the 'admin/node'-page or after having logged on using the
 * 'user login'-block in a sidebar.  The function drupal_get_destination()
 * can be used to help set the destination URL.
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
 *
 * @see drupal_get_destination()
 */
function drupal_goto($path = '', $query = NULL, $fragment = NULL) {
  if ($_REQUEST['destination']) {
    extract(parse_url($_REQUEST['destination']));
  }
  else if ($_REQUEST['edit']['destination']) {
    extract(parse_url($_REQUEST['edit']['destination']));
  }

  $url = url($path, $query, $fragment, TRUE);

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
  drupal_set_header('HTTP/1.0 404 Not Found');
  watchdog('page not found', t('%page not found.', array('%page' => theme('placeholder', $_GET['q']))), WATCHDOG_WARNING);

  $path = drupal_get_normal_path(variable_get('site_404', ''));
  $status = MENU_NOT_FOUND;
  if ($path  && $path != $_GET['q']) {
    menu_set_active_item($path);
    $status = menu_execute_active_handler();
  }

  if ($status != MENU_FOUND) {
    drupal_set_title(t('Page not found'));
    print theme('page', '');
  }
}

/**
 * Generates a 403 error if the request is not allowed.
 */
function drupal_access_denied() {
  drupal_set_header('HTTP/1.0 403 Forbidden');
  watchdog('access denied', t('%page denied access.', array('%page' => theme('placeholder', $_GET['q']))), WATCHDOG_WARNING, l(t('view'), $_GET['q']));

  $path = drupal_get_normal_path(variable_get('site_403', ''));
  $status = MENU_NOT_FOUND;
  if ($path  && $path != $_GET['q']) {
    menu_set_active_item($path);
    $status = menu_execute_active_handler();
  }

  if ($status != MENU_FOUND) {
    drupal_set_title(t('Access denied'));
    print theme('page', message_access());
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
  $result = new StdClass();

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
function error_handler($errno, $message, $filename, $line) {
  if ($errno & (E_ALL ^ E_NOTICE)) {
    $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning');
    $entry = $types[$errno] .': '. $message .' in '. $filename .' on line '. $line .'.';

    if (variable_get('error_level', 1) == 1) {
      print '<pre>'. $entry .'</pre>';
    }

    watchdog('php', t('%message in %file on line %line.', array('%error' => $types[$errno], '%message' => $message, '%file' => $filename, '%line' => $line)), WATCHDOG_ERROR);
  }
}

function _fix_gpc_magic(&$item) {
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
    $object = new StdClass();
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
  $allowed_characters = '[a-z0-9\/:_\-_\.\?\$,~=#&%\+]';
  if ($absolute) {
    return preg_match("/^(http|https|ftp):\/\/". $allowed_characters ."+$/i", $url);
  }
  else {
    return preg_match("/^". $allowed_characters ."+$/i", $url);
  }
}

/**
 * Register an event for the current visitor (hostname/IP) to the flood control mechanism.
 *
 * @param $name
 *   The name of the event.
 */
function flood_register_event($name) {
  db_query("INSERT INTO {flood} (event, hostname, timestamp) VALUES ('%s', '%s', %d)", $name, $_SERVER['REMOTE_ADDR'], time());
}

/**
 * Check if the current visitor (hostname/IP) is allowed to proceed with the specified event.
 * The user is allowed to proceed if he did not trigger the specified event more than
 * $threshold times per hour.
 *
 * @param $name
 *   The name of the event.
 * @param $number
 *   The maximum number of the specified event per hour (per visitor).
 * @return
 *   True if the user did not exceed the hourly threshold.  False otherwise.
 */
function flood_is_allowed($name, $threshold) {
  $number = db_num_rows(db_query("SELECT event FROM {flood} WHERE event = '%s' AND hostname = '%s' AND timestamp > %d", $name, $_SERVER['REMOTE_ADDR'], time() - 3600));
  return ($number < $threshold ? TRUE : FALSE);
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
  $output .= ' <title>'. check_plain($title) ."</title>\n";
  $output .= ' <link>'. check_url($link) ."</link>\n";
  $output .= ' <description>'. check_plain($description) ."</description>\n";
  $output .= ' <language>'. check_plain($language) ."</language>\n";
  foreach ($args as $key => $value) {
    $output .= ' <'. $key .'>'. check_plain($value) ."</$key>\n";
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
  $output .= ' <title>'. check_plain($title) ."</title>\n";
  $output .= ' <link>'. check_url($link) ."</link>\n";
  $output .= ' <description>'. check_plain($description) ."</description>\n";
  foreach ($args as $key => $value) {
    if (is_array($value)) {
      if ($value['key']) {
        $output .= ' <'. $value['key'];
        if (is_array($value['attributes'])) {
          $output .= drupal_attributes($value['attributes']);
        }

        if ($value['value']) {
          $output .= '>'. $value['value'] .'</'. $value['key'] .">\n";
        }
        else {
          $output .= " />\n";
        }
      }
    }
    else {
      $output .= ' <'. $key .'>'. check_plain($value) ."</$key>\n";
    }
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
 *   A PHP date format string as required by date(). A backslash should be used
 *   before a character to avoid interpreting the character as part of a date
 *   format.
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
    else if ($c == '\\') {
      $date .= $format[++$i];
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

    if (user_access('access user profiles')) {
      $output = l($name, 'user/'. $object->uid, array('title' => t('View user profile.')));
    }
    else {
      $output = check_plain($name);
    }
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
 * Check the form token if there is POST data for an authenticated user to defend against cross site request forgeries. 
 * 
 * $_POST will be cleared if the token is absent or incorrect.
 * 
 */
function drupal_check_token() {
  global $user; 
  if ($user->uid && ($_SERVER['REQUEST_METHOD'] == 'POST') && !(isset($_POST['edit']) && isset($_POST['edit']['token']) && drupal_valid_token($_POST['edit']['token']))) {
    drupal_set_message(t('Validation error. Please try again.'), 'error');
    $_POST = array();
  }
}

/**
 * Ensure the private key variable used to generate tokens is set.
 * 
 * @return 
 *   The private key
 */
function drupal_get_private_key() {   
  if (!($key = variable_get('drupal_private_key', 0))) {
    $key = mt_rand();
    variable_set('drupal_private_key', $key);
  }
  return $key;
}

/**
 * Generate a token based on $value, the current user session and private key.
 * 
 * @param $value
 *   An additional value to base the token on
 */
function drupal_get_token($value = '') {
  $private_key = drupal_get_private_key();
  return md5(session_id() . $value . $private_key);
}

/**
 * Validate a token based on $value, the current user session and private key or penultimate private key.
 * 
 * @param $token
 *   The token to be validated.
 * @param $value
 *   An additional value to base the token on
 * @return 
 *   True for a valid token, False for an invalid token.
 */
function drupal_valid_token($token, $value = '') {  
  return ($token == md5(session_id() . $value . variable_get('drupal_private_key', '')));    
}



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
 * Generate a form token based on the session and the private key to defend against cross site request forgeries.
 */
function form_token() { 
  return form_hidden('token', drupal_get_token());
}

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
  return '<form action="'. check_url($action) .'" method="'. $method .'"'. drupal_attributes($attributes) .">\n". $form . form_token() ."\n</form>\n";
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
 *   A themed HTML string representing the checkbox set.
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
  return theme('form_element', $title, '<input type="text" maxlength="'. $maxlength .'" class="'. _form_get_class('form-text', $required, _form_get_error($name)) .'" name="edit['. $name .']" id="edit-'. $name .'"'. $size .' value="'. check_plain($value) .'"'. drupal_attributes($attributes) .' />', $description, 'edit-'. $name, $required, _form_get_error($name));
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
  return theme('form_element', $title, '<input type="password" class="'. _form_get_class('form-password', $required, _form_get_error($name)) .'" maxlength="'. $maxlength .'" name="edit['. $name .']" id="edit-'. $name .'"'. $size .' value="'. check_plain($value) .'"'. drupal_attributes($attributes) .' />', $description, 'edit-'. $name, $required, _form_get_error($name));
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
  $pre = '';
  $post = '';

  // optionally plug in a WYSIWYG editor
  foreach (module_list() as $module_name) {
    if (module_hook($module_name, 'textarea')) {
      $pre  .= module_invoke($module_name, 'textarea', 'pre', $name);
      $post .= module_invoke($module_name, 'textarea', 'post', $name);
    }
  }

  return theme('form_element', $title, $pre .'<textarea wrap="virtual" cols="'. check_plain($cols) .'" rows="'. check_plain($rows) .'" name="edit['. $name .']" id="edit-'. $name .'" class="'. _form_get_class('textarea', $required, _form_get_error($name)) .'"'. drupal_attributes($attributes) .'>'. check_plain($value) .'</textarea>'. $post, $description, 'edit-'. $name, $required, _form_get_error($name));
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
  return theme('form_element', $title, '<input type="file" class="'. _form_get_class('form-file', $required, _form_get_error($name)) .'" name="edit['. $name .']" id="edit-'. $name .'" size="'. $size ."\" />\n", $description, 'edit-'. $name, $required, _form_get_error($name));
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
  return '<input type="hidden" name="edit['. $name .']" value="'. check_plain($value) ."\" />\n";
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
  return '<input type="'. $type .'" class="form-'. $type .'" name="'. $name .'" value="'. check_plain($value) .'" '. drupal_attributes($attributes) ." />\n";
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

  $base = ($absolute ? $base_url .'/' : '');

  if (variable_get('clean_url', '0') == '0') {
    if (isset($path)) {
      if (isset($query)) {
        return $base . $script .'?q='. $path .'&'. $query . $fragment;
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
      $t[] = $key .'="'. check_plain($value) .'"';
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
 * @param $html
 *   Whether the title is HTML, or just plain-text.
 * @return
 *   an HTML string containing a link to the given path.
 */
function l($text, $path, $attributes = array(), $query = NULL, $fragment = NULL, $absolute = FALSE, $html = FALSE) {
  if (drupal_get_normal_path($path) == $_GET['q']) {
    if (isset($attributes['class'])) {
      $attributes['class'] .= ' active';
    }
    else {
      $attributes['class'] = 'active';
    }
  }
  return '<a href="'. check_url(url($path, $query, $fragment, $absolute)) .'"'. drupal_attributes($attributes) .'>'. ($html ? $text : check_plain($text)) .'</a>';
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
 * This is also where unsupported encodings will be converted.
 * Callers should take this into account: $data might have been changed after
 * the call.
 *
 * @param &$data
 *   The XML data which will be parsed later.
 * @return
 *   An XML parser object.
 */
function drupal_xml_parser_create(&$data) {
  // Default XML encoding is UTF-8
  $encoding = 'utf-8';
  $bom = false;

  // Check for UTF-8 byte order mark (PHP5's XML parser doesn't handle it).
  if (!strncmp($data, "\xEF\xBB\xBF", 3)) {
    $bom = true;
    $data = substr($data, 3);
  }

  // Check for an encoding declaration in the XML prolog if no BOM was found.
  if (!$bom && ereg('^<\?xml[^>]+encoding="([^"]+)"', $data, $match)) {
    $encoding = $match[1];
  }

  // Unsupported encodings are converted here into UTF-8.
  $php_supported = array('utf-8', 'iso-8859-1', 'us-ascii');
  if (!in_array(strtolower($encoding), $php_supported)) {
    $out = drupal_convert_to_utf8($data, $encoding);
    if ($out !== false) {
      $encoding = 'utf-8';
      $data = ereg_replace('^(<\?xml[^>]+encoding)="([^"]+)"', '\\1="utf-8"', $out);
    }
    else {
      watchdog('php', t("Could not convert XML encoding '%s' to UTF-8.", array('%s' => theme('placeholder', $encoding))), WATCHDOG_WARNING);
      return 0;
    }
  }

  $xml_parser = xml_parser_create($encoding);
  xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
  return $xml_parser;
}

/**
 * Convert data to UTF-8
 *
 * Requires the iconv, GNU recode or mbstring PHP extension.
 *
 * @param $data
 *   The data to be converted.
 * @param $encoding
 *   The encoding that the data is in
 * @return
 *   Converted data or FALSE.
 */
function drupal_convert_to_utf8($data, $encoding) {
  if (function_exists('iconv')) {
    $out = @iconv($encoding, 'utf-8', $data);
  }
  else if (function_exists('mb_convert_encoding')) {
    $out = @mb_convert_encoding($data, 'utf-8', $encoding);
  }
  else if (function_exists('recode_string')) {
    $out = @recode_string($encoding .'..utf-8', $data);
  }
  else {
    watchdog('php', t("Unsupported encoding '%s'. Please install iconv, GNU recode or mbstring for PHP.", array('%s' => $encoding)), WATCHDOG_ERROR);
    return FALSE;
  }

  return $out;
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
 * @param $wordsafe
 *   Flag to truncate at nearest space. Defaults to FALSE.
 * @return
 *   The truncated string.
 */
function truncate_utf8($string, $len, $wordsafe = FALSE) {
  $slen = strlen($string);
  if ($slen <= $len) {
    return $string;
  }
  if ($wordsafe) {
    $end = $len;
    while (($string[--$len] != ' ') && ($len > 0)) {};
    if ($len == 0) {
      $len = $end;
    }
  }
  if ((ord($string[$len]) < 0x80) || (ord($string[$len]) >= 0xC0)) {
    return substr($string, 0, $len);
  }
  while (--$len >= 0 && ord($string[$len]) >= 0x80 && ord($string[$len]) < 0xC0) {};
  return substr($string, 0, $len);
}
/**
 * Encodes MIME/HTTP header values that contain non-ASCII, UTF-8 encoded
 * characters.
 *
 * For example, mime_header_encode('tést.txt') returns "=?UTF-8?B?dMOpc3QudHh0?=".
 *
 * See http://www.rfc-editor.org/rfc/rfc2047.txt for more information.
 *
 * Notes:
 * - Only encode strings that contain non-ASCII characters.
 * - We progressively cut-off a chunk with truncate_utf8(). This is to ensure
 *   each chunk starts and ends on a character boundary.
 * - Using \n as the chunk separator may cause problems on some systems and may
 *   have to be changed to \r\n or \r.
 */
function mime_header_encode($string) {
  if (preg_match('/[^\x20-\x7E]/', $string)) {
    $chunk_size = 47; // floor((75 - strlen("=?UTF-8?B??=")) * 0.75);
    $len = strlen($string);
    $output = '';
    while ($len > 0) {
      $chunk = truncate_utf8($string, $chunk_size);
      $output .= ' =?UTF-8?B?'. base64_encode($chunk) ."?=\n";
      $c = strlen($chunk);
      $string = substr($string, $c);
      $len -= $c;
    }
    return trim($output);
  }
  return $string;
}

/**
 * Decode all HTML entities (including numerical ones) to regular UTF-8 bytes.
 * Double-escaped entities will only be decoded once ("&amp;lt;" becomes "&lt;", not "<").
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
    $table = array_flip(get_html_translation_table(HTML_ENTITIES));
    // PHP gives us ISO-8859-1 data, we need UTF-8.
    $table = array_map('utf8_encode', $table);
    // Add apostrophe (XML)
    $table['&apos;'] = "'";
  }
  $newtable = array_diff($table, $exclude);

  // Use a regexp to select all entities in one pass, to avoid decoding double-escaped entities twice.
  return preg_replace('/&(#x?)?([A-Za-z0-9]+);/e', '_decode_entities("$1", "$2", "$0", $newtable, $exclude)', $text);
}

/**
 * Helper function for decode_entities
 */
function _decode_entities($prefix, $codepoint, $original, &$table, &$exclude) {
  // Named entity
  if (!$prefix) {
    if (isset($table[$original])) {
      return $table[$original];
    }
    else {
      return $original;
    }
  }
  // Hexadecimal numerical entity
  if ($prefix == '#x') {
    $codepoint = base_convert($codepoint, 16, 10);
  }
  else {
    // Decimal numerical entity (strip leading zeros to avoid PHP octal notation)
    $codepoint = preg_replace('/^0+/', '', $codepoint);
  }
  // Encode codepoint as UTF-8 bytes
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
  // Check for excluded characters
  if (in_array($str, $exclude)) {
    return $original;
  }
  else {
    return $str;
  }
}

/**
 * Count the amount of characters in a UTF-8 string. This is less than or
 * equal to the byte count.
 */
function string_length(&$text) {
  return strlen(preg_replace("/[\x80-\xBF]/", '', $text));
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

/**
 * Returns the path to a system item (module, theme, etc.).
 *
 * @param $type
 *   The type of the item (i.e. theme, theme_engine, module).
 * @param $name
 *   The name of the item for which the path is requested.
 *
 * @return
 *   The path to the requested item.
 */
function drupal_get_path($type, $name) {
  return dirname(drupal_get_filename($type, $name));
}

/**
 * Add a <link> tag to the page's HEAD.
 */
function drupal_add_link($attributes) {
  drupal_set_html_head('<link'. drupal_attributes($attributes) ." />\n");
}

/**
 * Provide a substitute clone() function for PHP4.
 */
if (version_compare(phpversion(), '5.0') < 0) {
  eval('
    function clone($object) {
      return $object;
    }
  ');
}

// Set the Drupal custom error handler.
set_error_handler('error_handler');

include_once 'includes/theme.inc';
include_once 'includes/pager.inc';
include_once 'includes/menu.inc';
include_once 'includes/tablesort.inc';
include_once 'includes/file.inc';
include_once 'includes/xmlrpc.inc';
include_once 'includes/image.inc';

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

?>
