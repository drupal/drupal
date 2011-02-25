<?php

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

/**
 * Return status for saving which involved creating a new item.
 */
define('SAVED_NEW', 1);

/**
 * Return status for saving which involved an update to an existing item.
 */
define('SAVED_UPDATED', 2);

/**
 * Return status for saving which deleted an existing item.
 */
define('SAVED_DELETED', 3);

/**
 * Set content for a specified region.
 *
 * @param $region
 *   Page region the content is assigned to.
 *
 * @param $data
 *   Content to be set.
 */
function drupal_set_content($region = null, $data = null) {
  static $content = array();

  if (!is_null($region) && !is_null($data)) {
    $content[$region][] = $data;
  }
  return $content;
}

/**
 * Get assigned content.
 *
 * @param $region
 *   A specified region to fetch content for.  If null, all regions will be returned.
 *
 * @param $delimiter
 *   Content to be inserted between exploded array elements.
 */
function drupal_get_content($region = NULL, $delimiter = ' ') {
  $content = drupal_set_content();
  if (isset($region)) {
    if (isset($content[$region]) && is_array($content[$region])) {
      return implode($delimiter, $content[$region]);
    }
  }
  else {
    foreach (array_keys($content) as $region) {
      if (is_array($content[$region])) {
        $content[$region] = implode($delimiter, $content[$region]);
      }
    }
    return $content;
  }
}

/**
 * Set the breadcrumb trail for the current page.
 *
 * @param $breadcrumb
 *   Array of links, starting with "home" and proceeding up to but not including
 *   the current page.
 */
function drupal_set_breadcrumb($breadcrumb = NULL) {
  static $stored_breadcrumb;

  if (!is_null($breadcrumb)) {
    $stored_breadcrumb = $breadcrumb;
  }
  return $stored_breadcrumb;
}

/**
 * Get the breadcrumb trail for the current page.
 */
