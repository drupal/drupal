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
 * Create E_DEPRECATED constant for older PHP versions (<5.3).
 */
if (!defined('E_DEPRECATED')) {
  define('E_DEPRECATED', 8192);
}

/**
 * Error code indicating that the request made by drupal_http_request() exceeded
 * the specified timeout.
 */
define('HTTP_REQUEST_TIMEOUT', -1);

/**
 * Set content for a specified region.
 *
 * @param $region
 *   Page region the content is assigned to.
 * @param $data
 *   Content to be set.
 */
function drupal_set_content($region = NULL, $data = NULL) {
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
 *   A specified region to fetch content for. If NULL, all regions will be
 *   returned.
 * @param $delimiter
 *   Content to be inserted between imploded array elements.
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
 *
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
 * Note: When sending a Content-Type header, always include a 'charset' type,
 * too. This is necessary to avoid security bugs (e.g. UTF-7 XSS).
 *
 * Note: No special sanitizing needs to be done to headers. However if a header
 * value contains a line break a PHP warning will be thrown and the header
 * will not be set.
 */
function drupal_set_header($header = NULL) {
  // We use an array to guarantee there are no leading or trailing delimiters.
  // Otherwise, header('') could get called when serving the page later, which
  // ends HTTP headers prematurely on some PHP versions.
  static $stored_headers = array();

  if (strlen($header)) {
    // Protect against header injection attacks if PHP is too old to do that.
    if (version_compare(PHP_VERSION, '5.1.2', '<') && (strpos($header, "\n") !== FALSE || strpos($header, "\r") !== FALSE)) {
      // Use the same warning message that newer versions of PHP use.
      trigger_error('Header may not contain more than a single header, new line detected', E_USER_WARNING);
    }
    else {
      header($header);
      $stored_headers[] = $header;
    }
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
 * Make any final alterations to the rendered xhtml.
 */
function drupal_final_markup($content) {
  // Make sure that the charset is always specified as the first element of the
  // head region to prevent encoding-based attacks.
  return preg_replace('/<head[^>]*>/i', "\$0\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />", $content, 1);
}

/**
 * Add a feed URL for the current page.
 *
 * @param $url
 *   A URL for the feed.
 * @param $title
 *   The title of the feed.
 */
function drupal_add_feed($url = NULL, $title = '') {
  static $stored_feed_links = array();

  if (!is_null($url) && !isset($stored_feed_links[$url])) {
    $stored_feed_links[$url] = theme('feed_icon', $url, $title);

    drupal_add_link(array('rel' => 'alternate',
                          'type' => 'application/rss+xml',
                          'title' => $title,
                          'href' => $url));
  }
  return $stored_feed_links;
}

/**
 * Get the feed URLs for the current page.
 *
 * @param $delimiter
 *   A delimiter to split feeds by.
 */
function drupal_get_feeds($delimiter = "\n") {
  $feeds = drupal_add_feed();
  return implode($feeds, $delimiter);
}

/**
 * @defgroup http_handling HTTP handling
 * @{
 * Functions to properly handle HTTP responses.
 */

/**
 * Parse an array into a valid urlencoded query string.
 *
 * @param $query
 *   The array to be processed e.g. $_GET.
 * @param $exclude
 *   The array filled with keys to be excluded. Use parent[child] to exclude
 *   nested items.
 * @param $parent
 *   Should not be passed, only used in recursive calls.
 * @return
 *   An urlencoded string which can be appended to/as the URL query string.
 */
function drupal_query_string_encode($query, $exclude = array(), $parent = '') {
  $params = array();

  foreach ($query as $key => $value) {
    $key = rawurlencode($key);
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
      $params[] = $key .'='. rawurlencode($value);
    }
  }

  return implode('&', $params);
}

/**
 * Prepare a destination query string for use in combination with drupal_goto().
 *
 * Used to direct the user back to the referring page after completing a form.
 * By default the current URL is returned. If a destination exists in the
 * previous request, that destination is returned. As such, a destination can
 * persist across multiple pages.
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
 * parameters. However you may override that behavior by setting a
 * destination in either the $_REQUEST-array (i.e. by using
 * the query string of an URI) or the $_REQUEST['edit']-array (i.e. by
 * using a hidden form field). This is used to direct the user back to
 * the proper page after completing a form. For example, after editing
 * a post on the 'admin/content/node'-page or after having logged on using the
 * 'user login'-block in a sidebar. The function drupal_get_destination()
 * can be used to help set the destination URL.
 *
 * Drupal will ensure that messages set by drupal_set_message() and other
 * session data are written to the database before the user is redirected.
 *
 * This function ends the request; use it rather than a print theme('page')
 * statement in your menu callback.
 *
 * @param $path
 *   (optional) A Drupal path or a full URL, which will be passed to url() to
 *   compute the redirect for the URL.
 * @param $query
 *   (optional) A URL-encoded query string to append to the link, or an array of
 *   query key/value-pairs without any URL-encoding. Passed to url().
 * @param $fragment
 *   (optional) A destination fragment identifier (named anchor).
 * @param $http_response_code
 *   (optional) The HTTP status code to use for the redirection, defaults to 302.
 *   The valid values for 3xx redirection status codes are defined in
 *   @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3 RFC 2616 @endlink
 *   and the
 *   @link http://tools.ietf.org/html/draft-reschke-http-status-308-07 draft for the new HTTP status codes: @endlink
 *   - 301: Moved Permanently (the recommended value for most redirects).
 *   - 302: Found (default in Drupal and PHP, sometimes used for spamming search
 *     engines).
 *   - 303: See Other.
 *   - 304: Not Modified.
 *   - 305: Use Proxy.
 *   - 307: Temporary Redirect.
 * @see drupal_get_destination()
 */
function drupal_goto($path = '', $query = NULL, $fragment = NULL, $http_response_code = 302) {

  $destination = FALSE;
  if (isset($_REQUEST['destination'])) {
    $destination = $_REQUEST['destination'];
  }
  else if (isset($_REQUEST['edit']['destination'])) {
    $destination = $_REQUEST['edit']['destination'];
  }

  if ($destination) {
    // Do not redirect to an absolute URL originating from user input.
    if (!menu_path_is_external($destination)) {
      extract(parse_url($destination));
    }
  }

  $options = array('query' => $query, 'fragment' => $fragment, 'absolute' => TRUE);
  // In some cases modules call drupal_goto($_GET['q']). We need to ensure that
  // such a redirect is not to an external URL.
  if ($path === $_GET['q'] && menu_path_is_external($path)) {
    // Force url() to generate a non-external URL.
    $options['external'] = FALSE;
  }

  $url = url($path, $options);
  // Remove newlines from the URL to avoid header injection attacks.
  $url = str_replace(array("\n", "\r"), '', $url);

  // Allow modules to react to the end of the page request before redirecting.
  // We do not want this while running update.php.
  if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
    module_invoke_all('exit', $url);
  }

  // Even though session_write_close() is registered as a shutdown function, we
  // need all session data written to the database before redirecting.
  session_write_close();

  header('Location: '. $url, TRUE, $http_response_code);

  // The "Location" header sends a redirect status code to the HTTP daemon. In
  // some cases this can be wrong, so we make sure none of the code below the
  // drupal_goto() call gets executed upon redirection.
  exit();
}

/**
 * Generates a site off-line message.
 */
function drupal_site_offline() {
  drupal_maintenance_theme();
  drupal_set_header($_SERVER['SERVER_PROTOCOL'] .' 503 Service unavailable');
  drupal_set_title(t('Site off-line'));
  print theme('maintenance_page', filter_xss_admin(variable_get('site_offline_message',
    t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal'))))));
}

/**
 * Generates a 404 error if the request can not be handled.
 */
function drupal_not_found() {
  drupal_set_header($_SERVER['SERVER_PROTOCOL'] .' 404 Not Found');

  watchdog('page not found', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

  // Keep old path for reference, and to allow forms to redirect to it.
  if (!isset($_REQUEST['destination'])) {
    // Make sure that the current path is not interpreted as external URL.
    if (!menu_path_is_external($_GET['q'])) {
      $_REQUEST['destination'] = $_GET['q'];
    }
  }

  $path = drupal_get_normal_path(variable_get('site_404', ''));
  if ($path && $path != $_GET['q']) {
    // Set the active item in case there are tabs to display, or other
    // dependencies on the path.
    menu_set_active_item($path);
    $return = menu_execute_active_handler($path);
  }

  if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
    drupal_set_title(t('Page not found'));
    $return = t('The requested page could not be found.');
  }

  // To conserve CPU and bandwidth, omit the blocks.
  print theme('page', $return, FALSE);
}

/**
 * Generates a 403 error if the request is not allowed.
 */
function drupal_access_denied() {
  drupal_set_header($_SERVER['SERVER_PROTOCOL'] .' 403 Forbidden');

  watchdog('access denied', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

  // Keep old path for reference, and to allow forms to redirect to it.
  if (!isset($_REQUEST['destination'])) {
    // Make sure that the current path is not interpreted as external URL.
    if (!menu_path_is_external($_GET['q'])) {
      $_REQUEST['destination'] = $_GET['q'];
    }
  }

  $path = drupal_get_normal_path(variable_get('site_403', ''));
  if ($path && $path != $_GET['q']) {
    // Set the active item in case there are tabs to display or other
    // dependencies on the path.
    menu_set_active_item($path);
    $return = menu_execute_active_handler($path);
  }

  if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
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
 * @param $timeout
 *   A float representing the maximum number of seconds the function call may
 *   take. The default is 30 seconds. If a timeout occurs, the error code is set
 *   to the HTTP_REQUEST_TIMEOUT constant.
 * @return
 *   An object containing the HTTP request headers, response code, protocol,
 *   status message, headers, data and redirect status.
 */
function drupal_http_request($url, $headers = array(), $method = 'GET', $data = NULL, $retry = 3, $timeout = 30.0) {
  global $db_prefix;

  $result = new stdClass();

  // Parse the URL and make sure we can handle the schema.
  $uri = parse_url($url);

  if ($uri == FALSE) {
    $result->error = 'unable to parse URL';
    $result->code = -1001;
    return $result;
  }

  if (!isset($uri['scheme'])) {
    $result->error = 'missing schema';
    $result->code = -1002;
    return $result;
  }

  timer_start(__FUNCTION__);

  switch ($uri['scheme']) {
    case 'http':
    case 'feed':
      $port = isset($uri['port']) ? $uri['port'] : 80;
      $host = $uri['host'] . ($port != 80 ? ':'. $port : '');
      $fp = @fsockopen($uri['host'], $port, $errno, $errstr, $timeout);
      break;
    case 'https':
      // Note: Only works for PHP 4.3 compiled with OpenSSL.
      $port = isset($uri['port']) ? $uri['port'] : 443;
      $host = $uri['host'] . ($port != 443 ? ':'. $port : '');
      $fp = @fsockopen('ssl://'. $uri['host'], $port, $errno, $errstr, $timeout);
      break;
    default:
      $result->error = 'invalid schema '. $uri['scheme'];
      $result->code = -1003;
      return $result;
  }

  // Make sure the socket opened properly.
  if (!$fp) {
    // When a network error occurs, we use a negative number so it does not
    // clash with the HTTP status codes.
    $result->code = -$errno;
    $result->error = trim($errstr);

    // Mark that this request failed. This will trigger a check of the web
    // server's ability to make outgoing HTTP requests the next time that
    // requirements checking is performed.
    // @see system_requirements()
    variable_set('drupal_http_request_fails', TRUE);

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
    // We don't add the port to prevent from breaking rewrite rules checking the
    // host that do not take into account the port number.
    'Host' => "Host: $host",
    'User-Agent' => 'User-Agent: Drupal (+http://drupal.org/)',
  );

  // Only add Content-Length if we actually have any content or if it is a POST
  // or PUT request. Some non-standard servers get confused by Content-Length in
  // at least HEAD/GET requests, and Squid always requires Content-Length in
  // POST/PUT requests.
  $content_length = strlen($data);
  if ($content_length > 0 || $method == 'POST' || $method == 'PUT') {
    $defaults['Content-Length'] = 'Content-Length: '. $content_length;
  }

  // If the server URL has a user then attempt to use basic authentication
  if (isset($uri['user'])) {
    $defaults['Authorization'] = 'Authorization: Basic '. base64_encode($uri['user'] . (!empty($uri['pass']) ? ":". $uri['pass'] : ''));
  }

  // If the database prefix is being used by SimpleTest to run the tests in a copied
  // database then set the user-agent header to the database prefix so that any
  // calls to other Drupal pages will run the SimpleTest prefixed database. The
  // user-agent is used to ensure that multiple testing sessions running at the
  // same time won't interfere with each other as they would if the database
  // prefix were stored statically in a file or database variable.
  if (is_string($db_prefix) && preg_match("/^simpletest\d+$/", $db_prefix, $matches)) {
    $defaults['User-Agent'] = 'User-Agent: ' . $matches[0];
  }

  foreach ($headers as $header => $value) {
    $defaults[$header] = $header .': '. $value;
  }

  $request = $method .' '. $path ." HTTP/1.0\r\n";
  $request .= implode("\r\n", $defaults);
  $request .= "\r\n\r\n";
  $request .= $data;

  $result->request = $request;

  // Calculate how much time is left of the original timeout value.
  $time_left = $timeout - timer_read(__FUNCTION__) / 1000;
  if ($time_left > 0) {
    stream_set_timeout($fp, floor($time_left), floor(1000000 * fmod($time_left, 1)));
    fwrite($fp, $request);
  }

  // Fetch response.
  $response = '';
  while (!feof($fp)) {
    // Calculate how much time is left of the original timeout value.
    $time_left = $timeout - timer_read(__FUNCTION__) / 1000;
    if ($time_left <= 0) {
      $result->code = HTTP_REQUEST_TIMEOUT;
      $result->error = 'request timed out';
      return $result;
    }
    stream_set_timeout($fp, floor($time_left), floor(1000000 * fmod($time_left, 1)));
    $chunk = fread($fp, 1024);
    $response .= $chunk;
  }
  fclose($fp);

  // Parse response headers from the response body.
  // Be tolerant of malformed HTTP responses that separate header and body with
  // \n\n or \r\r instead of \r\n\r\n.  See http://drupal.org/node/183435
  list($split, $result->data) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
  $split = preg_split("/\r\n|\n|\r/", $split);

  list($protocol, $code, $status_message) = explode(' ', trim(array_shift($split)), 3);
  $result->protocol = $protocol;
  $result->status_message = $status_message;

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
  // RFC 2616 states that all unknown HTTP codes must be treated the same as the
  // base code in their class.
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
      $timeout -= timer_read(__FUNCTION__) / 1000;
      if ($timeout <= 0) {
        $result->code = HTTP_REQUEST_TIMEOUT;
        $result->error = 'request timed out';
      }
      elseif ($retry) {
        $result = drupal_http_request($result->headers['Location'], $headers, $method, $data, --$retry, $timeout);
        $result->redirect_code = $result->code;
      }
      $result->redirect_url = $location;

      break;
    default:
      $result->error = $status_message;
  }

  $result->code = $code;
  return $result;
}
/**
 * @} End of "HTTP handling".
 */

/**
 * Log errors as defined by administrator.
 *
 * Error levels:
 * - 0 = Log errors to database.
 * - 1 = Log errors to database and to screen.
 */
function drupal_error_handler($errno, $message, $filename, $line, $context) {
  // If the @ error suppression operator was used, error_reporting will have
  // been temporarily set to 0.
  if (error_reporting() == 0) {
    return;
  }

  if ($errno & (E_ALL ^ E_DEPRECATED ^ E_NOTICE)) {
    $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning', 4096 => 'recoverable fatal error');

    // For database errors, we want the line number/file name of the place that
    // the query was originally called, not _db_query().
    if (isset($context[DB_ERROR])) {
      $backtrace = array_reverse(debug_backtrace());

      // List of functions where SQL queries can originate.
      $query_functions = array('db_query', 'pager_query', 'db_query_range', 'db_query_temporary', 'update_sql');

      // Determine where query function was called, and adjust line/file
      // accordingly.
      foreach ($backtrace as $index => $function) {
        if (in_array($function['function'], $query_functions)) {
          $line = $backtrace[$index]['line'];
          $filename = $backtrace[$index]['file'];
          break;
        }
      }
    }

    // Try to use filter_xss(). If it's too early in the bootstrap process for
    // filter_xss() to be loaded, use check_plain() instead.
    $entry = check_plain($types[$errno]) .': '. (function_exists('filter_xss') ? filter_xss($message) : check_plain($message)) .' in '. check_plain($filename) .' on line '. check_plain($line) .'.';

    // Force display of error messages in update.php.
    if (variable_get('error_level', 1) == 1 || strstr($_SERVER['SCRIPT_NAME'], 'update.php')) {
      drupal_set_message($entry, 'error');
    }

    watchdog('php', '%message in %file on line %line.', array('%error' => $types[$errno], '%message' => $message, '%file' => $filename, '%line' => $line), WATCHDOG_ERROR);
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
 * http://php.net/manual/en/features.file-upload.php#42280
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
 * Fix double-escaping problems caused by "magic quotes" in some PHP installations.
 */
function fix_gpc_magic() {
  static $fixed = FALSE;
  if (!$fixed && ini_get('magic_quotes_gpc')) {
    array_walk($_GET, '_fix_gpc_magic');
    array_walk($_POST, '_fix_gpc_magic');
    array_walk($_COOKIE, '_fix_gpc_magic');
    array_walk($_REQUEST, '_fix_gpc_magic');
    array_walk($_FILES, '_fix_gpc_magic_files');
    $fixed = TRUE;
  }
}

/**
 * Translate strings to the page language or a given language.
 *
 * Human-readable text that will be displayed somewhere within a page should
 * be run through the t() function.
 *
 * Examples:
 * @code
 *   if (!$info || !$info['extension']) {
 *     form_set_error('picture_upload', t('The uploaded file was not an image.'));
 *   }
 *
 *   $form['submit'] = array(
 *     '#type' => 'submit',
 *     '#value' => t('Log in'),
 *   );
 * @endcode
 *
 * Any text within t() can be extracted by translators and changed into
 * the equivalent text in their native language.
 *
 * Special variables called "placeholders" are used to signal dynamic
 * information in a string which should not be translated. Placeholders
 * can also be used for text that may change from time to time (such as
 * link paths) to be changed without requiring updates to translations.
 *
 * For example:
 * @code
 *   $output = t('There are currently %members and %visitors online.', array(
 *     '%members' => format_plural($total_users, '1 user', '@count users'),
 *     '%visitors' => format_plural($guests->count, '1 guest', '@count guests')));
 * @endcode
 *
 * There are three styles of placeholders:
 * - !variable, which indicates that the text should be inserted as-is. This is
 *   useful for inserting variables into things like e-mail.
 *   @code
 *     $message[] = t("If you don't want to receive such e-mails, you can change your settings at !url.", array('!url' => url("user/$account->uid", array('absolute' => TRUE))));
 *   @endcode
 *
 * - @variable, which indicates that the text should be run through
 *   check_plain, to escape HTML characters. Use this for any output that's
 *   displayed within a Drupal page.
 *   @code
 *     drupal_set_title($title = t("@name's blog", array('@name' => $account->name)));
 *   @endcode
 *
 * - %variable, which indicates that the string should be HTML escaped and
 *   highlighted with theme_placeholder() which shows up by default as
 *   <em>emphasized</em>.
 *   @code
 *     $message = t('%name-from sent %name-to an e-mail.', array('%name-from' => $user->name, '%name-to' => $account->name));
 *   @endcode
 *
 * When using t(), try to put entire sentences and strings in one t() call.
 * This makes it easier for translators, as it provides context as to what
 * each word refers to. HTML markup within translation strings is allowed, but
 * should be avoided if possible. The exception are embedded links; link
 * titles add a context for translators, so should be kept in the main string.
 *
 * Here is an example of incorrect usage of t():
 * @code
 *   $output .= t('<p>Go to the @contact-page.</p>', array('@contact-page' => l(t('contact page'), 'contact')));
 * @endcode
 *
 * Here is an example of t() used correctly:
 * @code
 *   $output .= '<p>'. t('Go to the <a href="@contact-page">contact page</a>.', array('@contact-page' => url('contact'))) .'</p>';
 * @endcode
 *
 * Avoid escaping quotation marks wherever possible.
 *
 * Incorrect:
 * @code
 *   $output .= t('Don\'t click me.');
 * @endcode
 *
 * Correct:
 * @code
 *   $output .= t("Don't click me.");
 * @endcode
 *
 * Because t() is designed for handling code-based strings, in almost all
 * cases, the actual string and not a variable must be passed through t().
 *
 * Extraction of translations is done based on the strings contained in t()
 * calls. If a variable is passed through t(), the content of the variable
 * cannot be extracted from the file for translation.
 *
 * Incorrect:
 * @code
 *   $message = 'An error occurred.';
 *   drupal_set_message(t($message), 'error');
 *   $output .= t($message);
 * @endcode
 *
 * Correct:
 * @code
 *   $message = t('An error occurred.');
 *   drupal_set_message($message, 'error');
 *   $output .= $message;
 * @endcode
 *
 * The only case in which variables can be passed safely through t() is when
 * code-based versions of the same strings will be passed through t() (or
 * otherwise extracted) elsewhere.
 *
 * In some cases, modules may include strings in code that can't use t()
 * calls. For example, a module may use an external PHP application that
 * produces strings that are loaded into variables in Drupal for output.
 * In these cases, module authors may include a dummy file that passes the
 * relevant strings through t(). This approach will allow the strings to be
 * extracted.
 *
 * Sample external (non-Drupal) code:
 * @code
 *   class Time {
 *     public $yesterday = 'Yesterday';
 *     public $today = 'Today';
 *     public $tomorrow = 'Tomorrow';
 *   }
 * @endcode
 *
 * Sample dummy file.
 * @code
 *   // Dummy function included in example.potx.inc.
 *   function example_potx() {
 *     $strings = array(
 *       t('Yesterday'),
 *       t('Today'),
 *       t('Tomorrow'),
 *     );
 *     // No return value needed, since this is a dummy function.
 *   }
 * @endcode
 *
 * Having passed strings through t() in a dummy function, it is then
 * okay to pass variables through t().
 *
 * Correct (if a dummy file was used):
 * @code
 *   $time = new Time();
 *   $output .= t($time->today);
 * @endcode
 *
 * However tempting it is, custom data from user input or other non-code
 * sources should not be passed through t(). Doing so leads to the following
 * problems and errors:
 *  - The t() system doesn't support updates to existing strings. When user
 *    data is updated, the next time it's passed through t() a new record is
 *    created instead of an update. The database bloats over time and any
 *    existing translations are orphaned with each update.
 *  - The t() system assumes any data it receives is in English. User data may
 *    be in another language, producing translation errors.
 *  - The "Built-in interface" text group in the locale system is used to
 *    produce translations for storage in .po files. When non-code strings are
 *    passed through t(), they are added to this text group, which is rendered
 *    inaccurate since it is a mix of actual interface strings and various user
 *    input strings of uncertain origin.
 *
 * Incorrect:
 * @code
 *   $item = item_load();
 *   $output .= check_plain(t($item['title']));
 * @endcode
 *
 * Instead, translation of these data can be done through the locale system,
 * either directly or through helper functions provided by contributed
 * modules.
 * @see hook_locale()
 *
 * During installation, st() is used in place of t(). Code that may be called
 * during installation or during normal operation should use the get_t()
 * helper function.
 * @see st()
 * @see get_t()
 *
 * @param $string
 *   A string containing the English string to translate.
 * @param $args
 *   An associative array of replacements to make after translation. Incidences
 *   of any key in this array are replaced with the corresponding value. Based
 *   on the first character of the key, the value is escaped and/or themed:
 *    - !variable: inserted as is
 *    - @variable: escape plain text to HTML (check_plain)
 *    - %variable: escape text and theme as a placeholder for user-submitted
 *      content (check_plain + theme_placeholder)
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 * @return
 *   The translated string.
 */
function t($string, $args = array(), $langcode = NULL) {
  global $language;
  static $custom_strings;

  $langcode = isset($langcode) ? $langcode : $language->language;

  // First, check for an array of customized strings. If present, use the array
  // *instead of* database lookups. This is a high performance way to provide a
  // handful of string replacements. See settings.php for examples.
  // Cache the $custom_strings variable to improve performance.
  if (!isset($custom_strings[$langcode])) {
    $custom_strings[$langcode] = variable_get('locale_custom_strings_'. $langcode, array());
  }
  // Custom strings work for English too, even if locale module is disabled.
  if (isset($custom_strings[$langcode][$string])) {
    $string = $custom_strings[$langcode][$string];
  }
  // Translate with locale module if enabled.
  elseif (function_exists('locale') && $langcode != 'en') {
    $string = locale($string, $langcode);
  }
  if (empty($args)) {
    return $string;
  }
  else {
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escaped only.
          $args[$key] = check_plain($value);
          break;

        case '%':
        default:
          // Escaped and placeholder.
          $args[$key] = theme('placeholder', $value);
          break;

        case '!':
          // Pass-through.
      }
    }
    return strtr($string, $args);
  }
}

/**
 * @defgroup validation Input validation
 * @{
 * Functions to validate user input.
 */

/**
 * Verifies the syntax of the given e-mail address.
 *
 * See @link http://tools.ietf.org/html/rfc5322 RFC 5322 @endlink for details.
 *
 * @param $mail
 *   A string containing an e-mail address.
 * @return
 *   1 if the email address is valid, 0 if it is invalid or empty, and FALSE if
 *   there is an input error (such as passing in an array instead of a string).
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
 * This function should only be used on actual URLs. It should not be used for
 * Drupal menu paths, which can contain arbitrary characters.
 * Valid values per RFC 3986.
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
    return (bool)preg_match("
      /^                                                      # Start at the beginning of the text
      (?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
      (?:                                                     # Userinfo (optional) which is typically
        (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
      )?
      (?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
        |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
      )
      (?::[0-9]+)?                                            # Server port number (optional)
      (?:[\/|\?]
        (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
      *)?
    $/xi", $url);
  }
  else {
    return (bool)preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
  }
}


/**
 * @} End of "defgroup validation".
 */

/**
 * Register an event for the current visitor (hostname/IP) to the flood control mechanism.
 *
 * @param $name
 *   The name of an event.
 */
function flood_register_event($name) {
  db_query("INSERT INTO {flood} (event, hostname, timestamp) VALUES ('%s', '%s', %d)", $name, ip_address(), time());
}

/**
 * Check if the current visitor (hostname/IP) is allowed to proceed with the specified event.
 *
 * The user is allowed to proceed if he did not trigger the specified event more
 * than $threshold times per hour.
 *
 * @param $name
 *   The name of the event.
 * @param $threshold
 *   The maximum number of the specified event per hour (per visitor).
 * @return
 *   True if the user did not exceed the hourly threshold. False otherwise.
 */
function flood_is_allowed($name, $threshold) {
  $number = db_result(db_query("SELECT COUNT(*) FROM {flood} WHERE event = '%s' AND hostname = '%s' AND timestamp > %d", $name, ip_address(), time() - 3600));
  return ($number < $threshold ? TRUE : FALSE);
}

function check_file($filename) {
  return is_uploaded_file($filename);
}

/**
 * Prepare a URL for use in an HTML attribute. Strips harmful protocols.
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
function format_rss_channel($title, $link, $description, $items, $langcode = NULL, $args = array()) {
  global $language;
  $langcode = $langcode ? $langcode : $language->language;

  $output = "<channel>\n";
  $output .= ' <title>'. check_plain($title) ."</title>\n";
  $output .= ' <link>'. check_url($link) ."</link>\n";

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $output .= ' <description>'. check_plain(decode_entities(strip_tags($description))) ."</description>\n";
  $output .= ' <language>'. check_plain($langcode) ."</language>\n";
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
  $output = '';
  foreach ($array as $key => $value) {
    if (is_numeric($key)) {
      if ($value['key']) {
        $output .= ' <'. $value['key'];
        if (isset($value['attributes']) && is_array($value['attributes'])) {
          $output .= drupal_attributes($value['attributes']);
        }

        if (isset($value['value']) && $value['value'] != '') {
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
 * called by this function, make sure not to pass already-localized strings to
 * it.
 *
 * For example:
 * @code
 *   $output = format_plural($node->comment_count, '1 comment', '@count comments');
 * @endcode
 *
 * Example with additional replacements:
 * @code
 *   $output = format_plural($update_count,
 *     'Changed the content type of 1 post from %old-type to %new-type.',
 *     'Changed the content type of @count posts from %old-type to %new-type.',
 *     array('%old-type' => $info->old_type, '%new-type' => $info->new_type)));
 * @endcode
 *
 * @param $count
 *   The item count to display.
 * @param $singular
 *   The string for the singular case. Please make sure it is clear this is
 *   singular, to ease translation (e.g. use "1 new comment" instead of "1 new").
 *   Do not use @count in the singular string.
 * @param $plural
 *   The string for the plural case. Please make sure it is clear this is plural,
 *   to ease translation. Use @count in place of the item count, as in "@count
 *   new comments".
 * @param $args
 *   An associative array of replacements to make after translation. Incidences
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or themed:
 *    - !variable: inserted as is
 *    - @variable: escape plain text to HTML (check_plain)
 *    - %variable: escape text and theme as a placeholder for user-submitted
 *      content (check_plain + theme_placeholder)
 *   Note that you do not need to include @count in this array.
 *   This replacement is done automatically for the plural case.
 * @param $langcode
 *   Optional language code to translate to a language other than
 *   what is used to display the page.
 * @return
 *   A translated string.
 */
function format_plural($count, $singular, $plural, $args = array(), $langcode = NULL) {
  $args['@count'] = $count;
  if ($count == 1) {
    return t($singular, $args, $langcode);
  }

  // Get the plural index through the gettext formula.
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, $langcode) : -1;
  // Backwards compatibility.
  if ($index < 0) {
    return t($plural, $args, $langcode);
  }
  else {
    switch ($index) {
      case "0":
        return t($singular, $args, $langcode);
      case "1":
        return t($plural, $args, $langcode);
      default:
        unset($args['@count']);
        $args['@count['. $index .']'] = $count;
        return t(strtr($plural, array('@count' => '@count['. $index .']')), $args, $langcode);
    }
  }
}

/**
 * Parse a given byte count.
 *
 * @param $size
 *   A size expressed as a number of bytes with optional SI size and unit
 *   suffix (e.g. 2, 3K, 5MB, 10G).
 * @return
 *   An integer representation of the size.
 */
function parse_size($size) {
  $suffixes = array(
    '' => 1,
    'k' => 1024,
    'm' => 1048576, // 1024 * 1024
    'g' => 1073741824, // 1024 * 1024 * 1024
  );
  if (preg_match('/([0-9]+)\s*(k|m|g)?(b?(ytes?)?)/i', $size, $match)) {
    return $match[1] * $suffixes[drupal_strtolower($match[2])];
  }
}

/**
 * Generate a string representation for the given byte count.
 *
 * @param $size
 *   A size in bytes.
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 * @return
 *   A translated string representation of the size.
 */
function format_size($size, $langcode = NULL) {
  if ($size < 1024) {
    return format_plural($size, '1 byte', '@count bytes', array(), $langcode);
  }
  else {
    $size = round($size / 1024, 2);
    $suffix = t('KB', array(), $langcode);
    if ($size >= 1024) {
      $size = round($size / 1024, 2);
      $suffix = t('MB', array(), $langcode);
    }
    return t('@size @suffix', array('@size' => $size, '@suffix' => $suffix), $langcode);
  }
}

/**
 * Format a time interval with the requested granularity.
 *
 * @param $timestamp
 *   The length of the interval in seconds.
 * @param $granularity
 *   How many different units to display in the string.
 * @param $langcode
 *   Optional language code to translate to a language other than
 *   what is used to display the page.
 * @return
 *   A translated string representation of the interval.
 */
function format_interval($timestamp, $granularity = 2, $langcode = NULL) {
  $units = array('1 year|@count years' => 31536000, '1 week|@count weeks' => 604800, '1 day|@count days' => 86400, '1 hour|@count hours' => 3600, '1 min|@count min' => 60, '1 sec|@count sec' => 1);
  $output = '';
  foreach ($units as $key => $value) {
    $key = explode('|', $key);
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '') . format_plural(floor($timestamp / $value), $key[0], $key[1], array(), $langcode);
      $timestamp %= $value;
      $granularity--;
    }

    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : t('0 sec', array(), $langcode);
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
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 * @return
 *   A translated date string in the requested format.
 */
function format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
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
      // No change to format.
      break;
    case 'medium':
    default:
      $format = variable_get('date_format_medium', 'D, m/d/Y - H:i');
  }

  $max = strlen($format);
  $date = '';
  for ($i = 0; $i < $max; $i++) {
    $c = $format[$i];
    if (strpos('AaDlM', $c) !== FALSE) {
      $date .= t(gmdate($c, $timestamp), array(), $langcode);
    }
    else if ($c == 'F') {
      // Special treatment for long month names: May is both an abbreviation
      // and a full month name in English, but other languages have
      // different abbreviations.
      $date .= trim(t('!long-month-name '. gmdate($c, $timestamp), array('!long-month-name' => ''), $langcode));
    }
    else if (strpos('BdgGhHiIjLmnsStTUwWYyz', $c) !== FALSE) {
      $date .= gmdate($c, $timestamp);
    }
    else if ($c == 'r') {
      $date .= format_date($timestamp - $timezone, 'custom', 'D, d M Y H:i:s O', $timezone, $langcode);
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
 * Generates an internal or external URL.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 *
 * @param $path
 *   (optional) The internal path or external URL being linked to, such as
 *   "node/34" or "http://example.com/foo". The default value is equivalent to
 *   passing in '<front>'. A few notes:
 *   - If you provide a full URL, it will be considered an external URL.
 *   - If you provide only the path (e.g. "node/34"), it will be
 *     considered an internal link. In this case, it should be a system URL,
 *     and it will be replaced with the alias, if one exists. Additional query
 *     arguments for internal paths must be supplied in $options['query'], not
 *     included in $path.
 *   - If you provide an internal path and $options['alias'] is set to TRUE, the
 *     path is assumed already to be the correct path alias, and the alias is
 *     not looked up.
 *   - The special string '<front>' generates a link to the site's base URL.
 *   - If your external URL contains a query (e.g. http://example.com/foo?a=b),
 *     then you can either URL encode the query keys and values yourself and
 *     include them in $path, or use $options['query'] to let this function
 *     URL encode them.
 * @param $options
 *   (optional) An associative array of additional options, with the following
 *   elements:
 *   - 'query': A URL-encoded query string to append to the link, or an array of
 *     query key/value-pairs without any URL-encoding.
 *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
 *     Do not include the leading '#' character.
 *   - 'absolute' (default FALSE): Whether to force the output to be an absolute
 *     link (beginning with http:). Useful for links that will be displayed
 *     outside the site, such as in an RSS feed.
 *   - 'alias' (default FALSE): Whether the given path is a URL alias already.
 *   - 'external': Whether the given path is an external URL.
 *   - 'language': An optional language object. Used to build the URL to link
 *     to and look up the proper alias for the link.
 *   - 'base_url': Only used internally, to modify the base URL when a language
 *     dependent URL requires so.
 *   - 'prefix': Only used internally, to modify the path when a language
 *     dependent URL requires so.
 *
 * @return
 *   A string containing a URL to the given path.
 */
function url($path = NULL, $options = array()) {
  // Merge in defaults.
  $options += array(
    'fragment' => '',
    'query' => '',
    'absolute' => FALSE,
    'alias' => FALSE,
    'prefix' => ''
  );

  if (!isset($options['external'])) {
    $options['external'] = menu_path_is_external($path);
  }

  // May need language dependent rewriting if language.inc is present.
  if (function_exists('language_url_rewrite')) {
    language_url_rewrite($path, $options);
  }
  if ($options['fragment']) {
    $options['fragment'] = '#'. $options['fragment'];
  }
  if (is_array($options['query'])) {
    $options['query'] = drupal_query_string_encode($options['query']);
  }

  if ($options['external']) {
    // Split off the fragment.
    if (strpos($path, '#') !== FALSE) {
      list($path, $old_fragment) = explode('#', $path, 2);
      if (isset($old_fragment) && !$options['fragment']) {
        $options['fragment'] = '#'. $old_fragment;
      }
    }
    // Append the query.
    if ($options['query']) {
      $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . $options['query'];
    }
    // Reassemble.
    return $path . $options['fragment'];
  }

  // Strip leading slashes from internal paths to prevent them becoming external
  // URLs without protocol. /example.com should not be turned into
  // //example.com.
  $path = ltrim($path, '/');

  global $base_url;
  static $script;

  if (!isset($script)) {
    // On some web servers, such as IIS, we can't omit "index.php". So, we
    // generate "index.php?q=foo" instead of "?q=foo" on anything that is not
    // Apache.
    $script = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === FALSE) ? 'index.php' : '';
  }

  if (!isset($options['base_url'])) {
    // The base_url might be rewritten from the language rewrite in domain mode.
    $options['base_url'] = $base_url;
  }

  // Preserve the original path before aliasing.
  $original_path = $path;

  // The special path '<front>' links to the default front page.
  if ($path == '<front>') {
    $path = '';
  }
  elseif (!empty($path) && !$options['alias']) {
    $path = drupal_get_path_alias($path, isset($options['language']) ? $options['language']->language : '');
  }

  if (function_exists('custom_url_rewrite_outbound')) {
    // Modules may alter outbound links by reference.
    custom_url_rewrite_outbound($path, $options, $original_path);
  }

  $base = $options['absolute'] ? $options['base_url'] .'/' : base_path();
  $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];
  $path = drupal_urlencode($prefix . $path);

  if (variable_get('clean_url', '0')) {
    // With Clean URLs.
    if ($options['query']) {
      return $base . $path .'?'. $options['query'] . $options['fragment'];
    }
    else {
      return $base . $path . $options['fragment'];
    }
  }
  else {
    // Without Clean URLs.
    $variables = array();
    if (!empty($path)) {
      $variables[] = 'q='. $path;
    }
    if (!empty($options['query'])) {
      $variables[] = $options['query'];
    }
    if ($query = join('&', $variables)) {
      return $base . $script .'?'. $query . $options['fragment'];
    }
    else {
      return $base . $options['fragment'];
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
 * Formats an internal or external URL link as an HTML anchor tag.
 *
 * This function correctly handles aliased paths, and adds an 'active' class
 * attribute to links that point to the current page (for theming), so all
 * internal links output by modules should be generated by this function if
 * possible.
 *
 * However, for links enclosed in translatable text you should use t() and
 * embed the HTML anchor tag directly in the translated string. For example:
 * @code
 * t('Visit the <a href="@url">settings</a> page', array('@url' => url('admin')));
 * @endcode
 * This keeps the context of the link title ('settings' in the example) for
 * translators.
 *
 * @param $text
 *   The link text for the anchor tag.
 * @param $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo". After the url() function is called to construct
 *   the URL from $path and $options, the resulting URL is passed through
 *   check_url() before it is inserted into the HTML anchor tag, to ensure
 *   well-formed HTML. See url() for more information and notes.
 * @param $options
 *   An associative array of additional options, with the following elements:
 *   - 'attributes': An associative array of HTML attributes to apply to the
 *     anchor tag.
 *   - 'html' (default FALSE): Whether $text is HTML or just plain-text. For
 *     example, to make an image tag into a link, this must be set to TRUE, or
 *     you will see the escaped HTML image tag.
 *   - 'language': An optional language object. If the path being linked to is
 *     internal to the site, $options['language'] is used to look up the alias
 *     for the URL, and to determine whether the link is "active", or pointing
 *     to the current page (the language as well as the path must match).This
 *     element is also used by url().
 *   - Additional $options elements used by the url() function.
 *
 * @return
 *   An HTML string containing a link to the given path.
 */
function l($text, $path, $options = array()) {
  global $language;

  // Merge in defaults.
  $options += array(
      'attributes' => array(),
      'html' => FALSE,
    );

  // Append active class.
  if (($path == $_GET['q'] || ($path == '<front>' && drupal_is_front_page())) &&
      (empty($options['language']) || $options['language']->language == $language->language)) {
    if (isset($options['attributes']['class'])) {
      $options['attributes']['class'] .= ' active';
    }
    else {
      $options['attributes']['class'] = 'active';
    }
  }

  // Remove all HTML and PHP tags from a tooltip. For best performance, we act only
  // if a quick strpos() pre-check gave a suspicion (because strip_tags() is expensive).
  if (isset($options['attributes']['title']) && strpos($options['attributes']['title'], '<') !== FALSE) {
    $options['attributes']['title'] = strip_tags($options['attributes']['title']);
  }

  return '<a href="'. check_url(url($path, $options)) .'"'. drupal_attributes($options['attributes']) .'>'. ($options['html'] ? $text : check_plain($text)) .'</a>';
}

/**
 * Perform end-of-request tasks.
 *
 * This function sets the page cache if appropriate, and allows modules to
 * react to the closing of the page by calling hook_exit().
 */
function drupal_page_footer() {
  if (variable_get('cache', CACHE_DISABLED) != CACHE_DISABLED) {
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
 *   A name of a function to apply to all values before output.
 *
 * @return
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
    foreach ($array as $value) {
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
  global $theme_path, $theme_info, $conf;

  // Store current theme path.
  $old_theme_path = $theme_path;

  // Restore theme_path to the theme, as long as drupal_eval() executes,
  // so code evaluted will not see the caller module as the current theme.
  // If theme info is not initialized get the path from theme_default.
  if (!isset($theme_info)) {
    $theme_path = drupal_get_path('theme', $conf['theme_default']);
  }
  else {
    $theme_path = dirname($theme_info->filename);
  }

  ob_start();
  print eval('?>'. $code);
  $output = ob_get_contents();
  ob_end_clean();

  // Recover original theme path.
  $theme_path = $old_theme_path;

  return $output;
}

/**
 * Returns the path to a system item (module, theme, etc.).
 *
 * @param $type
 *   The type of the item (i.e. theme, theme_engine, module, profile).
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
  drupal_set_html_head('<link'. drupal_attributes($attributes) .' />');
}

/**
 * Adds a CSS file to the stylesheet queue.
 *
 * @param $path
 *   (optional) The path to the CSS file relative to the base_path(), e.g.,
 *   modules/devel/devel.css.
 *
 *   Modules should always prefix the names of their CSS files with the module
 *   name, for example: system-menus.css rather than simply menus.css. Themes
 *   can override module-supplied CSS files based on their filenames, and this
 *   prefixing helps prevent confusing name collisions for theme developers.
 *   See drupal_get_css where the overrides are performed.
 *
 *   If the direction of the current language is right-to-left (Hebrew,
 *   Arabic, etc.), the function will also look for an RTL CSS file and append
 *   it to the list. The name of this file should have an '-rtl.css' suffix.
 *   For example a CSS file called 'name.css' will have a 'name-rtl.css'
 *   file added to the list, if exists in the same directory. This CSS file
 *   should contain overrides for properties which should be reversed or
 *   otherwise different in a right-to-left display.
 * @param $type
 *   (optional) The type of stylesheet that is being added. Types are: module
 *   or theme.
 * @param $media
 *   (optional) The media type for the stylesheet, e.g., all, print, screen.
 * @param $preprocess
 *   (optional) Should this CSS file be aggregated and compressed if this
 *   feature has been turned on under the performance section?
 *
 *   What does this actually mean?
 *   CSS preprocessing is the process of aggregating a bunch of separate CSS
 *   files into one file that is then compressed by removing all extraneous
 *   white space.
 *
 *   The reason for merging the CSS files is outlined quite thoroughly here:
 *   http://www.die.net/musings/page_load_time/
 *   "Load fewer external objects. Due to request overhead, one bigger file
 *   just loads faster than two smaller ones half its size."
 *
 *   However, you should *not* preprocess every file as this can lead to
 *   redundant caches. You should set $preprocess = FALSE when:
 *
 *     - Your styles are only used rarely on the site. This could be a special
 *       admin page, the homepage, or a handful of pages that does not represent
 *       the majority of the pages on your site.
 *
 *   Typical candidates for caching are for example styles for nodes across
 *   the site, or used in the theme.
 *
 * @return
 *   An array of CSS files.
 *
 * @see drupal_get_css()
 */
function drupal_add_css($path = NULL, $type = 'module', $media = 'all', $preprocess = TRUE) {
  static $css = array();
  global $language;

  // Create an array of CSS files for each media type first, since each type needs to be served
  // to the browser differently.
  if (isset($path)) {
    // This check is necessary to ensure proper cascading of styles and is faster than an asort().
    if (!isset($css[$media])) {
      $css[$media] = array('module' => array(), 'theme' => array());
    }
    $css[$media][$type][$path] = $preprocess;

    // If the current language is RTL, add the CSS file with RTL overrides.
    if ($language->direction == LANGUAGE_RTL) {
      $rtl_path = str_replace('.css', '-rtl.css', $path);
      if (file_exists($rtl_path)) {
        $css[$media][$type][$rtl_path] = $preprocess;
      }
    }
  }

  return $css;
}

/**
 * Returns a themed representation of all stylesheets that should be attached to the page.
 *
 * It loads the CSS in order, with 'module' first, then 'theme' afterwards.
 * This ensures proper cascading of styles so themes can easily override
 * module styles through CSS selectors.
 *
 * Themes may replace module-defined CSS files by adding a stylesheet with the
 * same filename. For example, themes/garland/system-menus.css would replace
 * modules/system/system-menus.css. This allows themes to override complete
 * CSS files, rather than specific selectors, when necessary.
 *
 * If the original CSS file is being overridden by a theme, the theme is
 * responsible for supplying an accompanying RTL CSS file to replace the
 * module's.
 *
 * @param $css
 *   (optional) An array of CSS files. If no array is provided, the default
 *   stylesheets array is used instead.
 *
 * @return
 *   A string of XHTML CSS tags.
 *
 * @see drupal_add_css()
 */
function drupal_get_css($css = NULL) {
  $output = '';
  if (!isset($css)) {
    $css = drupal_add_css();
  }
  $no_module_preprocess = '';
  $no_theme_preprocess = '';

  $preprocess_css = (variable_get('preprocess_css', FALSE) && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update'));
  $directory = file_directory_path();
  $is_writable = is_dir($directory) && is_writable($directory) && (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PUBLIC);

  // A dummy query-string is added to filenames, to gain control over
  // browser-caching. The string changes on every update or full cache
  // flush, forcing browsers to load a new copy of the files, as the
  // URL changed.
  $query_string = '?'. substr(variable_get('css_js_query_string', '0'), 0, 1);

  foreach ($css as $media => $types) {
    // If CSS preprocessing is off, we still need to output the styles.
    // Additionally, go through any remaining styles if CSS preprocessing is on and output the non-cached ones.
    foreach ($types as $type => $files) {
      if ($type == 'module') {
        // Setup theme overrides for module styles.
        $theme_styles = array();
        foreach (array_keys($css[$media]['theme']) as $theme_style) {
          $theme_styles[] = basename($theme_style);
        }
      }
      foreach ($types[$type] as $file => $preprocess) {
        // If the theme supplies its own style using the name of the module style, skip its inclusion.
        // This includes any RTL styles associated with its main LTR counterpart.
        if ($type == 'module' && in_array(str_replace('-rtl.css', '.css', basename($file)), $theme_styles)) {
          // Unset the file to prevent its inclusion when CSS aggregation is enabled.
          unset($types[$type][$file]);
          continue;
        }
        // Only include the stylesheet if it exists.
        if (file_exists($file)) {
          if (!$preprocess || !($is_writable && $preprocess_css)) {
            // If a CSS file is not to be preprocessed and it's a module CSS file, it needs to *always* appear at the *top*,
            // regardless of whether preprocessing is on or off.
            if (!$preprocess && $type == 'module') {
              $no_module_preprocess .= '<link type="text/css" rel="stylesheet" media="'. $media .'" href="'. base_path() . $file . $query_string .'" />'."\n";
            }
            // If a CSS file is not to be preprocessed and it's a theme CSS file, it needs to *always* appear at the *bottom*,
            // regardless of whether preprocessing is on or off.
            else if (!$preprocess && $type == 'theme') {
              $no_theme_preprocess .= '<link type="text/css" rel="stylesheet" media="'. $media .'" href="'. base_path() . $file . $query_string .'" />'."\n";
            }
            else {
              $output .= '<link type="text/css" rel="stylesheet" media="'. $media .'" href="'. base_path() . $file . $query_string .'" />'."\n";
            }
          }
        }
      }
    }

    if ($is_writable && $preprocess_css) {
      // Prefix filename to prevent blocking by firewalls which reject files
      // starting with "ad*".
      $filename = 'css_'. md5(serialize($types) . $query_string) .'.css';
      $preprocess_file = drupal_build_css_cache($types, $filename);
      $output .= '<link type="text/css" rel="stylesheet" media="'. $media .'" href="'. base_path() . $preprocess_file .'" />'."\n";
    }
  }

  return $no_module_preprocess . $output . $no_theme_preprocess;
}

/**
 * Aggregate and optimize CSS files, putting them in the files directory.
 *
 * @param $types
 *   An array of types of CSS files (e.g., screen, print) to aggregate and
 *   compress into one file.
 * @param $filename
 *   The name of the aggregate CSS file.
 * @return
 *   The name of the CSS file.
 */
function drupal_build_css_cache($types, $filename) {
  $data = '';

  // Create the css/ within the files folder.
  $csspath = file_create_path('css');
  file_check_directory($csspath, FILE_CREATE_DIRECTORY);

  if (!file_exists($csspath .'/'. $filename)) {
    // Build aggregate CSS file.
    foreach ($types as $type) {
      foreach ($type as $file => $cache) {
        if ($cache) {
          $contents = drupal_load_stylesheet($file, TRUE);
          // Return the path to where this CSS file originated from.
          $base = base_path() . dirname($file) .'/';
          _drupal_build_css_path(NULL, $base);
          // Prefix all paths within this CSS file, ignoring external and absolute paths.
          $data .= preg_replace_callback('/url\([\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\)/i', '_drupal_build_css_path', $contents);
        }
      }
    }

    // Per the W3C specification at http://www.w3.org/TR/REC-CSS2/cascade.html#at-import,
    // @import rules must proceed any other style, so we move those to the top.
    $regexp = '/@import[^;]+;/i';
    preg_match_all($regexp, $data, $matches);
    $data = preg_replace($regexp, '', $data);
    $data = implode('', $matches[0]) . $data;

    // Create the CSS file.
    file_save_data($data, $csspath .'/'. $filename, FILE_EXISTS_REPLACE);
  }
  return $csspath .'/'. $filename;
}

/**
 * Helper function for drupal_build_css_cache().
 *
 * This function will prefix all paths within a CSS file.
 */
function _drupal_build_css_path($matches, $base = NULL) {
  static $_base;
  // Store base path for preg_replace_callback.
  if (isset($base)) {
    $_base = $base;
  }

  // Prefix with base and remove '../' segments where possible.
  $path = $_base . $matches[1];
  $last = '';
  while ($path != $last) {
    $last = $path;
    $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
  }
  return 'url('. $path .')';
}

/**
 * Loads the stylesheet and resolves all @import commands.
 *
 * Loads a stylesheet and replaces @import commands with the contents of the
 * imported file. Use this instead of file_get_contents when processing
 * stylesheets.
 *
 * The returned contents are compressed removing white space and comments only
 * when CSS aggregation is enabled. This optimization will not apply for
 * color.module enabled themes with CSS aggregation turned off.
 *
 * @param $file
 *   Name of the stylesheet to be processed.
 * @param $optimize
 *   Defines if CSS contents should be compressed or not.
 * @return
 *   Contents of the stylesheet including the imported stylesheets.
 */
function drupal_load_stylesheet($file, $optimize = NULL) {
  static $_optimize;
  // Store optimization parameter for preg_replace_callback with nested @import loops.
  if (isset($optimize)) {
    $_optimize = $optimize;
  }

  $contents = '';
  if (file_exists($file)) {
    // Load the local CSS stylesheet.
    $contents = file_get_contents($file);

    // Change to the current stylesheet's directory.
    $cwd = getcwd();
    chdir(dirname($file));

    // Replaces @import commands with the actual stylesheet content.
    // This happens recursively but omits external files.
    $contents = preg_replace_callback('/@import\s*(?:url\()?[\'"]?(?![a-z]+:)([^\'"\()]+)[\'"]?\)?;/', '_drupal_load_stylesheet', $contents);
    // Remove multiple charset declarations for standards compliance (and fixing Safari problems).
    $contents = preg_replace('/^@charset\s+[\'"](\S*)\b[\'"];/i', '', $contents);

    if ($_optimize) {
      // Perform some safe CSS optimizations.
      // Regexp to match comment blocks.
      $comment     = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
      // Regexp to match double quoted strings.
      $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
      // Regexp to match single quoted strings.
      $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
      $contents = preg_replace_callback(
        "<$double_quot|$single_quot|$comment>Ss",  // Match all comment blocks along
        "_process_comment",                        // with double/single quoted strings
        $contents);                                // and feed them to _process_comment().
      $contents = preg_replace(
        '<\s*([@{}:;,]|\)\s|\s\()\s*>S',           // Remove whitespace around separators,
        '\1', $contents);                          // but keep space around parentheses.
      // End the file with a new line.
      $contents .= "\n";
    }

    // Change back directory.
    chdir($cwd);
  }

  return $contents;
}

/**
 * Process comment blocks.
 *
 * This is the callback function for the preg_replace_callback()
 * used in drupal_load_stylesheet_content(). Support for comment
 * hacks is implemented here.
 */
function _process_comment($matches) {
  static $keep_nextone = FALSE;

  // Quoted string, keep it.
  if ($matches[0][0] == "'" || $matches[0][0] == '"') {
    return $matches[0];
  }
  // End of IE-Mac hack, keep it.
  if ($keep_nextone) {
    $keep_nextone = FALSE;
    return $matches[0];
  }
  switch (strrpos($matches[0], '\\')) {
    case FALSE :
      // No backslash, strip it.
      return '';

    case drupal_strlen($matches[0])-3 :
      // Ends with \*/ so is a multi line IE-Mac hack, keep the next one also.
      $keep_nextone = TRUE;
      return '/*_\*/';

    default :
      // Single line IE-Mac hack.
      return '/*\_*/';
  }
}

/**
 * Loads stylesheets recursively and returns contents with corrected paths.
 *
 * This function is used for recursive loading of stylesheets and
 * returns the stylesheet content with all url() paths corrected.
 */
function _drupal_load_stylesheet($matches) {
  $filename = $matches[1];
  // Load the imported stylesheet and replace @import commands in there as well.
  $file = drupal_load_stylesheet($filename);
  // Determine the file's directory.
  $directory = dirname($filename);
  // If the file is in the current directory, make sure '.' doesn't appear in
  // the url() path.
  $directory = $directory == '.' ? '' : $directory .'/';

  // Alter all internal url() paths. Leave external paths alone. We don't need
  // to normalize absolute paths here (i.e. remove folder/... segments) because
  // that will be done later.
  return preg_replace('/url\s*\(([\'"]?)(?![a-z]+:|\/+)/i', 'url(\1'. $directory, $file);
}

/**
 * Delete all cached CSS files.
 */
function drupal_clear_css_cache() {
  file_scan_directory(file_create_path('css'), '.*', array('.', '..', 'CVS'), 'file_delete', TRUE);
}

/**
 * Add a JavaScript file, setting or inline code to the page.
 *
 * The behavior of this function depends on the parameters it is called with.
 * Generally, it handles the addition of JavaScript to the page, either as
 * reference to an existing file or as inline code. The following actions can be
 * performed using this function:
 *
 * - Add a file ('core', 'module' and 'theme'):
 *   Adds a reference to a JavaScript file to the page. JavaScript files
 *   are placed in a certain order, from 'core' first, to 'module' and finally
 *   'theme' so that files, that are added later, can override previously added
 *   files with ease.
 *
 * - Add inline JavaScript code ('inline'):
 *   Executes a piece of JavaScript code on the current page by placing the code
 *   directly in the page. This can, for example, be useful to tell the user that
 *   a new message arrived, by opening a pop up, alert box etc.
 *
 * - Add settings ('setting'):
 *   Adds a setting to Drupal's global storage of JavaScript settings. Per-page
 *   settings are required by some modules to function properly. The settings
 *   will be accessible at Drupal.settings.
 *
 * @param $data
 *   (optional) If given, the value depends on the $type parameter:
 *   - 'core', 'module' or 'theme': Path to the file relative to base_path().
 *   - 'inline': The JavaScript code that should be placed in the given scope.
 *   - 'setting': An array with configuration options as associative array. The
 *       array is directly placed in Drupal.settings. You might want to wrap your
 *       actual configuration settings in another variable to prevent the pollution
 *       of the Drupal.settings namespace.
 * @param $type
 *   (optional) The type of JavaScript that should be added to the page. Allowed
 *   values are 'core', 'module', 'theme', 'inline' and 'setting'. You
 *   can, however, specify any value. It is treated as a reference to a JavaScript
 *   file. Defaults to 'module'.
 * @param $scope
 *   (optional) The location in which you want to place the script. Possible
 *   values are 'header' and 'footer' by default. If your theme implements
 *   different locations, however, you can also use these.
 * @param $defer
 *   (optional) If set to TRUE, the defer attribute is set on the <script> tag.
 *   Defaults to FALSE. This parameter is not used with $type == 'setting'.
 * @param $cache
 *   (optional) If set to FALSE, the JavaScript file is loaded anew on every page
 *   call, that means, it is not cached. Defaults to TRUE. Used only when $type
 *   references a JavaScript file.
 * @param $preprocess
 *   (optional) Should this JS file be aggregated if this
 *   feature has been turned on under the performance section?
 * @return
 *   If the first parameter is NULL, the JavaScript array that has been built so
 *   far for $scope is returned. If the first three parameters are NULL,
 *   an array with all scopes is returned.
 */
function drupal_add_js($data = NULL, $type = 'module', $scope = 'header', $defer = FALSE, $cache = TRUE, $preprocess = TRUE) {
  static $javascript = array();

  if (isset($data)) {

    // Add jquery.js and drupal.js, as well as the basePath setting, the
    // first time a Javascript file is added.
    if (empty($javascript)) {
      $javascript['header'] = array(
        'core' => array(
          'misc/jquery.js' => array('cache' => TRUE, 'defer' => FALSE, 'preprocess' => TRUE),
          'misc/drupal.js' => array('cache' => TRUE, 'defer' => FALSE, 'preprocess' => TRUE),
        ),
        'module' => array(),
        'theme' => array(),
        'setting' => array(
          array('basePath' => base_path()),
        ),
        'inline' => array(),
      );
    }

    if (isset($scope) && !isset($javascript[$scope])) {
      $javascript[$scope] = array('core' => array(), 'module' => array(), 'theme' => array(), 'setting' => array(), 'inline' => array());
    }

    if (isset($type) && isset($scope) && !isset($javascript[$scope][$type])) {
      $javascript[$scope][$type] = array();
    }

    switch ($type) {
      case 'setting':
        $javascript[$scope][$type][] = $data;
        break;
      case 'inline':
        $javascript[$scope][$type][] = array('code' => $data, 'defer' => $defer);
        break;
      default:
        // If cache is FALSE, don't preprocess the JS file.
        $javascript[$scope][$type][$data] = array('cache' => $cache, 'defer' => $defer, 'preprocess' => (!$cache ? FALSE : $preprocess));
    }
  }

  if (isset($scope)) {

    if (isset($javascript[$scope])) {
      return $javascript[$scope];
    }
    else {
      return array();
    }
  }
  else {
    return $javascript;
  }
}

/**
 * Returns a themed presentation of all JavaScript code for the current page.
 *
 * References to JavaScript files are placed in a certain order: first, all
 * 'core' files, then all 'module' and finally all 'theme' JavaScript files
 * are added to the page. Then, all settings are output, followed by 'inline'
 * JavaScript code. If running update.php, all preprocessing is disabled.
 *
 * @param $scope
 *   (optional) The scope for which the JavaScript rules should be returned.
 *   Defaults to 'header'.
 * @param $javascript
 *   (optional) An array with all JavaScript code. Defaults to the default
 *   JavaScript array for the given scope.
 * @return
 *   All JavaScript code segments and includes for the scope as HTML tags.
 */
function drupal_get_js($scope = 'header', $javascript = NULL) {
  if ((!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') && function_exists('locale_update_js_files')) {
    locale_update_js_files();
  }

  if (!isset($javascript)) {
    $javascript = drupal_add_js(NULL, NULL, $scope);
  }

  if (empty($javascript)) {
    return '';
  }

  $output = '';
  $preprocessed = '';
  $no_preprocess = array('core' => '', 'module' => '', 'theme' => '');
  $files = array();
  $preprocess_js = (variable_get('preprocess_js', FALSE) && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update'));
  $directory = file_directory_path();
  $is_writable = is_dir($directory) && is_writable($directory) && (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PUBLIC);

  // A dummy query-string is added to filenames, to gain control over
  // browser-caching. The string changes on every update or full cache
  // flush, forcing browsers to load a new copy of the files, as the
  // URL changed. Files that should not be cached (see drupal_add_js())
  // get time() as query-string instead, to enforce reload on every
  // page request.
  $query_string = '?'. substr(variable_get('css_js_query_string', '0'), 0, 1);

  // For inline Javascript to validate as XHTML, all Javascript containing
  // XHTML needs to be wrapped in CDATA. To make that backwards compatible
  // with HTML 4, we need to comment out the CDATA-tag.
  $embed_prefix = "\n<!--//--><![CDATA[//><!--\n";
  $embed_suffix = "\n//--><!]]>\n";

  foreach ($javascript as $type => $data) {

    if (!$data) continue;

    switch ($type) {
      case 'setting':
        $output .= '<script type="text/javascript">' . $embed_prefix . 'jQuery.extend(Drupal.settings, ' . drupal_to_js(call_user_func_array('array_merge_recursive', $data)) . ");" . $embed_suffix . "</script>\n";
        break;
      case 'inline':
        foreach ($data as $info) {
          $output .= '<script type="text/javascript"' . ($info['defer'] ? ' defer="defer"' : '') . '>' . $embed_prefix . $info['code'] . $embed_suffix . "</script>\n";
        }
        break;
      default:
        // If JS preprocessing is off, we still need to output the scripts.
        // Additionally, go through any remaining scripts if JS preprocessing is on and output the non-cached ones.
        foreach ($data as $path => $info) {
          if (!$info['preprocess'] || !$is_writable || !$preprocess_js) {
            $no_preprocess[$type] .= '<script type="text/javascript"'. ($info['defer'] ? ' defer="defer"' : '') .' src="'. base_path() . $path . ($info['cache'] ? $query_string : '?'. time()) ."\"></script>\n";
          }
          else {
            $files[$path] = $info;
          }
        }
    }
  }

  // Aggregate any remaining JS files that haven't already been output.
  if ($is_writable && $preprocess_js && count($files) > 0) {
    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = 'js_'. md5(serialize($files) . $query_string) .'.js';
    $preprocess_file = drupal_build_js_cache($files, $filename);
    $preprocessed .= '<script type="text/javascript" src="'. base_path() . $preprocess_file .'"></script>'."\n";
  }

  // Keep the order of JS files consistent as some are preprocessed and others are not.
  // Make sure any inline or JS setting variables appear last after libraries have loaded.
  $output = $preprocessed . implode('', $no_preprocess) . $output;

  return $output;
}

/**
 * Assist in adding the tableDrag JavaScript behavior to a themed table.
 *
 * Draggable tables should be used wherever an outline or list of sortable items
 * needs to be arranged by an end-user. Draggable tables are very flexible and
 * can manipulate the value of form elements placed within individual columns.
 *
 * To set up a table to use drag and drop in place of weight select-lists or
 * in place of a form that contains parent relationships, the form must be
 * themed into a table. The table must have an id attribute set. If using
 * theme_table(), the id may be set as such:
 * @code
 * $output = theme('table', $header, $rows, array('id' => 'my-module-table'));
 * return $output;
 * @endcode
 *
 * In the theme function for the form, a special class must be added to each
 * form element within the same column, "grouping" them together.
 *
 * In a situation where a single weight column is being sorted in the table, the
 * classes could be added like this (in the theme function):
 * @code
 * $form['my_elements'][$delta]['weight']['#attributes']['class'] = "my-elements-weight";
 * @endcode
 *
 * Each row of the table must also have a class of "draggable" in order to enable the
 * drag handles:
 * @code
 * $row = array(...);
 * $rows[] = array(
 *   'data' => $row,
 *   'class' => 'draggable',
 * );
 * @endcode
 *
 * When tree relationships are present, the two additional classes
 * 'tabledrag-leaf' and 'tabledrag-root' can be used to refine the behavior:
 * - Rows with the 'tabledrag-leaf' class cannot have child rows.
 * - Rows with the 'tabledrag-root' class cannot be nested under a parent row.
 *
 * Calling drupal_add_tabledrag() would then be written as such:
 * @code
 * drupal_add_tabledrag('my-module-table', 'order', 'sibling', 'my-elements-weight');
 * @endcode
 *
 * In a more complex case where there are several groups in one column (such as
 * the block regions on the admin/build/block page), a separate subgroup class
 * must also be added to differentiate the groups.
 * @code
 * $form['my_elements'][$region][$delta]['weight']['#attributes']['class'] = "my-elements-weight my-elements-weight-". $region;
 * @endcode
 *
 * $group is still 'my-element-weight', and the additional $subgroup variable
 * will be passed in as 'my-elements-weight-'. $region. This also means that
 * you'll need to call drupal_add_tabledrag() once for every region added.
 *
 * @code
 * foreach ($regions as $region) {
 *   drupal_add_tabledrag('my-module-table', 'order', 'sibling', 'my-elements-weight', 'my-elements-weight-'. $region);
 * }
 * @endcode
 *
 * In a situation where tree relationships are present, adding multiple
 * subgroups is not necessary, because the table will contain indentations that
 * provide enough information about the sibling and parent relationships.
 * See theme_menu_overview_form() for an example creating a table containing
 * parent relationships.
 *
 * Please note that this function should be called from the theme layer, such as
 * in a .tpl.php file, theme_ function, or in a template_preprocess function,
 * not in a form declartion. Though the same JavaScript could be added to the
 * page using drupal_add_js() directly, this function helps keep template files
 * clean and readable. It also prevents tabledrag.js from being added twice
 * accidentally.
 *
 * @param $table_id
 *   String containing the target table's id attribute. If the table does not
 *   have an id, one will need to be set, such as <table id="my-module-table">.
 * @param $action
 *   String describing the action to be done on the form item. Either 'match'
 *   'depth', or 'order'. Match is typically used for parent relationships.
 *   Order is typically used to set weights on other form elements with the same
 *   group. Depth updates the target element with the current indentation.
 * @param $relationship
 *   String describing where the $action variable should be performed. Either
 *   'parent', 'sibling', 'group', or 'self'. Parent will only look for fields
 *   up the tree. Sibling will look for fields in the same group in rows above
 *   and below it. Self affects the dragged row itself. Group affects the
 *   dragged row, plus any children below it (the entire dragged group).
 * @param $group
 *   A class name applied on all related form elements for this action.
 * @param $subgroup
 *   (optional) If the group has several subgroups within it, this string should
 *   contain the class name identifying fields in the same subgroup.
 * @param $source
 *   (optional) If the $action is 'match', this string should contain the class
 *   name identifying what field will be used as the source value when matching
 *   the value in $subgroup.
 * @param $hidden
 *   (optional) The column containing the field elements may be entirely hidden
 *   from view dynamically when the JavaScript is loaded. Set to FALSE if the
 *   column should not be hidden.
 * @param $limit
 *   (optional) Limit the maximum amount of parenting in this table.
 * @see block-admin-display-form.tpl.php
 * @see theme_menu_overview_form()
 */
function drupal_add_tabledrag($table_id, $action, $relationship, $group, $subgroup = NULL, $source = NULL, $hidden = TRUE, $limit = 0) {
  static $js_added = FALSE;
  if (!$js_added) {
    drupal_add_js('misc/tabledrag.js', 'core');
    $js_added = TRUE;
  }

  // If a subgroup or source isn't set, assume it is the same as the group.
  $target = isset($subgroup) ? $subgroup : $group;
  $source = isset($source) ? $source : $target;
  $settings['tableDrag'][$table_id][$group][] = array(
    'target' => $target,
    'source' => $source,
    'relationship' => $relationship,
    'action' => $action,
    'hidden' => $hidden,
    'limit' => $limit,
  );
  drupal_add_js($settings, 'setting');
}

/**
 * Aggregate JS files, putting them in the files directory.
 *
 * @param $files
 *   An array of JS files to aggregate and compress into one file.
 * @param $filename
 *   The name of the aggregate JS file.
 * @return
 *   The name of the JS file.
 */
function drupal_build_js_cache($files, $filename) {
  $contents = '';

  // Create the js/ within the files folder.
  $jspath = file_create_path('js');
  file_check_directory($jspath, FILE_CREATE_DIRECTORY);

  if (!file_exists($jspath .'/'. $filename)) {
    // Build aggregate JS file.
    foreach ($files as $path => $info) {
      if ($info['preprocess']) {
        // Append a ';' and a newline after each JS file to prevent them from running together.
        $contents .= file_get_contents($path) .";\n";
      }
    }

    // Create the JS file.
    file_save_data($contents, $jspath .'/'. $filename, FILE_EXISTS_REPLACE);
  }

  return $jspath .'/'. $filename;
}

/**
 * Delete all cached JS files.
 */
function drupal_clear_js_cache() {
  file_scan_directory(file_create_path('js'), '.*', array('.', '..', 'CVS'), 'file_delete', TRUE);
  variable_set('javascript_parsed', array());
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
      if (empty ($var) || array_keys($var) === range(0, sizeof($var) - 1)) {
        $output = array();
        foreach ($var as $v) {
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
 * Return data in JSON format.
 *
 * This function should be used for JavaScript callback functions returning
 * data in JSON format. It sets the header for JavaScript output.
 *
 * @param $var
 *   (optional) If set, the variable will be converted to JSON and output.
 */
function drupal_json($var = NULL) {
  // We are returning JSON, so tell the browser.
  drupal_set_header('Content-Type: application/json');

  if (isset($var)) {
    echo drupal_to_js($var);
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
 * - This function should only be used on paths, not on query string arguments,
 *   otherwise unwanted double encoding will occur.
 *
 * @param $text
 *   String to encode
 */
function drupal_urlencode($text) {
  if (variable_get('clean_url', '0')) {
    return str_replace(array('%2F', '%26', '%23', '//'),
                       array('/', '%2526', '%2523', '/%252F'),
                       rawurlencode($text));
  }
  else {
    return str_replace('%2F', '/', rawurlencode($text));
  }
}

/**
 * Ensure the private key variable used to generate tokens is set.
 *
 * @return
 *   The private key.
 */
function drupal_get_private_key() {
  if (!($key = variable_get('drupal_private_key', 0))) {
    $key = drupal_random_key();
    variable_set('drupal_private_key', $key);
  }
  return $key;
}

/**
 * Generate a token based on $value, the current user session and private key.
 *
 * @param $value
 *   An additional value to base the token on.
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
 *   True for a valid token, false for an invalid token. When $skip_anonymous
 *   is true, the return value will always be true for anonymous users.
 */
function drupal_valid_token($token, $value = '', $skip_anonymous = FALSE) {
  global $user;
  return (($skip_anonymous && $user->uid == 0) || ($token === md5(session_id() . $value . variable_get('drupal_private_key', ''))));
}

/**
 * Performs one or more XML-RPC request(s).
 *
 * @param $url
 *   An absolute URL of the XML-RPC endpoint.
 *     Example:
 *     http://www.example.com/xmlrpc.php
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
  require_once './includes/mail.inc';
  require_once './includes/actions.inc';
  // Set the Drupal custom error handler.
  set_error_handler('drupal_error_handler');
  // Emit the correct charset HTTP header.
  drupal_set_header('Content-Type: text/html; charset=utf-8');
  // Detect string handling method
  unicode_check();
  // Undo magic quotes
  fix_gpc_magic();
  // Load all enabled modules
  module_load_all();
  // Ensure mt_rand is reseeded, to prevent random values from one page load
  // being exploited to predict random values in subsequent page loads.
  $seed = unpack("L", drupal_random_bytes(4));
  mt_srand($seed[1]);
  // Let all modules take action before menu system handles the request
  // We do not want this while running update.php.
  if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
    module_invoke_all('init');
  }
}

/**
 * Store the current page in the cache.
 *
 * If page_compression is enabled, a gzipped version of the page is stored in
 * the cache to avoid compressing the output on each request. The cache entry
 * is unzipped in the relatively rare event that the page is requested by a
 * client without gzip support.
 *
 * Page compression requires the PHP zlib extension
 * (http://php.net/manual/en/ref.zlib.php).
 *
 * @see drupal_page_header
 */
function page_set_cache() {
  global $user, $base_root;

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET' && page_get_cache(TRUE)) {
    // This will fail in some cases, see page_get_cache() for the explanation.
    if ($data = ob_get_contents()) {
      if (variable_get('page_compression', TRUE) && extension_loaded('zlib')) {
        $data = gzencode($data, 9, FORCE_GZIP);
      }
      ob_end_flush();
      cache_set($base_root . request_uri(), $data, 'cache_page', CACHE_TEMPORARY, drupal_get_headers());
    }
  }
}

/**
 * Executes a cron run when called
 * @return
 * Returns TRUE if ran successfully
 */
function drupal_cron_run() {
  // Try to allocate enough time to run all the hook_cron implementations.
  if (function_exists('set_time_limit')) {
    @set_time_limit(240);
  }

  // Fetch the cron semaphore
  $semaphore = variable_get('cron_semaphore', FALSE);

  if ($semaphore) {
    if (time() - $semaphore > 3600) {
      // Either cron has been running for more than an hour or the semaphore
      // was not reset due to a database error.
      watchdog('cron', 'Cron has been running for more than an hour and is most likely stuck.', array(), WATCHDOG_ERROR);

      // Release cron semaphore
      variable_del('cron_semaphore');
    }
    else {
      // Cron is still running normally.
      watchdog('cron', 'Attempting to re-run cron while it is already running.', array(), WATCHDOG_WARNING);
    }
  }
  else {
    // Register shutdown callback
    register_shutdown_function('drupal_cron_cleanup');

    // Lock cron semaphore
    variable_set('cron_semaphore', time());

    // Iterate through the modules calling their cron handlers (if any):
    module_invoke_all('cron');

    // Record cron time
    variable_set('cron_last', time());
    watchdog('cron', 'Cron run completed.', array(), WATCHDOG_NOTICE);

    // Release cron semaphore
    variable_del('cron_semaphore');

    // Return TRUE so other functions can check if it did run successfully
    return TRUE;
  }
}

/**
 * Shutdown function for cron cleanup.
 */
function drupal_cron_cleanup() {
  // See if the semaphore is still locked.
  if (variable_get('cron_semaphore', FALSE)) {
    watchdog('cron', 'Cron run exceeded the time limit and was aborted.', array(), WATCHDOG_WARNING);

    // Release cron semaphore
    variable_del('cron_semaphore');
  }
}

/**
 * Return an array of system file objects.
 *
 * Returns an array of file objects of the given type from the site-wide
 * directory (i.e. modules/), the all-sites directory (i.e.
 * sites/all/modules/), the profiles directory, and site-specific directory
 * (i.e. sites/somesite/modules/). The returned array will be keyed using the
 * key specified (name, basename, filename). Using name or basename will cause
 * site-specific files to be prioritized over similar files in the default
 * directories. That is, if a file with the same name appears in both the
 * site-wide directory and site-specific directory, only the site-specific
 * version will be included.
 *
 * @param $mask
 *   The regular expression of the files to find.
 * @param $directory
 *   The subdirectory name in which the files are found. For example,
 *   'modules' will search in both modules/ and
 *   sites/somesite/modules/.
 * @param $key
 *   The key to be passed to file_scan_directory().
 * @param $min_depth
 *   Minimum depth of directories to return files from.
 *
 * @return
 *   An array of file objects of the specified type.
 */
function drupal_system_listing($mask, $directory, $key = 'name', $min_depth = 1) {
  global $profile;
  $config = conf_path();

  // When this function is called during Drupal's initial installation process,
  // the name of the profile that's about to be installed is stored in the global
  // $profile variable. At all other times, the standard Drupal systems variable
  // table contains the name of the current profile, and we can call variable_get()
  // to determine what one is active.
  if (!isset($profile)) {
    $profile = variable_get('install_profile', 'default');
  }
  $searchdir = array($directory);
  $files = array();

  // The 'profiles' directory contains pristine collections of modules and
  // themes as organized by a distribution.  It is pristine in the same way
  // that /modules is pristine for core; users should avoid changing anything
  // there in favor of sites/all or sites/<domain> directories.
  if (file_exists("profiles/$profile/$directory")) {
    $searchdir[] = "profiles/$profile/$directory";
  }

  // Always search sites/all/* as well as the global directories
  $searchdir[] = 'sites/all/'. $directory;

  if (file_exists("$config/$directory")) {
    $searchdir[] = "$config/$directory";
  }

  // Get current list of items
  foreach ($searchdir as $dir) {
    $files = array_merge($files, file_scan_directory($dir, $mask, array('.', '..', 'CVS'), 0, TRUE, $key, $min_depth));
  }

  return $files;
}


/**
 * Hands off alterable variables to type-specific *_alter implementations.
 *
 * This dispatch function hands off the passed in variables to type-specific
 * hook_TYPE_alter() implementations in modules. It ensures a consistent
 * interface for all altering operations.
 *
 * @param $type
 *   A string describing the type of the alterable $data (e.g. 'form',
 *   'profile').
 * @param $data
 *   The variable that will be passed to hook_TYPE_alter() implementations to
 *   be altered. The type of this variable depends on $type. For example, when
 *   altering a 'form', $data will be a structured array. When altering a
 *   'profile', $data will be an object. If you need to pass additional
 *   parameters by reference to the hook_TYPE_alter() functions, include them
 *   as an array in $data['__drupal_alter_by_ref']. They will be unpacked and
 *   passed to the hook_TYPE_alter() functions, before the additional
 *   ... parameters (see below).
 * @param ...
 *   Any additional parameters will be passed on to the hook_TYPE_alter()
 *   functions (not by reference), after any by-reference parameters included
 *   in $data (see above)
 */
function drupal_alter($type, &$data) {
  // PHP's func_get_args() always returns copies of params, not references, so
  // drupal_alter() can only manipulate data that comes in via the required first
  // param. For the edge case functions that must pass in an arbitrary number of
  // alterable parameters (hook_form_alter() being the best example), an array of
  // those params can be placed in the __drupal_alter_by_ref key of the $data
  // array. This is somewhat ugly, but is an unavoidable consequence of a flexible
  // drupal_alter() function, and the limitations of func_get_args().
  // @todo: Remove this in Drupal 7.
  if (is_array($data) && isset($data['__drupal_alter_by_ref'])) {
    $by_ref_parameters = $data['__drupal_alter_by_ref'];
    unset($data['__drupal_alter_by_ref']);
  }

  // Hang onto a reference to the data array so that it isn't blown away later.
  // Also, merge in any parameters that need to be passed by reference.
  $args = array(&$data);
  if (isset($by_ref_parameters)) {
    $args = array_merge($args, $by_ref_parameters);
  }

  // Now, use func_get_args() to pull in any additional parameters passed into
  // the drupal_alter() call.
  $additional_args = func_get_args();
  array_shift($additional_args);
  array_shift($additional_args);
  $args = array_merge($args, $additional_args);

  foreach (module_implements($type .'_alter') as $module) {
    $function = $module .'_'. $type .'_alter';
    call_user_func_array($function, $args);
  }
}


/**
 * Renders HTML given a structured array tree.
 *
 * Recursively iterates over each of the array elements, generating HTML code.
 * This function is usually called from within another function, like
 * drupal_get_form() or node_view().
 *
 * drupal_render() flags each element with a '#printed' status to indicate that
 * the element has been rendered, which allows individual elements of a given
 * array to be rendered independently. This prevents elements from being
 * rendered more than once on subsequent calls to drupal_render() if, for example,
 * they are part of a larger array. If the same array or array element is passed
 * more than once to drupal_render(), it simply returns a NULL value.
 *
 * @param $elements
 *   The structured array describing the data to be rendered.
 * @return
 *   The rendered HTML.
 */
function drupal_render(&$elements) {
  if (!isset($elements) || (isset($elements['#access']) && !$elements['#access'])) {
    return NULL;
  }

  // If the default values for this element haven't been loaded yet, populate
  // them.
  if (!isset($elements['#defaults_loaded']) || !$elements['#defaults_loaded']) {
    if ((!empty($elements['#type'])) && ($info = _element_info($elements['#type']))) {
      $elements += $info;
    }
  }

  // Make any final changes to the element before it is rendered. This means
  // that the $element or the children can be altered or corrected before the
  // element is rendered into the final text.
  if (isset($elements['#pre_render'])) {
    foreach ($elements['#pre_render'] as $function) {
      if (function_exists($function)) {
        $elements = $function($elements);
      }
    }
  }

  $content = '';
  // Either the elements did not go through form_builder or one of the children
  // has a #weight.
  if (!isset($elements['#sorted'])) {
    uasort($elements, "element_sort");
  }
  $elements += array('#title' => NULL, '#description' => NULL);
  if (!isset($elements['#children'])) {
    $children = element_children($elements);
    // Render all the children that use a theme function.
    if (isset($elements['#theme']) && empty($elements['#theme_used'])) {
      $elements['#theme_used'] = TRUE;

      $previous = array();
      foreach (array('#value', '#type', '#prefix', '#suffix') as $key) {
        $previous[$key] = isset($elements[$key]) ? $elements[$key] : NULL;
      }
      // If we rendered a single element, then we will skip the renderer.
      if (empty($children)) {
        $elements['#printed'] = TRUE;
      }
      else {
        $elements['#value'] = '';
      }
      $elements['#type'] = 'markup';

      unset($elements['#prefix'], $elements['#suffix']);
      $content = theme($elements['#theme'], $elements);

      foreach (array('#value', '#type', '#prefix', '#suffix') as $key) {
        $elements[$key] = isset($previous[$key]) ? $previous[$key] : NULL;
      }
    }
    // Render each of the children using drupal_render and concatenate them.
    if (!isset($content) || $content === '') {
      foreach ($children as $key) {
        $content .= drupal_render($elements[$key]);
      }
    }
  }
  if (isset($content) && $content !== '') {
    $elements['#children'] = $content;
  }

  // Until now, we rendered the children, here we render the element itself
  if (!isset($elements['#printed'])) {
    $content = theme(!empty($elements['#type']) ? $elements['#type'] : 'markup', $elements);
    $elements['#printed'] = TRUE;
  }

  if (isset($content) && $content !== '') {
    // Filter the outputted content and make any last changes before the
    // content is sent to the browser. The changes are made on $content
    // which allows the output'ed text to be filtered.
    if (isset($elements['#post_render'])) {
      foreach ($elements['#post_render'] as $function) {
        if (function_exists($function)) {
          $content = $function($content, $elements);
        }
      }
    }
    $prefix = isset($elements['#prefix']) ? $elements['#prefix'] : '';
    $suffix = isset($elements['#suffix']) ? $elements['#suffix'] : '';
    return $prefix . $content . $suffix;
  }
}

/**
 * Function used by uasort to sort structured arrays by weight.
 */
function element_sort($a, $b) {
  $a_weight = (is_array($a) && isset($a['#weight'])) ? $a['#weight'] : 0;
  $b_weight = (is_array($b) && isset($b['#weight'])) ? $b['#weight'] : 0;
  if ($a_weight == $b_weight) {
    return 0;
  }
  return ($a_weight < $b_weight) ? -1 : 1;
}

/**
 * Check if the key is a property.
 */
function element_property($key) {
  return $key[0] == '#';
}

/**
 * Get properties of a structured array element. Properties begin with '#'.
 */
function element_properties($element) {
  return array_filter(array_keys((array) $element), 'element_property');
}

/**
 * Check if the key is a child.
 */
function element_child($key) {
  return !isset($key[0]) || $key[0] != '#';
}

/**
 * Get keys of a structured array tree element that are not properties (i.e., do not begin with '#').
 */
function element_children($element) {
  return array_filter(array_keys((array) $element), 'element_child');
}

/**
 * Provide theme registration for themes across .inc files.
 */
function drupal_common_theme() {
  return array(
    // theme.inc
    'placeholder' => array(
      'arguments' => array('text' => NULL)
    ),
    'page' => array(
      'arguments' => array('content' => NULL, 'show_blocks' => TRUE, 'show_messages' => TRUE),
      'template' => 'page',
    ),
    'maintenance_page' => array(
      'arguments' => array('content' => NULL, 'show_blocks' => TRUE, 'show_messages' => TRUE),
      'template' => 'maintenance-page',
    ),
    'update_page' => array(
      'arguments' => array('content' => NULL, 'show_messages' => TRUE),
    ),
    'install_page' => array(
      'arguments' => array('content' => NULL),
    ),
    'task_list' => array(
      'arguments' => array('items' => NULL, 'active' => NULL),
    ),
    'status_messages' => array(
      'arguments' => array('display' => NULL),
    ),
    'links' => array(
      'arguments' => array('links' => NULL, 'attributes' => array('class' => 'links')),
    ),
    'image' => array(
      'arguments' => array('path' => NULL, 'alt' => '', 'title' => '', 'attributes' => NULL, 'getsize' => TRUE),
    ),
    'breadcrumb' => array(
      'arguments' => array('breadcrumb' => NULL),
    ),
    'help' => array(
      'arguments' => array(),
    ),
    'submenu' => array(
      'arguments' => array('links' => NULL),
    ),
    'table' => array(
      'arguments' => array('header' => NULL, 'rows' => NULL, 'attributes' => array(), 'caption' => NULL),
    ),
    'table_select_header_cell' => array(
      'arguments' => array(),
    ),
    'tablesort_indicator' => array(
      'arguments' => array('style' => NULL),
    ),
    'box' => array(
      'arguments' => array('title' => NULL, 'content' => NULL, 'region' => 'main'),
      'template' => 'box',
    ),
    'block' => array(
      'arguments' => array('block' => NULL),
      'template' => 'block',
    ),
    'mark' => array(
      'arguments' => array('type' => MARK_NEW),
    ),
    'item_list' => array(
      'arguments' => array('items' => array(), 'title' => NULL, 'type' => 'ul', 'attributes' => NULL),
    ),
    'more_help_link' => array(
      'arguments' => array('url' => NULL),
    ),
    'xml_icon' => array(
      'arguments' => array('url' => NULL),
    ),
    'feed_icon' => array(
      'arguments' => array('url' => NULL, 'title' => NULL),
    ),
    'more_link' => array(
      'arguments' => array('url' => NULL, 'title' => NULL)
    ),
    'closure' => array(
      'arguments' => array('main' => 0),
    ),
    'blocks' => array(
      'arguments' => array('region' => NULL),
    ),
    'username' => array(
      'arguments' => array('object' => NULL),
    ),
    'progress_bar' => array(
      'arguments' => array('percent' => NULL, 'message' => NULL),
    ),
    'indentation' => array(
      'arguments' => array('size' => 1),
    ),
    // from pager.inc
    'pager' => array(
      'arguments' => array('tags' => array(), 'limit' => 10, 'element' => 0, 'parameters' => array()),
    ),
    'pager_first' => array(
      'arguments' => array('text' => NULL, 'limit' => NULL, 'element' => 0, 'parameters' => array()),
    ),
    'pager_previous' => array(
      'arguments' => array('text' => NULL, 'limit' => NULL, 'element' => 0, 'interval' => 1, 'parameters' => array()),
    ),
    'pager_next' => array(
      'arguments' => array('text' => NULL, 'limit' => NULL, 'element' => 0, 'interval' => 1, 'parameters' => array()),
    ),
    'pager_last' => array(
      'arguments' => array('text' => NULL, 'limit' => NULL, 'element' => 0, 'parameters' => array()),
    ),
    'pager_link' => array(
      'arguments' => array('text' => NULL, 'page_new' => NULL, 'element' => NULL, 'parameters' => array(), 'attributes' => array()),
    ),
    // from menu.inc
    'menu_item_link' => array(
      'arguments' => array('item' => NULL),
    ),
    'menu_tree' => array(
      'arguments' => array('tree' => NULL),
    ),
    'menu_item' => array(
      'arguments' => array('link' => NULL, 'has_children' => NULL, 'menu' => ''),
    ),
    'menu_local_task' => array(
      'arguments' => array('link' => NULL, 'active' => FALSE),
    ),
    'menu_local_tasks' => array(
      'arguments' => array(),
    ),
    // from form.inc
    'select' => array(
      'arguments' => array('element' => NULL),
    ),
    'fieldset' => array(
      'arguments' => array('element' => NULL),
    ),
    'radio' => array(
      'arguments' => array('element' => NULL),
    ),
    'radios' => array(
      'arguments' => array('element' => NULL),
    ),
    'password_confirm' => array(
      'arguments' => array('element' => NULL),
    ),
    'date' => array(
      'arguments' => array('element' => NULL),
    ),
    'item' => array(
      'arguments' => array('element' => NULL),
    ),
    'checkbox' => array(
      'arguments' => array('element' => NULL),
    ),
    'checkboxes' => array(
      'arguments' => array('element' => NULL),
    ),
    'submit' => array(
      'arguments' => array('element' => NULL),
    ),
    'button' => array(
      'arguments' => array('element' => NULL),
    ),
    'image_button' => array(
      'arguments' => array('element' => NULL),
    ),
    'hidden' => array(
      'arguments' => array('element' => NULL),
    ),
    'token' => array(
      'arguments' => array('element' => NULL),
    ),
    'textfield' => array(
      'arguments' => array('element' => NULL),
    ),
    'form' => array(
      'arguments' => array('element' => NULL),
    ),
    'textarea' => array(
      'arguments' => array('element' => NULL),
    ),
    'markup' => array(
      'arguments' => array('element' => NULL),
    ),
    'password' => array(
      'arguments' => array('element' => NULL),
    ),
    'file' => array(
      'arguments' => array('element' => NULL),
    ),
    'form_element' => array(
      'arguments' => array('element' => NULL, 'value' => NULL),
    ),
  );
}

/**
 * @ingroup schemaapi
 * @{
 */

/**
 * Get the schema definition of a table, or the whole database schema.
 *
 * The returned schema will include any modifications made by any
 * module that implements hook_schema_alter().
 *
 * @param $table
 *   The name of the table. If not given, the schema of all tables is returned.
 * @param $rebuild
 *   If true, the schema will be rebuilt instead of retrieved from the cache.
 */
function drupal_get_schema($table = NULL, $rebuild = FALSE) {
  static $schema = array();

  if (empty($schema) || $rebuild) {
    // Try to load the schema from cache.
    if (!$rebuild && $cached = cache_get('schema')) {
      $schema = $cached->data;
    }
    // Otherwise, rebuild the schema cache.
    else {
      $schema = array();
      // Load the .install files to get hook_schema.
      module_load_all_includes('install');

      // Invoke hook_schema for all modules.
      foreach (module_implements('schema') as $module) {
        // Cast the result of hook_schema() to an array, as a NULL return value
        // would cause array_merge() to set the $schema variable to NULL as well.
        // That would break modules which use $schema further down the line.
        $current = (array) module_invoke($module, 'schema');
        _drupal_initialize_schema($module, $current);
        $schema = array_merge($schema, $current);
      }

      drupal_alter('schema', $schema);
      cache_set('schema', $schema);
    }
  }

  if (!isset($table)) {
    return $schema;
  }
  elseif (isset($schema[$table])) {
    return $schema[$table];
  }
  else {
    return FALSE;
  }
}

/**
 * Create all tables that a module defines in its hook_schema().
 *
 * Note: This function does not pass the module's schema through
 * hook_schema_alter(). The module's tables will be created exactly as the
 * module defines them.
 *
 * @param $module
 *   The module for which the tables will be created.
 * @return
 *   An array of arrays with the following key/value pairs:
 *    - success: a boolean indicating whether the query succeeded.
 *    - query: the SQL query(s) executed, passed through check_plain().
 */
function drupal_install_schema($module) {
  $schema = drupal_get_schema_unprocessed($module);
  _drupal_initialize_schema($module, $schema);

  $ret = array();
  foreach ($schema as $name => $table) {
    db_create_table($ret, $name, $table);
  }
  return $ret;
}

/**
 * Remove all tables that a module defines in its hook_schema().
 *
 * Note: This function does not pass the module's schema through
 * hook_schema_alter(). The module's tables will be created exactly as the
 * module defines them.
 *
 * @param $module
 *   The module for which the tables will be removed.
 * @return
 *   An array of arrays with the following key/value pairs:
 *    - success: a boolean indicating whether the query succeeded.
 *    - query: the SQL query(s) executed, passed through check_plain().
 */
function drupal_uninstall_schema($module) {
  $schema = drupal_get_schema_unprocessed($module);
  _drupal_initialize_schema($module, $schema);

  $ret = array();
  foreach ($schema as $table) {
    db_drop_table($ret, $table['name']);
  }
  return $ret;
}

/**
 * Returns the unprocessed and unaltered version of a module's schema.
 *
 * Use this function only if you explicitly need the original
 * specification of a schema, as it was defined in a module's
 * hook_schema(). No additional default values will be set,
 * hook_schema_alter() is not invoked and these unprocessed
 * definitions won't be cached.
 *
 * This function can be used to retrieve a schema specification in
 * hook_schema(), so it allows you to derive your tables from existing
 * specifications.
 *
 * It is also used by drupal_install_schema() and
 * drupal_uninstall_schema() to ensure that a module's tables are
 * created exactly as specified without any changes introduced by a
 * module that implements hook_schema_alter().
 *
 * @param $module
 *   The module to which the table belongs.
 * @param $table
 *   The name of the table. If not given, the module's complete schema
 *   is returned.
 */
function drupal_get_schema_unprocessed($module, $table = NULL) {
  // Load the .install file to get hook_schema.
  module_load_install($module);
  $schema = module_invoke($module, 'schema');

  if (!is_null($table) && isset($schema[$table])) {
    return $schema[$table];
  }
  elseif (!empty($schema)) {
    return $schema;
  }

  return array();
}

/**
 * Fill in required default values for table definitions returned by hook_schema().
 *
 * @param $module
 *   The module for which hook_schema() was invoked.
 * @param $schema
 *   The schema definition array as it was returned by the module's
 *   hook_schema().
 */
function _drupal_initialize_schema($module, &$schema) {
  // Set the name and module key for all tables.
  foreach ($schema as $name => $table) {
    if (empty($table['module'])) {
      $schema[$name]['module'] = $module;
    }
    if (!isset($table['name'])) {
      $schema[$name]['name'] = $name;
    }
  }
}

/**
 * Retrieve a list of fields from a table schema. The list is suitable for use in a SQL query.
 *
 * @param $table
 *   The name of the table from which to retrieve fields.
 * @param
 *   An optional prefix to to all fields.
 *
 * @return An array of fields.
 **/
function drupal_schema_fields_sql($table, $prefix = NULL) {
  $schema = drupal_get_schema($table);
  $fields = array_keys($schema['fields']);
  if ($prefix) {
    $columns = array();
    foreach ($fields as $field) {
      $columns[] = "$prefix.$field";
    }
    return $columns;
  }
  else {
    return $fields;
  }
}

/**
 * Save a record to the database based upon the schema.
 *
 * Default values are filled in for missing items, and 'serial' (auto increment)
 * types are filled in with IDs.
 *
 * @param $table
 *   The name of the table; this must exist in schema API.
 * @param $object
 *   The object to write. This is a reference, as defaults according to
 *   the schema may be filled in on the object, as well as ID on the serial
 *   type(s). Both array an object types may be passed.
 * @param $update
 *   If this is an update, specify the primary keys' field names. It is the
 *   caller's responsibility to know if a record for this object already
 *   exists in the database. If there is only 1 key, you may pass a simple string.
 * @return
 *   Failure to write a record will return FALSE. Otherwise SAVED_NEW or
 *   SAVED_UPDATED is returned depending on the operation performed. The
 *   $object parameter contains values for any serial fields defined by
 *   the $table. For example, $object->nid will be populated after inserting
 *   a new node.
 */
function drupal_write_record($table, &$object, $update = array()) {
  // Standardize $update to an array.
  if (is_string($update)) {
    $update = array($update);
  }

  $schema = drupal_get_schema($table);
  if (empty($schema)) {
    return FALSE;
  }

  // Convert to an object if needed.
  if (is_array($object)) {
    $object = (object) $object;
    $array = TRUE;
  }
  else {
    $array = FALSE;
  }

  $fields = $defs = $values = $serials = $placeholders = array();

  // Go through our schema, build SQL, and when inserting, fill in defaults for
  // fields that are not set.
  foreach ($schema['fields'] as $field => $info) {
    // Special case -- skip serial types if we are updating.
    if ($info['type'] == 'serial' && count($update)) {
      continue;
    }

    // For inserts, populate defaults from Schema if not already provided
    if (!isset($object->$field) && !count($update) && isset($info['default'])) {
      $object->$field = $info['default'];
    }

    // Track serial fields so we can helpfully populate them after the query.
    if ($info['type'] == 'serial') {
      $serials[] = $field;
      // Ignore values for serials when inserting data. Unsupported.
      unset($object->$field);
    }

    // Build arrays for the fields, placeholders, and values in our query.
    if (isset($object->$field)) {
      $fields[] = $field;
      $placeholders[] = db_type_placeholder($info['type']);

      if (empty($info['serialize'])) {
        $values[] = $object->$field;
      }
      else {
        $values[] = serialize($object->$field);
      }
    }
  }

  // Build the SQL.
  $query = '';
  if (!count($update)) {
    $query = "INSERT INTO {". $table ."} (". implode(', ', $fields) .') VALUES ('. implode(', ', $placeholders) .')';
    $return = SAVED_NEW;
  }
  else {
    $query = '';
    foreach ($fields as $id => $field) {
      if ($query) {
        $query .= ', ';
      }
      $query .= $field .' = '. $placeholders[$id];
    }

    foreach ($update as $key){
      $conditions[] = "$key = ". db_type_placeholder($schema['fields'][$key]['type']);
      $values[] = $object->$key;
    }

    $query = "UPDATE {". $table ."} SET $query WHERE ". implode(' AND ', $conditions);
    $return = SAVED_UPDATED;
  }

  // Execute the SQL.
  if (db_query($query, $values)) {
    if ($serials) {
      // Get last insert ids and fill them in.
      foreach ($serials as $field) {
        $object->$field = db_last_insert_id($table, $field);
      }
    }
  }
  else {
    $return = FALSE;
  }

  // If we began with an array, convert back so we don't surprise the caller.
  if ($array) {
    $object = (array) $object;
  }

  return $return;
}

/**
 * @} End of "ingroup schemaapi".
 */

/**
 * Parse Drupal info file format.
 *
 * Files should use an ini-like format to specify values.
 * White-space generally doesn't matter, except inside values.
 * e.g.
 *
 * @code
 *   key = value
 *   key = "value"
 *   key = 'value'
 *   key = "multi-line
 *
 *   value"
 *   key = 'multi-line
 *
 *   value'
 *   key
 *   =
 *   'value'
 * @endcode
 *
 * Arrays are created using a GET-like syntax:
 *
 * @code
 *   key[] = "numeric array"
 *   key[index] = "associative array"
 *   key[index][] = "nested numeric array"
 *   key[index][index] = "nested associative array"
 * @endcode
 *
 * PHP constants are substituted in, but only when used as the entire value:
 *
 * Comments should start with a semi-colon at the beginning of a line.
 *
 * This function is NOT for placing arbitrary module-specific settings. Use
 * variable_get() and variable_set() for that.
 *
 * Information stored in the module.info file:
 * - name: The real name of the module for display purposes.
 * - description: A brief description of the module.
 * - dependencies: An array of shortnames of other modules this module depends on.
 * - package: The name of the package of modules this module belongs to.
 *
 * Example of .info file:
 * @code
 *   name = Forum
 *   description = Enables threaded discussions about general topics.
 *   dependencies[] = taxonomy
 *   dependencies[] = comment
 *   package = Core - optional
 *   version = VERSION
 * @endcode
 *
 * @param $filename
 *   The file we are parsing. Accepts file with relative or absolute path.
 * @return
 *   The info array.
 */
function drupal_parse_info_file($filename) {
  $info = array();
  $constants = get_defined_constants();

  if (!file_exists($filename)) {
    return $info;
  }

  $data = file_get_contents($filename);
  if (preg_match_all('
    @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
    ((?:
      [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
      \[[^\[\]]*\]                  # unless they are balanced and not nested
    )+?)
    \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
    (?:
      ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
      (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
      ([^\r\n]*?)                   # Non-quoted string
    )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
    @msx', $data, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      // Fetch the key and value string
      $i = 0;
      foreach (array('key', 'value1', 'value2', 'value3') as $var) {
        $$var = isset($match[++$i]) ? $match[$i] : '';
      }
      $value = stripslashes(substr($value1, 1, -1)) . stripslashes(substr($value2, 1, -1)) . $value3;

      // Parse array syntax
      $keys = preg_split('/\]?\[/', rtrim($key, ']'));
      $last = array_pop($keys);
      $parent = &$info;

      // Create nested arrays
      foreach ($keys as $key) {
        if ($key == '') {
          $key = count($parent);
        }
        if (!isset($parent[$key]) || !is_array($parent[$key])) {
          $parent[$key] = array();
        }
        $parent = &$parent[$key];
      }

      // Handle PHP constants.
      if (isset($constants[$value])) {
        $value = $constants[$value];
      }

      // Insert actual value
      if ($last == '') {
        $last = count($parent);
      }
      $parent[$last] = $value;
    }
  }

  return $info;
}

/**
 * @return
 *   Array of the possible severity levels for log messages.
 *
 * @see watchdog
 */
function watchdog_severity_levels() {
  return array(
    WATCHDOG_EMERG    => t('emergency'),
    WATCHDOG_ALERT    => t('alert'),
    WATCHDOG_CRITICAL => t('critical'),
    WATCHDOG_ERROR    => t('error'),
    WATCHDOG_WARNING  => t('warning'),
    WATCHDOG_NOTICE   => t('notice'),
    WATCHDOG_INFO     => t('info'),
    WATCHDOG_DEBUG    => t('debug'),
  );
}


/**
 * Explode a string of given tags into an array.
 *
 * @see drupal_implode_tags()
 */
function drupal_explode_tags($tags) {
  // This regexp allows the following types of user input:
  // this, "somecompany, llc", "and ""this"" w,o.rks", foo bar
  $regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
  preg_match_all($regexp, $tags, $matches);
  $typed_tags = array_unique($matches[1]);

  $tags = array();
  foreach ($typed_tags as $tag) {
    // If a user has escaped a term (to demonstrate that it is a group,
    // or includes a comma or quote character), we remove the escape
    // formatting so to save the term into the database as the user intends.
    $tag = trim(str_replace('""', '"', preg_replace('/^"(.*)"$/', '\1', $tag)));
    if ($tag != "") {
      $tags[] = $tag;
    }
  }

  return $tags;
}

/**
 * Implode an array of tags into a string.
 *
 * @see drupal_explode_tags()
 */
function drupal_implode_tags($tags) {
  $encoded_tags = array();
  foreach ($tags as $tag) {
    // Commas and quotes in tag names are special cases, so encode them.
    if (strpos($tag, ',') !== FALSE || strpos($tag, '"') !== FALSE) {
      $tag = '"'. str_replace('"', '""', $tag) .'"';
    }

    $encoded_tags[] = $tag;
  }
  return implode(', ', $encoded_tags);
}

/**
 * Flush all cached data on the site.
 *
 * Empties cache tables, rebuilds the menu cache and theme registries, and
 * invokes a hook so that other modules' cache data can be cleared as well.
 */
function drupal_flush_all_caches() {
  // Change query-strings on css/js files to enforce reload for all users.
  _drupal_flush_css_js();

  drupal_clear_css_cache();
  drupal_clear_js_cache();

  // If invoked from update.php, we must not update the theme information in the
  // database, or this will result in all themes being disabled.
  if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'update') {
    _system_theme_data();
  }
  else {
    system_theme_data();
  }

  drupal_rebuild_theme_registry();
  menu_rebuild();
  node_types_rebuild();
  // Don't clear cache_form - in-progress form submissions may break.
  // Ordered so clearing the page cache will always be the last action.
  $core = array('cache', 'cache_block', 'cache_filter', 'cache_page');
  $cache_tables = array_merge(module_invoke_all('flush_caches'), $core);
  foreach ($cache_tables as $table) {
    cache_clear_all('*', $table, TRUE);
  }
}

/**
 * Helper function to change query-strings on css/js files.
 *
 * Changes the character added to all css/js files as dummy query-string,
 * so that all browsers are forced to reload fresh files. We keep
 * 20 characters history (FIFO) to avoid repeats, but only the first
 * (newest) character is actually used on URLs, to keep them short.
 * This is also called from update.php.
 */
function _drupal_flush_css_js() {
  $string_history = variable_get('css_js_query_string', '00000000000000000000');
  $new_character = $string_history[0];
  // Not including 'q' to allow certain JavaScripts to re-use query string.
  $characters = 'abcdefghijklmnoprstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  while (strpos($string_history, $new_character) !== FALSE) {
    $new_character = $characters[mt_rand(0, strlen($characters) - 1)];
  }
  variable_set('css_js_query_string', $new_character . substr($string_history, 0, 19));
}
