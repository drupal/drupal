<?php
// $Id$

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

/**
 * @defgroup php_wrappers PHP wrapper functions
 * @{
 * Functions that are wrappers or custom implementations of PHP functions.
 *
 * Certain PHP functions should not be used in Drupal. Instead, Drupal's
 * replacement functions should be used.
 *
 * For example, for improved or more secure UTF8-handling, or RFC-compliant
 * handling of URLs in Drupal.
 *
 * For ease of use and memorizing, all these wrapper functions use the same name
 * as the original PHP function, but prefixed with "drupal_". Beware, however,
 * that not all wrapper functions support the same arguments as the original
 * functions.
 *
 * You should always use these wrapper functions in your code.
 *
 * Wrong:
 * @code
 *   $my_substring = substr($original_string, 0, 5);
 * @endcode
 *
 * Correct:
 * @code
 *   $my_substring = drupal_substr($original_string, 0, 5);
 * @endcode
 *
 * @} End of "defgroup php_wrappers".
 */

/**
 * Error reporting level: display no errors.
 */
define('ERROR_REPORTING_HIDE', 0);

/**
 * Error reporting level: display errors and warnings.
 */
define('ERROR_REPORTING_DISPLAY_SOME', 1);

/**
 * Error reporting level: display all messages.
 */
define('ERROR_REPORTING_DISPLAY_ALL', 2);

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
 * The default weight of system CSS files added to the page.
 */
define('CSS_SYSTEM', -100);

/**
 * The default weight of CSS files added to the page.
 */
define('CSS_DEFAULT', 0);

/**
 * The default weight of theme CSS files added to the page.
 */
define('CSS_THEME', 100);

/**
 * The weight of JavaScript libraries, settings or jQuery plugins being
 * added to the page.
 */
define('JS_LIBRARY', -100);

/**
 * The default weight of JavaScript being added to the page.
 */
define('JS_DEFAULT', 0);

/**
 * The weight of theme JavaScript code being added to the page.
 */
define('JS_THEME', 100);

/**
 * Error code indicating that the request made by drupal_http_request() exceeded
 * the specified timeout.
 */
define('HTTP_REQUEST_TIMEOUT', 1);

/**
 * Constants defining cache granularity for blocks and renderable arrays.
 *
 * Modules specify the caching patterns for their blocks using binary
 * combinations of these constants in their hook_block_info():
 *   $block[delta]['cache'] = DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE;
 * DRUPAL_CACHE_PER_ROLE is used as a default when no caching pattern is
 * specified. Use DRUPAL_CACHE_CUSTOM to disable standard block cache and
 * implement
 *
 * The block cache is cleared in cache_clear_all(), and uses the same clearing
 * policy than page cache (node, comment, user, taxonomy added or updated...).
 * Blocks requiring more fine-grained clearing might consider disabling the
 * built-in block cache (DRUPAL_NO_CACHE) and roll their own.
 *
 * Note that user 1 is excluded from block caching.
 */

/**
 * The block should not get cached. This setting should be used:
 * - for simple blocks (notably those that do not perform any db query),
 * where querying the db cache would be more expensive than directly generating
 * the content.
 * - for blocks that change too frequently.
 */
define('DRUPAL_NO_CACHE', -1);

/**
 * The block is handling its own caching in its hook_block_view(). From the
 * perspective of the block cache system, this is equivalent to DRUPAL_NO_CACHE.
 * Useful when time based expiration is needed or a site uses a node access
 * which invalidates standard block cache.
 */
define('DRUPAL_CACHE_CUSTOM', -2);

/**
 * The block or element can change depending on the roles the user viewing the
 * page belongs to. This is the default setting for blocks, used when the block
 * does not specify anything.
 */
define('DRUPAL_CACHE_PER_ROLE', 0x0001);

/**
 * The block or element can change depending on the user viewing the page.
 * This setting can be resource-consuming for sites with large number of users,
 * and thus should only be used when DRUPAL_CACHE_PER_ROLE is not sufficient.
 */
define('DRUPAL_CACHE_PER_USER', 0x0002);

/**
 * The block or element can change depending on the page being viewed.
 */
define('DRUPAL_CACHE_PER_PAGE', 0x0004);

/**
 * The block or element is the same for every user on every page where it is visible.
 */
define('DRUPAL_CACHE_GLOBAL', 0x0008);

/**
 * Add content to a specified region.
 *
 * @param $region
 *   Page region the content is added to.
 * @param $data
 *   Content to be added.
 */
function drupal_add_region_content($region = NULL, $data = NULL) {
  static $content = array();

  if (!is_null($region) && !is_null($data)) {
    $content[$region][] = $data;
  }
  return $content;
}

/**
 * Get assigned content for a given region.
 *
 * @param $region
 *   A specified region to fetch content for. If NULL, all regions will be
 *   returned.
 * @param $delimiter
 *   Content to be inserted between imploded array elements.
 */