function drupal_get_breadcrumb() {
  $breadcrumb = drupal_set_breadcrumb();

  if (is_null($breadcrumb)) {
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
  $output = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
  $output .= theme('stylesheet_import', base_path() .'misc/drupal.css');
  return $output . drupal_set_html_head();
}

/**
 * Reset the static variable which holds the aliases mapped for this request.
 */
function drupal_clear_path_cache() {
  drupal_lookup_path('wipe');
}

/**
 * Set an HTTP response header for the current page.
 *
 * Note: when sending a Content-Type header, always include a 'charset' type
 * too. This is necessary to avoid security bugs (e.g. UTF-7 XSS).
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
 * Parse an array into a valid urlencoded query string.
 *
 * @param $query
 *   The array to be processed e.g. $_GET
 * @param $exclude
 *   The array filled with keys to be excluded. Use parent[child] to exclude nested items.
 * @param $parent
 *   Should not be passed, only used in recursive calls
 * @return
 *   urlencoded string which can be appended to/as the URL query string
 */
function drupal_query_string_encode($query, $exclude = array(), $parent = '') {
  $params = array();

  foreach ($query as $key => $value) {
    $key = drupal_urlencode($key);
    if ($parent) {
      $key = $parent .'['. $key .']';
    }

    if (in_array($key, $exclude)) {
      continue;
    }

    if (is_array($value)) {
      $params[] = drupal_query_string_encode($value, $exclude, $key);
    }
    else {
      $params[] = $key .'='. drupal_urlencode($value);
    }
  }

  return implode('&', $params);
}

/**
 * Prepare a destination query string for use in combination with
 * drupal_goto(). Used to direct the user back to the referring page
 * after completing a form. By default the current URL is returned.
 * If a destination exists in the previous request, that destination
 * is returned.  As such, a destination can persist across multiple
 * pages.
 *
 * @see drupal_goto()
 */
function drupal_get_destination() {
  if (isset($_REQUEST['destination'])) {
    return 'destination='. urlencode($_REQUEST['destination']);
  }
  else {
    // Use $_GET here to retrieve the original path in source form.
    $path = isset($_GET['q']) ? $_GET['q'] : '';
    $query = drupal_query_string_encode($_GET, array('q'));
    if ($query != '') {
      $path .= '?'. $query;
    }
    return 'destination='. urlencode($path);
  }
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
 * This function ends the request; use it rather than a print theme('page')
 * statement in your menu callback.
 *
 * @param $path
 *   A Drupal path or a full URL.
 * @param $query
 *   The query string component, if any.
 * @param $fragment
 *   The destination fragment identifier (named anchor).
 *
 * @see drupal_get_destination()
 */
function drupal_goto($path = '', $query = NULL, $fragment = NULL) {
  if (isset($_REQUEST['destination'])) {
    extract(parse_url(urldecode($_REQUEST['destination'])));
  }
  else if (isset($_REQUEST['edit']['destination'])) {
    extract(parse_url(urldecode($_REQUEST['edit']['destination'])));
  }

  $url = url($path, $query, $fragment, TRUE);
  // Remove newlines from the URL to avoid header injection attacks.
  $url = str_replace(array("\n", "\r"), '', $url);

  // Before the redirect, allow modules to react to the end of the page request.
  module_invoke_all('exit', $url);

  header('Location: '. $url);

  // The "Location" header sends a REDIRECT status code to the http
  // daemon. In some cases this can go wrong, so we make sure none
  // of the code below the drupal_goto() call gets executed when we redirect.
  exit();
}

/**
 * Generates a site off-line message
 */
function drupal_site_offline() {
  drupal_set_header('HTTP/1.0 503 Service unavailable');
  drupal_set_title(t('Site off-line'));
  print theme('maintenance_page', filter_xss_admin(variable_get('site_offline_message',
    t('%site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('%site' => variable_get('site_name', t('This Drupal site')))))));
}

/**
 * Generates a 404 error if the request can not be handled.
 */
function drupal_not_found() {
  drupal_set_header('HTTP/1.0 404 Not Found');
  watchdog('page not found', t('%page not found.', array('%page' => theme('placeholder', $_GET['q']))), WATCHDOG_WARNING);

  // Keep old path for reference
  if (!isset($_REQUEST['destination'])) {
    $_REQUEST['destination'] = $_GET['q'];
  }

  $path = drupal_get_normal_path(variable_get('site_404', ''));
  if ($path && $path != $_GET['q']) {
    menu_set_active_item($path);
    $return = menu_execute_active_handler();
  }
  else {
    // Redirect to a non-existent menu item to make possible tabs disappear.
    menu_set_active_item('');
  }

  if (empty($return)) {
    drupal_set_title(t('Page not found'));
  }
  print theme('page', $return);
}

/**
 * Generates a 403 error if the request is not allowed.
 */
function drupal_access_denied() {
  drupal_set_header('HTTP/1.0 403 Forbidden');
  watchdog('access denied', t('%page denied access.', array('%page' => theme('placeholder', $_GET['q']))), WATCHDOG_WARNING, l(t('view'), $_GET['q']));

  // Keep old path for reference
  if (!isset($_REQUEST['destination'])) {
    $_REQUEST['destination'] = $_GET['q'];
  }

  $path = drupal_get_normal_path(variable_get('site_403', ''));
  if ($path && $path != $_GET['q']) {
    menu_set_active_item($path);
    $return = menu_execute_active_handler();
  }
  else {
    // Redirect to a non-existent menu item to make possible tabs disappear.
    menu_set_active_item('');
  }

  if (empty($return)) {
    drupal_set_title(t('Access denied'));
    $return = t('You are not authorized to access this page.');
  }
  print theme('page', $return);
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
      $port = isset($uri['port']) ? $uri['port'] : 80;
      $host = $uri['host'] . ($port != 80 ? ':'. $port : '');
      $fp = @fsockopen($uri['host'], $port, $errno, $errstr, 15);
      break;
    case 'https':
      // Note: Only works for PHP 4.3 compiled with OpenSSL.
      $port = isset($uri['port']) ? $uri['port'] : 443;
      $host = $uri['host'] . ($port != 443 ? ':'. $port : '');
      $fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, 20);
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
  $path = isset($uri['path']) ? $uri['path'] : '/';
  if (isset($uri['query'])) {
    $path .= '?'. $uri['query'];
  }

  // Create HTTP request.
  $defaults = array(
    // RFC 2616: "non-standard ports MUST, default ports MAY be included".
    // We don't add the port to prevent from breaking rewrite rules checking
    // the host that do not take into account the port number.
    'Host' => "Host: $host",
    'User-Agent' => 'User-Agent: Drupal (+http://drupal.org/)',
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
  while (!feof($fp) && $chunk = fread($fp, 1024)) {
    $response .= $chunk;
  }
  fclose($fp);

  // Parse response.
  list($split, $result->data) = explode("\r\n\r\n", $response, 2);
  $split = preg_split("/\r\n|\n|\r/", $split);

  list($protocol, $code, $text) = explode(' ', trim(array_shift($split)), 3);
  $result->headers = array();

  // Parse headers.
  while ($line = trim(array_shift($split))) {
    list($header, $value) = explode(':', $line, 2);
    if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
      // RFC 2109: the Set-Cookie response header comprises the token Set-
      // Cookie:, followed by a comma-separated list of one or more cookies.
      $result->headers[$header] .= ','. trim($value);
    }
    else {
      $result->headers[$header] = trim($value);
    }
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
 *  0 = Log errors to database.
 *  1 = Log errors to database and to screen.
 */
function error_handler($errno, $message, $filename, $line) {
  // If the @ error suppression operator was used, error_reporting is temporarily set to 0
  if (error_reporting() == 0) {
    return;
  }

  if ($errno & (E_ALL ^ E_NOTICE)) {
    $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning');
    $entry = $types[$errno] .': '. $message .' in '. $filename .' on line '. $line .'.';

    // Force display of error messages in update.php
    if (variable_get('error_level', 1) == 1 || strstr($_SERVER['SCRIPT_NAME'], 'update.php')) {
      drupal_set_message($entry, 'error');
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
 * Helper function to strip slashes from $_FILES skipping over the tmp_name keys
 * since PHP generates single backslashes for file paths on Windows systems.
 *
 * tmp_name does not have backslashes added see
 * http://us2.php.net/manual/en/features.file-upload.php#42280
 */
function _fix_gpc_magic_files(&$item, $key) {
  if ($key != 'tmp_name') {
    if (is_array($item)) {
      array_walk($item, '_fix_gpc_magic_files');
    }
    else {
      $item = stripslashes($item);
    }
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
    array_walk($_FILES, '_fix_gpc_magic_files');
    $fixed = true;
  }
}

/**
 * @name Messages
 * @{
 * Frequently used messages.
 */

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
  if ($user->uid && isset($languages[$user->language])) {
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
 *   A string containing an e-mail address.
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
  $allowed_characters = '[a-z0-9\/:_\-_\.\?\$,;~=#&%\+]';
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

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $output .= ' <description>'. check_plain(decode_entities(strip_tags($description))) ."</description>\n";
  $output .= ' <language>'. check_plain($language) ."</language>\n";
  $output .= format_xml_elements($args);
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
  $output .= format_xml_elements($args);
  $output .= "</item>\n";

  return $output;
}

/**
 * Format XML elements.
 *
 * @param $array
 *   An array where each item represent an element and is either a:
 *   - (key => value) pair (<key>value</key>)
 *   - Associative array with fields:
 *     - 'key': element name
 *     - 'value': element contents
 *     - 'attributes': associative array of element attributes
 *
 * In both cases, 'value' can be a simple string, or it can be another array
 * with the same format as $array itself for nesting.
 */
function format_xml_elements($array) {
  foreach ($array as $key => $value) {
    if (is_numeric($key)) {
      if ($value['key']) {
        $output .= ' <'. $value['key'];
        if (isset($value['attributes']) && is_array($value['attributes'])) {
          $output .= drupal_attributes($value['attributes']);
        }

        if ($value['value'] != '') {
          $output .= '>'. (is_array($value['value']) ? format_xml_elements($value['value']) : check_plain($value['value'])) .'</'. $value['key'] .">\n";
        }
        else {
          $output .= " />\n";
        }
      }
    }
    else {
      $output .= ' <'. $key .'>'. (is_array($value) ? format_xml_elements($value) : check_plain($value)) ."</$key>\n";
    }
  }
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
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count) : -1;
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
  if ($size >= 1024) {
    $size = round($size / 1024, 2);
    $suffix = t('KB');
  }
  if ($size >= 1024) {
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
  if (!isset($timezone)) {
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
    $c = $format[$i];
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
 * @} End of "defgroup format".
 */

/**
 * Generate a URL from a Drupal menu path. Will also pass-through existing URLs.
 *
 * @param $path
 *   The Drupal path being linked to, such as "admin/node", or an existing URL
 *   like "http://drupal.org/".
 * @param $query
 *   A query string to append to the link or URL.
 * @param $fragment
 *   A fragment identifier (named anchor) to append to the link. If an existing
 *   URL with a fragment identifier is used, it will be replaced. Note, do not
 *   include the '#'.
 * @param $absolute
 *   Whether to force the output to be an absolute link (beginning with http:).
 *   Useful for links that will be displayed outside the site, such as in an
 *   RSS feed.
 * @return
 *   a string containing a URL to the given path.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 */
function url($path = NULL, $query = NULL, $fragment = NULL, $absolute = FALSE) {
  if (isset($fragment)) {
    $fragment = '#'. $fragment;
  }

  // Return an external link if $path contains an allowed absolute URL.
  // Only call the slow filter_xss_bad_protocol if $path contains a ':' before any / ? or #.
  $colonpos = strpos($path, ':');
  if ($colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && filter_xss_bad_protocol($path, FALSE) == check_plain($path)) {
    // Split off the fragment
    if (strpos($path, '#') !== FALSE) {
      list($path, $old_fragment) = explode('#', $path, 2);
      if (isset($old_fragment) && !isset($fragment)) {
        $fragment = '#'. $old_fragment;
      }
    }
    // Append the query
    if (isset($query)) {
      $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . $query;
    }
    // Reassemble
    return $path . $fragment;
  }

  global $base_url;
  static $script;
  static $clean_url;

  if (empty($script)) {
    // On some web servers, such as IIS, we can't omit "index.php".  So, we
    // generate "index.php?q=foo" instead of "?q=foo" on anything that is not
    // Apache.
    $script = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false) ? 'index.php' : '';
  }

  // Cache the clean_url variable to improve performance.
  if (!isset($clean_url)) {
    $clean_url = (bool)variable_get('clean_url', '0');
  }

  $base = ($absolute ? $base_url . '/' : base_path());

  // The special path '<front>' links to the default front page.
  if (!empty($path) && $path != '<front>') {
    $path = drupal_get_path_alias($path);
    $path = drupal_urlencode($path);
    if (!$clean_url) {
      if (isset($query)) {
        return $base . $script .'?q='. $path .'&'. $query . $fragment;
      }
      else {
        return $base . $script .'?q='. $path . $fragment;
      }
    }
    else {
      if (isset($query)) {
        return $base . $path .'?'. $query . $fragment;
      }
      else {
        return $base . $path . $fragment;
      }
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

/**
 * Format an attribute string to insert in a tag.
 *
 * @param $attributes
 *   An associative array of HTML attributes.
 * @return
 *   An HTML string ready for insertion in a tag.
 */
function drupal_attributes($attributes = array()) {
  if (is_array($attributes)) {
    $t = '';
    foreach ($attributes as $key => $value) {
      $t .= " $key=".'"'. check_plain($value) .'"';
    }
    return $t;
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
 *   The Drupal path being linked to, such as "admin/node". Can be an external
 *   or internal URL.
 *     - If you provide the full URL, it will be considered an
 *   external URL.
 *     - If you provide only the path (e.g. "admin/node"), it is considered an
 *   internal link. In this case, it must be a system URL as the url() function
 *   will generate the alias.
 * @param $attributes
 *   An associative array of HTML attributes to apply to the anchor tag.
 * @param $query
 *   A query string to append to the link.
 * @param $fragment
 *   A fragment identifier (named anchor) to append to the link.
 * @param $absolute
 *   Whether to force the output to be an absolute link (beginning with http:).
 *   Useful for links that will be displayed outside the site, such as in an RSS
 *   feed.
 * @param $html
 *   Whether the title is HTML, or just plain-text. For example for making an
 *   image a link, this must be set to TRUE, or else you will see the encoded
 *   HTML.
 * @return
 *   an HTML string containing a link to the given path.
 */
function l($text, $path, $attributes = array(), $query = NULL, $fragment = NULL, $absolute = FALSE, $html = FALSE) {
  if ($path == $_GET['q']) {
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
 * Returns the base URL path of the Drupal installation.
 * At the very least, this will always default to /.
 */
function base_path() {
  return $GLOBALS['base_path'];
}

/**
 * Provide a substitute clone() function for PHP4.
 */
function drupal_clone($object) {
  return version_compare(phpversion(), '5.0') < 0 ? $object : clone($object);
}

/**
 * Add a <link> tag to the page's HEAD.
 */
function drupal_add_link($attributes) {
  drupal_set_html_head('<link'. drupal_attributes($attributes) ." />\n");
}

/**
 * Add a JavaScript file to the output.
 *
 * The first time this function is invoked per page request,
 * it adds "misc/drupal.js" to the output. Other scripts
 * depends on the 'killswitch' inside it.
 */
function drupal_add_js($file, $nocache = FALSE) {
  static $sent = array();

  $postfix = $nocache ? '?'. time() : '';
  if (!isset($sent['misc/drupal.js'])) {
    drupal_set_html_head('<script type="text/javascript" src="'. base_path() .'misc/drupal.js'. $postfix .'"></script>');
    $sent['misc/drupal.js'] = true;
  }
  if (!isset($sent[$file])) {
    drupal_set_html_head('<script type="text/javascript" src="'. check_url(base_path() . $file) . $postfix .'"></script>');
    $sent[$file] = true;
  }
}

/**
 * Generates a Javascript call, while importing the arguments as is.
 * PHP arrays are turned into JS objects to preserve keys. This means the array
 * keys must conform to JS's member naming rules.
 *
 * @param $function
 *   The name of the function to call.
 * @param $arguments
 *   An array of arguments.
 */
function drupal_call_js($function) {
  $arguments = func_get_args();
  array_shift($arguments);
  $args = array();
  foreach ($arguments as $arg) {
    $args[] = drupal_to_js($arg);
  }
  $output = '<script type="text/javascript">'. $function .'('. implode(', ', $args) .');</script>';
  return $output;
}

/**
 * Converts a PHP variable into its Javascript equivalent.
 *
 * We use HTML-safe strings, i.e. with <, > and & escaped.
 */
function drupal_to_js($var) {
  switch (gettype($var)) {
    case 'boolean':
      return $var ? 'true' : 'false'; // Lowercase necessary!
    case 'integer':
    case 'double':
      return $var;
    case 'resource':
    case 'string':
      return '"'. str_replace(array("\r", "\n", "<", ">", "&"),
                              array('\r', '\n', '\x3c', '\x3e', '\x26'),
                              addslashes($var)) .'"';
    case 'array':
      // Arrays in JSON can't be associative. If the array is empty or if it
      // has sequential whole number keys starting with 0, it's not associative
      // so we can go ahead and convert it as an array.
      if (empty($var) || array_keys($var) === range(0, sizeof($var) - 1)) {
        $output = array();
        foreach($var as $v) {
          $output[] = drupal_to_js($v);
        }
        return '[ '. implode(', ', $output) .' ]';
      }
      // Otherwise, fall through to convert the array as an object.
    case 'object':
      $output = array();
      foreach ($var as $k => $v) {
        $output[] = drupal_to_js(strval($k)) .': '. drupal_to_js($v);
      }
      return '{ '. implode(', ', $output) .' }';
    default:
      return 'null';
  }
}

/**
 * Wrapper around urlencode() which avoids Apache quirks.
 *
 * Should be used when placing arbitrary data in an URL. Note that Drupal paths
 * are urlencoded() when passed through url() and do not require urlencoding()
 * of individual components.
 *
 * Notes:
 * - For esthetic reasons, we do not escape slashes. This also avoids a 'feature'
 *   in Apache where it 404s on any path containing '%2F'.
 * - mod_rewrite unescapes %-encoded ampersands, hashes, and slashes when clean
 *   URLs are used, which are interpreted as delimiters by PHP. These
 *   characters are double escaped so PHP will still see the encoded version.
 * - With clean URLs, Apache changes '//' to '/', so every second slash is
 *   double escaped.
 *
 * @param $text
 *   String to encode
 */
function drupal_urlencode($text) {
  if (variable_get('clean_url', '0')) {
    return str_replace(array('%2F', '%26', '%23', '//'),
                       array('/', '%2526', '%2523', '/%252F'),
                       urlencode($text));
  }
  else {
    return str_replace('%2F', '/', urlencode($text));
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
 * Validate a token based on $value, the current user session and private key.
 *
 * @param $token
 *   The token to be validated.
 * @param $value
 *   An additional value to base the token on.
 * @param $skip_anonymous
 *   Set to true to skip token validation for anonymous users.
 * @return
 *   True for a valid token, false for an invalid token. When $skip_anonymous is true, the return value will always be true for anonymous users.
 */
function drupal_valid_token($token, $value = '', $skip_anonymous = FALSE) {
  global $user;
  return (($skip_anonymous && $user->uid == 0) || ($token == md5(session_id() . $value . variable_get('drupal_private_key', ''))));
}

/**
 * Performs one or more XML-RPC request(s).
 *
 * @param $url
 *   An absolute URL of the XML-RPC endpoint.
 *     Example:
 *     http://www.domain.com/xmlrpc.php
 * @param ...
 *   For one request:
 *     The method name followed by a variable number of arguments to the method.
 *   For multiple requests (system.multicall):
 *     An array of call arrays. Each call array follows the pattern of the single
 *     request: method name followed by the arguments to the method.
 * @return
 *   For one request:
 *     Either the return value of the method on success, or FALSE.
 *     If FALSE is returned, see xmlrpc_errno() and xmlrpc_error_msg().
 *   For multiple requests:
 *     An array of results. Each result will either be the result
 *     returned by the method called, or an xmlrpc_error object if the call
 *     failed. See xmlrpc_error().
 */
function xmlrpc($url) {
  require_once './includes/xmlrpc.inc';
  $args = func_get_args();
  return call_user_func_array('_xmlrpc', $args);
}

function _drupal_bootstrap_full() {
  static $called;
  global $locale;

  if ($called) {
    return;
  }
  $called = 1;
  require_once './includes/theme.inc';
  require_once './includes/pager.inc';
  require_once './includes/menu.inc';
  require_once './includes/tablesort.inc';
  require_once './includes/file.inc';
  require_once './includes/unicode.inc';
  require_once './includes/image.inc';
  require_once './includes/form.inc';
  // Set the Drupal custom error handler.
  set_error_handler('error_handler');
  // Emit the correct charset HTTP header.
  drupal_set_header('Content-Type: text/html; charset=utf-8');
  // Detect string handling method
  unicode_check();
  // Undo magic quotes
  fix_gpc_magic();
  // Load all enabled modules
  module_load_all();
  // Initialize the localization system. Depends on i18n.module being loaded already.
  $locale = locale_initialize();
  // Let all modules take action before menu system handles the reqest
  module_invoke_all('init');

}

/**
 * Store the current page in the cache.
 *
 * We try to store a gzipped version of the cache. This requires the
 * PHP zlib extension (http://php.net/manual/en/ref.zlib.php).
 * Presence of the extension is checked by testing for the function
 * gzencode. There are two compression algorithms: gzip and deflate.
 * The majority of all modern browsers support gzip or both of them.
 * We thus only deal with the gzip variant and unzip the cache in case
 * the browser does not accept gzip encoding.
 *
 * @see drupal_page_header
 */
function page_set_cache() {
  global $user, $base_root;

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET') {
    // This will fail in some cases, see page_get_cache() for the explanation.
    if ($data = ob_get_contents()) {
      $cache = TRUE;
      if (function_exists('gzencode')) {
        // We do not store the data in case the zlib mode is deflate.
        // This should be rarely happening.
        if (zlib_get_coding_type() == 'deflate') {
          $cache = FALSE;
        }
        else if (zlib_get_coding_type() == FALSE) {
          $data = gzencode($data, 9, FORCE_GZIP);
        }
        // The remaining case is 'gzip' which means the data is
        // already compressed and nothing left to do but to store it.
      }
      ob_end_flush();
      if ($cache && $data) {
        cache_set($base_root . request_uri(), $data, CACHE_TEMPORARY, drupal_get_headers());
      }
    }
  }
}
