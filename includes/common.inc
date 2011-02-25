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
 *   A specified region to fetch content for. If NULL, all regions will be returned.
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
 *   The url for the feed
 * @param $title
 *   The title of the feed
 */
function drupal_add_feed($url = NULL, $title = '') {
  static $stored_feed_links = array();

  if (!is_null($url)) {
    $stored_feed_links[$url] = theme('feed_icon', $url);

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
 *   The delimiter to split feeds by
 */
function drupal_get_feeds($delimiter = "\n") {
  $feeds = drupal_add_feed();
  return implode($feeds, $delimiter);
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
 * is returned. As such, a destination can persist across multiple
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
 * parameters. However you may override that behavior by setting a
 * <em>destination</em> in either the $_REQUEST-array (i.e. by using
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
 *   A Drupal path or a full URL.
 * @param $query
 *   The query string component, if any.
 * @param $fragment
 *   The destination fragment identifier (named anchor).
 * @param $http_response_code
 *   Valid values for an actual "goto" as per RFC 2616 section 10.3 are:
 *   - 301 Moved Permanently (the recommended value for most redirects)
 *   - 302 Found (default in Drupal and PHP, sometimes used for spamming search
 *         engines)
 *   - 303 See Other
 *   - 304 Not Modified
 *   - 305 Use Proxy
 *   - 307 Temporary Redirect (an alternative to "503 Site Down for Maintenance")
 *   Note: Other values are defined by RFC 2616, but are rarely used and poorly
 *         supported.
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
    $colonpos = strpos($destination, ':');
    $absolute = ($colonpos !== FALSE && !preg_match('![/?#]!', substr($destination, 0, $colonpos)));
    if (!$absolute) {
      extract(parse_url(urldecode($destination)));
    }
  }

  $url = url($path, $query, $fragment, TRUE);
  // Remove newlines from the URL to avoid header injection attacks.
  $url = str_replace(array("\n", "\r"), '', $url);

  // Before the redirect, allow modules to react to the end of the page request.
  module_invoke_all('exit', $url);

  // Even though session_write_close() is registered as a shutdown function, we
  // need all session data written to the database before redirecting.
  session_write_close();

  header('Location: '. $url, TRUE, $http_response_code);

  // The "Location" header sends a REDIRECT status code to the http
  // daemon. In some cases this can go wrong, so we make sure none
  // of the code below the drupal_goto() call gets executed when we redirect.
  exit();
}

/**
 * Generates a site off-line message
 */
function drupal_site_offline() {
  drupal_set_header('HTTP/1.1 503 Service unavailable');
  drupal_set_title(t('Site off-line'));
  print theme('maintenance_page', filter_xss_admin(variable_get('site_offline_message',
    t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal'))))));
}

/**
 * Generates a 404 error if the request can not be handled.
 */
function drupal_not_found() {
  drupal_set_header('HTTP/1.1 404 Not Found');

  watchdog('page not found', check_plain($_GET['q']), WATCHDOG_WARNING);

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

  if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
    drupal_set_title(t('Page not found'));
    menu_set_active_item('');
    $return = '';
  }
  // To conserve CPU and bandwidth, omit the blocks
  print theme('page', $return, FALSE);
}

/**
 * Generates a 403 error if the request is not allowed.
 */
function drupal_access_denied() {
  drupal_set_header('HTTP/1.1 403 Forbidden');
  watchdog('access denied', check_plain($_GET['q']), WATCHDOG_WARNING);

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

  if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
    drupal_set_title(t('Access denied'));
    menu_set_active_item('');
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
  $result = new stdClass();

  // Parse the URL, and make sure we can handle the schema.
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
      $result->code = -1003;
      return $result;
  }

  // Make sure the socket opened properly.
  if (!$fp) {
    $result->error = trim($errno .' '. $errstr);
    $result->code = -$errno;
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

  // If the server url has a user then attempt to use basic authentication
  if (isset($uri['user'])) {
    $defaults['Authorization'] = 'Authorization: Basic '. base64_encode($uri['user'] . (!empty($uri['pass']) ? ":". $uri['pass'] : ''));
  }

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
    $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning', 4096 => 'recoverable fatal error');
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
 * Correct double-escaping problems caused by "magic quotes" in some PHP
 * installations.
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
 * can also be used for text that may change from time to time
 * (such as link paths) to be changed without requiring updates to translations.
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
 *     $message[] = t("If you don't want to receive such e-mails, you can change your settings at !url.", array('!url' => url("user/$account->uid", NULL, NULL, TRUE)));
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
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or themed:
 *    - !variable: inserted as is
 *    - @variable: escape plain text to HTML (check_plain)
 *    - %variable: escape text and theme as a placeholder for user-submitted
 *      content (check_plain + theme_placeholder)
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
    // Transform arguments before inserting them
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        // Escaped only
        case '@':
          $args[$key] = check_plain($value);
        break;
        // Escaped and placeholder
        case '%':
        default:
          $args[$key] = theme('placeholder', $value);
          break;
        // Pass-through
        case '!':
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
 * This function should only be used on actual URLs. It should not be used for
 * Drupal menu paths, which can contain arbitrary characters.
 * Valid values per RFC 3986.
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
      (?:ftp|https?):\/\/                                     # Look for ftp, http, or https schemes
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
 *   True if the user did not exceed the hourly threshold. False otherwise.
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
 *   to ease translation. Use @count in place of the item count, as in "@count
 *   new comments".
 * @return
 *   A translated string.
 */
function format_plural($count, $singular, $plural) {
  if ($count == 1) return t($singular, array("@count" => $count));

  // get the plural index through the gettext formula
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count) : -1;
  if ($index < 0) { // backward compatibility
    return t($plural, array("@count" => $count));
  }
  else {
    switch ($index) {
      case "0":
        return t($singular, array("@count" => $count));
      case "1":
        return t($plural, array("@count" => $count));
      default:
        return t(strtr($plural, array("@count" => '@count['. $index .']')), array('@count['. $index .']' => $count));
    }
  }
}

/**
 * Parse a given byte count.
 *
 * @param $size
 *   The size expressed as a number of bytes with optional SI size and unit
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
 *   The size in bytes.
 * @return
 *   A translated string representation of the size.
 */
function format_size($size) {
  if ($size < 1024) {
    return format_plural($size, '1 byte', '@count bytes');
  }
  else {
    $size = round($size / 1024, 2);
    $suffix = t('KB');
    if ($size >= 1024) {
      $size = round($size / 1024, 2);
      $suffix = t('MB');
    }
    return t('@size @suffix', array('@size' => $size, '@suffix' => $suffix));
  }
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
  $units = array('1 year|@count years' => 31536000, '1 week|@count weeks' => 604800, '1 day|@count days' => 86400, '1 hour|@count hours' => 3600, '1 min|@count min' => 60, '1 sec|@count sec' => 1);
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
    if (strpos('AaDFlM', $c) !== FALSE) {
      $date .= t(gmdate($c, $timestamp));
    }
    else if (strpos('BdgGhHiIjLmnsStTUwWYyz', $c) !== FALSE) {
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
 *   The Drupal path being linked to, such as "admin/content/node", or an existing URL
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

  if (!isset($script)) {
    // On some web servers, such as IIS, we can't omit "index.php". So, we
    // generate "index.php?q=foo" instead of "?q=foo" on anything that is not
    // Apache.
    $script = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === FALSE) ? 'index.php' : '';
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
 *   The Drupal path being linked to, such as "admin/content/node". Can be an external
 *   or internal URL.
 *     - If you provide the full URL, it will be considered an
 *   external URL.
 *     - If you provide only the path (e.g. "admin/content/node"), it is considered an
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
  if (($path == $_GET['q']) || ($path == '<front>' && drupal_is_front_page())) {
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
 * Adds a CSS file to the stylesheet queue.
 *
 * @param $path
 *   (optional) The path to the CSS file relative to the base_path(), e.g.,
 *   /modules/devel/devel.css.
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
 * @return
 *   An array of CSS files.
 */
function drupal_add_css($path = NULL, $type = 'module', $media = 'all', $preprocess = TRUE) {
  static $css = array();

  // Create an array of CSS files for each media type first, since each type needs to be served
  // to the browser differently.
  if (isset($path)) {
    // This check is necessary to ensure proper cascading of styles and is faster than an asort().
    if (!isset($css[$media])) {
      $css[$media] = array('module' => array(), 'theme' => array());
    }
    $css[$media][$type][$path] = $preprocess;
  }

  return $css;
}

/**
 * Returns a themed representation of all stylesheets that should be attached to the page.
 * It loads the CSS in order, with 'core' CSS first, then 'module' CSS, then 'theme' CSS files.
 * This ensures proper cascading of styles for easy overriding in modules and themes.
 *
 * @param $css
 *   (optional) An array of CSS files. If no array is provided, the default stylesheets array is used instead.
 * @return
 *   A string of XHTML CSS tags.
 */
function drupal_get_css($css = NULL) {
  $output = '';
  if (!isset($css)) {
    $css = drupal_add_css();
  }

  $preprocess_css = variable_get('preprocess_css', FALSE);
  $directory = file_directory_path();
  $is_writable = is_dir($directory) && is_writable($directory) && (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PUBLIC);

  foreach ($css as $media => $types) {
    // If CSS preprocessing is off, we still need to output the styles.
    // Additionally, go through any remaining styles if CSS preprocessing is on and output the non-cached ones.
    foreach ($types as $type => $files) {
      foreach ($types[$type] as $file => $preprocess) {
        if (!$preprocess || !($is_writable && $preprocess_css)) {
          // If a CSS file is not to be preprocessed and it's a module CSS file, it needs to *always* appear at the *top*,
          // regardless of whether preprocessing is on or off.
          if (!$preprocess && $type == 'module') {
            $no_module_preprocess .= '<style type="text/css" media="'. $media .'">@import "'. base_path() . $file .'";</style>' ."\n";
          }
          // If a CSS file is not to be preprocessed and it's a theme CSS file, it needs to *always* appear at the *bottom*,
          // regardless of whether preprocessing is on or off.
          else if (!$preprocess && $type == 'theme') {
            $no_theme_preprocess .= '<style type="text/css" media="'. $media .'">@import "'. base_path() . $file .'";</style>' ."\n";
          }
          else {
            $output .= '<style type="text/css" media="'. $media .'">@import "'. base_path() . $file .'";</style>' ."\n";
          }
        }
      }
    }

    if ($is_writable && $preprocess_css) {
      $filename = md5(serialize($types)) .'.css';
      $preprocess_file = drupal_build_css_cache($types, $filename);
      $output .= '<style type="text/css" media="'. $media .'">@import "'. base_path() . $preprocess_file .'";</style>'. "\n";
    }
  }

  return $no_module_preprocess . $output . $no_theme_preprocess;
}

/**
 * Aggregate and optimize CSS files, putting them in the files directory.
 *
 * @param $types
 *   An array of types of CSS files (e.g., screen, print) to aggregate and compress into one file.
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
          $contents = file_get_contents($file);
          // Remove multiple charset declarations for standards compliance (and fixing Safari problems)
          $contents = preg_replace('/^@charset\s+[\'"](\S*)\b[\'"];/i', '', $contents);
          // Return the path to where this CSS file originated from, stripping off the name of the file at the end of the path.
          $path = base_path() . substr($file, 0, strrpos($file, '/')) .'/';
          // Wraps all @import arguments in url().
          $contents = preg_replace('/@import\s+(?!url)[\'"]?(\S*)\b[\'"]?/i', '@import url("\1")', $contents);
          // Fix all paths within this CSS file, ignoring absolute paths.
          $data .= preg_replace('/url\(([\'"]?)(?![a-z]+:)/i', 'url(\1'. $path . '\2', $contents);
        }
      }
    }

    // @import rules must proceed any other style, so we move those to the top.
    $regexp = '/@import[^;]+;/i';
    preg_match_all($regexp, $data, $matches);
    $data = preg_replace($regexp, '', $data);
    $data = implode('', $matches[0]) . $data;

    // Perform some safe CSS optimizations.
    $data = preg_replace('<
      \s*([@{}:;,]|\)\s|\s\()\s* |  # Remove whitespace around separators, but keep space around parentheses.
      /\*([^*\\\\]|\*(?!/))+\*/ |   # Remove comments that are not CSS hacks.
      [\n\r]                        # Remove line breaks.
      >x', '\1', $data);

    // Create the CSS file.
    file_save_data($data, $csspath .'/'. $filename, FILE_EXISTS_REPLACE);
  }
  return $csspath .'/'. $filename;
}

/**
 * Delete all cached CSS files.
 */
function drupal_clear_css_cache() {
  file_scan_directory(file_create_path('css'), '.*', array('.', '..', 'CVS'), 'file_delete', TRUE);
  // Clear the page cache, so cached pages do not reference nonexistent CSS.
  cache_clear_all();
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
 * @return
 *   If the first parameter is NULL, the JavaScript array that has been built so
 *   far for $scope is returned.
 */
function drupal_add_js($data = NULL, $type = 'module', $scope = 'header', $defer = FALSE, $cache = TRUE) {
  if (!is_null($data)) {
    _drupal_add_js('misc/jquery.js', 'core', 'header', FALSE, $cache);
    _drupal_add_js('misc/drupal.js', 'core', 'header', FALSE, $cache);
  }
  return _drupal_add_js($data, $type, $scope, $defer, $cache);
}

/**
 * Helper function for drupal_add_js().
 */
function _drupal_add_js($data, $type, $scope, $defer, $cache) {
  static $javascript = array();

  if (!isset($javascript[$scope])) {
    $javascript[$scope] = array('core' => array(), 'module' => array(), 'theme' => array(), 'setting' => array(), 'inline' => array());
  }

  if (!isset($javascript[$scope][$type])) {
    $javascript[$scope][$type] = array();
  }

  if (!is_null($data)) {
    switch ($type) {
      case 'setting':
        $javascript[$scope][$type][] = $data;
        break;
      case 'inline':
        $javascript[$scope][$type][] = array('code' => $data, 'defer' => $defer);
        break;
      default:
        $javascript[$scope][$type][$data] = array('cache' => $cache, 'defer' => $defer);
    }
  }

  return $javascript[$scope];
}

/**
 * Returns a themed presentation of all JavaScript code for the current page.
 * References to JavaScript files are placed in a certain order: first, all
 * 'core' files, then all 'module' and finally all 'theme' JavaScript files
 * are added to the page. Then, all settings are output, followed by 'inline'
 * JavaScript code.
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
  $output = '';
  if (is_null($javascript)) {
    $javascript = drupal_add_js(NULL, NULL, $scope);
  }

  foreach ($javascript as $type => $data) {
    if (!$data) continue;

    switch ($type) {
      case 'setting':
        $output .= '<script type="text/javascript">Drupal.extend({ settings: '. drupal_to_js(call_user_func_array('array_merge_recursive', $data)) ." });</script>\n";
        break;
      case 'inline':
        foreach ($data as $info) {
          $output .= '<script type="text/javascript"'. ($info['defer'] ? ' defer="defer"' : '') .'>'. $info['code'] ."</script>\n";
        }
        break;
      default:
        foreach ($data as $path => $info) {
          $output .= '<script type="text/javascript"'. ($info['defer'] ? ' defer="defer"' : '') .' src="'. check_url(base_path() . $path) . ($info['cache'] ? '' : '?'. time()) ."\"></script>\n";
        }
    }
  }

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
    $key = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
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
  // Initialize the localization system.  Depends on i18n.module being loaded already.
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

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET' && count(drupal_get_messages(NULL, FALSE)) == 0) {
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
        cache_set($base_root . request_uri(), 'cache_page', $data, CACHE_TEMPORARY, drupal_get_headers());
      }
    }
  }
}

/**
 * Send an e-mail message, using Drupal variables and default settings.
 * More information in the PHP function reference for mail()
 * @param $mailkey
 *   A key to identify the mail sent, for altering.
 * @param $to
 *   The mail address or addresses where the message will be send to. The
 *   formatting of this string must comply with RFC 2822. Some examples are:
 *    user@example.com
 *    user@example.com, anotheruser@example.com
 *    User <user@example.com>
 *    User <user@example.com>, Another User <anotheruser@example.com>
 * @param $subject
 *   Subject of the e-mail to be sent. This must not contain any newline
 *   characters, or the mail may not be sent properly.
 * @param $body
 *   Message to be sent. Drupal will format the correct line endings for you.
 * @param $from
 *   Sets From to this value, if given.
 * @param $headers
 *   Associative array containing the headers to add. This is typically
 *   used to add extra headers (From, Cc, and Bcc).
 *   <em>When sending mail, the mail must contain a From header.</em>
 * @return Returns TRUE if the mail was successfully accepted for delivery,
 *   FALSE otherwise.
 */
function drupal_mail($mailkey, $to, $subject, $body, $from = NULL, $headers = array()) {
  $defaults = array(
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer' => 'Drupal'
  );
  // To prevent e-mail from looking like spam, the addresses in the Sender and
  // Return-Path headers should have a domain authorized to use the originating
  // SMTP server.  Errors-To is redundant, but shouldn't hurt.
  $default_from = variable_get('site_mail', ini_get('sendmail_from'));
  if ($default_from) {
    $defaults['From'] = $defaults['Sender'] = $defaults['Return-Path'] = $defaults['Errors-To'] = $default_from;
  }
  if ($from) {
    $defaults['From'] = $from;
  }
  $headers = array_merge($defaults, $headers);
  // Custom hook traversal to allow pass by reference
  foreach (module_implements('mail_alter') AS $module) {
    $function = $module .'_mail_alter';
    $function($mailkey, $to, $subject, $body, $from, $headers);
  }
  // Allow for custom mail backend
  if (variable_get('smtp_library', '') && file_exists(variable_get('smtp_library', ''))) {
    include_once './' . variable_get('smtp_library', '');
    return drupal_mail_wrapper($mailkey, $to, $subject, $body, $from, $headers);
  }
  else {
    // Note: if you are having problems with sending mail, or mails look wrong
    // when they are received you may have to modify the str_replace to suit
    // your systems.
    //  - \r\n will work under dos and windows.
    //  - \n will work for linux, unix and BSDs.
    //  - \r will work for macs.
    //
    // According to RFC 2646, it's quite rude to not wrap your e-mails:
    //
    // "The Text/Plain media type is the lowest common denominator of
    // Internet e-mail, with lines of no more than 997 characters (by
    // convention usually no more than 80), and where the CRLF sequence
    // represents a line break [MIME-IMT]."
    //
    // CRLF === \r\n
    //
    // http://www.rfc-editor.org/rfc/rfc2646.txt

    $mimeheaders = array();
    foreach ($headers as $name => $value) {
      $mimeheaders[] = $name .': '. mime_header_encode($value);
    }
    return mail(
      $to,
      mime_header_encode($subject),
      str_replace("\r", '', $body),
      join("\n", $mimeheaders)
    );
  }
}

/**
 * Executes a cron run when called
 * @return
 * Returns TRUE if ran successfully
 */
function drupal_cron_run() {
  // If not in 'safe mode', increase the maximum execution time:
  if (!ini_get('safe_mode')) {
    set_time_limit(240);
  }

  // Fetch the cron semaphore
  $semaphore = variable_get('cron_semaphore', FALSE);

  if ($semaphore) {
    if (time() - $semaphore > 3600) {
      // Either cron has been running for more than an hour or the semaphore
      // was not reset due to a database error.
      watchdog('cron', t('Cron has been running for more than an hour and is most likely stuck.'), WATCHDOG_ERROR);

      // Release cron semaphore
      variable_del('cron_semaphore');
    }
    else {
      // Cron is still running normally.
      watchdog('cron', t('Attempting to re-run cron while it is already running.'), WATCHDOG_WARNING);
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
    watchdog('cron', t('Cron run completed.'), WATCHDOG_NOTICE);

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
    watchdog('cron', t('Cron run exceeded the time limit and was aborted.'), WATCHDOG_WARNING);

    // Release cron semaphore
    variable_del('cron_semaphore');
  }
}

/**
 * Returns an array of files objects of the given type from the site-wide
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

  // Always search sites/all/* as well as the global directories
  $searchdir[] = 'sites/all/'. $directory;

  // The 'profiles' directory contains pristine collections of modules and
  // themes as organized by a distribution.  It is pristine in the same way
  // that /modules is pristine for core; users should avoid changing anything
  // there in favor of sites/all or sites/<domain> directories.
  if (file_exists("profiles/$profile/$directory")) {
    $searchdir[] = "profiles/$profile/$directory";
  }

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
 * Renders HTML given a structured array tree. Recursively iterates over each
 * of the array elements, generating HTML code. This function is usually
 * called from within a another function, like drupal_get_form() or node_view().
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

  $content = '';
  // Either the elements did not go through form_builder or one of the children
  // has a #weight.
  if (!isset($elements['#sorted'])) {
    uasort($elements, "_element_sort");
  }
  if (!isset($elements['#children'])) {
    $children = element_children($elements);
    /* Render all the children that use a theme function */
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
    /* render each of the children using drupal_render and concatenate them */
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
    $prefix = isset($elements['#prefix']) ? $elements['#prefix'] : '';
    $suffix = isset($elements['#suffix']) ? $elements['#suffix'] : '';
    return $prefix . $content . $suffix;
  }
}

/**
 * Function used by uasort in drupal_render() to sort structured arrays
 * by weight.
 */
function _element_sort($a, $b) {
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
  return $key[0] != '#';
}

/**
 * Get keys of a structured array tree element that are not properties
 * (i.e., do not begin with '#').
 */
function element_children($element) {
  return array_filter(array_keys((array) $element), 'element_child');
}