function drupal_get_region_content($region = NULL, $delimiter = ' ') {
  $content = drupal_add_region_content();
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
 * Get the name of the currently active install profile.
 *
 * When this function is called during Drupal's initial installation process,
 * the name of the profile that's about to be installed is stored in the global
 * installation state. At all other times, the standard Drupal systems variable
 * table contains the name of the current profile, and we can call variable_get()
 * to determine what one is active.
 *
 * @return $profile
 *   The name of the install profile.
 */
function drupal_get_profile() {
  global $install_state;

  if (isset($install_state['parameters']['profile'])) {
    $profile = $install_state['parameters']['profile'];
  }
  else {
    $profile = variable_get('install_profile', 'default');
  }

  return $profile;
}


/**
 * Set the breadcrumb trail for the current page.
 *
 * @param $breadcrumb
 *   Array of links, starting with "home" and proceeding up to but not including
 *   the current page.
 */
function drupal_set_breadcrumb($breadcrumb = NULL) {
  $stored_breadcrumb = &drupal_static(__FUNCTION__);

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
 * Return a string containing RDF namespaces for the <html> tag of an XHTML
 * page.
 */
function drupal_get_rdf_namespaces() {
  // Serialize the RDF namespaces used in RDFa annotation.
  $xml_rdf_namespaces = array();
  foreach (module_invoke_all('rdf_namespaces') as $prefix => $uri) {
    $xml_rdf_namespaces[] = 'xmlns:' . $prefix . '="' . $uri . '"';
  }
  return implode("\n  ", $xml_rdf_namespaces);
}

/**
 * Add output to the head tag of the HTML page.
 *
 * This function can be called as long the headers aren't sent.
 */
function drupal_add_html_head($data = NULL) {
  $stored_head = &drupal_static(__FUNCTION__, '');

  if (!is_null($data)) {
    $stored_head .= $data . "\n";
  }
  return $stored_head;
}

/**
 * Retrieve output to be displayed in the head tag of the HTML page.
 */
function drupal_get_html_head() {
  $output = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
  return $output . drupal_add_html_head();
}

/**
 * Reset the static variable which holds the aliases mapped for this request.
 */
function drupal_clear_path_cache() {
  drupal_lookup_path('wipe');
}

/**
 * Add a feed URL for the current page.
 *
 * This function can be called as long the HTML header hasn't been sent.
 *
 * @param $url
 *   A url for the feed.
 * @param $title
 *   The title of the feed.
 */
function drupal_add_feed($url = NULL, $title = '') {
  $stored_feed_links = &drupal_static(__FUNCTION__, array());

  if (!is_null($url) && !isset($stored_feed_links[$url])) {
    $stored_feed_links[$url] = theme('feed_icon', array('url' => $url, 'title' => $title));

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
 * @name HTTP handling
 * @{
 * Functions to properly handle HTTP responses.
 */

/**
 * Process a URL query parameter array to remove unwanted elements.
 *
 * @param $query
 *   (optional) An array to be processed. Defaults to $_GET.
 * @param $exclude
 *   (optional) A list of $query array keys to remove. Use "parent[child]" to
 *   exclude nested items. Defaults to array('q').
 * @param $parent
 *   Internal use only. Used to build the $query array key for nested items.
 *
 * @return
 *   An array containing query parameters, which can be used for url().
 */
function drupal_get_query_parameters(array $query = NULL, array $exclude = array('q'), $parent = '') {
  // Set defaults, if none given.
  if (!isset($query)) {
    $query = $_GET;
  }
  // If $exclude is empty, there is nothing to filter.
  if (empty($exclude)) {
    return $query;
  }
  elseif (!$parent) {
    $exclude = array_flip($exclude);
  }

  $params = array();
  foreach ($query as $key => $value) {
    $string_key = ($parent ? $parent . '[' . $key . ']' : $key);
    if (isset($exclude[$string_key])) {
      continue;
    }

    if (is_array($value)) {
      $params[$key] = drupal_get_query_parameters($value, $exclude, $string_key);
    }
    else {
      $params[$key] = $value;
    }
  }

  return $params;
}

/**
 * Split an URL-encoded query string into an array.
 *
 * @param $query
 *   The query string to split.
 *
 * @return
 *   An array of url decoded couples $param_name => $value.
 */
function drupal_get_query_array($query) {
  $result = array();
  if (!empty($query)) {
    foreach (explode('&', $query) as $param) {
      $param = explode('=', $param);
      $result[$param[0]] = isset($param[1]) ? rawurldecode($param[1]) : '';
    }
  }
  return $result;
}

/**
 * Parse an array into a valid, rawurlencoded query string.
 *
 * This differs from http_build_query() as we need to rawurlencode() (instead of
 * urlencode()) all query parameters.
 *
 * @param $query
 *   The query parameter array to be processed, e.g. $_GET.
 * @param $parent
 *   Internal use only. Used to build the $query array key for nested items.
 *
 * @return
 *   A rawurlencoded string which can be used as or appended to the URL query
 *   string.
 *
 * @see drupal_get_query_parameters()
 * @ingroup php_wrappers
 */
function drupal_http_build_query(array $query, $parent = '') {
  $params = array();

  foreach ($query as $key => $value) {
    $key = ($parent ? $parent . '[' . rawurlencode($key) . ']' : rawurlencode($key));

    // Recurse into children.
    if (is_array($value)) {
      $params[] = drupal_http_build_query($value, $key);
    }
    // If a query parameter value is NULL, only append its key.
    elseif (!isset($value)) {
      $params[] = $key;
    }
    else {
      // For better readability of paths in query strings, we decode slashes.
      // @see drupal_encode_path()
      $params[] = $key . '=' . str_replace('%2F', '/', rawurlencode($value));
    }
  }

  return implode('&', $params);
}

/**
 * Prepare a 'destination' URL query parameter for use in combination with drupal_goto().
 *
 * Used to direct the user back to the referring page after completing a form.
 * By default the current URL is returned. If a destination exists in the
 * previous request, that destination is returned. As such, a destination can
 * persist across multiple pages.
 *
 * @see drupal_goto()
 */
function drupal_get_destination() {
  $destination = &drupal_static(__FUNCTION__);

  if (isset($destination)) {
    return $destination;
  }

  if (isset($_GET['destination'])) {
    $destination = array('destination' => $_GET['destination']);
  }
  else {
    $path = $_GET['q'];
    $query = drupal_http_build_query(drupal_get_query_parameters());
    if ($query != '') {
      $path .= '?' . $query;
    }
    $destination = array('destination' => $path);
  }
  return $destination;
}

/**
 * Wrapper around parse_url() to parse a system URL string into an associative array, suitable for url().
 *
 * This function should only be used for URLs that have been generated by the
 * system, resp. url(). It should not be used for URLs that come from external
 * sources, or URLs that link to external resources.
 *
 * The returned array contains a 'path' that may be passed separately to url().
 * For example:
 * @code
 *   $options = drupal_parse_url($_GET['destination']);
 *   $my_url = url($options['path'], $options);
 *   $my_link = l('Example link', $options['path'], $options);
 * @endcode
 *
 * This is required, because url() does not support relative URLs containing a
 * query string or fragment in its $path argument. Instead, any query string
 * needs to be parsed into an associative query parameter array in
 * $options['query'] and the fragment into $options['fragment'].
 *
 * @param $url
 *   The URL string to parse, f.e. $_GET['destination'].
 *
 * @return
 *   An associative array containing the keys:
 *   - 'path': The path of the URL. If the given $url is external, this includes
 *     the scheme and host.
 *   - 'query': An array of query parameters of $url, if existent.
 *   - 'fragment': The fragment of $url, if existent.
 *
 * @see url()
 * @see drupal_goto()
 * @ingroup php_wrappers
 */
function drupal_parse_url($url) {
  $options = array(
    'path' => NULL,
    'query' => array(),
    'fragment' => '',
  );

  // External URLs: not using parse_url() here, so we do not have to rebuild
  // the scheme, host, and path without having any use for it.
  if (strpos($url, '://') !== FALSE) {
    // Split off everything before the query string into 'path'.
    $parts = explode('?', $url);
    $options['path'] = $parts[0];
    // If there is a query string, transform it into keyed query parameters.
    if (isset($parts[1])) {
      $query_parts = explode('#', $parts[1]);
      parse_str($query_parts[0], $options['query']);
      // Take over the fragment, if there is any.
      if (isset($query_parts[1])) {
        $options['fragment'] = $query_parts[1];
      }
    }
  }
  // Internal URLs.
  else {
    // parse_url() does not support relative URLs, so make it absolute. E.g. the
    // relative URL "foo/bar:1" isn't properly parsed.
    $parts = parse_url('http://example.com/' . $url);
    // Strip the leading slash that was just added.
    $options['path'] = substr($parts['path'], 1);
    if (isset($parts['query'])) {
      parse_str($parts['query'], $options['query']);
    }
    if (isset($parts['fragment'])) {
      $options['fragment'] = $parts['fragment'];
    }
  }
  // The 'q' parameter contains the path of the current page if clean URLs are
  // disabled. It overrides the 'path' of the URL when present, even if clean
  // URLs are enabled, due to how Apache rewriting rules work.
  if (isset($options['query']['q'])) {
    $options['path'] = $options['query']['q'];
    unset($options['query']['q']);
  }

  return $options;
}

/**
 * Encode a path for usage in a URL.
 *
 * Wrapper around rawurlencode() which avoids Apache quirks. Should be used when
 * placing arbitrary data into the path component of an URL.
 *
 * Do not use this function to pass a path to url(). url() properly handles
 * and encodes paths internally.
 * This function should only be used on paths, not on query string arguments.
 * Otherwise, unwanted double encoding will occur.
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
 * @param $path
 *   The URL path component to encode.
 */
function drupal_encode_path($path) {
  if (!empty($GLOBALS['conf']['clean_url'])) {
    return str_replace(array('%2F', '%26', '%23', '//'),
                       array('/', '%2526', '%2523', '/%252F'),
                       rawurlencode($path)
    );
  }
  else {
    return str_replace('%2F', '/', rawurlencode($path));
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
 * the query string of an URI) This is used to direct the user back to
 * the proper page after completing a form. For example, after editing
 * a post on the 'admin/content'-page or after having logged on using the
 * 'user login'-block in a sidebar. The function drupal_get_destination()
 * can be used to help set the destination URL.
 *
 * Drupal will ensure that messages set by drupal_set_message() and other
 * session data are written to the database before the user is redirected.
 *
 * This function ends the request; use it instead of a return in your menu
 * callback.
 *
 * @param $path
 *   A Drupal path or a full URL.
 * @param $options
 *   An associative array of additional URL options to pass to url().
 * @param $http_response_code
 *   Valid values for an actual "goto" as per RFC 2616 section 10.3 are:
 *   - 301 Moved Permanently (the recommended value for most redirects)
 *   - 302 Found (default in Drupal and PHP, sometimes used for spamming search
 *         engines)
 *   - 303 See Other
 *   - 304 Not Modified
 *   - 305 Use Proxy
 *   - 307 Temporary Redirect (alternative to "503 Site Down for Maintenance")
 *   Note: Other values are defined by RFC 2616, but are rarely used and poorly
 *   supported.
 *
 * @see drupal_get_destination()
 * @see url()
 */
function drupal_goto($path = '', array $options = array(), $http_response_code = 302) {
  // A destination in $_GET always overrides the function arguments.
  if (isset($_GET['destination'])) {
    $destination = drupal_parse_url(urldecode($_GET['destination']));
    $path = $destination['path'];
    $options['query'] = $destination['query'];
    $options['fragment'] = $destination['fragment'];
  }

  drupal_alter('drupal_goto', $path, $options, $http_response_code);

  // The 'Location' HTTP header must be absolute.
  $options['absolute'] = TRUE;

  $url = url($path, $options);

  header('Location: ' . $url, TRUE, $http_response_code);

  // The "Location" header sends a redirect status code to the HTTP daemon. In
  // some cases this can be wrong, so we make sure none of the code below the
  // drupal_goto() call gets executed upon redirection.
  drupal_exit($url);
}

/**
 * Deliver a "site is under maintenance" message to the browser.
 *
 * Page callback functions wanting to report a "site offline" message should
 * return MENU_SITE_OFFLINE instead of calling drupal_site_offline(). However,
 * functions that are invoked in contexts where that return value might not
 * bubble up to menu_execute_active_handler() should call drupal_site_offline().
 */
function drupal_site_offline() {
  drupal_deliver_page(MENU_SITE_OFFLINE);
}

/**
 * Deliver a "page not found" error to the browser.
 *
 * Page callback functions wanting to report a "page not found" message should
 * return MENU_NOT_FOUND instead of calling drupal_not_found(). However,
 * functions that are invoked in contexts where that return value might not
 * bubble up to menu_execute_active_handler() should call drupal_not_found().
 */
function drupal_not_found() {
  drupal_deliver_page(MENU_NOT_FOUND);
}

/**
 * Deliver a "access denied" error to the browser.
 *
 * Page callback functions wanting to report an "access denied" message should
 * return MENU_ACCESS_DENIED instead of calling drupal_access_denied(). However,
 * functions that are invoked in contexts where that return value might not
 * bubble up to menu_execute_active_handler() should call drupal_access_denied().
 */
function drupal_access_denied() {
  drupal_deliver_page(MENU_ACCESS_DENIED);
}

/**
 * Perform an HTTP request.
 *
 * This is a flexible and powerful HTTP client implementation. Correctly
 * handles GET, POST, PUT or any other HTTP requests. Handles redirects.
 *
 * @param $url
 *   A string containing a fully qualified URI.
 * @param $options
 *   (optional) An array which can have one or more of following keys:
 *   - headers
 *       An array containing request headers to send as name/value pairs.
 *   - method
 *       A string containing the request method. Defaults to 'GET'.
 *   - data
 *       A string containing the request body. Defaults to NULL.
 *   - max_redirects
 *       An integer representing how many times a redirect may be followed.
 *       Defaults to 3.
 *   - timeout
 *       A float representing the maximum number of seconds the function call
 *       may take. The default is 30 seconds. If a timeout occurs, the error
 *       code is set to the HTTP_REQUEST_TIMEOUT constant.
 * @return
 *   An object which can have one or more of the following parameters:
 *   - request
 *       A string containing the request body that was sent.
 *   - code
 *       An integer containing the response status code, or the error code if
 *       an error occurred.
 *   - protocol
 *       The response protocol (e.g. HTTP/1.1 or HTTP/1.0).
 *   - status_message
 *       The status message from the response, if a response was received.
 *   - redirect_code
 *       If redirected, an integer containing the initial response status code.
 *   - redirect_url
 *       If redirected, a string containing the redirection location.
 *   - error
 *       If an error occurred, the error message. Otherwise not set.
 *   - headers
 *       An array containing the response headers as name/value pairs.
 *   - data
 *       A string containing the response body that was received.
 */
function drupal_http_request($url, array $options = array()) {
  global $db_prefix;

  $result = new stdClass();

  // Parse the URL and make sure we can handle the schema.
  $uri = @parse_url($url);

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

  // Merge the default options.
  $options += array(
    'headers' => array(),
    'method' => 'GET',
    'data' => NULL,
    'max_redirects' => 3,
    'timeout' => 30,
  );

  switch ($uri['scheme']) {
    case 'http':
      $port = isset($uri['port']) ? $uri['port'] : 80;
      $host = $uri['host'] . ($port != 80 ? ':' . $port : '');
      $fp = @fsockopen($uri['host'], $port, $errno, $errstr, $options['timeout']);
      break;
    case 'https':
      // Note: Only works when PHP is compiled with OpenSSL support.
      $port = isset($uri['port']) ? $uri['port'] : 443;
      $host = $uri['host'] . ($port != 443 ? ':' . $port : '');
      $fp = @fsockopen('ssl://' . $uri['host'], $port, $errno, $errstr, $options['timeout']);
      break;
    default:
      $result->error = 'invalid schema ' . $uri['scheme'];
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
    $path .= '?' . $uri['query'];
  }

  // Merge the default headers.
  $options['headers'] += array(
    'User-Agent' => 'Drupal (+http://drupal.org/)',
  );

  // RFC 2616: "non-standard ports MUST, default ports MAY be included".
  // We don't add the standard port to prevent from breaking rewrite rules
  // checking the host that do not take into account the port number.
  $options['headers']['Host'] = $host;

  // Only add Content-Length if we actually have any content or if it is a POST
  // or PUT request. Some non-standard servers get confused by Content-Length in
  // at least HEAD/GET requests, and Squid always requires Content-Length in
  // POST/PUT requests.
  $content_length = strlen($options['data']);
  if ($content_length > 0 || $options['method'] == 'POST' || $options['method'] == 'PUT') {
    $options['headers']['Content-Length'] = $content_length;
  }

  // If the server URL has a user then attempt to use basic authentication.
  if (isset($uri['user'])) {
    $options['headers']['Authorization'] = 'Basic ' . base64_encode($uri['user'] . (!empty($uri['pass']) ? ":" . $uri['pass'] : ''));
  }

  // If the database prefix is being used by SimpleTest to run the tests in a copied
  // database then set the user-agent header to the database prefix so that any
  // calls to other Drupal pages will run the SimpleTest prefixed database. The
  // user-agent is used to ensure that multiple testing sessions running at the
  // same time won't interfere with each other as they would if the database
  // prefix were stored statically in a file or database variable.
  if (is_string($db_prefix) && preg_match("/simpletest\d+/", $db_prefix, $matches)) {
    $options['headers']['User-Agent'] = drupal_generate_test_ua($matches[0]);
  }

  $request = $options['method'] . ' ' . $path . " HTTP/1.0\r\n";
  foreach ($options['headers'] as $name => $value) {
    $request .= $name . ': ' . trim($value) . "\r\n";
  }
  $request .= "\r\n" . $options['data'];
  $result->request = $request;

  fwrite($fp, $request);

  // Fetch response.
  $response = '';
  while (!feof($fp)) {
    // Calculate how much time is left of the original timeout value.
    $timeout = $options['timeout'] - timer_read(__FUNCTION__) / 1000;
    if ($timeout <= 0) {
      $result->code = HTTP_REQUEST_TIMEOUT;
      $result->error = 'request timed out';
      return $result;
    }
    stream_set_timeout($fp, floor($timeout), floor(1000000 * fmod($timeout, 1)));
    $response .= fread($fp, 1024);
  }
  fclose($fp);

  // Parse response headers from the response body.
  list($response, $result->data) = explode("\r\n\r\n", $response, 2);
  $response = preg_split("/\r\n|\n|\r/", $response);

  // Parse the response status line.
  list($protocol, $code, $status_message) = explode(' ', trim(array_shift($response)), 3);
  $result->protocol = $protocol;
  $result->status_message = $status_message;

  $result->headers = array();

  // Parse the response headers.
  while ($line = trim(array_shift($response))) {
    list($header, $value) = explode(':', $line, 2);
    if (isset($result->headers[$header]) && $header == 'Set-Cookie') {
      // RFC 2109: the Set-Cookie response header comprises the token Set-
      // Cookie:, followed by a comma-separated list of one or more cookies.
      $result->headers[$header] .= ',' . trim($value);
    }
    else {
      $result->headers[$header] = trim($value);
    }
  }

  $responses = array(
    100 => 'Continue',
    101 => 'Switching Protocols',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    307 => 'Temporary Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Time-out',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Large',
    415 => 'Unsupported Media Type',
    416 => 'Requested range not satisfiable',
    417 => 'Expectation Failed',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Time-out',
    505 => 'HTTP Version not supported',
  );
  // RFC 2616 states that all unknown HTTP codes must be treated the same as the
  // base code in their class.
  if (!isset($responses[$code])) {
    $code = floor($code / 100) * 100;
  }
  $result->code = $code;

  switch ($code) {
    case 200: // OK
    case 304: // Not modified
      break;
    case 301: // Moved permanently
    case 302: // Moved temporarily
    case 307: // Moved temporarily
      $location = $result->headers['Location'];
      $options['timeout'] -= timer_read(__FUNCTION__) / 1000;
      if ($options['timeout'] <= 0) {
        $result->code = HTTP_REQUEST_TIMEOUT;
        $result->error = 'request timed out';
      }
      elseif ($options['max_redirects']) {
        // Redirect to the new location.
        $options['max_redirects']--;
        $result = drupal_http_request($location, $options);
        $result->redirect_code = $code;
      }
      $result->redirect_url = $location;
      break;
    default:
      $result->error = $status_message;
  }

  return $result;
}
/**
 * @} End of "HTTP handling".
 */

/**
 * Custom PHP error handler.
 *
 * @param $error_level
 *   The level of the error raised.
 * @param $message
 *   The error message.
 * @param $filename
 *   The filename that the error was raised in.
 * @param $line
 *   The line number the error was raised at.
 * @param $context
 *   An array that points to the active symbol table at the point the error occurred.
 */
function _drupal_error_handler($error_level, $message, $filename, $line, $context) {
  if ($error_level & error_reporting()) {
    // All these constants are documented at http://php.net/manual/en/errorfunc.constants.php
    $types = array(
      E_ERROR => 'Error',
      E_WARNING => 'Warning',
      E_PARSE => 'Parse error',
      E_NOTICE => 'Notice',
      E_CORE_ERROR => 'Core error',
      E_CORE_WARNING => 'Core warning',
      E_COMPILE_ERROR => 'Compile error',
      E_COMPILE_WARNING => 'Compile warning',
      E_USER_ERROR => 'User error',
      E_USER_WARNING => 'User warning',
      E_USER_NOTICE => 'User notice',
      E_STRICT => 'Strict warning',
      E_RECOVERABLE_ERROR => 'Recoverable fatal error'
    );
    $caller = _drupal_get_last_caller(debug_backtrace());

    // We treat recoverable errors as fatal.
    _drupal_log_error(array(
      '%type' => isset($types[$error_level]) ? $types[$error_level] : 'Unknown error',
      '%message' => $message,
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
    ), $error_level == E_RECOVERABLE_ERROR);
  }
}

/**
 * Custom PHP exception handler.
 *
 * Uncaught exceptions are those not enclosed in a try/catch block. They are
 * always fatal: the execution of the script will stop as soon as the exception
 * handler exits.
 *
 * @param $exception
 *   The exception object that was thrown.
 */
function _drupal_exception_handler($exception) {
  // Log the message to the watchdog and return an error page to the user.
  _drupal_log_error(_drupal_decode_exception($exception), TRUE);
}

/**
 * Decode an exception, especially to retrive the correct caller.
 *
 * @param $exception
 *   The exception object that was thrown.
 * @return An error in the format expected by _drupal_log_error().
 */
function _drupal_decode_exception($exception) {
  $message = $exception->getMessage();

  $backtrace = $exception->getTrace();
  // Add the line throwing the exception to the backtrace.
  array_unshift($backtrace, array('line' => $exception->getLine(), 'file' => $exception->getFile()));

  // For PDOException errors, we try to return the initial caller,
  // skipping internal functions of the database layer.
  if ($exception instanceof PDOException) {
    // The first element in the stack is the call, the second element gives us the caller.
    // We skip calls that occurred in one of the classes of the database layer
    // or in one of its global functions.
    $db_functions = array('db_query',  'db_query_range');
    while (!empty($backtrace[1]) && ($caller = $backtrace[1]) &&
        ((isset($caller['class']) && (strpos($caller['class'], 'Query') !== FALSE || strpos($caller['class'], 'Database') !== FALSE || strpos($caller['class'], 'PDO') !== FALSE)) ||
        in_array($caller['function'], $db_functions))) {
      // We remove that call.
      array_shift($backtrace);
    }
    if (isset($exception->query_string, $exception->args)) {
      $message .= ": " . $exception->query_string . "; " . print_r($exception->args, TRUE);
    }
  }
  $caller = _drupal_get_last_caller($backtrace);

  return array(
    '%type' => get_class($exception),
    '%message' => $message,
    '%function' => $caller['function'],
    '%file' => $caller['file'],
    '%line' => $caller['line'],
  );
}

/**
 * Log a PHP error or exception, display an error page in fatal cases.
 *
 * @param $error
 *   An array with the following keys: %type, %message, %function, %file, %line.
 * @param $fatal
 *   TRUE if the error is fatal.
 */
function _drupal_log_error($error, $fatal = FALSE) {
  // Initialize a maintenance theme if the boostrap was not complete.
  // Do it early because drupal_set_message() triggers a drupal_theme_initialize().
  if ($fatal && (drupal_get_bootstrap_phase() != DRUPAL_BOOTSTRAP_FULL)) {
    unset($GLOBALS['theme']);
    if (!defined('MAINTENANCE_MODE')) {
      define('MAINTENANCE_MODE', 'error');
    }
    drupal_maintenance_theme();
  }

  // When running inside the testing framework, we relay the errors
  // to the tested site by the way of HTTP headers.
  if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^simpletest\d+;/", $_SERVER['HTTP_USER_AGENT']) && !headers_sent() && (!defined('SIMPLETEST_COLLECT_ERRORS') || SIMPLETEST_COLLECT_ERRORS)) {
    // $number does not use drupal_static as it should not be reset
    // as it uniquely identifies each PHP error.
    static $number = 0;
    $assertion = array(
      $error['%message'],
      $error['%type'],
      array(
        'function' => $error['%function'],
        'file' => $error['%file'],
        'line' => $error['%line'],
      ),
    );
    header('X-Drupal-Assertion-' . $number . ': ' . rawurlencode(serialize($assertion)));
    $number++;
  }

  try {
    watchdog('php', '%type: %message in %function (line %line of %file).', $error, WATCHDOG_ERROR);
  }
  catch (Exception $e) {
    // Ignore any additional watchdog exception, as that probably means
    // that the database was not initialized correctly.
  }

  if ($fatal) {
    drupal_add_http_header('500 Service unavailable (with message)');
  }

  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    if ($fatal) {
      // When called from JavaScript, simply output the error message.
      print t('%type: %message in %function (line %line of %file).', $error);
      exit;
    }
  }
  else {
    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionnaly in update.php.
    $error_level = variable_get('error_level', ERROR_REPORTING_DISPLAY_ALL);
    $display_error = $error_level == ERROR_REPORTING_DISPLAY_ALL || ($error_level == ERROR_REPORTING_DISPLAY_SOME && $error['%type'] != 'Notice');
    if ($display_error || (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'update')) {
      $class = 'error';

      // If error type is 'User notice' then treat it as debug information
      // instead of an error message, see dd().
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
        $class = 'status';
      }

      drupal_set_message(t('%type: %message in %function (line %line of %file).', $error), $class);
    }

    if ($fatal) {
      drupal_set_title(t('Error'));
      // We fallback to a maintenance page at this point, because the page generation
      // itself can generate errors.
      print theme('maintenance_page', array('content' => t('The website encountered an unexpected error. Please try again later.')));
      exit;
    }
  }
}

/**
 * Gets the last caller from a backtrace.
 *
 * @param $backtrace
 *   A standard PHP backtrace.
 * @return
 *   An associative array with keys 'file', 'line' and 'function'.
 */
function _drupal_get_last_caller($backtrace) {
  // Errors that occur inside PHP internal functions do not generate
  // information about file and line. Ignore black listed functions.
  $blacklist = array('debug');
  while (($backtrace && !isset($backtrace[0]['line'])) ||
         (isset($backtrace[1]['function']) && in_array($backtrace[1]['function'], $blacklist))) {
    array_shift($backtrace);
  }

  // The first trace is the call itself.
  // It gives us the line and the file of the last call.
  $call = $backtrace[0];

  // The second call give us the function where the call originated.
  if (isset($backtrace[1])) {
    if (isset($backtrace[1]['class'])) {
      $call['function'] = $backtrace[1]['class'] . $backtrace[1]['type'] . $backtrace[1]['function'] . '()';
    }
    else {
      $call['function'] = $backtrace[1]['function'] . '()';
    }
  }
  else {
    $call['function'] = 'main()';
  }
  return $call;
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
  $fixed = &drupal_static(__FUNCTION__, FALSE);
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
 *     drupal_set_title($title = t("@name's blog", array('@name' => format_username($account))), PASS_THROUGH);
 *   @endcode
 *
 * - %variable, which indicates that the string should be HTML escaped and
 *   highlighted with theme_placeholder() which shows up by default as
 *   <em>emphasized</em>.
 *   @code
 *     $message = t('%name-from sent %name-to an e-mail.', array('%name-from' => format_username($user), '%name-to' => format_username($account)));
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
 *   $output .= '<p>' . t('Go to the <a href="@contact-page">contact page</a>.', array('@contact-page' => url('contact'))) . '</p>';
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
 * @param $options
 *   An associative array of additional options, with the following keys:
 *     - 'langcode' (default to the current language) The language code to
 *       translate to a language other than what is used to display the page.
 *     - 'context' (default to the empty context) The context the source string
 *       belongs to.
 * @return
 *   The translated string.
 */
function t($string, array $args = array(), array $options = array()) {
  global $language_interface;
  static $custom_strings;

  // Merge in default.
  if (empty($options['langcode'])) {
    $options['langcode'] = isset($language_interface->language) ? $language_interface->language : 'en';
  }
  if (empty($options['context'])) {
    $options['context'] = '';
  }

  // First, check for an array of customized strings. If present, use the array
  // *instead of* database lookups. This is a high performance way to provide a
  // handful of string replacements. See settings.php for examples.
  // Cache the $custom_strings variable to improve performance.
  if (!isset($custom_strings[$options['langcode']])) {
    $custom_strings[$options['langcode']] = variable_get('locale_custom_strings_' . $options['langcode'], array());
  }
  // Custom strings work for English too, even if locale module is disabled.
  if (isset($custom_strings[$options['langcode']][$options['context']][$string])) {
    $string = $custom_strings[$options['langcode']][$options['context']][$string];
  }
  // Translate with locale module if enabled.
  // We don't use function_exists() here, because it breaks the testing
  // framework if the locale module is enabled in the parent site (we cannot
  // unload functions in PHP).
  elseif (function_exists('locale') && $options['langcode'] != 'en') {
    $string = locale($string, $options['context'], $options['langcode']);
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
          $args[$key] = theme('placeholder', array('text' => $value));
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
  return (bool)filter_var($mail, FILTER_VALIDATE_EMAIL);
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
 * @} End of "defgroup validation".
 */

/**
 * Register an event for the current visitor to the flood control mechanism.
 *
 * @param $name
 *   The name of an event.
 * @param $window
 *   Optional number of seconds before this event expires. Defaults to 3600 (1
 *   hour). Typically uses the same value as the flood_is_allowed() $window
 *   parameter. Expired events are purged on cron run to prevent the flood table
 *   from growing indefinitely.
 * @param $identifier
 *   Optional identifier (defaults to the current user's IP address).
 */
function flood_register_event($name, $window = 3600, $identifier = NULL) {
  if (!isset($identifier)) {
    $identifier = ip_address();
  }
  db_insert('flood')
    ->fields(array(
      'event' => $name,
      'identifier' => $identifier,
      'timestamp' => REQUEST_TIME,
      'expiration' => REQUEST_TIME + $window,
    ))
    ->execute();
}

/**
 * Make the flood control mechanism forget about an event for the current visitor.
 *
 * @param $name
 *   The name of an event.
 * @param $identifier
 *   Optional identifier (defaults to the current user's IP address).
 */
function flood_clear_event($name, $identifier = NULL) {
  if (!isset($identifier)) {
    $identifier = ip_address();
  }
  db_delete('flood')
    ->condition('event', $name)
    ->condition('identifier', $identifier)
    ->execute();
}

/**
 * Check if the current visitor is allowed to proceed with the specified event.
 *
 * The user is allowed to proceed if he did not trigger the specified event more
 * than $threshold times in the specified time window.
 *
 * @param $name
 *   The name of the event.
 * @param $threshold
 *   The maximum number of the specified event allowed per time window.
 * @param $window
 *   Optional number of seconds over which to look for events.  Defaults to
 *   3600 (1 hour).
 * @param $identifier
 *   Optional identifier (defaults to the current user's IP address).
 * @return
 *   True if the user did not exceed the hourly threshold. False otherwise.
 */
function flood_is_allowed($name, $threshold, $window = 3600, $identifier = NULL) {
  if (!isset($identifier)) {
    $identifier = ip_address();
  }
  $number = db_query("SELECT COUNT(*) FROM {flood} WHERE event = :event AND identifier = :identifier AND timestamp > :timestamp", array(
    ':event' => $name,
    ':identifier' => $identifier,
    ':timestamp' => REQUEST_TIME - $window))
    ->fetchField();
  return ($number < $threshold);
}

function check_file($filename) {
  return is_uploaded_file($filename);
}

/**
 * @defgroup sanitization Sanitization functions
 * @{
 * Functions to sanitize values.
 */

/**
 * Prepare a URL for use in an HTML attribute. Strips harmful protocols.
 */
function check_url($uri) {
  return filter_xss_bad_protocol($uri, FALSE);
}

/**
 * Very permissive XSS/HTML filter for admin-only use.
 *
 * Use only for fields where it is impractical to use the
 * whole filter system, but where some (mainly inline) mark-up
 * is desired (so check_plain() is not acceptable).
 *
 * Allows all tags that can be used inside an HTML body, save
 * for scripts and styles.
 */
function filter_xss_admin($string) {
  return filter_xss($string, array('a', 'abbr', 'acronym', 'address', 'b', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'dd', 'del', 'dfn', 'div', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'ul', 'var'));
}

/**
 * Filter XSS.
 *
 * Based on kses by Ulf Harnhammar, see
 * http://sourceforge.net/projects/kses
 *
 * For examples of various XSS attacks, see:
 * http://ha.ckers.org/xss.html
 *
 * This code does four things:
 * - Removes characters and constructs that can trick browsers
 * - Makes sure all HTML entities are well-formed
 * - Makes sure all HTML tags and attributes are well-formed
 * - Makes sure no HTML tags contain URLs with a disallowed protocol (e.g. javascript:)
 *
 * @param $string
 *   The string with raw HTML in it. It will be stripped of everything that can cause
 *   an XSS attack.
 * @param $allowed_tags
 *   An array of allowed tags.
 */
function filter_xss($string, $allowed_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd')) {
  // Only operate on valid UTF-8 strings. This is necessary to prevent cross
  // site scripting issues on Internet Explorer 6.
  if (!drupal_validate_utf8($string)) {
    return '';
  }
  // Store the text format
  _filter_xss_split($allowed_tags, TRUE);
  // Remove NULL characters (ignored by some browsers)
  $string = str_replace(chr(0), '', $string);
  // Remove Netscape 4 JS entities
  $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

  // Defuse all HTML entities
  $string = str_replace('&', '&amp;', $string);
  // Change back only well-formed entities in our whitelist
  // Decimal numeric entities
  $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
  // Hexadecimal numeric entities
  $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
  // Named entities
  $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);

  return preg_replace_callback('%
    (
    <(?=[^a-zA-Z!/])  # a lone <
    |                 # or
    <[^>]*(>|$)       # a string that starts with a <, up until the > or the end of the string
    |                 # or
    >                 # just a >
    )%x', '_filter_xss_split', $string);
}

/**
 * Processes an HTML tag.
 *
 * @param $m
 *   An array with various meaning depending on the value of $store.
 *   If $store is TRUE then the array contains the allowed tags.
 *   If $store is FALSE then the array has one element, the HTML tag to process.
 * @param $store
 *   Whether to store $m.
 * @return
 *   If the element isn't allowed, an empty string. Otherwise, the cleaned up
 *   version of the HTML element.
 */
function _filter_xss_split($m, $store = FALSE) {
  static $allowed_html;

  if ($store) {
    $allowed_html = array_flip($m);
    return;
  }

  $string = $m[1];

  if (substr($string, 0, 1) != '<') {
    // We matched a lone ">" character
    return '&gt;';
  }
  elseif (strlen($string) == 1) {
    // We matched a lone "<" character
    return '&lt;';
  }

  if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?$%', $string, $matches)) {
    // Seriously malformed
    return '';
  }

  $slash = trim($matches[1]);
  $elem = &$matches[2];
  $attrlist = &$matches[3];

  if (!isset($allowed_html[strtolower($elem)])) {
    // Disallowed HTML element
    return '';
  }

  if ($slash != '') {
    return "</$elem>";
  }

  // Is there a closing XHTML slash at the end of the attributes?
  $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
  $xhtml_slash = $count ? ' /' : '';

  // Clean up attributes
  $attr2 = implode(' ', _filter_xss_attributes($attrlist));
  $attr2 = preg_replace('/[<>]/', '', $attr2);
  $attr2 = strlen($attr2) ? ' ' . $attr2 : '';

  return "<$elem$attr2$xhtml_slash>";
}

/**
 * Processes a string of HTML attributes.
 *
 * @return
 *   Cleaned up version of the HTML attributes.
 */
function _filter_xss_attributes($attr) {
  $attrarr = array();
  $mode = 0;
  $attrname = '';

  while (strlen($attr) != 0) {
    // Was the last operation successful?
    $working = 0;

    switch ($mode) {
      case 0:
        // Attribute name, href for instance
        if (preg_match('/^([-a-zA-Z]+)/', $attr, $match)) {
          $attrname = strtolower($match[1]);
          $skip = ($attrname == 'style' || substr($attrname, 0, 2) == 'on');
          $working = $mode = 1;
          $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
        }
        break;

      case 1:
        // Equals sign or valueless ("selected")
        if (preg_match('/^\s*=\s*/', $attr)) {
          $working = 1; $mode = 2;
          $attr = preg_replace('/^\s*=\s*/', '', $attr);
          break;
        }

        if (preg_match('/^\s+/', $attr)) {
          $working = 1; $mode = 0;
          if (!$skip) {
            $attrarr[] = $attrname;
          }
          $attr = preg_replace('/^\s+/', '', $attr);
        }
        break;

      case 2:
        // Attribute value, a URL after href= for instance
        if (preg_match('/^"([^"]*)"(\s+|$)/', $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname=\"$thisval\"";
          }
          $working = 1;
          $mode = 0;
          $attr = preg_replace('/^"[^"]*"(\s+|$)/', '', $attr);
          break;
        }

        if (preg_match("/^'([^']*)'(\s+|$)/", $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname='$thisval'";
          }
          $working = 1; $mode = 0;
          $attr = preg_replace("/^'[^']*'(\s+|$)/", '', $attr);
          break;
        }

        if (preg_match("%^([^\s\"']+)(\s+|$)%", $attr, $match)) {
          $thisval = filter_xss_bad_protocol($match[1]);

          if (!$skip) {
            $attrarr[] = "$attrname=\"$thisval\"";
          }
          $working = 1; $mode = 0;
          $attr = preg_replace("%^[^\s\"']+(\s+|$)%", '', $attr);
        }
        break;
    }

    if ($working == 0) {
      // not well formed, remove and try again
      $attr = preg_replace('/
        ^
        (
        "[^"]*("|$)     # - a string that starts with a double quote, up until the next double quote or the end of the string
        |               # or
        \'[^\']*(\'|$)| # - a string that starts with a quote, up until the next quote or the end of the string
        |               # or
        \S              # - a non-whitespace character
        )*              # any number of the above three
        \s*             # any number of whitespaces
        /x', '', $attr);
      $mode = 0;
    }
  }

  // The attribute list ends with a valueless attribute like "selected".
  if ($mode == 1) {
    $attrarr[] = $attrname;
  }
  return $attrarr;
}

/**
 * Processes an HTML attribute value and ensures it does not contain an URL with a disallowed protocol (e.g. javascript:).
 *
 * @param $string
 *   The string with the attribute value.
 * @param $decode
 *   Whether to decode entities in the $string. Set to FALSE if the $string
 *   is in plain text, TRUE otherwise. Defaults to TRUE.
 * @return
 *   Cleaned up and HTML-escaped version of $string.
 */
function filter_xss_bad_protocol($string, $decode = TRUE) {
  static $allowed_protocols;

  if (!isset($allowed_protocols)) {
    $allowed_protocols = array_flip(variable_get('filter_allowed_protocols', array('ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp', 'rtsp', 'sftp', 'ssh', 'telnet', 'webcal')));
  }

  // Get the plain text representation of the attribute value (i.e. its meaning).
  if ($decode) {
    $string = decode_entities($string);
  }

  // Iteratively remove any invalid protocol found.
  do {
    $before = $string;
    $colonpos = strpos($string, ':');
    if ($colonpos > 0) {
      // We found a colon, possibly a protocol. Verify.
      $protocol = substr($string, 0, $colonpos);
      // If a colon is preceded by a slash, question mark or hash, it cannot
      // possibly be part of the URL scheme. This must be a relative URL,
      // which inherits the (safe) protocol of the base document.
      if (preg_match('![/?#]!', $protocol)) {
        break;
      }
      // Per RFC2616, section 3.2.3 (URI Comparison) scheme comparison must be case-insensitive
      // Check if this is a disallowed protocol.
      if (!isset($allowed_protocols[strtolower($protocol)])) {
        $string = substr($string, $colonpos + 1);
      }
    }
  } while ($before != $string);

  return check_plain($string);
}

/**
 * @} End of "defgroup sanitization".
 */

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
  $output .= ' <title>' . check_plain($title) . "</title>\n";
  $output .= ' <link>' . check_url($link) . "</link>\n";

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $output .= ' <description>' . check_plain(decode_entities(strip_tags($description))) . "</description>\n";
  $output .= ' <language>' . check_plain($langcode) . "</language>\n";
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
  $output .= ' <title>' . check_plain($title) . "</title>\n";
  $output .= ' <link>' . check_url($link) . "</link>\n";
  $output .= ' <description>' . check_plain($description) . "</description>\n";
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
        $output .= ' <' . $value['key'];
        if (isset($value['attributes']) && is_array($value['attributes'])) {
          $output .= drupal_attributes($value['attributes']);
        }

        if (isset($value['value']) && $value['value'] != '') {
          $output .= '>' . (is_array($value['value']) ? format_xml_elements($value['value']) : check_plain($value['value'])) . '</' . $value['key'] . ">\n";
        }
        else {
          $output .= " />\n";
        }
      }
    }
    else {
      $output .= ' <' . $key . '>' . (is_array($value) ? format_xml_elements($value) : check_plain($value)) . "</$key>\n";
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
 * @param $options
 *   An associative array of additional options, with the following keys:
 *     - 'langcode' (default to the current language) The language code to
 *       translate to a language other than what is used to display the page.
 *     - 'context' (default to the empty context) The context the source string
 *       belongs to.
 * @return
 *   A translated string.
 */
function format_plural($count, $singular, $plural, array $args = array(), array $options = array()) {
  $args['@count'] = $count;
  if ($count == 1) {
    return t($singular, $args, $options);
  }

  // Get the plural index through the gettext formula.
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, isset($options['langcode']) ? $options['langcode'] : NULL) : -1;
  // Backwards compatibility.
  if ($index < 0) {
    return t($plural, $args, $options);
  }
  else {
    switch ($index) {
      case "0":
        return t($singular, $args, $options);
      case "1":
        return t($plural, $args, $options);
      default:
        unset($args['@count']);
        $args['@count[' . $index . ']'] = $count;
        return t(strtr($plural, array('@count' => '@count[' . $index . ']')), $args, $options);
    }
  }
}

/**
 * Parse a given byte count.
 *
 * @param $size
 *   A size expressed as a number of bytes with optional SI or IEC binary unit
 *   prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8 bytes, 9mbytes).
 * @return
 *   An integer representation of the size in bytes.
 */
function parse_size($size) {
  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
  if ($unit) {
    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
    return round($size * pow(DRUPAL_KILOBYTE, stripos('bkmgtpezy', $unit[0])));
  }
  else {
    return round($size);
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
  if ($size < DRUPAL_KILOBYTE) {
    return format_plural($size, '1 byte', '@count bytes', array(), array('langcode' => $langcode));
  }
  else {
    $size = $size / DRUPAL_KILOBYTE; // Convert bytes to kilobytes.
    $units = array(
      t('@size KB', array(), array('langcode' => $langcode)),
      t('@size MB', array(), array('langcode' => $langcode)),
      t('@size GB', array(), array('langcode' => $langcode)),
      t('@size TB', array(), array('langcode' => $langcode)),
      t('@size PB', array(), array('langcode' => $langcode)),
      t('@size EB', array(), array('langcode' => $langcode)),
      t('@size ZB', array(), array('langcode' => $langcode)),
      t('@size YB', array(), array('langcode' => $langcode)),
    );
    foreach ($units as $unit) {
      if (round($size, 2) >= DRUPAL_KILOBYTE) {
        $size = $size / DRUPAL_KILOBYTE;
      }
      else {
        break;
      }
    }
    return str_replace('@size', round($size, 2), $unit);
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
  $units = array(
    '1 year|@count years' => 31536000,
    '1 month|@count months' => 2592000,
    '1 week|@count weeks' => 604800,
    '1 day|@count days' => 86400,
    '1 hour|@count hours' => 3600,
    '1 min|@count min' => 60,
    '1 sec|@count sec' => 1
  );
  $output = '';
  foreach ($units as $key => $value) {
    $key = explode('|', $key);
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '') . format_plural(floor($timestamp / $value), $key[0], $key[1], array(), array('langcode' => $langcode));
      $timestamp %= $value;
      $granularity--;
    }

    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : t('0 sec', array(), array('langcode' => $langcode));
}

/**
 * Format a date with the given configured format or a custom format string.
 *
 * Drupal allows administrators to select formatting strings for 'short',
 * 'medium' and 'long' date formats. This function can handle these formats,
 * as well as any custom format.
 *
 * @param $timestamp
 *   The exact date to format, as a UNIX timestamp.
 * @param $type
 *   The format to use. Can be "short", "medium" or "long" for the preconfigured
 *   date formats. If "custom" is specified, then $format is required as well.
 * @param $format
 *   A PHP date format string as required by date(). A backslash should be used
 *   before a character to avoid interpreting the character as part of a date
 *   format.
 * @param $timezone
 *   Time zone identifier; if omitted, the user's time zone is used.
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 * @return
 *   A translated date string in the requested format.
 */
function format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
  $timezones = &drupal_static(__FUNCTION__, array());
  if (!isset($timezone)) {
    global $user;
    if (variable_get('configurable_timezones', 1) && $user->uid && $user->timezone) {
      $timezone = $user->timezone;
    }
    else {
      $timezone = variable_get('date_default_timezone', 'UTC');
    }
  }
  // Store DateTimeZone objects in an array rather than repeatedly
  // contructing identical objects over the life of a request.
  if (!isset($timezones[$timezone])) {
    $timezones[$timezone] = timezone_open($timezone);
  }

  // Use the default langcode if none is set.
  global $language;
  if (empty($langcode)) {
    $langcode = isset($language->language) ? $language->language : 'en';
  }

  switch ($type) {
    case 'short':
      $format = variable_get('date_format_short', 'm/d/Y - H:i');
      break;
    case 'long':
      $format = variable_get('date_format_long', 'l, F j, Y - H:i');
      break;
    case 'custom':
      // No change to format.
      break;
    case 'medium':
    default:
      $format = variable_get('date_format_medium', 'D, m/d/Y - H:i');
  }

  // Create a DateTime object from the timestamp.
  $date_time = date_create('@' . $timestamp);
  // Set the time zone for the DateTime object.
  date_timezone_set($date_time, $timezones[$timezone]);

  // Encode markers that should be translated. 'A' becomes '\xEF\AA\xFF'.
  // xEF and xFF are invalid UTF-8 sequences, and we assume they are not in the
  // input string.
  // Paired backslashes are isolated to prevent errors in read-ahead evaluation.
  // The read-ahead expression ensures that A matches, but not \A.
  $format = preg_replace(array('/\\\\\\\\/', '/(?<!\\\\)([AaeDlMTF])/'), array("\xEF\\\\\\\\\xFF", "\xEF\\\\\$1\$1\xFF"), $format);

  // Call date_format().
  $format = date_format($date_time, $format);

  // Pass the langcode to _format_date_callback().
  _format_date_callback(NULL, $langcode);

  // Translate the marked sequences.
  return preg_replace_callback('/\xEF([AaeDlMTF]?)(.*?)\xFF/', '_format_date_callback', $format);
}

/**
 * Returns an ISO8601 formatted date based on the given date.
 *
 * Can be used as a callback for RDF mappings.
 *
 * @param $date
 *   A UNIX timestamp.
 * @return string
 *   An ISO8601 formatted date.
 */
function date_iso8601($date) {
  // The DATE_ISO8601 constant cannot be used here because it does not match
  // date('c') and produces invalid RDF markup.
  return date('c', $date);
}

/**
 * Callback function for preg_replace_callback().
 */
function _format_date_callback(array $matches = NULL, $new_langcode = NULL) {
  // We cache translations to avoid redundant and rather costly calls to t().
  static $cache, $langcode;

  if (!isset($matches)) {
    $langcode = $new_langcode;
    return;
  }

  $code = $matches[1];
  $string = $matches[2];

  if (!isset($cache[$langcode][$code][$string])) {
    $options = array(
      'langcode' => $langcode,
    );

    if ($code == 'F') {
      $options['context'] = 'Long month name';
    }

    if ($code == '') {
      $cache[$langcode][$code][$string] = $string;
    }
    else {
      $cache[$langcode][$code][$string] = t($string, array(), $options);
    }
  }
  return $cache[$langcode][$code][$string];
}

/**
 * Format a username.
 *
 * By default, the passed in object's 'name' property is used if it exists, or
 * else, the site-defined value for the 'anonymous' variable. However, a module
 * may override this by implementing hook_username_alter(&$name, $account).
 *
 * @see hook_username_alter()
 *
 * @param $account
 *   The account object for the user whose name is to be formatted.
 *
 * @return
 *   An unsanitized string with the username to display. The code receiving
 *   this result must ensure that check_plain() is called on it before it is
 *   printed to the page.
 */
function format_username($account) {
  $name = !empty($account->name) ? $account->name : variable_get('anonymous', t('Anonymous'));
  drupal_alter('username', $name, $account);
  return $name;
}

/**
 * @} End of "defgroup format".
 */

/**
 * Generate a URL.
 *
 * @param $path
 *   The Drupal path being linked to, such as "admin/content", or an existing
 *   URL like "http://drupal.org/". The special path '<front>' may also be given
 *   and will generate the site's base URL.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *   - 'query': An array of query key/value-pairs (without any URL-encoding) to
 *     append to the link.
 *   - 'fragment': A fragment identifier (or named anchor) to append to the
 *     link. Do not include the leading '#' character.
 *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
 *     absolute link (beginning with http:). Useful for links that will be
 *     displayed outside the site, such as in a RSS feed.
 *   - 'alias': Defaults to FALSE. Whether the given path is a URL alias
 *     already.
 *   - 'external': Whether the given path is an external URL.
 *   - 'language': An optional language object. Used to build the URL to link to
 *     and look up the proper alias for the link.
 *   - 'https': Whether this URL should point to a secure location. If not
 *     specified, the current scheme is used, so the user stays on http or https
 *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP, but HTTPS can
 *     only be enforced when the variable 'https' is set to TRUE.
 *   - 'base_url': Only used internally, to modify the base URL when a language
 *     dependent URL requires so.
 *   - 'prefix': Only used internally, to modify the path when a language
 *     dependent URL requires so.
 *
 * @return
 *   A string containing a URL to the given path.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 */
function url($path = NULL, array $options = array()) {
  static $url_outbound;

  // Merge in defaults.
  $options += array(
    'fragment' => '',
    'query' => array(),
    'absolute' => FALSE,
    'alias' => FALSE,
    'https' => FALSE,
    'prefix' => ''
  );

  if (!isset($options['external'])) {
    // Return an external link if $path contains an allowed absolute URL.
    // Only call the slow filter_xss_bad_protocol if $path contains a ':' before
    // any / ? or #.
    $colonpos = strpos($path, ':');
    $options['external'] = ($colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && filter_xss_bad_protocol($path, FALSE) == check_plain($path));
  }

  // Preserve the original path before altering or aliasing.
  $original_path = $path;

  // Allow other modules to alter the outbound URL and options.
  // Since PHP code cannot be unloaded, we statically cache the implementations
  // of hook_url_outbound_alter() and only invoke them in case there are any.
  if (!isset($url_outbound)) {
    $url_outbound = (bool) module_implements('url_outbound_alter');
  }
  if ($url_outbound) {
    drupal_alter('url_outbound', $path, $options, $original_path);
  }

  if ($options['fragment']) {
    $options['fragment'] = '#' . $options['fragment'];
  }

  if ($options['external']) {
    // Split off the fragment.
    if (strpos($path, '#') !== FALSE) {
      list($path, $old_fragment) = explode('#', $path, 2);
      // If $options contains no fragment, take it over from the path.
      if (isset($old_fragment) && !$options['fragment']) {
        $options['fragment'] = '#' . $old_fragment;
      }
    }
    // Append the query.
    if ($options['query']) {
      $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . drupal_http_build_query($options['query']);
    }
    // Reassemble.
    return $path . $options['fragment'];
  }

  global $base_url, $base_secure_url, $base_insecure_url;
  $script = &drupal_static(__FUNCTION__);

  if (!isset($script)) {
    // On some web servers, such as IIS, we can't omit "index.php". So, we
    // generate "index.php?q=foo" instead of "?q=foo" on anything that is not
    // Apache.
    $script = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === FALSE) ? 'index.php' : '';
  }

  // The base_url might be rewritten from the language rewrite in domain mode.
  if (!isset($options['base_url'])) {
    if (isset($options['https']) && variable_get('https', FALSE)) {
      if ($options['https'] === TRUE) {
        $options['base_url'] = $base_secure_url;
        $options['absolute'] = TRUE;
      }
      elseif ($options['https'] === FALSE) {
        $options['base_url'] = $base_insecure_url;
        $options['absolute'] = TRUE;
      }
    }
    else {
      $options['base_url'] = $base_url;
    }
  }

  // The special path '<front>' links to the default front page.
  if ($path == '<front>') {
    $path = '';
  }
  elseif (!empty($path) && !$options['alias']) {
    $language = isset($options['language']) && isset($options['language']->language) ? $options['language']->language : '';
    $alias = drupal_get_path_alias($original_path, $language);
    if ($alias != $original_path) {
      $path = $alias;
    }
  }

  $base = $options['absolute'] ? $options['base_url'] . '/' : base_path();
  $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];

  // With Clean URLs.
  if (!empty($GLOBALS['conf']['clean_url'])) {
    $path = drupal_encode_path($prefix . $path);
    if ($options['query']) {
      return $base . $path . '?' . drupal_http_build_query($options['query']) . $options['fragment'];
    }
    else {
      return $base . $path . $options['fragment'];
    }
  }
  // Without Clean URLs.
  else {
    $path = $prefix . $path;
    $query = array();
    if (!empty($path)) {
      $query['q'] = $path;
    }
    if ($options['query']) {
      // We do not use array_merge() here to prevent overriding $path via query
      // parameters.
      $query += $options['query'];
    }
    if ($query) {
      return $base . $script . '?' . drupal_http_build_query($query) . $options['fragment'];
    }
    else {
      return $base . $options['fragment'];
    }
  }
}

/**
 * Format an attribute string to insert in a tag.
 *
 * Each array key and its value will be formatted into an HTML attribute string.
 * If a value is itself an array, then each array element is concatenated with a
 * space between each value (e.g. a multi-value class attribute).
 *
 * @param $attributes
 *   An associative array of HTML attributes.
 * @return
 *   An HTML string ready for insertion in a tag.
 */
function drupal_attributes(array $attributes = array()) {
  foreach ($attributes as $attribute => &$data) {
    if (is_array($data)) {
      $data = implode(' ', $data);
    }
    $data = $attribute . '="' . check_plain($data) . '"';
  }
  return $attributes ? ' ' . implode(' ', $attributes) : '';
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
 *   The Drupal path being linked to, such as "admin/content". Can be an
 *   external or internal URL.
 *     - If you provide the full URL, it will be considered an external URL.
 *     - If you provide only the path (e.g. "admin/content"), it is
 *       considered an internal link. In this case, it must be a system URL
 *       as the url() function will generate the alias.
 *     - If you provide '<front>', it generates a link to the site's
 *       base URL (again via the url() function).
 *     - If you provide a path, and 'alias' is set to TRUE (see below), it is
 *       used as is.
 * @param $options
 *   An associative array of additional options, with the following keys:
 *     - 'attributes'
 *       An associative array of HTML attributes to apply to the anchor tag.
 *     - 'query'
 *       A query string to append to the link, or an array of query key/value
 *       properties.
 *     - 'fragment'
 *       A fragment identifier (named anchor) to append to the link.
 *       Do not include the '#' character.
 *     - 'absolute' (default FALSE)
 *       Whether to force the output to be an absolute link (beginning with
 *       http:). Useful for links that will be displayed outside the site, such
 *       as in an RSS feed.
 *     - 'html' (default FALSE)
 *       Whether $text is HTML, or just plain-text. For example for making
 *       an image a link, this must be set to TRUE, or else you will see the
 *       escaped HTML.
 *     - 'alias' (default FALSE)
 *       Whether the given path is an alias already.
 * @return
 *   an HTML string containing a link to the given path.
 */
function l($text, $path, array $options = array()) {
  global $language_url;

  // Merge in defaults.
  $options += array(
      'attributes' => array(),
      'html' => FALSE,
    );

  // Append active class.
  if (($path == $_GET['q'] || ($path == '<front>' && drupal_is_front_page())) &&
      (empty($options['language']) || $options['language']->language == $language_url->language)) {
    $options['attributes']['class'][] = 'active';
  }

  // Remove all HTML and PHP tags from a tooltip. For best performance, we act only
  // if a quick strpos() pre-check gave a suspicion (because strip_tags() is expensive).
  if (isset($options['attributes']['title']) && strpos($options['attributes']['title'], '<') !== FALSE) {
    $options['attributes']['title'] = strip_tags($options['attributes']['title']);
  }

  return '<a href="' . check_plain(url($path, $options)) . '"' . drupal_attributes($options['attributes']) . '>' . ($options['html'] ? $text : check_plain($text)) . '</a>';
}

/**
 * Deliver a page callback result to the browser in the format appropriate.
 *
 * This function is most commonly called by menu_execute_active_handler(), but
 * can also be called by error conditions such as drupal_not_found(),
 * drupal_access_denied(), and drupal_site_offline().
 *
 * When a user requests a page, index.php calls menu_execute_active_handler()
 * which calls the 'page callback' function registered in hook_menu(). The page
 * callback function can return one of:
 * - NULL: to indicate no content
 * - an integer menu status constant: to indicate an error condition
 * - a string of HTML content
 * - a renderable array of content
 * Returning a renderable array rather than a string of HTML is preferred,
 * because that provides modules with more flexibility in customizing the final
 * result.
 *
 * When the page callback returns its constructed content to
 * menu_execute_active_handler(), this functions gets called. The purpose of
 * this function is to determine the most appropriate 'delivery callback'
 * function to route the content to. The delivery callback function then
 * sends the content to the browser in the needed format. The default delivery
 * callback is drupal_deliver_html_page() which delivers the content as an HTML
 * page, complete with blocks in addition to the content. This default can be
 * overridden on a per menu item basis by setting 'delivery callback' in
 * hook_menu(), hook_menu_alter(), or hook_menu_active_handler_alter().
 * Additionally, modules may use hook_page_delivery_callback_alter() to specify
 * a different delivery callback to use for the page request.
 *
 * For example, the same page callback function can be used for an HTML
 * version of the page and an AJAX version of the page. The page callback
 * function just needs to decide what content is to be returned and the
 * delivery callback function will send it as an HTML page or an AJAX
 * response, as appropriate.
 *
 * In order for page callbacks to be reusable in different delivery formats,
 * they should not issue any "print" or "echo" statements, but instead just
 * return content.
 *
 * @param $page_callback_result
 *   The result of a page callback. Can be one of:
 *   - NULL: to indicate no content.
 *   - An integer menu status constant: to indicate an error condition.
 *   - A string of HTML content.
 *   - A renderable array of content.
 * @param $default_delivery_callback
 *   (Optional) If given, it is the name of a delivery function most likely
 *   to be appropriate for the page request as determined by the calling
 *   function (e.g., menu_execute_active_handler()). If not given, it is
 *   determined from the menu router information of the current page. In either
 *   case, modules have a final chance to alter which function is called.
 *
 * @see menu_execute_active_handler()
 * @see hook_menu()
 * @see hook_menu_alter()
 * @see hook_menu_active_handler_alter()
 * @see hook_page_delivery_callback_alter()
 */
function drupal_deliver_page($page_callback_result, $default_delivery_callback = NULL) {
  if (!isset($default_delivery_callback) && ($router_item = menu_get_item())) {
    drupal_alter('menu_active_handler', $router_item);
    $default_delivery_callback = $router_item['delivery_callback'];
  }
  $delivery_callback = !empty($default_delivery_callback) ? $default_delivery_callback : 'drupal_deliver_html_page';
  // Give modules a final chance to alter the delivery callback used. This is
  // for modules that need to decide which delivery callback to use based on
  // information made available during page callback execution and for pages
  // without router items.
  drupal_alter('page_delivery_callback', $delivery_callback);
  if (function_exists($delivery_callback)) {
    $delivery_callback($page_callback_result);
  }
  else {
    // If a delivery callback is specified, but doesn't exist as a function,
    // something is wrong, but don't print anything, since it's not known
    // what format the response needs to be in.
    watchdog('delivery callback not found', check_plain($delivery_callback) . ': ' . check_plain($_GET['q']), NULL, WATCHDOG_ERROR);
  }
}

/**
 * Package and send the result of a page callback to the browser as a normal
 * HTML page.
 *
 * @param $page_callback_result
 *   The result of a page callback. Can be one of:
 *   - NULL: to indicate no content.
 *   - An integer menu status constant: to indicate an error condition.
 *   - A string of HTML content.
 *   - A renderable array of content.
 *
 * @see drupal_deliver_page
 */
function drupal_deliver_html_page($page_callback_result) {
  // Menu status constants are integers; page content is a string or array.
  if (is_int($page_callback_result)) {
    // @todo: Break these up into separate functions?
    switch ($page_callback_result) {
      case MENU_NOT_FOUND:
        // Print a 404 page.
        drupal_add_http_header('404 Not Found');

        watchdog('page not found', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

        // Keep old path for reference, and to allow forms to redirect to it.
        if (!isset($_GET['destination'])) {
          $_GET['destination'] = $_GET['q'];
        }

        $path = drupal_get_normal_path(variable_get('site_404', ''));
        if ($path && $path != $_GET['q']) {
          // Custom 404 handler. Set the active item in case there are tabs to
          // display, or other dependencies on the path.
          menu_set_active_item($path);
          $return = menu_execute_active_handler($path, FALSE);
        }

        if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
          // Standard 404 handler.
          drupal_set_title(t('Page not found'));
          $return = t('The requested page could not be found.');
        }

        drupal_set_page_content($return);
        $page = element_info('page');
        print drupal_render_page($page);
        break;

      case MENU_ACCESS_DENIED:
        // Print a 403 page.
        drupal_add_http_header('403 Forbidden');
        watchdog('access denied', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

        // Keep old path for reference, and to allow forms to redirect to it.
        if (!isset($_GET['destination'])) {
          $_GET['destination'] = $_GET['q'];
        }

        $path = drupal_get_normal_path(variable_get('site_403', ''));
        if ($path && $path != $_GET['q']) {
          // Custom 403 handler. Set the active item in case there are tabs to
          // display or other dependencies on the path.
          menu_set_active_item($path);
          $return = menu_execute_active_handler($path, FALSE);
        }

        if (empty($return) || $return == MENU_NOT_FOUND || $return == MENU_ACCESS_DENIED) {
          // Standard 403 handler.
          drupal_set_title(t('Access denied'));
          $return = t('You are not authorized to access this page.');
        }

        print drupal_render_page($return);
        break;

      case MENU_SITE_OFFLINE:
        // Print a 503 page.
        drupal_maintenance_theme();
        drupal_add_http_header('503 Service unavailable');
        drupal_set_title(t('Site under maintenance'));
        print theme('maintenance_page', array('content' => filter_xss_admin(variable_get('maintenance_mode_message',
          t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => variable_get('site_name', 'Drupal')))))));
        break;
    }
  }
  elseif (isset($page_callback_result)) {
    // Print anything besides a menu constant, assuming it's not NULL or
    // undefined.
    print drupal_render_page($page_callback_result);
  }

  // Perform end-of-request tasks.
  drupal_page_footer();
}

/**
 * Perform end-of-request tasks.
 *
 * This function sets the page cache if appropriate, and allows modules to
 * react to the closing of the page by calling hook_exit().
 */
function drupal_page_footer() {
  global $user;

  module_invoke_all('exit');

  // Commit the user session, if needed.
  drupal_session_commit();

  if (variable_get('cache', CACHE_DISABLED) != CACHE_DISABLED && ($cache = drupal_page_set_cache())) {
    drupal_serve_page_from_cache($cache);
  }
  else {
    ob_flush();
  }

  _registry_check_code(REGISTRY_WRITE_LOOKUP_CACHE);
  drupal_cache_system_paths();
  module_implements_write_cache();
}

/**
 * Perform end-of-request tasks.
 *
 * In some cases page requests need to end without calling drupal_page_footer().
 * In these cases, call drupal_exit() instead. There should rarely be a reason
 * to call exit instead of drupal_exit();
 *
 * @param $destination
 *   If this function is called from drupal_goto(), then this argument
 *   will be a fully-qualified URL that is the destination of the redirect.
 *   This should be passed along to hook_exit() implementations.
 */
function drupal_exit($destination = NULL) {
  if (drupal_get_bootstrap_phase() == DRUPAL_BOOTSTRAP_FULL) {
    if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
      module_invoke_all('exit', $destination);
    }
    drupal_session_commit();
  }
  exit;
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
 * Attempts to set the PHP maximum execution time.
 *
 * This function is a wrapper around the PHP function set_time_limit().
 * When called, set_time_limit() restarts the timeout counter from zero.
 * In other words, if the timeout is the default 30 seconds, and 25 seconds
 * into script execution a call such as set_time_limit(20) is made, the
 * script will run for a total of 45 seconds before timing out.
 *
 * It also means that it is possible to decrease the total time limit if
 * the sum of the new time limit and the current time spent running the
 * script is inferior to the original time limit. It is inherent to the way
 * set_time_limit() works, it should rather be called with an appropriate
 * value every time you need to allocate a certain amount of time
 * to execute a task than only once at the beginning of the script.
 *
 * Before calling set_time_limit(), we check if this function is available
 * because it could be disabled by the server administrator. We also hide all
 * the errors that could occur when calling set_time_limit(), because it is
 * not possible to reliably ensure that PHP or a security extension will
 * not issue a warning/error if they prevent the use of this function.
 *
 * @param $time_limit
 *   An integer specifying the new time limit, in seconds. A value of 0
 *   indicates unlimited execution time.
 *
 * @ingroup php_wrappers
 */
function drupal_set_time_limit($time_limit) {
  if (function_exists('set_time_limit')) {
    @set_time_limit($time_limit);
  }
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
 * Return the base URL path (i.e., directory) of the Drupal installation.
 *
 * base_path() prefixes and suffixes a "/" onto the returned path if the path is
 * not empty. At the very least, this will return "/".
 *
 * Examples:
 * - http://example.com returns "/" because the path is empty.
 * - http://example.com/drupal/folder returns "/drupal/folder/".
 */
function base_path() {
  return $GLOBALS['base_path'];
}

/**
 * Add a <link> tag to the page's HEAD.
 *
 * This function can be called as long the HTML header hasn't been sent.
 */
function drupal_add_link($attributes) {
  drupal_add_html_head('<link' . drupal_attributes($attributes) . " />\n");
}

/**
 * Adds a cascading stylesheet to the stylesheet queue.
 *
 * Calling drupal_static_reset('drupal_add_css') will clear all cascading
 * stylesheets added so far.
 *
 * @param $data
 *   (optional) The stylesheet data to be added, depending on what is passed
 *   through to the $options['type'] parameter:
 *   - 'file': The path to the CSS file relative to the base_path(),
 *     e.g., "modules/devel/devel.css".
 *
 *     Modules should always prefix the names of their CSS files with the
 *     module name, for example: system-menus.css rather than simply menus.css.
 *     Themes can override module-supplied CSS files based on their filenames,
 *     and this prefixing helps prevent confusing name collisions for theme
 *     developers. See drupal_get_css where the overrides are performed.
 *
 *     If the direction of the current language is right-to-left (Hebrew,
 *     Arabic, etc.), the function will also look for an RTL CSS file and append
 *     it to the list. The name of this file should have an '-rtl.css' suffix.
 *     For example a CSS file called 'mymodule-name.css' will have a
 *     'mymodule-name-rtl.css' file added to the list, if exists in the same
 *     directory. This CSS file should contain overrides for properties which
 *     should be reversed or otherwise different in a right-to-left display.
 *   - 'inline': A string of CSS that should be placed in the given scope. Note
 *     that it is better practice to use 'file' stylesheets, rather than 'inline'
 *     as the CSS would then be aggregated and cached.
 *   - 'external': The absolute path to an external CSS file that is not hosted
 *     on the local server. These files will not be aggregated if CSS aggregation
 *     is enabled.
 *
 * @param $options
 *   (optional) A string defining the 'type' of CSS that is being added in the
 *   $data parameter ('file'/'inline'), or an array which can have any or all of
 *   the following keys:
 *   - 'type': The type of stylesheet being added. Available options are 'file',
 *     'inline' or 'external'. Defaults to 'file'.
 *   - 'weight': The weight of the stylesheet specifies the order in which the
 *     CSS will appear when presented on the page.
 *
 *       Available constants are:
 *       - CSS_SYSTEM: Any system-layer CSS.
 *       - CSS_DEFAULT: Any module-layer CSS.
 *       - CSS_THEME: Any theme-layer CSS.
 *
 *       If you need to embed a CSS file before any other module's stylesheets,
 *       for example, you would use CSS_DEFAULT - 1. Note that inline CSS is
 *       simply appended to the end of the specified scope (region), so they
 *       always come last.
 *
 *   - 'media': The media type for the stylesheet, e.g., all, print, screen.
 *     Defaults to 'all'.
 *   - 'preprocess': Allows the CSS to be aggregated and compressed if the
 *     Optimize CSS feature has been turned on under the performance section.
 *     Defaults to TRUE.
 *
 *     What does this actually mean?
 *     CSS preprocessing is the process of aggregating a bunch of separate CSS
 *     files into one file that is then compressed by removing all extraneous
 *     white space. Note that preprocessed inline stylesheets will not be
 *     aggregated into this single file, instead it will just be compressed
 *     when being output on the page. External stylesheets will not be
 *     aggregated.
 *
 *     The reason for merging the CSS files is outlined quite thoroughly here:
 *     http://www.die.net/musings/page_load_time/
 *     "Load fewer external objects. Due to request overhead, one bigger file
 *     just loads faster than two smaller ones half its size."
 *
 *     However, you should *not* preprocess every file as this can lead to
 *     redundant caches. You should set $preprocess = FALSE when your styles
 *     are only used rarely on the site. This could be a special admin page,
 *     the homepage, or a handful of pages that does not represent the
 *     majority of the pages on your site.
 *
 *     Typical candidates for caching are for example styles for nodes across
 *     the site, or used in the theme.
 * @return
 *   An array of queued cascading stylesheets.
 */
function drupal_add_css($data = NULL, $options = NULL) {
  $css = &drupal_static(__FUNCTION__, array());

  // Construct the options, taking the defaults into consideration.
  if (isset($options)) {
    if (!is_array($options)) {
      $options = array('type' => $options);
    }
  }
  else {
    $options = array();
  }

  // Create an array of CSS files for each media type first, since each type needs to be served
  // to the browser differently.
  if (isset($data)) {
    $options += array(
      'type' => 'file',
      'weight' => CSS_DEFAULT,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => $data,
    );

    // Always add a tiny value to the weight, to conserve the insertion order.
    $options['weight'] += count($css) / 1000;

    // Add the data to the CSS array depending on the type.
    switch ($options['type']) {
      case 'inline':
        // For inline stylesheets, we don't want to use the $data as the array
        // key as $data could be a very long string of CSS.
        $css[] = $options;
        break;
      default:
        // Local and external files must keep their name as the associative key
        // so the same CSS file is not be added twice.
        $css[$data] = $options;
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
 * @return
 *   A string of XHTML CSS tags.
 */
function drupal_get_css($css = NULL) {
  $output = '';
  if (!isset($css)) {
    $css = drupal_add_css();
  }

  $preprocess_css = (variable_get('preprocess_css', FALSE) && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update'));
  $directory = file_directory_path('public');
  $is_writable = is_dir($directory) && is_writable($directory);

  // A dummy query-string is added to filenames, to gain control over
  // browser-caching. The string changes on every update or full cache
  // flush, forcing browsers to load a new copy of the files, as the
  // URL changed.
  $query_string = '?' . substr(variable_get('css_js_query_string', '0'), 0, 1);

  // Allow modules to alter the css items.
  drupal_alter('css', $css);

  // Sort css items according to their weights.
  uasort($css, 'drupal_sort_weight');

  // Remove the overriden CSS files. Later CSS files override former ones.
  $previous_item = array();
  foreach ($css as $key => $item) {
    if ($item['type'] == 'file') {
      $basename = basename($item['data']);
      if (isset($previous_item[$basename])) {
        // Remove the previous item that shared the same base name.
        unset($css[$previous_item[$basename]]);
      }
      $previous_item[$basename] = $key;
    }
  }

  // If CSS preprocessing is off, we still need to output the styles.
  // Additionally, go through any remaining styles if CSS preprocessing is on and output the non-cached ones.
  $rendered_css = array();
  $inline_css = '';
  $external_css = '';
  $preprocess_items = array();
  foreach ($css as $data => $item) {
    // Loop through each of the stylesheets, including them appropriately based
    // on their type.
    switch ($item['type']) {
      case 'file':
        // Depending on whether aggregation is desired, include the file.
        if (!$item['preprocess'] || !($is_writable && $preprocess_css)) {
          $rendered_css[] = '<link type="text/css" rel="stylesheet" media="' . $item['media'] . '" href="' . file_create_url($item['data']) . $query_string . '" />';
        }
        else {
          $preprocess_items[$item['media']][] = $item;
          // Mark the position of the preprocess element,
          // it should be at the position of the first preprocessed file.
          $rendered_css['preprocess'] = '';
        }
        break;
      case 'inline':
        // Include inline stylesheets.
        $inline_css .= drupal_load_stylesheet_content($item['data'], $item['preprocess']);
        break;
      case 'external':
        // Preprocessing for external CSS files is ignored.
        $external_css .= '<link type="text/css" rel="stylesheet" media="' . $item['media'] . '" href="' . $item['data'] . '" />' . "\n";
        break;
    }
  }

  if (!empty($preprocess_items)) {
    foreach ($preprocess_items as $media => $items) {
      // Prefix filename to prevent blocking by firewalls which reject files
      // starting with "ad*".
      $filename = 'css_' . md5(serialize($items) . $query_string) . '.css';
      $preprocess_file = file_create_url(drupal_build_css_cache($items, $filename));
      $rendered_css['preprocess'] .= '<link type="text/css" rel="stylesheet" media="' . $media . '" href="' . $preprocess_file . '" />' . "\n";
    }
  }
  // Enclose the inline CSS with the style tag if required.
  if (!empty($inline_css)) {
    $inline_css = "\n" . '<style type="text/css">' . $inline_css .'</style>';
  }

  // Output all the CSS files with the inline stylesheets showing up last.
  return implode("\n", $rendered_css) . $external_css . $inline_css;
}

/**
 * Aggregate and optimize CSS files, putting them in the files directory.
 *
 * @param $css
 *   An array of CSS files to aggregate and compress into one file.
 * @param $filename
 *   The name of the aggregate CSS file.
 * @return
 *   The name of the CSS file.
 */
function drupal_build_css_cache($css, $filename) {
  $data = '';

  // Create the css/ within the files folder.
  $csspath = 'public://css';
  file_prepare_directory($csspath, FILE_CREATE_DIRECTORY);
  if (!file_exists($csspath . '/' . $filename)) {
    // Build aggregate CSS file.
    foreach ($css as $stylesheet) {
      // Only 'file' stylesheets can be aggregated.
      if ($stylesheet['type'] == 'file') {
        $contents = drupal_load_stylesheet($stylesheet['data'], TRUE);
        // Return the path to where this CSS file originated from.
        $base = base_path() . dirname($stylesheet['data']) . '/';
        _drupal_build_css_path(NULL, $base);
        // Prefix all paths within this CSS file, ignoring external and absolute paths.
        $data .= preg_replace_callback('/url\([\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\)/i', '_drupal_build_css_path', $contents);
      }
    }

    // Per the W3C specification at http://www.w3.org/TR/REC-CSS2/cascade.html#at-import,
    // @import rules must proceed any other style, so we move those to the top.
    $regexp = '/@import[^;]+;/i';
    preg_match_all($regexp, $data, $matches);
    $data = preg_replace($regexp, '', $data);
    $data = implode('', $matches[0]) . $data;

    // Create the CSS file.
    file_unmanaged_save_data($data, $csspath . '/' . $filename, FILE_EXISTS_REPLACE);
  }
  return $csspath . '/' . $filename;
}

/**
 * Helper function for drupal_build_css_cache().
 *
 * This function will prefix all paths within a CSS file.
 */
function _drupal_build_css_path($matches, $base = NULL) {
  $_base = &drupal_static(__FUNCTION__);
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
  return 'url(' . $path . ')';
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
 *   Contents of the stylesheet, including any resolved @import commands.
 */
function drupal_load_stylesheet($file, $optimize = NULL) {
  // $_optimize does not use drupal_static as it is set by $optimize.
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

    // Process the stylesheet.
    $contents = drupal_load_stylesheet_content($contents, $_optimize);

    // Change back directory.
    chdir($cwd);
  }

  return $contents;
}

/**
 * Process the contents of a stylesheet for aggregation.
 *
 * @param $contents
 *   The contents of the stylesheet.
 * @param $optimize
 *   (optional) Boolean whether CSS contents should be minified. Defaults to
 *   FALSE.
 * @return
 *   Contents of the stylesheet including the imported stylesheets.
 */
function drupal_load_stylesheet_content($contents, $optimize = FALSE) {
  // Remove multiple charset declarations for standards compliance (and fixing Safari problems).
  $contents = preg_replace('/^@charset\s+[\'"](\S*)\b[\'"];/i', '', $contents);

  if ($optimize) {
    // Perform some safe CSS optimizations.
    $contents = preg_replace('<
      \s*([@{}:;,]|\)\s|\s\()\s* |  # Remove whitespace around separators, but keep space around parentheses.
      /\*([^*\\\\]|\*(?!/))+\*/     # Remove comments that are not CSS hacks.
      >x', '\1', $contents);
  }

  // Replaces @import commands with the actual stylesheet content.
  // This happens recursively but omits external files.
  $contents = preg_replace_callback('/@import\s*(?:url\()?[\'"]?(?![a-z]+:)([^\'"\()]+)[\'"]?\)?;/', '_drupal_load_stylesheet', $contents);
  return $contents;
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
  // Alter all url() paths, but not external.
  return preg_replace('/url\(([\'"]?)(?![a-z]+:)([^\'")]+)[\'"]?\)?;/i', 'url(\1' . dirname($filename) . '/', $file);
}

/**
 * Delete all cached CSS files.
 */
function drupal_clear_css_cache() {
  file_scan_directory('public://css', '/.*/', array('callback' => 'file_unmanaged_delete'));
}

/**
 * Prepare a string for use as a valid CSS identifier (element, class or ID name).
 *
 * http://www.w3.org/TR/CSS21/syndata.html#characters shows the syntax for valid
 * CSS identifiers (including element names, classes, and IDs in selectors.)
 *
 * @param $identifier
 *   The identifier to clean.
 * @param $filter
 *   An array of string replacements to use on the identifier.
 * @return
 *   The cleaned identifier.
 */
function drupal_clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => '')) {
  // By default, we filter using Drupal's coding standards.
  $identifier = strtr($identifier, $filter);

  // Valid characters in a CSS identifier are:
  // - the hyphen (U+002D)
  // - a-z (U+0030 - U+0039)
  // - A-Z (U+0041 - U+005A)
  // - the underscore (U+005F)
  // - 0-9 (U+0061 - U+007A)
  // - ISO 10646 characters U+00A1 and higher
  // We strip out any character not in the above list.
  $identifier = preg_replace('/[^\x{002D}\x{0030}-\x{0039}\x{0041}-\x{005A}\x{005F}\x{0061}-\x{007A}\x{00A1}-\x{FFFF}]/u', '', $identifier);

  return $identifier;
}

/**
 * Prepare a string for use as a valid class name.
 *
 * Do not pass one string containing multiple classes as they will be
 * incorrectly concatenated with dashes, i.e. "one two" will become "one-two".
 *
 * @param $class
 *   The class name to clean.
 * @return
 *   The cleaned class name.
 */
function drupal_html_class($class) {
  return drupal_clean_css_identifier(drupal_strtolower($class));
}

/**
 * Prepare a string for use as a valid HTML ID and guarantee uniqueness.
 *
 * @param $id
 *   The ID to clean.
 * @return
 *   The cleaned ID.
 */
function drupal_html_id($id) {
  $seen_ids = &drupal_static(__FUNCTION__, array());
  $id = strtr(drupal_strtolower($id), array(' ' => '-', '_' => '-', '[' => '-', ']' => ''));

  // As defined in http://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
  // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
  // colons (":"), and periods ("."). We strip out any character not in that
  // list. Note that the CSS spec doesn't allow colons or periods in identifiers
  // (http://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
  // characters as well.
  $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);

  // Ensure IDs are unique. The first occurrence is held but left alone.
  // Subsequent occurrences get a number appended to them. This incrementing
  // will almost certainly break code that relies on explicit HTML IDs in forms
  // that appear more than once on the page, but the alternative is outputting
  // duplicate IDs, which would break JS code and XHTML validity anyways. For
  // now, it's an acceptable stopgap solution.
  if (isset($seen_ids[$id])) {
    $id = $id . '-' . ++$seen_ids[$id];
  }
  else {
    $seen_ids[$id] = 1;
  }

  return $id;
}

/**
 * Add a JavaScript file, setting or inline code to the page.
 *
 * The behavior of this function depends on the parameters it is called with.
 * Generally, it handles the addition of JavaScript to the page, either as
 * reference to an existing file or as inline code. The following actions can be
 * performed using this function:
 *
 * - Add a file ('file'):
 *   Adds a reference to a JavaScript file to the page.
 *
 * - Add inline JavaScript code ('inline'):
 *   Executes a piece of JavaScript code on the current page by placing the code
 *   directly in the page. This can, for example, be useful to tell the user that
 *   a new message arrived, by opening a pop up, alert box etc. This should only
 *   be used for JavaScript which cannot be placed and executed from a file.
 *   When adding inline code, make sure that you are not relying on $ being jQuery.
 *   Wrap your code in (function ($) { ... })(jQuery); or use jQuery instead of $.
 *
 * - Add external JavaScript ('external'):
 *   Allows the inclusion of external JavaScript files that are not hosted on the
 *   local server. Note that these external JavaScript references do not get
 *   aggregated when preprocessing is on.
 *
 * - Add settings ('setting'):
 *   Adds a setting to Drupal's global storage of JavaScript settings. Per-page
 *   settings are required by some modules to function properly. All settings
 *   will be accessible at Drupal.settings.
 *
 * Examples:
 * @code
 *   drupal_add_js('misc/collapse.js');
 *   drupal_add_js('misc/collapse.js', 'file');
 *   drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
 *   drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });',
 *     array('type' => 'inline', 'scope' => 'footer', 'weight' => 5)
 *   );
 *   drupal_add_js('http://example.com/example.js', 'external');
 * @endcode
 *
 * Calling drupal_static_reset('drupal_add_js') will clear all JavaScript added
 * so far.
 *
 * @param $data
 *   (optional) If given, the value depends on the $options parameter:
 *   - 'file': Path to the file relative to base_path().
 *   - 'inline': The JavaScript code that should be placed in the given scope.
 *   - 'external': The absolute path to an external JavaScript file that is not
 *     hosted on the local server. These files will not be aggregated if
 *     JavaScript aggregation is enabled.
 *   - 'setting': An array with configuration options as associative array. The
 *       array is directly placed in Drupal.settings. All modules should wrap
 *       their actual configuration settings in another variable to prevent
 *       the pollution of the Drupal.settings namespace.
 * @param $options
 *   (optional) A string defining the type of JavaScript that is being added
 *   in the $data parameter ('file'/'setting'/'inline'), or an array which
 *   can have any or all of the following keys. JavaScript settings should
 *   always pass the string 'setting' only.
 *   - type
 *       The type of JavaScript that is to be added to the page. Allowed
 *       values are 'file', 'inline', 'external' or 'setting'. Defaults
 *       to 'file'.
 *   - scope
 *       The location in which you want to place the script. Possible values
 *       are 'header' or 'footer'. If your theme implements different regions,
 *       however, you can also use these. Defaults to 'header'.
 *   - weight
 *       A number defining the order in which the JavaScript is added to the
 *       page. In some cases, the order in which the JavaScript is presented
 *       on the page is very important. jQuery, for example, must be added to
 *       to the page before any jQuery code is run, so jquery.js uses a weight
 *       of JS_LIBRARY - 2, drupal.js uses a weight of JS_LIBRARY - 1, and all
 *       following scripts depending on jQuery and Drupal behaviors are simply
 *       added using the default weight of JS_DEFAULT.
 *
 *       Available constants are:
 *       - JS_LIBRARY: Any libraries, settings, or jQuery plugins.
 *       - JS_DEFAULT: Any module-layer JavaScript.
 *       - JS_THEME: Any theme-layer JavaScript.
 *
 *       If you need to invoke a JavaScript file before any other module's
 *       JavaScript, for example, you would use JS_DEFAULT - 1.
 *       Note that inline JavaScripts are simply appended to the end of the
 *       specified scope (region), so they always come last.
 *   - defer
 *       If set to TRUE, the defer attribute is set on the &lt;script&gt; tag.
 *       Defaults to FALSE.
 *   - cache
 *       If set to FALSE, the JavaScript file is loaded anew on every page
 *       call, that means, it is not cached. Used only when 'type' references
 *       a JavaScript file. Defaults to TRUE.
 *   - preprocess
 *       Aggregate the JavaScript if the JavaScript optimization setting has
 *       been toggled in admin/config/development/performance. Note that
 *       JavaScript of type 'external' is not aggregated. Defaults to TRUE.
 * @return
 *   The contructed array of JavaScript files.
 * @see drupal_get_js()
 */
function drupal_add_js($data = NULL, $options = NULL) {
  $javascript = &drupal_static(__FUNCTION__, array());

  // Construct the options, taking the defaults into consideration.
  if (isset($options)) {
    if (!is_array($options)) {
      $options = array('type' => $options);
    }
  }
  else {
    $options = array();
  }
  $options += drupal_js_defaults($data);

  // Preprocess can only be set if caching is enabled.
  $options['preprocess'] = $options['cache'] ? $options['preprocess'] : FALSE;

  // Tweak the weight so that files of the same weight are included in the
  // order of the calls to drupal_add_js().
  $options['weight'] += count($javascript) / 1000;

  if (isset($data)) {
    // Add jquery.js and drupal.js, as well as the basePath setting, the
    // first time a Javascript file is added.
    if (empty($javascript)) {
      $javascript = array(
        'settings' => array(
          'data' => array(
            array('basePath' => base_path()),
          ),
          'type' => 'setting',
          'scope' => 'header',
          'weight' => JS_LIBRARY,
        ),
        'misc/drupal.js' => array(
          'data' => 'misc/drupal.js',
          'type' => 'file',
          'scope' => 'header',
          'weight' => JS_LIBRARY - 1,
          'cache' => TRUE,
          'defer' => FALSE,
          'preprocess' => TRUE,
        ),
      );
      // Register all required libraries.
      drupal_add_library('system', 'jquery');
      drupal_add_library('system', 'once');
    }

    switch ($options['type']) {
      case 'setting':
        // All JavaScript settings are placed in the header of the page with
        // the library weight so that inline scripts appear afterwards.
        $javascript['settings']['data'][] = $data;
        break;

      case 'inline':
        $javascript[] = $options;
        break;

      default: // 'file' and 'external'
        // Local and external files must keep their name as the associative key
        // so the same JavaScript file is not be added twice.
        $javascript[$options['data']] = $options;
    }
  }
  return $javascript;
}

/**
 * Constructs an array of the defaults that are used for JavaScript items.
 *
 * @param $data
 *   (optional) The default data parameter for the JavaScript item array.
 * @see drupal_get_js()
 * @see drupal_add_js()
 */
function drupal_js_defaults($data = NULL) {
  return array(
    'type' => 'file',
    'weight' => JS_DEFAULT,
    'scope' => 'header',
    'cache' => TRUE,
    'defer' => FALSE,
    'preprocess' => TRUE,
    'data' => $data,
  );
}

/**
 * Returns a themed presentation of all JavaScript code for the current page.
 *
 * References to JavaScript files are placed in a certain order: first, all
 * 'core' files, then all 'module' and finally all 'theme' JavaScript files
 * are added to the page. Then, all settings are output, followed by 'inline'
 * JavaScript code. If running update.php, all preprocessing is disabled.
 *
 * Note that hook_js_alter(&$javascript) is called during this function call
 * to allow alterations of the JavaScript during its presentation. Calls to
 * drupal_add_js() from hook_js_alter() will not be added to the output
 * presentation. The correct way to add JavaScript during hook_js_alter()
 * is to add another element to the $javascript array, deriving from
 * drupal_js_defaults(). See locale_js_alter() for an example of this.
 *
 * @param $scope
 *   (optional) The scope for which the JavaScript rules should be returned.
 *   Defaults to 'header'.
 * @param $javascript
 *   (optional) An array with all JavaScript code. Defaults to the default
 *   JavaScript array for the given scope.
 * @return
 *   All JavaScript code segments and includes for the scope as HTML tags.
 * @see drupal_add_js()
 * @see locale_js_alter()
 * @see drupal_js_defaults()
 */
function drupal_get_js($scope = 'header', $javascript = NULL) {
  if (!isset($javascript)) {
    $javascript = drupal_add_js();
  }
  if (empty($javascript)) {
    return '';
  }

  // Allow modules to alter the JavaScript.
  drupal_alter('js', $javascript);

  // Filter out elements of the given scope.
  $items = array();
  foreach ($javascript as $item) {
    if ($item['scope'] == $scope) {
      $items[] = $item;
    }
  }

  $output = '';
  $preprocessed = '';
  $no_preprocess = '';
  $files = array();
  $preprocess_js = (variable_get('preprocess_js', FALSE) && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update'));
  $directory = file_directory_path('public');
  $is_writable = is_dir($directory) && is_writable($directory);

  // A dummy query-string is added to filenames, to gain control over
  // browser-caching. The string changes on every update or full cache
  // flush, forcing browsers to load a new copy of the files, as the
  // URL changed. Files that should not be cached (see drupal_add_js())
  // get REQUEST_TIME as query-string instead, to enforce reload on every
  // page request.
  $query_string = '?' . substr(variable_get('css_js_query_string', '0'), 0, 1);

  // For inline Javascript to validate as XHTML, all Javascript containing
  // XHTML needs to be wrapped in CDATA. To make that backwards compatible
  // with HTML 4, we need to comment out the CDATA-tag.
  $embed_prefix = "\n<!--//--><![CDATA[//><!--\n";
  $embed_suffix = "\n//--><!]]>\n";

  // Sort the JavaScript by weight so that it appears in the correct order.
  uasort($items, 'drupal_sort_weight');

  // Loop through the JavaScript to construct the rendered output.
  foreach ($items as $item) {
    switch ($item['type']) {
      case 'setting':
        $output .= '<script type="text/javascript">' . $embed_prefix . 'jQuery.extend(Drupal.settings, ' . drupal_json_encode(call_user_func_array('array_merge_recursive', $item['data'])) . ");" . $embed_suffix . "</script>\n";
        break;

      case 'inline':
        $output .= '<script type="text/javascript"' . ($item['defer'] ? ' defer="defer"' : '') . '>' . $embed_prefix . $item['data'] . $embed_suffix . "</script>\n";
        break;

      case 'file':
        if (!$item['preprocess'] || !$is_writable || !$preprocess_js) {
          $no_preprocess .= '<script type="text/javascript"' . ($item['defer'] ? ' defer="defer"' : '') . ' src="' . file_create_url($item['data']) . ($item['cache'] ? $query_string : '?' . REQUEST_TIME) . "\"></script>\n";
        }
        else {
          $files[$item['data']] = $item;
        }
        break;

      case 'external':
        // Preprocessing for external JavaScript files is ignored.
        $output .= '<script type="text/javascript"' . ($item['defer'] ? ' defer="defer"' : '') . ' src="' . check_plain($item['data']) . "\"></script>\n";
        break;
    }
  }

  // Aggregate any remaining JS files that haven't already been output.
  if ($is_writable && $preprocess_js && count($files) > 0) {
    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = 'js_' . md5(serialize($files) . $query_string) . '.js';
    $preprocess_file = file_create_url(drupal_build_js_cache($files, $filename));
    $preprocessed .= '<script type="text/javascript" src="' . $preprocess_file . '"></script>' . "\n";
  }

  // Keep the order of JS files consistent as some are preprocessed and others are not.
  // Make sure any inline or JS setting variables appear last after libraries have loaded.
  return $preprocessed . $no_preprocess . $output;
}

/**
 * Add to the page all structures attached to a render() structure.
 *
 * Libraries, JavaScript, CSS and other types of custom structures are attached
 * to elements using the #attached property. The #attached property contains an
 * associative array, where the keys are the the types of the structure, and
 * the value the attached data. For example:
 * @code
 * $build['#attached'] = array(
 *   'js' => array(drupal_get_path('module', 'taxonomy') . '/taxonomy.js'),
 *   'css' => array(drupal_get_path('module', 'taxonomy') . '/taxonomy.css'),
 * );
 * @endcode
 *
 * 'js', 'css', and 'library' are types that get special handling.  For any
 * other kind of attached data, the array key must be the full name of the
 * callback function and each value an array of arguments. For example:
 *
 * @code
 * $build['#attached']['drupal_add_http_header'] = array(
 *   array('Content-Type', 'application/rss+xml; charset=utf-8'),
 * );
 * @endcode
 *
 * @param $elements
 *   The structured array describing the data being rendered.
 * @param $weight
 *   The default weight of JavaScript and CSS being added. This is only applied
 *   to the stylesheets and JavaScript items that don't have an explicit weight
 *   assigned to them.
 * @param $dependency_check
 *   When TRUE, will exit if a given library's dependencies are missing. When
 *   set to FALSE, will continue to add the libraries, even though one of the
 *   dependencies are missing. Defaults to FALSE.
 * @return
 *   Will return FALSE if there were any missing library dependencies. TRUE will
 *   be returned if all library dependencies were met.
 *
 * @see drupal_add_library().
 * @see drupal_add_js().
 * @see drupal_add_css().
 * @see drupal_render().
 */
function drupal_process_attached($elements, $weight = JS_DEFAULT, $dependency_check = FALSE) {
  // If there is nothing to process then return.
  if (empty($elements['#attached'])) {
    return;
  }
  // Add defaults to the special attached structures that should be processed differently.
  $elements['#attached'] += array(
    'library' => array(),
    'js' => array(),
    'css' => array(),
  );

  // Add the libraries first.
  $success = TRUE;
  foreach ($elements['#attached']['library'] as $library) {
    if (drupal_add_library($library[0], $library[1]) === FALSE) {
      $success = FALSE;
      // Exit if the dependency is missing.
      if ($dependency_check) {
        return $success;
      }
    }
  }
  unset($elements['#attached']['library']);

  // Add both the JavaScript and the CSS.
  // The parameters for drupal_add_js() and drupal_add_css() require special
  // handling.
  foreach (array('js', 'css') as $type) {
    foreach ($elements['#attached'][$type] as $data => $options) {
      // If the value is not an array, it's a filename and passed as first
      // (and only) argument.
      if (!is_array($options)) {
        $data = $options;
        $options = NULL;
      }
      // In some cases, the first parameter ($data) is an array. Arrays can't be
      // passed as keys in PHP, so we have to get $data from the value array.
      if (is_numeric($data)) {
        $data = $options['data'];
        unset($options['data']);
      }
      // Apply the default weight if the weight isn't explicitly given.
      if (!isset($options['weight'])) {
        $options['weight'] = $weight;
      }
      call_user_func('drupal_add_' . $type, $data, $options);
    }
    unset($elements['#attached'][$type]);
  }

  // Add additional types of attachments specified in the render() structure.
  // Libraries, Javascript and CSS have been added already, as they require
  // special handling.
  foreach ($elements['#attached'] as $callback => $options) {
    if (function_exists($callback)) {
      foreach ($elements['#attached'][$callback] as $args) {
        call_user_func_array($callback, $args);
      }
    }
  }

  return $success;
}

/**
 * Adds JavaScript to the element to allow it to have different active states.
 *
 * @param $elements
 *   The structured array that may contain an array item named states. This
 *   array describes the different JavaScript states that can be applied to the
 *   element when certain contitions are met. The #states array is first keyed
 *   by one of the following states:
 *   - enabled
 *   - invisible
 *   - invalid
 *   - untouched
 *   - optional
 *   - filled
 *   - unchecked
 *   - irrelevant
 *   - expanded
 *   - readwrite
 *
 *   Each of these states is an array containing conditions that must be met in
 *   order for this state to be active. The key to this conditioning array is
 *   a jQuery selector for the element that is checked. The value of the
 *   conditioning array are the states that are checked on the element (empty,
 *   checked, value, collapsed, etc) and the expected value of that condition.
 *
 *   @code
 *   $form['email_canceled']['settings'] = array(
 *     '#type' => 'container',
 *     '#states' => array(
 *       // Hide the settings when the cancel notify checkbox is disabled.
 *       'invisible' => array(
 *         'input[name="email_canceled_toggle"]' => array('checked' => FALSE),
 *       ),
 *     ),
 *   );
 *   @endcode
 */
function drupal_process_states(&$elements) {
  if (!empty($elements['#states'])) {
    $elements['#attached']['js']['misc/states.js'] = array('weight' => JS_LIBRARY + 1);
    $elements['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('states' => array('#' . $elements['#id'] => $elements['#states'])),
    );
  }
}

/**
 * Adds multiple JavaScript or CSS files at the same time.
 *
 * A library defines a set of JavaScript and/or CSS files, optionally using
 * settings, and optionally requiring another library. For example, a library
 * can be a jQuery plugin, a JavaScript framework, or a CSS framework. This
 * function allows modules to load a library defined/shipped by itself or a
 * depending module; without having to add all files of the library separately.
 * Each library is only loaded once.
 *
 * @param $module
 *   The name of the module that registered the library.
 * @param $name
 *   The name of the library to add.
 * @return
 *   TRUE when the library was successfully added or FALSE if the library or one
 *   of its dependencies could not be added.
 *
 * @see drupal_get_library()
 * @see hook_library()
 * @see hook_library_alter()
 */
function drupal_add_library($module, $name) {
  $added = &drupal_static(__FUNCTION__, array());

  // Only process the library if it exists and it was not added already.
  if (!isset($added[$module][$name])) {
    if ($library = drupal_get_library($module, $name)) {
      // Add all components within the library.
      $elements['#attached'] = array(
        'library' => $library['dependencies'],
        'js' => $library['js'],
        'css' => $library['css'],
      );
      $added[$module][$name] = drupal_process_attached($elements, JS_LIBRARY, TRUE);
    }
    else {
      // Requested library does not exist.
      $added[$module][$name] = FALSE;
    }
  }

  return $added[$module][$name];
}

/**
 * Retrieves information for a JavaScript/CSS library.
 *
 * Library information is statically cached. Libraries are keyed by module for
 * several reasons:
 * - Libraries are not unique. Multiple modules might ship with the same library
 *   in a different version or variant. This registry cannot (and does not
 *   attempt to) prevent library conflicts.
 * - Modules implementing and thereby depending on a library that is registered
 *   by another module can only rely on that module's library.
 * - Two (or more) modules can still register the same library and use it
 *   without conflicts in case the libraries are loaded on certain pages only.
 *
 * @param $module
 *   The name of a module that registered a library.
 * @param $library
 *   The name of a registered library.
 * @return
 *   The definition of the requested library, if existent, or FALSE.
 *
 * @see drupal_add_library()
 * @see hook_library()
 * @see hook_library_alter()
 *
 * @todo The purpose of drupal_get_*() is completely different to other page
 *   requisite API functions; find and use a different name.
 */
function drupal_get_library($module, $name) {
  $libraries = &drupal_static(__FUNCTION__, array());

  if (!array_key_exists($module, $libraries)) {
    // Retrieve all libraries associated with the module.
    $module_libraries = module_invoke($module, 'library');

    // Allow modules to alter the module's registered libraries.
    if (!empty($module_libraries)) {
      drupal_alter('library', $module_libraries, $module);
    }
    $libraries[$module] = $module_libraries;
  }
  if (!empty($libraries[$module][$name]) && is_array($libraries[$module][$name])) {
    // Add default elements to allow for easier processing.
    $libraries[$module][$name] += array('dependencies' => array(), 'js' => array(), 'css' => array());
  }
  else {
    $libraries[$module][$name] = FALSE;
  }

  return $libraries[$module][$name];
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
 * $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'my-module-table')));
 * return $output;
 * @endcode
 *
 * In the theme function for the form, a special class must be added to each
 * form element within the same column, "grouping" them together.
 *
 * In a situation where a single weight column is being sorted in the table, the
 * classes could be added like this (in the theme function):
 * @code
 * $form['my_elements'][$delta]['weight']['#attributes']['class'] = array('my-elements-weight');
 * @endcode
 *
 * Each row of the table must also have a class of "draggable" in order to enable the
 * drag handles:
 * @code
 * $row = array(...);
 * $rows[] = array(
 *   'data' => $row,
 *   'class' => array('draggable'),
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
 * the block regions on the admin/structure/block page), a separate subgroup class
 * must also be added to differentiate the groups.
 * @code
 * $form['my_elements'][$region][$delta]['weight']['#attributes']['class'] = array('my-elements-weight', 'my-elements-weight-' . $region);
 * @endcode
 *
 * $group is still 'my-element-weight', and the additional $subgroup variable
 * will be passed in as 'my-elements-weight-' . $region. This also means that
 * you'll need to call drupal_add_tabledrag() once for every region added.
 *
 * @code
 * foreach ($regions as $region) {
 *   drupal_add_tabledrag('my-module-table', 'order', 'sibling', 'my-elements-weight', 'my-elements-weight-' . $region);
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
 * not in a form declaration. Though the same JavaScript could be added to the
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
  $js_added = &drupal_static(__FUNCTION__, FALSE);
  if (!$js_added) {
    // Add the table drag JavaScript to the page before the module JavaScript
    // to ensure that table drag behaviors are registered before any module
    // uses it.
    drupal_add_js('misc/tabledrag.js', array('weight' => JS_DEFAULT - 1));
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
  $jspath = 'public://js';
  file_prepare_directory($jspath, FILE_CREATE_DIRECTORY);

  if (!file_exists($jspath . '/' . $filename)) {
    // Build aggregate JS file.
    foreach ($files as $path => $info) {
      if ($info['preprocess']) {
        // Append a ';' after each JS file to prevent them from running together.
        $contents .= file_get_contents($path) . ';';
      }
    }

    // Create the JS file.
    file_unmanaged_save_data($contents, $jspath . '/' . $filename, FILE_EXISTS_REPLACE);
  }

  return $jspath . '/' . $filename;
}

/**
 * Delete all cached JS files.
 */
function drupal_clear_js_cache() {
  file_scan_directory('public://js', '/.*/', array('callback' => 'file_unmanaged_delete'));
  variable_set('javascript_parsed', array());
}

/**
 * Converts a PHP variable into its Javascript equivalent.
 *
 * We use HTML-safe strings, i.e. with <, > and & escaped.
 */
function drupal_json_encode($var) {
  // json_encode() does not escape <, > and &, so we do it with str_replace()
  return str_replace(array("<", ">", "&"), array('\x3c', '\x3e', '\x26'), json_encode($var));
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
function drupal_json_output($var = NULL) {
  // We are returning JavaScript, so tell the browser.
  drupal_add_http_header('Content-Type', 'text/javascript; charset=utf-8');

  if (isset($var)) {
    echo drupal_json_encode($var);
  }
}

/**
 * Returns a string of highly randomized bytes (over the full 8-bit range).
 *
 * This function is better than simply calling mt_rand() or any other built-in
 * PHP function because it can return a long string of bytes (compared to < 4
 * bytes normally from mt_rand()) and uses the best available pseudo-random source.
 *
 * @param $count
 *   The number of characters (bytes) to return in the string.
 */
function drupal_random_bytes($count)  {
  // $random_state does not use drupal_static as it stores random bytes.
  static $random_state;
  // We initialize with the somewhat random PHP process ID on the first call.
  if (empty($random_state)) {
    $random_state = getmypid();
  }
  $output = '';
  // /dev/urandom is available on many *nix systems and is considered the best
  // commonly available pseudo-random source.
  if ($fh = @fopen('/dev/urandom', 'rb')) {
    $output = fread($fh, $count);
    fclose($fh);
  }
  // If /dev/urandom is not available or returns no bytes, this loop will
  // generate a good set of pseudo-random bytes on any system.
  // Note that it may be important that our $random_state is passed
  // through md5() prior to being rolled into $output, that the two md5()
  // invocations are different, and that the extra input into the first one -
  // the microtime() - is prepended rather than appended. This is to avoid
  // directly leaking $random_state via the $output stream, which could
  // allow for trivial prediction of further "random" numbers.
  while (strlen($output) < $count) {
    $random_state = md5(microtime() . mt_rand() . $random_state);
    $output .= md5(mt_rand() . $random_state, TRUE);
  }
  return substr($output, 0, $count);
}

/**
 * Ensure the private key variable used to generate tokens is set.
 *
 * @return
 *   The private key.
 */
function drupal_get_private_key() {
  if (!($key = variable_get('drupal_private_key', 0))) {
    $key = md5(drupal_random_bytes(64));
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
  return (($skip_anonymous && $user->uid == 0) || ($token == md5(session_id() . $value . variable_get('drupal_private_key', ''))));
}

function _drupal_bootstrap_full() {
  $called = &drupal_static(__FUNCTION__);

  if ($called) {
    return;
  }
  $called = 1;
  require_once DRUPAL_ROOT . '/includes/path.inc';
  require_once DRUPAL_ROOT . '/includes/theme.inc';
  require_once DRUPAL_ROOT . '/includes/pager.inc';
  require_once DRUPAL_ROOT . '/includes/menu.inc';
  require_once DRUPAL_ROOT . '/includes/tablesort.inc';
  require_once DRUPAL_ROOT . '/includes/file.inc';
  require_once DRUPAL_ROOT . '/includes/unicode.inc';
  require_once DRUPAL_ROOT . '/includes/image.inc';
  require_once DRUPAL_ROOT . '/includes/form.inc';
  require_once DRUPAL_ROOT . '/includes/mail.inc';
  require_once DRUPAL_ROOT . '/includes/actions.inc';
  require_once DRUPAL_ROOT . '/includes/ajax.inc';
  require_once DRUPAL_ROOT . '/includes/token.inc';
  // Set the Drupal custom error handler.
  set_error_handler('_drupal_error_handler');
  set_exception_handler('_drupal_exception_handler');

  // Emit the correct charset HTTP header.
  drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
  // Detect string handling method
  unicode_check();
  // Undo magic quotes
  fix_gpc_magic();
  // Load all enabled modules
  module_load_all();
  // Make sure all stream wrappers are registered.
  file_get_stream_wrappers();
  if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'simpletest') !== FALSE) {
    // Valid SimpleTest user-agent, log fatal errors to test specific file
    // directory. The user-agent is validated in DRUPAL_BOOTSTRAP_DATABASE
    // phase so as long as it is a SimpleTest user-agent it is valid.
    ini_set('log_errors', 1);
    ini_set('error_log', file_directory_path() . '/error.log');
  }
  // Initialize $_GET['q'] prior to invoking hook_init().
  drupal_path_initialize();
  // Set a custom theme for the current page, if there is one. We need to run
  // this before invoking hook_init(), since any modules which initialize the
  // theme system will prevent a custom theme from being correctly set later.
  menu_set_custom_theme();
  // Let all modules take action before menu system handles the request
  // We do not want this while running update.php.
  if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
    module_invoke_all('init');
  }
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
function drupal_page_set_cache() {
  global $base_root;

  if (drupal_page_is_cacheable()) {
    $cache_page = TRUE;

    $cache = (object) array(
      'cid' => $base_root . request_uri(),
      'data' => ob_get_clean(),
      'expire' => CACHE_TEMPORARY,
      'created' => REQUEST_TIME,
      'headers' => array(),
    );

    // Restore preferred header names based on the lower-case names returned
    // by drupal_get_http_header().
    $header_names = _drupal_set_preferred_header_name();
    foreach (drupal_get_http_header() as $name_lower => $value) {
      $cache->headers[$header_names[$name_lower]] = $value;
    }

    if (variable_get('page_compression', TRUE) && function_exists('gzencode')) {
      // We do not store the data in case the zlib mode is deflate. This should
      // be rarely happening.
      if (zlib_get_coding_type() == 'deflate') {
        $cache_page = FALSE;
      }
      elseif (zlib_get_coding_type() == FALSE) {
        $cache->data = gzencode($cache->data, 9, FORCE_GZIP);
      }
      // The remaining case is 'gzip' which means the data is already
      // compressed and nothing left to do but to store it.
    }
    if ($cache_page && $cache->data) {
      cache_set($cache->cid, $cache->data, 'cache_page', $cache->expire, $cache->headers);
    }
    return $cache;
  }
}

/**
 * Executes a cron run when called.
 *
 * Do not call this function from test, use $this->cronRun() instead.
 *
 * @return
 *   Returns TRUE if ran successfully
 */
function drupal_cron_run() {
  // Allow execution to continue even if the request gets canceled.
  @ignore_user_abort(TRUE);

  // Try to allocate enough time to run all the hook_cron implementations.
  drupal_set_time_limit(240);

  // Fetch the cron semaphore
  $semaphore = variable_get('cron_semaphore', FALSE);

  $return = FALSE;
  // Grab the defined cron queues.
  $queues = module_invoke_all('cron_queue_info');
  drupal_alter('cron_queue_info', $queues);

  if ($semaphore) {
    if (REQUEST_TIME - $semaphore > 3600) {
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
    // Make sure every queue exists. There is no harm in trying to recreate an
    // existing queue.
    foreach ($queues as $queue_name => $info) {
      DrupalQueue::get($queue_name)->createQueue();
    }
    // Register shutdown callback
    register_shutdown_function('drupal_cron_cleanup');

    // Lock cron semaphore
    variable_set('cron_semaphore', REQUEST_TIME);

    // Iterate through the modules calling their cron handlers (if any):
    module_invoke_all('cron');

    // Record cron time
    variable_set('cron_last', REQUEST_TIME);
    watchdog('cron', 'Cron run completed.', array(), WATCHDOG_NOTICE);

    // Release cron semaphore
    variable_del('cron_semaphore');

    // Return TRUE so other functions can check if it did run successfully
    $return = TRUE;
  }

  foreach ($queues as $queue_name => $info) {
    $function = $info['worker callback'];
    $end = time() + (isset($info['time']) ? $info['time'] : 15);
    $queue = DrupalQueue::get($queue_name);
    while (time() < $end && ($item = $queue->claimItem())) {
      $function($item->data);
      $queue->deleteItem($item);
    }
  }
  return $return;
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
 *   The preg_match() regular expression of the files to find.
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
  $config = conf_path();

  $profile = drupal_get_profile();

  $searchdir = array($directory);
  $files = array();

  // The 'profiles' directory contains pristine collections of modules and
  // themes as organized by a distribution. It is pristine in the same way
  // that /modules is pristine for core; users should avoid changing anything
  // there in favor of sites/all or sites/<domain> directories.
  if (file_exists("profiles/$profile/$directory")) {
    $searchdir[] = "profiles/$profile/$directory";
  }

  // Always search sites/all/* as well as the global directories
  $searchdir[] = 'sites/all/' . $directory;

  if (file_exists("$config/$directory")) {
    $searchdir[] = "$config/$directory";
  }

  // If the database is not available, we can't use function_exists(), so
  // we load the file_scan_directory function definition manually.
  if (!function_exists('file_scan_directory')) {
    require_once DRUPAL_ROOT . '/includes/file.inc';
  }

  // Get current list of items
  foreach ($searchdir as $dir) {
    $files = array_merge($files, file_scan_directory($dir, $mask, array('key' => $key, 'min_depth' => $min_depth)));
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
 * A maximum of 2 alterable arguments is supported. In case more arguments need
 * to be passed and alterable, modules provide additional variables assigned by
 * reference in the last $context argument:
 * @code
 *   $context = array(
 *     'alterable' => &$alterable,
 *     'unalterable' => $unalterable,
 *     'foo' => 'bar',
 *   );
 *   drupal_alter('mymodule_data', $alterable1, $alterable2, $context);
 * @endcode
 *
 * Note that objects are always passed by reference in PHP5. If it is absolutely
 * required that no implementation alters a passed object in $context, then an
 * object needs to be cloned:
 * @code
 *   $context = array(
 *     'unalterable_object' => clone $object,
 *   );
 *   drupal_alter('mymodule_data', $data, $context);
 * @endcode
 *
 * @param $type
 *   A string describing the data type of the alterable $data. 'form', 'links',
 *   'node_content', and so on are several examples.
 * @param &$data
 *   The primary data to be altered.
 * @param &$context1
 *   (optional) An additional variable that is passed by reference.
 * @param &$context2
 *   (optional) An additional variable that is passed by reference. If more
 *   context needs to be provided to implementations, then this should be an
 *   keyed array as described above.
 */
function drupal_alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
  $hook = $type . '_alter';
  foreach (module_implements($hook) as $module) {
    $function = $module . '_' . $hook;
    $function($data, $context1, $context2);
  }
  // Allow the theme to alter variables after the theme system has been
  // initialized.
  global $theme, $base_theme_info;
  if (isset($theme)) {
    $theme_keys = array();
    foreach ($base_theme_info as $base) {
      $theme_keys[] = $base->name;
    }
    $theme_keys[] = $theme;
    foreach ($theme_keys as $theme_key) {
      $function = $theme_key . '_' . $hook;
      if (function_exists($function)) {
        $function($data, $context1, $context2);
      }
    }
  }
}

/**
 * Set the main page content value for later use.
 *
 * Given the nature of the Drupal page handling, this will be called once with
 * a string or array. We store that and return it later as the block is being
 * displayed.
 *
 * @param $content
 *   A string or renderable array representing the body of the page.
 * @return
 *   A renderable array representing the body of the page.
 */
function drupal_set_page_content($content = NULL) {
  $content_block = &drupal_static(__FUNCTION__, NULL);
  $main_content_display = &drupal_static('system_main_content_added', FALSE);

  if (!empty($content)) {
    $content_block = (is_array($content) ? $content : array('main' => array('#markup' => $content)));
  }
  else {
    // Indicate that the main content has been requested. We assume that
    // the module requesting the content will be adding it to the page.
    // A module can indicate that it does not handle the content by setting
    // the static variable back to FALSE after calling this function.
    $main_content_display = TRUE;
    return $content_block;
  }
}

/**
 * Renders the page, including all theming.
 *
 * @param $page
 *   A string or array representing the content of a page. The array consists of
 *   the following keys:
 *   - #type: Value is always 'page'. This pushes the theming through page.tpl.php (required).
 *   - #show_messages: Suppress drupal_get_message() items. Used by Batch API (optional).
 *
 * @see hook_page_alter()
 * @see element_info('page')
 */
function drupal_render_page($page) {
  $main_content_display = &drupal_static('system_main_content_added', FALSE);

  // Allow menu callbacks to return strings or arbitrary arrays to render.
  // If the array returned is not of #type page directly, we need to fill
  // in the page with defaults.
  if (is_string($page) || (is_array($page) && (!isset($page['#type']) || ($page['#type'] != 'page')))) {
    drupal_set_page_content($page);
    $page = element_info('page');
  }

  // Modules can add elements to $page as needed in hook_page_build().
  foreach (module_implements('page_build') as $module) {
    $function = $module . '_page_build';
    $function($page);
  }
  // Modules alter the $page as needed. Blocks are populated into regions like
  // 'sidebar_first', 'footer', etc.
  drupal_alter('page', $page);

  // If no module has taken care of the main content, add it to the page now.
  // This allows the site to still be usable even if no modules that
  // control page regions (for example, the Block module) are enabled.
  if (!$main_content_display) {
    $page['content']['system_main'] = drupal_set_page_content();
  }

  return drupal_render($page);
}

/**
 * Renders HTML given a structured array tree.
 *
 * Recursively iterates over each of the array elements, generating HTML code.
 *
 * HTML generation is controlled by two properties containing theme functions,
 * #theme and #theme_wrappers.
 *
 * #theme is the theme function called first. If it is set and the element has
 * any children, they have to be rendered there. For elements that are not
 * allowed to have any children, e.g. buttons or textfields, it can be used to
 * render the element itself. If #theme is not present and the element has
 * children, they are rendered and concatenated into a string by
 * drupal_render_children().
 *
 * The #theme_wrappers property contains an array of theme functions which will
 * be called, in order, after #theme has run. These can be used to add further
 * markup around the rendered children; e.g., fieldsets add the required markup
 * for a fieldset around their rendered child elements. All wrapper theme
 * functions have to include the element's #children property in their output,
 * as it contains the output of the previous theme functions and the rendered
 * children.
 *
 * For example, for the form element type, by default only the #theme_wrappers
 * property is set, which adds the form markup around the rendered child
 * elements of the form. This allows you to set the #theme property on a
 * specific form to a custom theme function, giving you complete control over
 * the placement of the form's children while not at all having to deal with
 * the form markup itself.
 *
 * drupal_render() can optionally cache the rendered output of elements to
 * improve performance. To use drupal_render() caching, set the element's #cache
 * property to an associative array with one or several of the following keys:
 *    - 'keys': An array of one or more keys that identify the element. If 'keys'
 *       is set, the cache ID is created automatically from these keys.
 *       @see drupal_render_cid_create()
 *    - 'granularity' (optional): Define the cache granularity using binary
 *       combinations of the cache granularity constants, e.g. DRUPAL_CACHE_PER_USER
 *       to cache for each user seperately or
 *       DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE to cache seperately for each
 *       page and role. If not specified the element is cached globally for each
 *       theme and language.
 *    - 'cid': Specify the cache ID directly. Either 'keys' or 'cid' is required.
 *       If 'cid' is set, 'keys' and 'granularity' are ignored. Use only if you
 *       have special requirements.
 *    - 'expire': Set to one of the cache lifetime constants.
 *    - 'bin': Specify a cache bin to cache the element in. Defaults to 'cache'.
 *
 * This function is usually called from within another function, like
 * drupal_get_form() or a theme function. Elements are sorted internally
 * using uasort(). Since this is expensive, when passing already sorted
 * elements to drupal_render(), for example from a database query, set
 * $elements['#sorted'] = TRUE to avoid sorting them a second time.
 *
 * @param $elements
 *   The structured array describing the data to be rendered.
 * @return
 *   The rendered HTML.
 */
function drupal_render(&$elements) {
  static $defaults;
  // Early-return nothing if user does not have access.
  if (!isset($elements) || (isset($elements['#access']) && !$elements['#access'])) {
    return;
  }

  // Do not print elements twice.
  if (isset($elements['#printed']) && $elements['#printed']) {
    return;
  }

  // Try to fetch the element's markup from cache and return.
  if (isset($elements['#cache']) && $cached_output = drupal_render_cache_get($elements)) {
    return $cached_output;
  }

  // If the default values for this element have not been loaded yet, populate
  // them.
  if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
    $elements += element_info($elements['#type']);
  }
  else {
    if (!isset($defaults)) {
      $defaults = element_basic_defaults();
    }
    $elements += $defaults;
  }

  // If #markup is not empty and no theme function is set, use theme_markup.
  // This allows to specify just #markup on an element without setting the #type.
  if (!empty($elements['#markup']) && empty($elements['#theme'])) {
    $elements['#theme'] = 'markup';
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

  // Get the children of the element, sorted by weight.
  $children = element_children($elements, TRUE);

  $elements['#children'] = '';
  // Call the element's #theme function if it is set. Then any children of the
  // element have to be rendered there.
  if (isset($elements['#theme'])) {
    $elements['#children'] = theme($elements['#theme'], $elements);
  }
  // If #theme was not set and the element has children, render them now
  // using drupal_render_children().
  if ($elements['#children'] == '') {
    $elements['#children'] = drupal_render_children($elements, $children);
  }

  // Let the theme functions in #theme_wrappers add markup around the rendered
  // children.
  if (isset($elements['#theme_wrappers'])) {
    foreach ($elements['#theme_wrappers'] as $theme_wrapper) {
      $elements['#children'] = theme($theme_wrapper, $elements);
    }
  }

  // Filter the outputted content and make any last changes before the
  // content is sent to the browser. The changes are made on $content
  // which allows the output'ed text to be filtered.
  if (isset($elements['#post_render'])) {
    foreach ($elements['#post_render'] as $function) {
      if (function_exists($function)) {
        $elements['#children'] = $function($elements['#children'], $elements);
      }
    }
  }

  // Add any JavaScript state information associated with the element.
  drupal_process_states($elements);

  // Add additional libraries, CSS, JavaScript an other custom
  // attached data associated with this element.
  drupal_process_attached($elements);

  $prefix = isset($elements['#prefix']) ? $elements['#prefix'] : '';
  $suffix = isset($elements['#suffix']) ? $elements['#suffix'] : '';

  // Cache the processed element if #cache is set.
  if (isset($elements['#cache'])) {
    drupal_render_cache_set($prefix . $elements['#children'] . $suffix, $elements);
  }

  $elements['#printed'] = TRUE;
  return $prefix . $elements['#children'] . $suffix;
}

/**
 * Render children of an element and concatenate them.
 *
 * This renders all children of an element using drupal_render() and then
 * joins them together into a single string.
 *
 * @param $element
 *   The structured array whose children shall be rendered.
 * @param $children_keys
 *   If the keys of the element's children are already known, they can be passed
 *   in to save another run of element_children().
 */
function drupal_render_children(&$element, $children_keys = NULL) {
  if ($children_keys === NULL) {
    $children_keys = element_children($element);
  }
  $output = '';
  foreach ($children_keys as $key) {
    $output .= drupal_render($element[$key]);
  }
  return $output;
}

/**
 * Render and print an element.
 *
 * This function renders an element using drupal_render(). The top level
 * element is always rendered even if hide() had been previously used on it.
 *
 * Any nested elements are only rendered if they haven't been rendered before
 * or if they have been re-enabled with show().
 *
 * @see drupal_render()
 * @see show()
 * @see hide()
 */
function render(&$element) {
  if (is_array($element)) {
    show($element);
    return drupal_render($element);
  }
  else {
    // Safe-guard for inappropriate use of render() on flat variables: return
    // the variable as-is.
    return $element;
  }
}

/**
 * Hide an element from later rendering.
 *
 * @see render()
 * @see show()
 */
function hide(&$element) {
  $element['#printed'] = TRUE;
  return $element;
}

/**
 * Show a hidden or already printed element from later rendering.
 *
 * Alternatively, render($element) could be used which automatically shows the
 * element while rendering it.
 *
 * @see render()
 * @see hide()
 */
function show(&$element) {
  $element['#printed'] = FALSE;
  return $element;
}

/**
 * Get the rendered output of a renderable element from cache.
 *
 * @see drupal_render()
 * @see drupal_render_cache_set()
 *
 * @param $elements
 *   A renderable array.
 * @return
 *   A markup string containing the rendered content of the element, or FALSE
 *   if no cached copy of the element is available.
 */
function drupal_render_cache_get($elements) {
  if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')) || !$cid = drupal_render_cid_create($elements)) {
    return FALSE;
  }
  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'cache';

  if (!empty($cid) && $cache = cache_get($cid, $bin)) {
    // Add additional libraries, JavaScript, CSS and other data attached
    // to this element.
    drupal_process_attached($cache->data);
    // Return the rendered output.
    return $cache->data['#markup'];;
  }
  return FALSE;
}

/**
 * Cache the rendered output of a renderable element.
 *
 * This is called by drupal_render() if the #cache property is set on an element.
 *
 * @see drupal_render()
 * @see drupal_render_cache_get()
 *
 * @param $markup
 *   The rendered output string of $elements.
 * @param $elements
 *   A renderable array.
 */
function drupal_render_cache_set($markup, $elements) {
  // Create the cache ID for the element.
  if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')) || !$cid = drupal_render_cid_create($elements)) {
    return FALSE;
  }

  $data['#markup'] = $markup;
  // Persist attached data associated with this element.
  $data['#attached'] = $elements['#attached'];
  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'cache';
  $expire = isset($elements['#cache']['expire']) ? $elements['#cache']['expire'] : CACHE_PERMANENT;
  cache_set($cid, $data, $bin, $expire);
}

/**
 * Helper function for building cache ids.
 *
 * @param $granularity
 *   One or more cache granularity constants, e.g. DRUPAL_CACHE_PER_USER to cache
 *   for each user seperately or DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE to
 *   cache seperately for each page and role.
 *
 * @return
 *   An array of cache ID parts, always containing the active theme. If the
 *   locale module is enabled it also contains the active language. If
 *   $granularity was passed in, more parts are added.
 */
function drupal_render_cid_parts($granularity = NULL) {
  global $theme, $base_root, $user;

  $cid_parts[] = $theme;
  if (module_exists('locale')) {
    global $language;
    $cid_parts[] = $language->language;
  }

  if (!empty($granularity)) {
    // 'PER_ROLE' and 'PER_USER' are mutually exclusive. 'PER_USER' can be a
    // resource drag for sites with many users, so when a module is being
    // equivocal, we favor the less expensive 'PER_ROLE' pattern.
    if ($granularity & DRUPAL_CACHE_PER_ROLE) {
      $cid_parts[] = 'r.' . implode(',', array_keys($user->roles));
    }
    elseif ($granularity & DRUPAL_CACHE_PER_USER) {
      $cid_parts[] = "u.$user->uid";
    }

    if ($granularity & DRUPAL_CACHE_PER_PAGE) {
      $cid_parts[] = $base_root . request_uri();
    }
  }

  return $cid_parts;
}

/**
 * Create the cache ID for a renderable element.
 *
 * This creates the cache ID string, either by returning the #cache['cid']
 * property if present or by building the cache ID out of the #cache['keys']
 * and, optionally, the #cache['granularity'] properties.
 *
 * @param $elements
 *   A renderable array.
 *
 * @return
 *   The cache ID string, or FALSE if the element may not be cached.
 */
function drupal_render_cid_create($elements) {
  if (isset($elements['#cache']['cid'])) {
    return $elements['#cache']['cid'];
  }
  elseif (isset($elements['#cache']['keys'])) {
    $granularity = isset($elements['#cache']['granularity']) ? $elements['#cache']['granularity'] : NULL;
    // Merge in additional cache ID parts based provided by drupal_render_cid_parts().
    $cid_parts = array_merge($elements['#cache']['keys'], drupal_render_cid_parts($granularity));
    return implode(':', $cid_parts);
  }
  return FALSE;
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
 * Retrieve the default properties for the defined element type.
 */
function element_info($type) {
  $cache = &drupal_static(__FUNCTION__);

  if (!isset($cache)) {
    $basic_defaults = element_basic_defaults();
    $cache = module_invoke_all('element_info');
    foreach ($cache as $element_type => $info) {
      $cache[$element_type] = array_merge_recursive($basic_defaults, $info);
      $cache[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    drupal_alter('element_info', $cache);
  }

  return isset($cache[$type]) ? $cache[$type] : array();
}

/**
 * Retrieve the basic default properties that are common to all elements.
 */
function element_basic_defaults() {
  return array(
    '#description' => '',
    '#title' => '',
    '#attributes' => array(),
    '#required' => FALSE,
    '#attached' => array(),
  );
}

/**
 * Function used by uasort to sort structured arrays by weight, without the property weight prefix.
 */
function drupal_sort_weight($a, $b) {
  $a_weight = (is_array($a) && isset($a['weight'])) ? $a['weight'] : 0;
  $b_weight = (is_array($b) && isset($b['weight'])) ? $b['weight'] : 0;
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
 * Return the children of an element, optionally sorted by weight.
 *
 * @param $elements
 *   The element to be sorted.
 * @param $sort
 *   Boolean to indicate whether the children should be sorted by weight.
 * @return
 *   The array keys of the element's children.
 */
function element_children(&$elements, $sort = FALSE) {
  // Do not attempt to sort elements which have already been sorted.
  $sort = isset($elements['#sorted']) ? !$elements['#sorted'] : $sort;

  // Filter out properties from the element, leaving only children.
  $children = array();
  $sortable = FALSE;
  foreach ($elements as $key => $value) {
    if ($key[0] !== '#') {
      $children[$key] = $value;
      if (is_array($value) && isset($value['#weight'])) {
        $sortable = TRUE;
      }
    }
  }
  // Sort the children if necessary.
  if ($sort && $sortable) {
    uasort($children, 'element_sort');
    // Put the sorted children back into $elements in the correct order, to
    // preserve sorting if the same element is passed through
    // element_children() twice.
    foreach ($children as $key => $child) {
      unset($elements[$key]);
      $elements[$key] = $child;
    }
    $elements['#sorted'] = TRUE;
  }

  return array_keys($children);
}

/**
 * Provide theme registration for themes across .inc files.
 */
function drupal_common_theme() {
  return array(
    // theme.inc
    'placeholder' => array(
      'variables' => array('text' => NULL)
    ),
    'html' => array(
      'render element' => 'page',
      'template' => 'html',
    ),
    'page' => array(
      'render element' => 'page',
      'template' => 'page',
    ),
    'region' => array(
      'render element' => 'elements',
      'template' => 'region',
    ),
    'status_messages' => array(
      'variables' => array('display' => NULL),
    ),
    'links' => array(
      'variables' => array('links' => NULL, 'attributes' => array('class' => array('links')), 'heading' => array()),
    ),
    'image' => array(
      'variables' => array('path' => NULL, 'alt' => '', 'title' => '', 'attributes' => array(), 'getsize' => TRUE),
    ),
    'breadcrumb' => array(
      'variables' => array('breadcrumb' => NULL),
    ),
    'help' => array(
      'variables' => array(),
    ),
    'submenu' => array(
      'variables' => array('links' => NULL),
    ),
    'table' => array(
      'variables' => array('header' => NULL, 'rows' => NULL, 'attributes' => array(), 'caption' => NULL, 'colgroups' => array(), 'sticky' => TRUE),
    ),
    'table_select_header_cell' => array(
      'variables' => array(),
    ),
    'tablesort_indicator' => array(
      'variables' => array('style' => NULL),
    ),
    'mark' => array(
      'variables' => array('type' => MARK_NEW),
    ),
    'item_list' => array(
      'variables' => array('items' => array(), 'title' => NULL, 'type' => 'ul', 'attributes' => array()),
    ),
    'more_help_link' => array(
      'variables' => array('url' => NULL),
    ),
    'feed_icon' => array(
      'variables' => array('url' => NULL, 'title' => NULL),
    ),
    'more_link' => array(
      'variables' => array('url' => NULL, 'title' => NULL)
    ),
    'blocks' => array(
      'variables' => array('region' => NULL),
    ),
    'username' => array(
      'variables' => array('account' => NULL),
    ),
    'progress_bar' => array(
      'variables' => array('percent' => NULL, 'message' => NULL),
    ),
    'indentation' => array(
      'variables' => array('size' => 1),
    ),
    // from theme.maintenance.inc
    'maintenance_page' => array(
      'variables' => array('content' => NULL, 'show_messages' => TRUE),
      'template' => 'maintenance-page',
    ),
    'update_page' => array(
      'variables' => array('content' => NULL, 'show_messages' => TRUE),
    ),
    'install_page' => array(
      'variables' => array('content' => NULL),
    ),
    'task_list' => array(
      'variables' => array('items' => NULL, 'active' => NULL),
    ),
    'authorize_message' => array(
      'variables' => array('message' => NULL, 'success' => TRUE),
    ),
    'authorize_report' => array(
      'variables' => array('messages' => array()),
    ),
    // from pager.inc
    'pager' => array(
      'variables' => array('tags' => array(), 'element' => 0, 'parameters' => array(), 'quantity' => 9),
    ),
    'pager_first' => array(
      'variables' => array('text' => NULL, 'element' => 0, 'parameters' => array()),
    ),
    'pager_previous' => array(
      'variables' => array('text' => NULL, 'element' => 0, 'interval' => 1, 'parameters' => array()),
    ),
    'pager_next' => array(
      'variables' => array('text' => NULL, 'element' => 0, 'interval' => 1, 'parameters' => array()),
    ),
    'pager_last' => array(
      'variables' => array('text' => NULL, 'element' => 0, 'parameters' => array()),
    ),
    'pager_link' => array(
      'variables' => array('text' => NULL, 'page_new' => NULL, 'element' => NULL, 'parameters' => array(), 'attributes' => array()),
    ),
    // from locale.inc
    'locale_admin_manage_screen' => array(
      'render element' => 'form',
    ),
    // from menu.inc
    'menu_link' => array(
      'render element' => 'element',
    ),
    'menu_tree' => array(
      'render element' => 'tree',
    ),
    'menu_local_task' => array(
      'render element' => 'element',
    ),
    'menu_local_action' => array(
      'render element' => 'element',
    ),
    'menu_local_tasks' => array(
      'variables' => array(),
    ),
    // from form.inc
    'select' => array(
      'render element' => 'element',
    ),
    'fieldset' => array(
      'render element' => 'element',
    ),
    'radio' => array(
      'render element' => 'element',
    ),
    'radios' => array(
      'render element' => 'element',
    ),
    'date' => array(
      'render element' => 'element',
    ),
    'checkbox' => array(
      'render element' => 'element',
    ),
    'checkboxes' => array(
      'render element' => 'element',
    ),
    'button' => array(
      'render element' => 'element',
    ),
    'image_button' => array(
      'render element' => 'element',
    ),
    'hidden' => array(
      'render element' => 'element',
    ),
    'textfield' => array(
      'render element' => 'element',
    ),
    'form' => array(
      'render element' => 'element',
    ),
    'textarea' => array(
      'render element' => 'element',
    ),
    'markup' => array(
      'render element' => 'element',
    ),
    'password' => array(
      'render element' => 'element',
    ),
    'file' => array(
      'render element' => 'element',
    ),
    'tableselect' => array(
      'render element' => 'element',
    ),
    'form_element' => array(
      'render element' => 'element',
    ),
    'text_format_wrapper' => array(
      'render element' => 'element',
    ),
    'vertical_tabs' => array(
      'render element' => 'element',
    ),
    'container' => array(
      'render element' => 'element',
    ),
  );
}

/**
 * @ingroup schemaapi
 * @{
 */

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
  _drupal_schema_initialize($module, $schema);

  foreach ($schema as $name => $table) {
    db_create_table($name, $table);
  }
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
  _drupal_schema_initialize($module, $schema);

  foreach ($schema as $table) {
    if (db_table_exists($table['name'])) {
      db_drop_table($table['name']);
    }
  }
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
  else if (!empty($schema)) {
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
function _drupal_schema_initialize($module, &$schema) {
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
 * @param $primary_keys
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
function drupal_write_record($table, &$object, $primary_keys = array()) {
  // Standardize $primary_keys to an array.
  if (is_string($primary_keys)) {
    $primary_keys = array($primary_keys);
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

  $fields = array();

  // Go through our schema, build SQL, and when inserting, fill in defaults for
  // fields that are not set.
  foreach ($schema['fields'] as $field => $info) {
    // Special case -- skip serial types if we are updating.
    if ($info['type'] == 'serial' && !empty($primary_keys)) {
      continue;
    }

    // For inserts, populate defaults from schema if not already provided.
    if (!isset($object->$field) && empty($primary_keys) && isset($info['default'])) {
      $object->$field = $info['default'];
    }

    // Track serial field so we can helpfully populate them after the query.
    // NOTE: Each table should come with one serial field only.
    if ($info['type'] == 'serial') {
      $serial = $field;
    }

    // Build arrays for the fields and values in our query.
    if (isset($object->$field)) {
      if (empty($info['serialize'])) {
        $fields[$field] = $object->$field;
      }
      elseif (!empty($object->$field)) {
        $fields[$field] = serialize($object->$field);
      }
      else {
        $fields[$field] = '';
      }
    }

    // We don't need to care about type casting if value does not exist.
    if (!isset($fields[$field])) {
      continue;
    }

    // Special case -- skip null value if field allows null.
    if ($fields[$field] == NULL && $info['not null'] == FALSE) {
      continue;
    }

    // Type cast if field does not allow null. Required by DB API.
    if ($info['type'] == 'int' || $info['type'] == 'serial') {
      $fields[$field] = (int) $fields[$field];
    }
    elseif ($info['type'] == 'float') {
      $fields[$field] = (float) $fields[$field];
    }
    else {
      $fields[$field] = (string) $fields[$field];
    }
  }

  if (empty($fields)) {
    // No changes requested.
    // If we began with an array, convert back so we don't surprise the caller.
    if ($array) {
      $object = (array) $object;
    }
    return;
  }

  // Build the SQL.
  if (empty($primary_keys)) {
    $options = array('return' => Database::RETURN_INSERT_ID);
    if (isset($serial) && isset($fields[$serial])) {
      // If the serial column has been explicitly set with an ID, then we don't
      // require the database to return the last insert id.
      if ($fields[$serial]) {
        $options['return'] = Database::RETURN_AFFECTED;
      }
      // If a serial column does exist with no value (i.e. 0) then remove it as
      // the database will insert the correct value for us.
      else {
        unset($fields[$serial]);
      }
    }
    $query = db_insert($table, $options)->fields($fields);
    $return = SAVED_NEW;
  }
  else {
    $query = db_update($table)->fields($fields);
    foreach ($primary_keys as $key) {
      $query->condition($key, $object->$key);
    }
    $return = SAVED_UPDATED;
  }

  // Execute the SQL.
  if ($last_insert_id = $query->execute()) {
    if (isset($serial)) {
      // If the database was not told to return the last insert id, it will be
      // because we already know it.
      if (isset($options) && $options['return'] != Database::RETURN_INSERT_ID) {
        $object->$serial = $fields[$serial];
      }
      else {
        $object->$serial = $last_insert_id;
      }
    }
  }
  // If we have a single-field primary key but got no insert ID, the
  // query failed.
  elseif (count($primary_keys) == 1) {
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
 * Parse Drupal module and theme info file format.
 *
 * Info files are NOT for placing arbitrary theme and module-specific settings.
 * Use variable_get() and variable_set() for that.
 *
 * Information stored in a module .info file:
 * - name: The real name of the module for display purposes.
 * - description: A brief description of the module.
 * - dependencies: An array of shortnames of other modules this module requires.
 * - package: The name of the package of modules this module belongs to.
 *
 * @see forum.info
 *
 * Information stored in a theme .info file:
 * - name: The real name of the theme for display purposes
 * - description: Brief description
 * - screenshot: Path to screenshot relative to the theme's .info file.
 * - engine: Theme engine, typically: engine = phptemplate
 * - base: Name of a base theme, if applicable, eg: base = zen
 * - regions: Listed regions eg: region[left] = Left sidebar
 * - features: Features available eg: features[] = logo
 * - stylesheets: Theme stylesheets eg: stylesheets[all][] = my-style.css
 * - scripts: Theme scripts eg: scripts[] = my-script.css
 *
 * @see garland.info
 *
 * @param $filename
 *   The file we are parsing. Accepts file with relative or absolute path.
 * @return
 *   The info array.
 *
 * @see drupal_parse_info_format()
 */
function drupal_parse_info_file($filename) {
  if (!file_exists($filename)) {
    return array();
  }

  $data = file_get_contents($filename);
  return drupal_parse_info_format($data);
}

/**
 * Parse data in Drupal's .info format.
 *
 * Data should be in an .ini-like format to specify values. White-space
 * generally doesn't matter, except inside values:
 * @code
 *   key = value
 *   key = "value"
 *   key = 'value'
 *   key = "multi-line
 *   value"
 *   key = 'multi-line
 *   value'
 *   key
 *   =
 *   'value'
 * @endcode
 *
 * Arrays are created using a HTTP GET alike syntax:
 * @code
 *   key[] = "numeric array"
 *   key[index] = "associative array"
 *   key[index][] = "nested numeric array"
 *   key[index][index] = "nested associative array"
 * @endcode
 *
 * PHP constants are substituted in, but only when used as the entire value.
 * Comments should start with a semi-colon at the beginning of a line.
 *
 * @param $data
 *   A string to parse.
 * @return
 *   The info array.
 *
 * @see drupal_parse_info_file()
 */
function drupal_parse_info_format($data) {
  $info = array();

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

      // Handle PHP constants
      if (defined($value)) {
        $value = constant($value);
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
 * Severity levels, as defined in RFC 3164: http://www.ietf.org/rfc/rfc3164.txt.
 *
 * @return
 *   Array of the possible severity levels for log messages.
 *
 * @see watchdog()
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
 */
function drupal_implode_tags($tags) {
  $encoded_tags = array();
  foreach ($tags as $tag) {
    // Commas and quotes in tag names are special cases, so encode them.
    if (strpos($tag, ',') !== FALSE || strpos($tag, '"') !== FALSE) {
      $tag = '"' . str_replace('"', '""', $tag) . '"';
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

  registry_rebuild();
  drupal_clear_css_cache();
  drupal_clear_js_cache();

  // If invoked from update.php, we must not update the theme information in the
  // database, or this will result in all themes being disabled.
  if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'update') {
    _system_rebuild_theme_data();
  }
  else {
    system_rebuild_theme_data();
  }

  drupal_theme_rebuild();
  menu_rebuild();
  node_types_rebuild();
  // Don't clear cache_form - in-progress form submissions may break.
  // Ordered so clearing the page cache will always be the last action.
  $core = array('cache', 'cache_filter', 'cache_registry', 'cache_page');
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
 * (newest) character is actually used on urls, to keep them short.
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

/**
 * Debug function used for outputting debug information.
 *
 * The debug information is passed on to trigger_error() after being converted
 * to a string using _drupal_debug_message().
 *
 * @param $data
 *   Data to be output.
 * @param $label
 *   Label to prefix the data.
 * @param $print_r
 *   Flag to switch between print_r() and var_export() for data conversion to
 *   string. Set $print_r to TRUE when dealing with a recursive data structure
 *   as var_export() will generate an error.
 */
function debug($data, $label = NULL, $print_r = FALSE) {
  // Print $data contents to string.
  $string = $print_r ? print_r($data, TRUE) : var_export($data, TRUE);
  trigger_error(trim($label ? "$label: $string" : $string));
}

/**
 * Parse a dependency for comparison by drupal_check_incompatibility().
 *
 * @param $dependency
 *   A dependency string, for example 'foo (>=7.x-4.5-beta5, 3.x)'.
 * @return
 *   An associative array with three keys:
 *   - 'name' includes the name of the thing to depend on (e.g. 'foo').
 *   - 'original_version' contains the original version string (which can be
 *     used in the UI for reporting incompatibilities).
 *   - 'versions' is a list of associative arrays, each containing the keys
 *     'op' and 'version'. 'op' can be one of: '=', '==', '!=', '<>', '<',
 *     '<=', '>', or '>='. 'version' is one piece like '4.5-beta3'.
 *   Callers should pass this structure to drupal_check_incompatibility().
 *
 * @see drupal_check_incompatibility()
 */
function drupal_parse_dependency($dependency) {
  // We use named subpatterns and support every op that version_compare
  // supports. Also, op is optional and defaults to equals.
  $p_op = '(?P<operation>!=|==|=|<|<=|>|>=|<>)?';
  // Core version is always optional: 7.x-2.x and 2.x is treated the same.
  $p_core = '(?:' . preg_quote(DRUPAL_CORE_COMPATIBILITY) . '-)?';
  $p_major = '(?P<major>\d+)';
  // By setting the minor version to x, branches can be matched.
  $p_minor = '(?P<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
  $value = array();
  $parts = explode('(', $dependency, 2);
  $value['name'] = trim($parts[0]);
  if (isset($parts[1])) {
    $value['original_version'] = ' (' . $parts[1];
    foreach (explode(',', $parts[1]) as $version) {
      if (preg_match("/^\s*$p_op\s*$p_core$p_major\.$p_minor/", $version, $matches)) {
        $op = !empty($matches['operation']) ? $matches['operation'] : '=';
        if ($matches['minor'] == 'x') {
          // Drupal considers "2.x" to mean any version that begins with
          // "2" (e.g. 2.0, 2.9 are all "2.x"). PHP's version_compare(),
          // on the other hand, treats "x" as a string; so to
          // version_compare(), "2.x" is considered less than 2.0. This
          // means that >=2.x and <2.x are handled by version_compare()
          // as we need, but > and <= are not.
          if ($op == '>' || $op == '<=') {
            $matches['major']++;
          }
          // Equivalence can be checked by adding two restrictions.
          if ($op == '=' || $op == '==') {
            $value['versions'][] = array('op' => '<', 'version' => ($matches['major'] + 1) . '.x');
            $op = '>=';
          }
        }
        $value['versions'][] = array('op' => $op, 'version' => $matches['major'] . '.' . $matches['minor']);
      }
    }
  }
  return $value;
}

/**
 * Check whether a version is compatible with a given dependency.
 *
 * @param $v
 *   The parsed dependency structure from drupal_parse_dependency().
 * @param $current_version
 *   The version to check against (like 4.2).
 * @return
 *   NULL if compatible, otherwise the original dependency version string that
 *   caused the incompatiblity.
 *
 * @see drupal_parse_dependency()
 */
function drupal_check_incompatibility($v, $current_version) {
  if (!empty($v['versions'])) {
    foreach ($v['versions'] as $required_version) {
      if ((isset($required_version['op']) && !version_compare($current_version, $required_version['version'], $required_version['op']))) {
        return $v['original_version'];
      }
    }
  }
}

/**
 * Get the entity info array of an entity type.
 *
 * @see hook_entity_info()
 * @see hook_entity_info_alter()
 *
 * @param $entity_type
 *   The entity type, e.g. node, for which the info shall be returned, or NULL
 *   to return an array with info about all types.
 */
function entity_get_info($entity_type = NULL) {
  // We statically cache the information returned by hook_entity_info().
  $entity_info = &drupal_static(__FUNCTION__, array());

  if (empty($entity_info)) {
    if ($cache = cache_get('entity_info')) {
      $entity_info = $cache->data;
    }
    else {
      $entity_info = module_invoke_all('entity_info');
      // Merge in default values.
      foreach ($entity_info as $name => $data) {
        $entity_info[$name] += array(
          'fieldable' => FALSE,
          'controller class' => 'DrupalDefaultEntityController',
          'static cache' => TRUE,
          'load hook' => $name . '_load',
          'bundles' => array(),
          'object keys' => array(),
          'cacheable' => TRUE,
          'translation' => array(),
        );
        $entity_info[$name]['object keys'] += array(
          'revision' => '',
          'bundle' => '',
        );
        // If no bundle key is provided, assume a single bundle, named after
        // the entity type.
        if (empty($entity_info[$name]['object keys']['bundle']) && empty($entity_info[$name]['bundles'])) {
          $entity_info[$name]['bundles'] = array($name => array('label' => $entity_info[$name]['label']));
        }
      }
      // Let other modules alter the entity info.
      drupal_alter('entity_info', $entity_info);
      cache_set('entity_info', $entity_info);
    }
  }

  return empty($entity_type) ? $entity_info : $entity_info[$entity_type];
}

/**
 * Helper function to extract id, vid, and bundle name from an entity.
 *
 * @param $entity_type
 *   The entity type; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity from which to extract values.
 * @return
 *   A numerically indexed array (not a hash table) containing these
 *   elements:
 *
 *   0: primary id of the entity
 *   1: revision id of the entity, or NULL if $entity_type is not versioned
 *   2: bundle name of the entity
 *   3: whether $entity_type's fields should be cached (TRUE/FALSE)
 */
function entity_extract_ids($entity_type, $entity) {
  $info = entity_get_info($entity_type);
  // Objects being created might not have id/vid yet.
  $id = isset($entity->{$info['object keys']['id']}) ? $entity->{$info['object keys']['id']} : NULL;
  $vid = ($info['object keys']['revision'] && isset($entity->{$info['object keys']['revision']})) ? $entity->{$info['object keys']['revision']} : NULL;
  // If no bundle key provided, then we assume a single bundle, named after the
  // entity type.
  $bundle = $info['object keys']['bundle'] ? $entity->{$info['object keys']['bundle']} : $entity_type;
  $cacheable = $info['cacheable'];
  return array($id, $vid, $bundle, $cacheable);
}

/**
 * Helper function to assemble an object structure with initial ids.
 *
 * This function can be seen as reciprocal to entity_extract_ids().
 *
 * @param $entity_type
 *   The entity type; e.g. 'node' or 'user'.
 * @param $ids
 *   A numerically indexed array, as returned by entity_extract_ids(),
 *   containing these elements:
 *   0: primary id of the entity
 *   1: revision id of the entity, or NULL if $entity_type is not versioned
 *   2: bundle name of the entity
 * @return
 *   An entity structure, initialized with the ids provided.
 */
function entity_create_stub_entity($entity_type, $ids) {
  $entity = new stdClass();
  $info = entity_get_info($entity_type);
  $entity->{$info['object keys']['id']} = $ids[0];
  if (isset($info['object keys']['revision']) && !is_null($ids[1])) {
    $entity->{$info['object keys']['revision']} = $ids[1];
  }
  if ($info['object keys']['bundle']) {
    $entity->{$info['object keys']['bundle']} = $ids[2];
  }
  return $entity;
}

/**
 * Load entities from the database.
 *
 * This function should be used whenever you need to load more than one entity
 * from the database. The entities are loaded into memory and will not require
 * database access if loaded again during the same page request.
 *
 * The actual loading is done through a class that has to implement the
 * DrupalEntityController interface. By default, DrupalDefaultEntityController
 * is used. Entity types can specify that a different class should be used by
 * setting the 'controller class' key in hook_entity_info(). These classes can
 * either implement the DrupalEntityController interface, or, most commonly,
 * extend the DrupalDefaultEntityController class. See node_entity_info() and
 * the NodeController in node.module as an example.
 *
 * @see hook_entity_info()
 * @see DrupalEntityController
 * @see DrupalDefaultEntityController
 *
 * @param $entity_type
 *   The entity type to load, e.g. node or user.
 * @param $ids
 *   An array of entity IDs, or FALSE to load all entities.
 * @param $conditions
 *   An array of conditions in the form 'field' => $value.
 * @param $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return
 *   An array of entity objects indexed by their ids.
 */
function entity_load($entity_type, $ids = array(), $conditions = array(), $reset = FALSE) {
  if ($reset) {
    entity_get_controller($entity_type)->resetCache();
  }
  return entity_get_controller($entity_type)->load($ids, $conditions);
}

/**
 * Get the entity controller class for an entity type.
 */
function entity_get_controller($entity_type) {
  $controllers = &drupal_static(__FUNCTION__, array());
  if (!isset($controllers[$entity_type])) {
    $type_info = entity_get_info($entity_type);
    $class = $type_info['controller class'];
    $controllers[$entity_type] = new $class($entity_type);
  }
  return $controllers[$entity_type];
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
  require_once DRUPAL_ROOT . '/includes/xmlrpc.inc';
  $args = func_get_args();
  return call_user_func_array('_xmlrpc', $args);
}

/**
 * Retrieve a list of all available archivers.
 */
function archiver_get_info() {
  $archiver_info = &drupal_static(__FUNCTION__, array());

  if (empty($archiver_info)) {
    $cache = cache_get('archiver_info');
    if ($cache === FALSE) {
      // Rebuild the cache and save it.
      $archiver_info = module_invoke_all('archiver_info');
      drupal_alter('archiver_info', $archiver_info);
      uasort($archiver_info, 'drupal_sort_weight');
      cache_set('archiver_info', $archiver_info);
    }
    else {
      $archiver_info = $cache->data;
    }
  }

  return $archiver_info;
}

/**
 * Create the appropriate archiver for the specified file.
 *
 * @param $file
 *   The full path of the archive file.  Note that stream wrapper
 *   paths are supported, but not remote ones.
 * @return
 *   A newly created instance of the archiver class appropriate
 *   for the specified file, already bound to that file.
 *   If no appropriate archiver class was found, will return FALSE.
 */
function archiver_get_archiver($file) {
  // Archivers can only work on local paths
  $filepath = drupal_realpath($file);
  if (!is_file($filepath)) {
    throw new Exception(t('Archivers can only operate on local files: %file not supported', array('%file' => $file)));
  }
  $archiver_info = archiver_get_info();

  foreach ($archiver_info as $implementation) {
    foreach ($implementation['extensions'] as $extension) {
      // Because extensions may be multi-part, such as .tar.gz,
      // we cannot use simpler approaches like substr() or pathinfo().
      // This method isn't quite as clean but gets the job done.
      // Also note that the file may not yet exist, so we cannot rely
      // on fileinfo() or other disk-level utilities.
      if (strrpos($filepath, '.' . $extension) === strlen($filepath) - strlen('.' . $extension)) {
        return new $implementation['class']($filepath);
      }
    }
  }
}

/**
 * Drupal Updater registry.
 *
 * An Updater is a class that knows how to update various parts of the Drupal
 * file system, for example to update modules that have newer releases, or to
 * install a new theme.
 *
 * @return
 *   Returns the Drupal Updater class registry.
 *
 * @see hook_updater_info()
 * @see hook_updater_info_alter()
 */
function drupal_get_updaters() {
  $updaters = &drupal_static(__FUNCTION__);
  if (!isset($updaters)) {
    $updaters = module_invoke_all('updater_info');
    drupal_alter('updater_info', $updaters);
    uasort($updaters, 'drupal_sort_weight');
  }
  return $updaters;
}
