<?php
// $Id: common.inc,v 1.1191 2010/07/07 17:00:42 webchick Exp $

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
    $profile = variable_get('install_profile', 'standard');
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
 * Returns a string containing RDF namespace declarations for use in XML and
 * XHTML output.
 */
function drupal_get_rdf_namespaces() {
  $xml_rdf_namespaces = array();

  // Serializes the RDF namespaces in XML namespace syntax.
  if (function_exists('rdf_get_namespaces')) {
    foreach (rdf_get_namespaces() as $prefix => $uri) {
      $xml_rdf_namespaces[] = 'xmlns:' . $prefix . '="' . $uri . '"';
    }
  }
  return count($xml_rdf_namespaces) ? "\n  " . implode("\n  ", $xml_rdf_namespaces) : '';
}

/**
 * Add output to the head tag of the HTML page.
 *
 * This function can be called as long the headers aren't sent. Pass no
 * arguments (or NULL for both) to retrieve the currently stored elements.
 *
 * @param $data
 *   A renderable array. If the '#type' key is not set then 'html_tag' will be
 *   added as the default '#type'.
 * @param $key
 *   A unique string key to allow implementations of hook_html_head_alter() to
 *   identify the element in $data. Required if $data is not NULL.
 *
 * @return
 *   An array of all stored HEAD elements.
 *
 * @see theme_html_tag()
 */
function drupal_add_html_head($data = NULL, $key = NULL) {
  $stored_head = &drupal_static(__FUNCTION__);

  if (!isset($stored_head)) {
    // Make sure the defaults, including Content-Type, come first.
    $stored_head = _drupal_default_html_head();
  }

  if (isset($data) && isset($key)) {
    if (!isset($data['#type'])) {
      $data['#type'] = 'html_tag';
    }
    $stored_head[$key] = $data;
  }
  return $stored_head;
}

/**
 * Returns elements that are always displayed in the HEAD tag of the HTML page.
 */
function _drupal_default_html_head() {
  // Add default elements. Make sure the Content-Type comes first because the
  // IE browser may be vulnerable to XSS via encoding attacks from any content
  // that comes before this META tag, such as a TITLE tag.
  $elements['system_meta_content_type'] = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'http-equiv' => 'Content-Type',
      'content' => 'text/html; charset=utf-8',
    ),
    // Security: This always has to be output first.
    '#weight' => -1000,
  );
  // Show Drupal and the major version number in the META GENERATOR tag.
  // Get the major version.
  list($version, ) = explode('.', VERSION);
  $elements['system_meta_generator'] = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'name' => 'Generator',
      'content' => 'Drupal ' . $version . ' (http://drupal.org)',
    ),
  );
  // Also send the generator in the HTTP header.
  $elements['system_meta_generator']['#attached']['drupal_add_http_header'][] = array('X-Generator', $elements['system_meta_generator']['#attributes']['content']);
  return $elements;
}

/**
 * Retrieve output to be displayed in the HEAD tag of the HTML page.
 */
function drupal_get_html_head() {
  $elements = drupal_add_html_head();
  drupal_alter('html_head', $elements);
  return drupal_render($elements);
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

  if (isset($url)) {
    $stored_feed_links[$url] = theme('feed_icon', array('url' => $url, 'title' => $title));

    drupal_add_html_head_link(array('rel' => 'alternate',
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
 * Encodes a Drupal path for use in a URL.
 *
 * For aesthetic reasons slashes are not escaped.
 *
 * Note that url() takes care of calling this function, so a path passed to that
 * function should not be encoded in advance.
 *
 * @param $path
 *   The Drupal path to encode.
 */
function drupal_encode_path($path) {
  return str_replace('%2F', '/', rawurlencode($path));
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
  // We do not allow absolute URLs to be passed via $_GET, as this can be an attack vector.
  if (isset($_GET['destination']) && !url_is_external($_GET['destination'])) {
    $destination = drupal_parse_url($_GET['destination']);
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
 *       An array containing the response headers as name/value pairs. HTTP
 *       header names are case-insensitive (RFC 2616, section 4.2), so for easy
 *       access the array keys are returned in lower case.
 *   - data
 *       A string containing the response body that was received.
 */
function drupal_http_request($url, array $options = array()) {
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
    case 'feed':
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
    // See system_requirements()
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
  $test_info = &$GLOBALS['drupal_test_info'];
  if (!empty($test_info['test_run_id'])) {
    $options['headers']['User-Agent'] = drupal_generate_test_ua($test_info['test_run_id']);
  }

  $request = $options['method'] . ' ' . $path . " HTTP/1.0\r\n";
  foreach ($options['headers'] as $name => $value) {
    $request .= $name . ': ' . trim($value) . "\r\n";
  }
  $request .= "\r\n" . $options['data'];
  $result->request = $request;

  fwrite($fp, $request);

  // Fetch response. Due to PHP bugs like http://bugs.php.net/bug.php?id=43782
  // and http://bugs.php.net/bug.php?id=46049 we can't rely on feof(), but
  // instead must invoke stream_get_meta_data() each iteration.
  $info = stream_get_meta_data($fp);
  $alive = !$info['eof'] && !$info['timed_out'];
  $response = '';

  while ($alive) {
    // Calculate how much time is left of the original timeout value.
    $timeout = $options['timeout'] - timer_read(__FUNCTION__) / 1000;
    if ($timeout <= 0) {
      $info['timed_out'] = TRUE;
      break;
    }
    stream_set_timeout($fp, floor($timeout), floor(1000000 * fmod($timeout, 1)));
    $chunk = fread($fp, 1024);
    $response .= $chunk;
    $info = stream_get_meta_data($fp);
    $alive = !$info['eof'] && !$info['timed_out'] && $chunk;
  }
  fclose($fp);

  if ($info['timed_out']) {
    $result->code = HTTP_REQUEST_TIMEOUT;
    $result->error = 'request timed out';
    return $result;
  }
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
    list($name, $value) = explode(':', $line, 2);
    $name = strtolower($name);
    if (isset($result->headers[$name]) && $name == 'set-cookie') {
      // RFC 2109: the Set-Cookie response header comprises the token Set-
      // Cookie:, followed by a comma-separated list of one or more cookies.
      $result->headers[$name] .= ',' . trim($value);
    }
    else {
      $result->headers[$name] = trim($value);
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
      $location = $result->headers['location'];
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
 * Checks whether user is allowed to proceed with the specified event.
 *
 * Events can have thresholds saying that each user can only do that event
 * a certain number of times in a time window. This function verifies that the
 * current user has not exceeded this threshold.
 *
 * @param $name
 *   The unique name of the event.
 * @param $threshold
 *   The maximum number of times each user can do this event per time window.
 * @param $window
 *   Number of seconds in the time window for this event (default is 3600
 *   seconds, or 1 hour).
 * @param $identifier
 *   Unique identifier of the current user. Defaults to their IP address.
 *
 * @return
 *   TRUE if the user is allowed to proceed. FALSE if they have exceeded the
 *   threshold and should not be allowed to proceed.
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

/**
 * @defgroup sanitization Sanitization functions
 * @{
 * Functions to sanitize values.
 *
 * See http://drupal.org/writing-secure-code for information
 * on writing secure code.
 */

/**
 * Strips dangerous protocols (e.g. 'javascript:') from a URI.
 *
 * This function must be called for all URIs within user-entered input prior
 * to being output to an HTML attribute value. It is often called as part of
 * check_url() or filter_xss(), but those functions return an HTML-encoded
 * string, so this function can be called independently when the output needs to
 * be a plain-text string for passing to t(), l(), drupal_attributes(), or
 * another function that will call check_plain() separately.
 *
 * @param $uri
 *   A plain-text URI that might contain dangerous protocols.
 *
 * @return
 *   A plain-text URI stripped of dangerous protocols. As with all plain-text
 *   strings, this return value must not be output to an HTML page without
 *   check_plain() being called on it. However, it can be passed to functions
 *   expecting plain-text strings.
 *
 * @see check_url()
 */
function drupal_strip_dangerous_protocols($uri) {
  static $allowed_protocols;

  if (!isset($allowed_protocols)) {
    $allowed_protocols = array_flip(variable_get('filter_allowed_protocols', array('ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp', 'rtsp', 'sftp', 'ssh', 'telnet', 'webcal')));
  }

  // Iteratively remove any invalid protocol found.
  do {
    $before = $uri;
    $colonpos = strpos($uri, ':');
    if ($colonpos > 0) {
      // We found a colon, possibly a protocol. Verify.
      $protocol = substr($uri, 0, $colonpos);
      // If a colon is preceded by a slash, question mark or hash, it cannot
      // possibly be part of the URL scheme. This must be a relative URL, which
      // inherits the (safe) protocol of the base document.
      if (preg_match('![/?#]!', $protocol)) {
        break;
      }
      // Check if this is a disallowed protocol. Per RFC2616, section 3.2.3
      // (URI Comparison) scheme comparison must be case-insensitive.
      if (!isset($allowed_protocols[strtolower($protocol)])) {
        $uri = substr($uri, $colonpos + 1);
      }
    }
  } while ($before != $uri);

  return $uri;
}

/**
 * Strips dangerous protocols (e.g. 'javascript:') from a URI and encodes it for output to an HTML attribute value.
 *
 * @param $uri
 *   A plain-text URI that might contain dangerous protocols.
 *
 * @return
 *   A URI stripped of dangerous protocols and encoded for output to an HTML
 *   attribute value. Because it is already encoded, it should not be set as a
 *   value within a $attributes array passed to drupal_attributes(), because
 *   drupal_attributes() expects those values to be plain-text strings. To pass
 *   a filtered URI to drupal_attributes(), call
 *   drupal_strip_dangerous_protocols() instead.
 *
 * @see drupal_strip_dangerous_protocols()
 */
function check_url($uri) {
  return check_plain(drupal_strip_dangerous_protocols($uri));
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
 * Filters an HTML string to prevent cross-site-scripting (XSS) vulnerabilities.
 *
 * Based on kses by Ulf Harnhammar, see http://sourceforge.net/projects/kses.
 * For examples of various XSS attacks, see: http://ha.ckers.org/xss.html.
 *
 * This code does four things:
 * - Removes characters and constructs that can trick browsers.
 * - Makes sure all HTML entities are well-formed.
 * - Makes sure all HTML tags and attributes are well-formed.
 * - Makes sure no HTML tags contain URLs with a disallowed protocol (e.g.
 *   javascript:).
 *
 * @param $string
 *   The string with raw HTML in it. It will be stripped of everything that can
 *   cause an XSS attack.
 * @param $allowed_tags
 *   An array of allowed tags.
 *
 * @return
 *   An XSS safe version of $string, or an empty string if $string is not
 *   valid UTF-8.
 *
 * @see drupal_validate_utf8()
 * @ingroup sanitization
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
  if ($mode == 1 && !$skip) {
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
 *   (Deprecated) Whether to decode entities in the $string. Set to FALSE if the
 *   $string is in plain text, TRUE otherwise. Defaults to TRUE. This parameter
 *   is deprecated and will be removed in Drupal 8. To process a plain-text URI,
 *   call drupal_strip_dangerous_protocols() or check_url() instead.
 * @return
 *   Cleaned up and HTML-escaped version of $string.
 */
function filter_xss_bad_protocol($string, $decode = TRUE) {
  // Get the plain text representation of the attribute value (i.e. its meaning).
  // @todo Remove the $decode parameter in Drupal 8, and always assume an HTML
  //   string that needs decoding.
  if ($decode) {
    $string = decode_entities($string);
  }
  return check_plain(drupal_strip_dangerous_protocols($string));
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
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['timezones'] = &drupal_static(__FUNCTION__);
  }
  $timezones = &$drupal_static_fast['timezones'];

  if (!isset($timezone)) {
    $timezone = date_default_timezone_get();
  }
  // Store DateTimeZone objects in an array rather than repeatedly
  // constructing identical objects over the life of a request.
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
 * Generates an internal or external URL.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 *
 * @param $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo". A few notes:
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
 *   An associative array of additional options, with the following elements:
 *   - 'query': An array of query key/value-pairs (without any URL-encoding) to
 *     append to the URL.
 *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
 *     Do not include the leading '#' character.
 *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
 *     absolute link (beginning with http:). Useful for links that will be
 *     displayed outside the site, such as in an RSS feed.
 *   - 'alias': Defaults to FALSE. Whether the given path is a URL alias
 *     already.
 *   - 'external': Whether the given path is an external URL.
 *   - 'language': An optional language object. If the path being linked to is
 *     internal to the site, $options['language'] is used to look up the alias
 *     for the URL. If $options['language'] is omitted, the global $language
 *     will be used.
 *   - 'https': Whether this URL should point to a secure location. If not
 *     defined, the current scheme is used, so the user stays on http or https
 *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP, but HTTPS can
 *     only be enforced when the variable 'https' is set to TRUE.
 *   - 'base_url': Only used internally, to modify the base URL when a language
 *     dependent URL requires so.
 *   - 'prefix': Only used internally, to modify the path when a language
 *     dependent URL requires so.
 *   - 'script': The script filename in Drupal's root directory to use when
 *     clean URLs are disabled, such as 'index.php'. Defaults to an empty
 *     string, as most modern web servers automatically find 'index.php'. If
 *     clean URLs are disabled, the value of $path is appended as query
 *     parameter 'q' to $options['script'] in the returned URL. When deploying
 *     Drupal on a web server that cannot be configured to automatically find
 *     index.php, then hook_url_outbound_alter() can be implemented to force
 *     this value to 'index.php'.
 *   - 'entity_type': The entity type of the object that called url(). Only set if
 *     url() is invoked by entity_uri().
 *   - 'entity': The entity object (such as a node) for which the URL is being
 *     generated. Only set if url() is invoked by entity_uri().
 *
 * @return
 *   A string containing a URL to the given path.
 */
function url($path = NULL, array $options = array()) {
  // Merge in defaults.
  $options += array(
    'fragment' => '',
    'query' => array(),
    'absolute' => FALSE,
    'alias' => FALSE,
    'prefix' => ''
  );

  if (!isset($options['external'])) {
    // Return an external link if $path contains an allowed absolute URL. Only
    // call the slow drupal_strip_dangerous_protocols() if $path contains a ':'
    // before any / ? or #. Note: we could use url_is_external($path) here, but
    // that would require another function call, and performance inside url() is
    // critical.
    $colonpos = strpos($path, ':');
    $options['external'] = ($colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && drupal_strip_dangerous_protocols($path) == $path);
  }

  // Preserve the original path before altering or aliasing.
  $original_path = $path;

  // Allow other modules to alter the outbound URL and options.
  drupal_alter('url_outbound', $path, $options, $original_path);

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
    if (isset($options['https']) && variable_get('https', FALSE)) {
      if ($options['https'] === TRUE) {
        $path = str_replace('http://', 'https://', $path);
      }
      elseif ($options['https'] === FALSE) {
        $path = str_replace('https://', 'http://', $path);
      }
    }
    // Reassemble.
    return $path . $options['fragment'];
  }

  global $base_url, $base_secure_url, $base_insecure_url;

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
    $query = $query ? ('?' . drupal_http_build_query($query)) : '';
    $script = isset($options['script']) ? $options['script'] : '';
    return $base . $script . $query . $options['fragment'];
  }
}

/**
 * Return TRUE if a path is external to Drupal (e.g. http://example.com).
 *
 * If a path cannot be assessed by Drupal's menu handler, then we must
 * treat it as potentially insecure.
 *
 * @param $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo".
 * @return 
 *   Boolean TRUE or FALSE, where TRUE indicates an external path.
 */
function url_is_external($path) {
  $colonpos = strpos($path, ':');
  // Only call the slow drupal_strip_dangerous_protocols() if $path contains a
  // ':' before any / ? or #.
  return $colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && drupal_strip_dangerous_protocols($path) == $path;
}

/**
 * Format an attribute string for a HTTP header.
 *
 * @param $attributes
 *   An associative array of attributes such as 'rel'.
 *
 * @return
 *   A ; separated string ready for insertion in a HTTP header. No escaping is
 *   performed for HTML entities, so this string is not safe to be printed.
 *
 * @see drupal_add_http_header()
 */
function drupal_http_header_attributes(array $attributes = array()) {
  foreach ($attributes as $attribute => &$data) {
    if (is_array($data)) {
      $data = implode(' ', $data);
    }
    $data = $attribute . '="' . $data . '"';
  }
  return $attributes ? ' ' . implode('; ', $attributes) : '';
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
 * Formats an internal or external URL link as an HTML anchor tag.
 *
 * This function correctly handles aliased paths, and adds an 'active' class
 * attribute to links that point to the current page (for theming), so all
 * internal links output by modules should be generated by this function if
 * possible.
 *
 * @param $text
 *   The link text for the anchor tag.
 * @param $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo". After the url() function is called to construct
 *   the URL from $path and $options, the resulting URL is passed through
 *   check_plain() before it is inserted into the HTML anchor tag, to ensure
 *   well-formed HTML. See url() for more information and notes.
 * @param array $options
 *   An associative array of additional options, with the following elements:
 *   - 'attributes': An associative array of HTML attributes to apply to the
 *     anchor tag.
 *   - 'html' (default FALSE): Whether $text is HTML or just plain-text. For
 *     example, to make an image tag into a link, this must be set to TRUE, or
 *     you will see the escaped HTML image tag.
 *   - 'language': An optional language object. If the path being linked to is
 *     internal to the site, $options['language'] is used to determine whether
 *     the link is "active", or pointing to the current page (the language as
 *     well as the path must match). This element is also used by url().
 *   - Additional $options elements used by the url() function.
 *
 * @return
 *   An HTML string containing a link to the given path.
 */
function l($text, $path, array $options = array()) {
  global $language_url;
  static $use_theme = NULL;

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

  // Determine if rendering of the link is to be done with a theme function
  // or the inline default. Inline is faster, but if the theme system has been
  // loaded and a module or theme implements a preprocess or process function
  // or overrides the theme_link() function, then invoke theme(). Preliminary
  // benchmarks indicate that invoking theme() can slow down the l() function
  // by 20% or more, and that some of the link-heavy Drupal pages spend more
  // than 10% of the total page request time in the l() function.
  if (!isset($use_theme) && function_exists('theme')) {
    // Allow edge cases to prevent theme initialization and force inline link
    // rendering.
    if (variable_get('theme_link', TRUE)) {
      drupal_theme_initialize();
      $registry = theme_get_registry();
      // We don't want to duplicate functionality that's in theme(), so any
      // hint of a module or theme doing anything at all special with the 'link'
      // theme hook should simply result in theme() being called. This includes
      // the overriding of theme_link() with an alternate function or template,
      // the presence of preprocess or process functions, or the presence of
      // include files.
      $use_theme = !isset($registry['link']['function']) || ($registry['link']['function'] != 'theme_link');
      $use_theme = $use_theme || !empty($registry['link']['preprocess functions']) || !empty($registry['link']['process functions']) || !empty($registry['link']['includes']);
    }
    else {
      $use_theme = FALSE;
    }
  }
  if ($use_theme) {
    return theme('link', array('text' => $text, 'path' => $path, 'options' => $options));
  }
  // The result of url() is a plain-text URL. Because we are using it here
  // in an HTML argument context, we need to encode it properly.
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
 * overridden on a per menu router item basis by setting 'delivery callback' in
 * hook_menu() or hook_menu_alter(), and can also be overridden on a per request
 * basis in hook_page_delivery_callback_alter().
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
 *   determined from the menu router information of the current page.
 *
 * @see menu_execute_active_handler()
 * @see hook_menu()
 * @see hook_menu_alter()
 * @see hook_page_delivery_callback_alter()
 */
function drupal_deliver_page($page_callback_result, $default_delivery_callback = NULL) {
  if (!isset($default_delivery_callback) && ($router_item = menu_get_item())) {
    $default_delivery_callback = $router_item['delivery_callback'];
  }
  $delivery_callback = !empty($default_delivery_callback) ? $default_delivery_callback : 'drupal_deliver_html_page';
  // Give modules a chance to alter the delivery callback used, based on
  // request-time context (e.g., HTTP request headers).
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
 * Package and send the result of a page callback to the browser as HTML.
 *
 * @param $page_callback_result
 *   The result of a page callback. Can be one of:
 *   - NULL: to indicate no content.
 *   - An integer menu status constant: to indicate an error condition.
 *   - A string of HTML content.
 *   - A renderable array of content.
 *
 * @see drupal_deliver_page()
 */
function drupal_deliver_html_page($page_callback_result) {
  // Emit the correct charset HTTP header, but not if the page callback
  // result is NULL, since that likely indicates that it printed something
  // in which case, no further headers may be sent, and not if code running
  // for this page request has already set the content type header.
  if (isset($page_callback_result) && is_null(drupal_get_http_header('Content-Type'))) {
    drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
  }

  // Menu status constants are integers; page content is a string or array.
  if (is_int($page_callback_result)) {
    // @todo: Break these up into separate functions?
    switch ($page_callback_result) {
      case MENU_NOT_FOUND:
        // Print a 404 page.
        drupal_add_http_header('Status', '404 Not Found');

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
        drupal_add_http_header('Status', '403 Forbidden');
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
        drupal_add_http_header('Status', '503 Service unavailable');
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

  if (variable_get('cache', 0) && ($cache = drupal_page_set_cache())) {
    drupal_serve_page_from_cache($cache);
  }
  else {
    ob_flush();
  }

  _registry_check_code(REGISTRY_WRITE_LOOKUP_CACHE);
  drupal_cache_system_paths();
  module_implements_write_cache();
  system_run_automated_cron();
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
  // array_combine() fails with empty arrays:
  // http://bugs.php.net/bug.php?id=34857.
  $array = !empty($array) ? array_combine($array, $array) : array();
  if (is_callable($function)) {
    $array = array_map($function, $array);
  }
  return $array;
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
 * Add a LINK tag with a distinct 'rel' attribute to the page's HEAD.
 *
 * This function can be called as long the HTML header hasn't been sent,
 * which on normal pages is up through the preprocess step of theme('html').
 * Adding a link will overwrite a prior link with the exact same 'rel' and
 * 'href' attributes.
 *
 * @param $attributes
 *   Associative array of element attributes including 'href' and 'rel'.
 * @param $header
 *   Optional flag to determine if a HTTP 'Link:' header should be sent.
 */
function drupal_add_html_head_link($attributes, $header = FALSE) {
  $element = array(
    '#tag' => 'link',
    '#attributes' => $attributes,
  );
  $href = $attributes['href'];

  if ($header) {
    // Also add a HTTP header "Link:".
    $href = '<' . check_plain($attributes['href']) . '>;';
    unset($attributes['href']);
    $element['#attached']['drupal_add_http_header'][] = array('Link',  $href . drupal_http_header_attributes($attributes), TRUE);
  }

  drupal_add_html_head($element, 'drupal_add_html_head_link:' . $attributes['rel'] . ':' . $href);
}

/**
 * Adds a cascading stylesheet to the stylesheet queue.
 *
 * Calling drupal_static_reset('drupal_add_css') will clear all cascading
 * stylesheets added so far.
 *
 * If preprocessing is turned on, the cascading style sheets added using this
 * function will be preprocessed before they are added to the HTML header of the
 * page. Preprocessing merges all the CSS files into one file, which is then
 * compressed by removing all extraneous white space. Note that preprocessed
 * inline stylesheets will not be aggregated into this single file; instead,
 * they will just be compressed when being output on the page. External
 * stylesheets will also not be aggregated.
 *
 * The reason for merging the CSS files is outlined quite thoroughly here:
 * http://www.die.net/musings/page_load_time/ "Load fewer external objects. Due
 * to request overhead, one bigger file just loads faster than two smaller ones
 * half its size."
 *
 * However, you should *not* preprocess every file as this can lead to redundant
 * caches. You should set $options['preprocess'] to FALSE when your styles are
 * only used on a few pages of the site. This could be a special admin page, the
 * homepage, or a handful of pages that does not represent the majority of the
 * pages on your site.
 *
 * Typical candidates for preprocessing are for example styles for nodes across
 * the site, or styles used in the theme.
 *
 * @param $data
 *   (optional) The stylesheet data to be added, depending on what is passed
 *   through to the $options['type'] parameter:
 *   - 'file': The path to the CSS file relative to the base_path(), e.g.,
 *     "modules/devel/devel.css". Note that Modules should always prefix the
 *     names of their CSS files with the module name; for example,
 *     system-menus.css rather than simply menus.css. Themes can override
 *     module-supplied CSS files based on their filenames, and this prefixing
 *     helps prevent confusing name collisions for theme developers. See
 *     drupal_get_css() where the overrides are performed. Also, if the
 *     direction of the current language is right-to-left (Hebrew, Arabic,
 *     etc.), the function will also look for an RTL CSS file and append it to
 *     the list. The name of this file should have an '-rtl.css' suffix.  For
 *     example a CSS file called 'mymodule-name.css' will have a
 *     'mymodule-name-rtl.css' file added to the list, if exists in the same
 *     directory. This CSS file should contain overrides for properties which
 *     should be reversed or otherwise different in a right-to-left display.
 *   - 'inline': A string of CSS that should be placed in the given scope. Note
 *     that it is better practice to use 'file' stylesheets, rather than
 *     'inline', as the CSS would then be aggregated and cached.
 *   - 'external': The absolute path to an external CSS file that is not hosted
 *     on the local server. These files will not be aggregated if CSS
 *     aggregation is enabled.
 * @param $options
 *   (optional) A string defining the 'type' of CSS that is being added in the
 *   $data parameter ('file', 'inline', or 'external'), or an array which can
 *   have any or all of the following keys:
 *   - 'type': The type of stylesheet being added. Available options are 'file',
 *     'inline' or 'external'. Defaults to 'file'.
 *   - 'basename': Force a basename for the file being added. Modules are
 *     expected to use stylesheets with unique filenames, but integration of
 *     external libraries may make this impossible. The basename of
 *     'modules/node/node.css' is 'node.css'. If the external library "node.js"
 *     ships with a 'node.css', then a different, unique basename would be
 *     'node.js.css'.
 *   - 'weight': The weight of the stylesheet specifies the order in which the
 *     CSS will appear when presented on the page. Available constants are:
 *     - CSS_SYSTEM: Any system-layer CSS.
 *     - CSS_DEFAULT: Any module-layer CSS.
 *     - CSS_THEME: Any theme-layer CSS.
 *     If you need to embed a CSS file before any other module's stylesheets,
 *     for example, you would use CSS_DEFAULT - 1. Note that inline CSS is
 *     simply appended to the end of the specified scope (region), so they
 *     always come last.
 *   - 'media': The media type for the stylesheet, e.g., all, print, screen.
 *     Defaults to 'all'.
 *   - 'preprocess': If TRUE, Allows the CSS to be aggregated and compressed if
 *     the Optimize CSS feature has been turned on under the performance
 *     section.  Defaults to TRUE.
 *   - 'browsers': An array containing information specifying which browsers
 *     should load the CSS item. See drupal_pre_render_conditional_comments()
 *     for details.
 *
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
      'browsers' => array(),
    );
    $options['browsers'] += array(
      'IE' => TRUE,
      '!IE' => TRUE,
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
  if (!isset($css)) {
    $css = drupal_add_css();
  }

  // Allow modules and themes to alter the CSS items.
  drupal_alter('css', $css);

  // Sort CSS items according to their weights.
  uasort($css, 'drupal_sort_weight');

  // Remove the overridden CSS files. Later CSS files override former ones.
  $previous_item = array();
  foreach ($css as $key => $item) {
    if ($item['type'] == 'file') {
      // If defined, force a unique basename for this file.
      $basename = isset($item['basename']) ? $item['basename'] : basename($item['data']);
      if (isset($previous_item[$basename])) {
        // Remove the previous item that shared the same base name.
        unset($css[$previous_item[$basename]]);
      }
      $previous_item[$basename] = $key;
    }
  }

  // Render the HTML needed to load the CSS.
  $styles = array(
    '#type' => 'styles',
    '#items' => $css,
  );
  return drupal_render($styles);
}

/**
 * Default callback to group CSS items.
 *
 * This function arranges the CSS items that are in the #items property of the
 * styles element into groups. Arranging the CSS items into groups serves two
 * purposes. When aggregation is enabled, files within a group are aggregated
 * into a single file, significantly improving page loading performance by
 * minimizing network traffic overhead. When aggregation is disabled, grouping
 * allows multiple files to be loaded from a single STYLE tag, enabling sites
 * with many modules enabled or a complex theme being used to stay within IE's
 * 31 CSS inclusion tag limit: http://drupal.org/node/228818.
 *
 * This function puts multiple items into the same group if they are groupable
 * and if they are for the same 'media' and 'browsers'. Items of the 'file' type
 * are groupable if their 'preprocess' flag is TRUE, items of the 'inline' type
 * are always groupable, and items of the 'external' type are never groupable.
 * This function also ensures that the process of grouping items does not change
 * their relative order. This requirement may result in multiple groups for the
 * same type, media, and browsers, if needed to accomodate other items in
 * between.
 *
 * @param $css
 *   An array of CSS items, as returned by drupal_add_css(), but after
 *   alteration performed by drupal_get_css().
 *
 * @return
 *   An array of CSS groups. Each group contains the same keys (e.g., 'media',
 *   'data', etc.) as a CSS item from the $css parameter, with the value of
 *   each key applying to the group as a whole. Each group also contains an
 *   'items' key, which is the subset of items from $css that are in the group.
 *
 * @see drupal_pre_render_styles()
 */
function drupal_group_css($css) {
  $groups = array();
  // If a group can contain multiple items, we track the information that must
  // be the same for each item in the group, so that when we iterate the next
  // item, we can determine if it can be put into the current group, or if a
  // new group needs to be made for it.
  $current_group_keys = NULL;
  // When creating a new group, we pre-increment $i, so by initializing it to
  // -1, the first group will have index 0.
  $i = -1;
  foreach ($css as $item) {
    // The browsers for which the CSS item needs to be loaded is part of the
    // information that determines when a new group is needed, but the order of
    // keys in the array doesn't matter, and we don't want a new group if all
    // that's different is that order.
    ksort($item['browsers']);

    // If the item can be grouped with other items, set $group_keys to an array
    // of information that must be the same for all items in its group. If the
    // item can't be grouped with other items, set $group_keys to FALSE. We
    // put items into a group that can be aggregated together: whether they will
    // be aggregated is up to the _drupal_css_aggregate() function or an
    // override of that function specified in hook_css_alter(), but regardless
    // of the details of that function, a group represents items that can be
    // aggregated. Since a group may be rendered with a single HTML tag, all
    // items in the group must share the same information that would need to be
    // part of that HTML tag.
    switch ($item['type']) {
      case 'file':
        // Group file items if their 'preprocess' flag is TRUE.
        $group_keys = $item['preprocess'] ? array($item['type'], $item['media'], $item['browsers']) : FALSE;
        break;
      case 'inline':
        // Always group inline items.
        $group_keys = array($item['type'], $item['media'], $item['browsers']);
        break;
      case 'external':
        // Do not group external items.
        $group_keys = FALSE;
        break;
    }

    // If the group keys don't match the most recent group we're working with,
    // then a new group must be made.
    if ($group_keys !== $current_group_keys) {
      $i++;
      // Initialize the new group with the same properties as the first item
      // being placed into it. The item's 'data' and 'weight' properties are
      // unique to the item and should not be carried over to the group.
      $groups[$i] = $item;
      unset($groups[$i]['data'], $groups[$i]['weight']);
      $groups[$i]['items'] = array();
      $current_group_keys = $group_keys ? $group_keys : NULL;
    }

    // Add the item to the current group.
    $groups[$i]['items'][] = $item;
  }
  return $groups;
}

/**
 * Default callback to aggregate CSS files and inline content.
 *
 * Having the browser load fewer CSS files results in much faster page loads
 * than when it loads many small files. This function aggregates files within
 * the same group into a single file unless the site-wide setting to do so is
 * disabled (commonly the case during site development). To optimize download,
 * it also compresses the aggregate files by removing comments, whitespace, and
 * other unnecessary content. Additionally, this functions aggregates inline
 * content together, regardless of the site-wide aggregation setting.
 *
 * @param $css_groups
 *   An array of CSS groups as returned by drupal_group_css(). This function
 *   modifies the group's 'data' property for each group that is aggregated.
 *
 * @see drupal_group_css()
 * @see drupal_pre_render_styles()
 */
function drupal_aggregate_css(&$css_groups) {
  $preprocess_css = (variable_get('preprocess_css', FALSE) && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update'));

  // For each group that needs aggregation, aggregate its items.
  foreach ($css_groups as $key => $group) {
    switch ($group['type']) {
      // If a file group can be aggregated into a single file, do so, and set
      // the group's data property to the file path of the aggregate file.
      case 'file':
        if ($group['preprocess'] && $preprocess_css) {
          $css_groups[$key]['data'] = drupal_build_css_cache($group['items']);
        }
        break;
      // Aggregate all inline CSS content into the group's data property.
      case 'inline':
        $css_groups[$key]['data'] = '';
        foreach ($group['items'] as $item) {
          $css_groups[$key]['data'] .= drupal_load_stylesheet_content($item['data'], $item['preprocess']);
        }
        break;
    }
  }
}

/**
 * #pre_render callback to add the elements needed for CSS tags to be rendered.
 *
 * For production websites, LINK tags are preferable to STYLE tags with @import
 * statements, because:
 * - They are the standard tag intended for linking to a resource.
 * - On Firefox 2 and perhaps other browsers, CSS files included with @import
 *   statements don't get saved when saving the complete web page for offline
 *   use: http://drupal.org/node/145218.
 * - On IE, if only LINK tags and no @import statements are used, all the CSS
 *   files are downloaded in parallel, resulting in faster page load, but if
 *   @import statements are used and span across multiple STYLE tags, all the
 *   ones from one STYLE tag must be downloaded before downloading begins for
 *   the next STYLE tag. Furthermore, IE7 does not support media declaration on
 *   the @import statement, so multiple STYLE tags must be used when different
 *   files are for different media types. Non-IE browsers always download in
 *   parallel, so this is an IE-specific performance quirk:
 *   http://www.stevesouders.com/blog/2009/04/09/dont-use-import/.
 *
 * However, IE has an annoying limit of 31 total CSS inclusion tags
 * (http://drupal.org/node/228818) and LINK tags are limited to one file per
 * tag, whereas STYLE tags can contain multiple @import statements allowing
 * multiple files to be loaded per tag. When CSS aggregation is disabled, a
 * Drupal site can easily have more than 31 CSS files that need to be loaded, so
 * using LINK tags exclusively would result in a site that would display
 * incorrectly in IE. Depending on different needs, different strategies can be
 * employed to decide when to use LINK tags and when to use STYLE tags.
 *
 * The strategy employed by this function is to use LINK tags for all aggregate
 * files and for all files that cannot be aggregated (e.g., if 'preprocess' is
 * set to FALSE or the type is 'external'), and to use STYLE tags for groups
 * of files that could be aggregated together but aren't (e.g., if the site-wide
 * aggregation setting is disabled). This results in all LINK tags when
 * aggregation is enabled, a guarantee that as many or only slightly more tags
 * are used with aggregation disabled than enabled (so that if the limit were to
 * be crossed with aggregation enabled, the site developer would also notice the
 * problem while aggregation is disabled), and an easy way for a developer to
 * view HTML source while aggregation is disabled and know what files will be
 * aggregated together when aggregation becomes enabled.
 *
 * This function evaluates the aggregation enabled/disabled condition on a group
 * by group basis by testing whether an aggregate file has been made for the
 * group rather than by testing the site-wide aggregation setting. This allows
 * this function to work correctly even if modules have implemented custom
 * logic for grouping and aggregating files.
 *
 * @param $element
 *   A render array containing:
 *   - '#items': The CSS items as returned by drupal_add_css() and altered by
 *     drupal_get_css().
 *   - '#group_callback': A function to call to group #items to enable the use
 *     of fewer tags by aggregating files and/or using multiple @import
 *     statements within a single tag.
 *   - '#aggregate_callback': A function to call to aggregate the items within
 *     the groups arranged by the #group_callback function.
 *
 * @return
 *   A render array that will render to a string of XHTML CSS tags.
 *
 * @see drupal_get_css()
 */
function drupal_pre_render_styles($elements) {
  // Group and aggregate the items.
  if (isset($elements['#group_callback'])) {
    $elements['#groups'] = $elements['#group_callback']($elements['#items']);
  }
  if (isset($elements['#aggregate_callback'])) {
    $elements['#aggregate_callback']($elements['#groups']);
  }

  // A dummy query-string is added to filenames, to gain control over
  // browser-caching. The string changes on every update or full cache
  // flush, forcing browsers to load a new copy of the files, as the
  // URL changed.
  $query_string = variable_get('css_js_query_string', '0');

  // Defaults for LINK and STYLE elements.
  $link_element_defaults = array(
    '#type' => 'html_tag',
    '#tag' => 'link',
    '#attributes' => array(
      'type' => 'text/css',
      'rel' => 'stylesheet',
    ),
  );
  $style_element_defaults = array(
    '#type' => 'html_tag',
    '#tag' => 'style',
    '#attributes' => array(
      'type' => 'text/css',
    ),
  );

  // Loop through each group.
  foreach ($elements['#groups'] as $group) {
    switch ($group['type']) {
      // For file items, there are three possibilites.
      // - The group has been aggregated: in this case, output a LINK tag for
      //   the aggregate file.
      // - The group can be aggregated but has not been (most likely because
      //   the site administrator disabled the site-wide setting): in this case,
      //   output as few STYLE tags for the group as possible, using @import
      //   statement for each file in the group. This enables us to stay within
      //   IE's limit of 31 total CSS inclusion tags.
      // - The group contains items not eligible for aggregation (their
      //   'preprocess' flag has been set to FALSE): in this case, output a LINK
      //   tag for each file.
      case 'file':
        // The group has been aggregated into a single file: output a LINK tag
        // for the aggregate file.
        if (isset($group['data'])) {
          $element = $link_element_defaults;
          $element['#attributes']['href'] = file_create_url($group['data']);
          $element['#attributes']['media'] = $group['media'];
          $element['#browsers'] = $group['browsers'];
          $elements[] = $element;
        }
        // The group can be aggregated, but hasn't been: combine multiple items
        // into as few STYLE tags as possible.
        elseif ($group['preprocess']) {
          $import = array();
          foreach ($group['items'] as $item) {
            // The dummy query string needs to be added to the URL to control
            // browser-caching. IE7 does not support a media type on the @import
            // statement, so we instead specify the media for the group on the
            // STYLE tag.
            $query_string_separator = (strpos($item['data'], '?') !== FALSE) ? '&' : '?';
            $import[] = '@import url("' . check_plain(file_create_url($item['data']) . $query_string_separator . $query_string) . '");';
          }
          // In addition to IE's limit of 31 total CSS inclusion tags, it also
          // has a limit of 31 @import statements per STYLE tag.
          while (!empty($import)) {
            $import_batch = array_slice($import, 0, 31);
            $import = array_slice($import, 31);
            $element = $style_element_defaults;
            $element['#value'] = implode("\n", $import_batch);
            $element['#attributes']['media'] = $group['media'];
            $element['#browsers'] = $group['browsers'];
            $elements[] = $element;
          }
        }
        // The group contains items ineligible for aggregation: output a LINK
        // tag for each file.
        else {
          foreach ($group['items'] as $item) {
            $element = $link_element_defaults;
            // The dummy query string needs to be added to the URL to control
            // browser-caching.
            $query_string_separator = (strpos($item['data'], '?') !== FALSE) ? '&' : '?';
            $element['#attributes']['href'] = file_create_url($item['data']) . $query_string_separator . $query_string;
            $element['#attributes']['media'] = $item['media'];
            $element['#browsers'] = $group['browsers'];
            $elements[] = $element;
          }
        }
        break;
      // For inline content, the 'data' property contains the CSS content. If
      // the group's 'data' property is set, then output it in a single STYLE
      // tag. Otherwise, output a separate STYLE tag for each item.
      case 'inline':
        if (isset($group['data'])) {
          $element = $style_element_defaults;
          $element['#value'] = $group['data'];
          $element['#attributes']['media'] = $group['media'];
          $element['#browsers'] = $group['browsers'];
          $elements[] = $element;
        }
        else {
          foreach ($group['items'] as $item) {
            $element = $style_element_defaults;
            $element['#value'] = $item['data'];
            $element['#attributes']['media'] = $item['media'];
            $element['#browsers'] = $group['browsers'];
            $elements[] = $element;
          }
        }
        break;
      // Output a LINK tag for each external item. The item's 'data' property
      // contains the full URL.
      case 'external':
        foreach ($group['items'] as $item) {
          $element = $link_element_defaults;
          $element['#attributes']['href'] = $item['data'];
          $element['#attributes']['media'] = $item['media'];
          $element['#browsers'] = $group['browsers'];
          $elements[] = $element;
        }
        break;
    }
  }

  return $elements;
}

/**
 * Aggregates and optimizes CSS files into a cache file in the files directory.
 *
 * The file name for the CSS cache file is generated from the hash of the
 * aggregated contents of the files in $css. This forces proxies and browsers
 * to download new CSS when the CSS changes.
 *
 * The cache file name is retrieved on a page load via a lookup variable that
 * contains an associative array. The array key is the hash of the file names
 * in $css while the value is the cache file name. The cache file is generated
 * in two cases. First, if there is no file name value for the key, which will
 * happen if a new file name has been added to $css or after the lookup
 * variable is emptied to force a rebuild of the cache.  Second, the cache
 * file is generated if it is missing on disk. Old cache files are not deleted
 * immediately when the lookup variable is emptied, but are deleted after a set
 * period by drupal_delete_file_if_stale(). This ensures that files referenced
 * by a cached page will still be available.
 *
 * @param $css
 *   An array of CSS files to aggregate and compress into one file.
 *
 * @return
 *   The URI of the CSS cache file, or FALSE if the file could not be saved.
 */
function drupal_build_css_cache($css) {
  $data = '';
  $uri = '';
  $map = variable_get('drupal_css_cache_files', array());
  $key = hash('sha256', serialize($css));
  if (isset($map[$key])) {
    $uri = $map[$key];
  }

  if (empty($uri) || !file_exists($uri)) {
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

    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = 'css_' . drupal_hash_base64($data) . '.css';
    // Create the css/ within the files folder.
    $csspath = 'public://css';
    $uri = $csspath . '/' . $filename;
    // Create the CSS file.
    file_prepare_directory($csspath, FILE_CREATE_DIRECTORY);
    if (!file_exists($uri) && !file_unmanaged_save_data($data, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }
    // Save the updated map.
    $map[$key] = $uri;
    variable_set('drupal_css_cache_files', $map);
  }
  return $uri;
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
    // Regexp to match comment blocks.
    $comment     = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
    // Regexp to match double quoted strings.
    $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
    // Regexp to match single quoted strings.
    $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
    $contents = preg_replace(
      "<($double_quot|$single_quot)|$comment>Sus", // Strip all comment blocks
      "$1",                                        // but keep double/single
      $contents);                                  // quoted strings.
    $contents = preg_replace(
      '<\s*([@{}:;,]|\)\s|\s\()\s*>S',             // Remove whitespace around separators,
      '\1', $contents);                            // but keep space around parentheses.
    // End the file with a new line.
    $contents .= "\n";
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
 * Deletes old cached CSS files.
 */
function drupal_clear_css_cache() {
  variable_del('drupal_css_cache_files');
  file_scan_directory('public://css', '/.*/', array('callback' => 'drupal_delete_file_if_stale'));
}

/**
 * Callback to delete files modified more than a set time ago.
 */
function drupal_delete_file_if_stale($uri) {
  // Default stale file threshold is 30 days.
  if (REQUEST_TIME - filemtime($uri) > variable_get('drupal_stale_file_threshold', 2592000)) {
    file_unmanaged_delete($uri);
  }
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
 * This function ensures that each passed HTML ID value only exists once on the
 * page. By tracking the already returned ids, this function enables forms,
 * blocks, and other content to be output multiple times on the same page,
 * without breaking (X)HTML validation.
 *
 * For already existing ids, a counter is appended to the id string. Therefore,
 * JavaScript and CSS code should not rely on any value that was generated by
 * this function and instead should rely on manually added CSS classes or
 * similarly reliable constructs.
 *
 * Two consecutive hyphens separate the counter from the original id. To manage
 * uniqueness across multiple AJAX requests on the same page, AJAX requests
 * POST an array of all IDs currently present on the page, which are used to
 * prime this function's cache upon first invocation.
 *
 * To allow reverse-parsing of ids submitted via AJAX, any multiple consecutive
 * hyphens in the originally passed $id are replaced with a single hyphen.
 *
 * @param $id
 *   The ID to clean.
 *
 * @return
 *   The cleaned ID.
 */
function drupal_html_id($id) {
  // If this is an AJAX request, then content returned by this page request will
  // be merged with content already on the base page. The HTML ids must be
  // unique for the fully merged content. Therefore, initialize $seen_ids to
  // take into account ids that are already in use on the base page.
  $seen_ids_init = &drupal_static(__FUNCTION__ . ':init');
  if (!isset($seen_ids_init)) {
    // Ideally, Drupal would provide an API to persist state information about
    // prior page requests in the database, and we'd be able to add this
    // function's $seen_ids static variable to that state information in order
    // to have it properly initialized for this page request. However, no such
    // page state API exists, so instead, ajax.js adds all of the in-use HTML
    // ids to the POST data of AJAX submissions. Direct use of $_POST is
    // normally not recommended as it could open up security risks, but because
    // the raw POST data is cast to a number before being returned by this
    // function, this usage is safe.
    if (empty($_POST['ajax_html_ids'])) {
      $seen_ids_init = array();
    }
    else {
      // This function ensures uniqueness by appending a counter to the base id
      // requested by the calling function after the first occurrence of that
      // requested id. $_POST['ajax_html_ids'] contains the ids as they were
      // returned by this function, potentially with the appended counter, so
      // we parse that to reconstruct the $seen_ids array.
      foreach ($_POST['ajax_html_ids'] as $seen_id) {
        // We rely on '--' being used solely for separating a base id from the
        // counter, which this function ensures when returning an id.
        $parts = explode('--', $seen_id, 2);
        if (!empty($parts[1]) && is_numeric($parts[1])) {
          list($seen_id, $i) = $parts;
        }
        else {
          $i = 1;
        }
        if (!isset($seen_ids_init[$seen_id]) || ($i > $seen_ids_init[$seen_id])) {
          $seen_ids_init[$seen_id] = $i;
        }
      }
    }
  }
  $seen_ids = &drupal_static(__FUNCTION__, $seen_ids_init);

  $id = strtr(drupal_strtolower($id), array(' ' => '-', '_' => '-', '[' => '-', ']' => ''));

  // As defined in http://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
  // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
  // colons (":"), and periods ("."). We strip out any character not in that
  // list. Note that the CSS spec doesn't allow colons or periods in identifiers
  // (http://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
  // characters as well.
  $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);

  // Removing multiple consecutive hyphens.
  $id = preg_replace('/\-+/', '-', $id);
  // Ensure IDs are unique by appending a counter after the first occurrence.
  // The counter needs to be appended with a delimiter that does not exist in
  // the base ID. Requiring a unique delimiter helps ensure that we really do
  // return unique IDs and also helps us re-create the $seen_ids array during
  // AJAX requests.
  if (isset($seen_ids[$id])) {
    $id = $id . '--' . ++$seen_ids[$id];
  }
  else {
    $seen_ids[$id] = 1;
  }

  return $id;
}

/**
 * Provides a standard HTML class name that identifies a page region.
 *
 * It is recommended that template preprocess functions apply this class to any
 * page region that is output by the theme (Drupal core already handles this in
 * the standard template preprocess implementation). Standardizing the class
 * names in this way allows modules to implement certain features, such as
 * drag-and-drop or dynamic AJAX loading, in a theme-independent way.
 *
 * @param $region
 *   The name of the page region (for example, 'page_top' or 'content').
 *
 * @return
 *   An HTML class that identifies the region (for example, 'region-page-top'
 *   or 'region-content').
 *
 * @see template_preprocess_region()
 */
function drupal_region_class($region) {
  return drupal_html_class("region-$region");
}

/**
 * Adds a JavaScript file, setting, or inline code to the page.
 *
 * The behavior of this function depends on the parameters it is called with.
 * Generally, it handles the addition of JavaScript to the page, either as
 * reference to an existing file or as inline code. The following actions can be
 * performed using this function:
 * - Add a file ('file'): Adds a reference to a JavaScript file to the page.
 * - Add inline JavaScript code ('inline'): Executes a piece of JavaScript code
 *   on the current page by placing the code directly in the page (for example,
 *   to tell the user that a new message arrived, by opening a pop up, alert
 *   box, etc.). This should only be used for JavaScript that cannot be executed
 *   from a file. When adding inline code, make sure that you are not relying on
 *   $() being the jQuery function.  Wrap your code in
 *   @code (function ($) {... })(jQuery); @endcode
 *   or use jQuery() instead of $().
 * - Add external JavaScript ('external'): Allows the inclusion of external
 *   JavaScript files that are not hosted on the local server. Note that these
 *   external JavaScript references do not get aggregated when preprocessing is
 *   on.
 * - Add settings ('setting'): Adds settings to Drupal's global storage of
 *   JavaScript settings. Per-page settings are required by some modules to
 *   function properly. All settings will be accessible at Drupal.settings.
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
 *   - 'setting': An associative array with configuration options. The array is
 *     directly placed in Drupal.settings. All modules should wrap their actual
 *     configuration settings in another variable to prevent conflicts in the
 *     Drupal.settings namespace.
 * @param $options
 *   (optional) A string defining the type of JavaScript that is being added in
 *   the $data parameter ('file'/'setting'/'inline'/'external'), or an
 *   associative array. JavaScript settings should always pass the string
 *   'setting' only. Other types can have the following elements in the array:
 *   - type: The type of JavaScript that is to be added to the page. Allowed
 *     values are 'file', 'inline', 'external' or 'setting'. Defaults
 *     to 'file'.
 *   - scope: The location in which you want to place the script. Possible
 *     values are 'header' or 'footer'. If your theme implements different
 *     regions, you can also use these. Defaults to 'header'.
 *   - weight: A number defining the order in which the JavaScript is added to
 *     the page. In some cases, the order in which the JavaScript is presented
 *     on the page is very important. jQuery, for example, must be added to
 *     the page before any jQuery code is run, so jquery.js uses a weight of
 *     JS_LIBRARY - 20, jquery.once.js (a library drupal.js depends on) uses
 *     a weight of JS_LIBRARY - 19, drupal.js uses a weight of JS_LIBRARY - 1,
 *     and all following scripts depending on jQuery and Drupal behaviors are
 *     simply added using the default weight of JS_DEFAULT. Available constants
 *     are:
 *     - JS_LIBRARY: Any libraries, settings, or jQuery plugins.
 *     - JS_DEFAULT: Any module-layer JavaScript.
 *     - JS_THEME: Any theme-layer JavaScript.
 *     If you need to invoke a JavaScript file before any other module's
 *     JavaScript, for example, you would use JS_DEFAULT - 1.
 *   - defer: If set to TRUE, the defer attribute is set on the &lt;script&gt;
 *     tag.  Defaults to FALSE.
 *   - cache: If set to FALSE, the JavaScript file is loaded anew on every page
 *     call; in other words, it is not cached. Used only when 'type' references
 *     a JavaScript file. Defaults to TRUE.
 *   - preprocess: Aggregate the JavaScript if the JavaScript optimization
 *     setting has been toggled in admin/config/development/performance. Note
 *     that JavaScript of type 'external' is not aggregated. Defaults to TRUE.
 *
 * @return
 *   The current array of JavaScript files, settings, and in-line code,
 *   including Drupal defaults, anything previously added with calls to
 *   drupal_add_js(), and this function call's additions.
 *
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
    'version' => NULL,
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
  // The index counter is used to keep aggregated and non-aggregated files in
  // order by weight.
  $index = 1;
  $processed = array();
  $files = array();
  $preprocess_js = (variable_get('preprocess_js', FALSE) && (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update'));

  // A dummy query-string is added to filenames, to gain control over
  // browser-caching. The string changes on every update or full cache
  // flush, forcing browsers to load a new copy of the files, as the
  // URL changed. Files that should not be cached (see drupal_add_js())
  // get REQUEST_TIME as query-string instead, to enforce reload on every
  // page request.
  $default_query_string = variable_get('css_js_query_string', '0');

  // For inline Javascript to validate as XHTML, all Javascript containing
  // XHTML needs to be wrapped in CDATA. To make that backwards compatible
  // with HTML 4, we need to comment out the CDATA-tag.
  $embed_prefix = "\n<!--//--><![CDATA[//><!--\n";
  $embed_suffix = "\n//--><!]]>\n";

  // Since Javascript may look for arguments in the url and act on them, some
  // third-party code might require the use of a different query string.
  $js_version_string = variable_get('drupal_js_version_query_string', 'v=');

  // Sort the JavaScript by weight so that it appears in the correct order.
  uasort($items, 'drupal_sort_weight');

  // Loop through the JavaScript to construct the rendered output.
  $element = array(
    '#tag' => 'script',
    '#value' => '',
    '#attributes' => array(
      'type' => 'text/javascript',
    ),
  );
  foreach ($items as $item) {
    $query_string =  empty($item['version']) ? $default_query_string : $js_version_string . $item['version'];

    switch ($item['type']) {
      case 'setting':
        $js_element = $element;
        $js_element['#value_prefix'] = $embed_prefix;
        $js_element['#value'] = 'jQuery.extend(Drupal.settings, ' . drupal_json_encode(call_user_func_array('array_merge_recursive', $item['data'])) . ");";
        $js_element['#value_suffix'] = $embed_suffix;
        $output .= theme('html_tag', array('element' => $js_element));
        break;

      case 'inline':
        $js_element = $element;
        if ($item['defer']) {
          $js_element['#attributes']['defer'] = 'defer';
        }
        $js_element['#value_prefix'] = $embed_prefix;
        $js_element['#value'] = $item['data'];
        $js_element['#value_suffix'] = $embed_suffix;
        $processed[$index++] = theme('html_tag', array('element' => $js_element));
        break;

      case 'file':
        $js_element = $element;
        if (!$item['preprocess'] || !$preprocess_js) {
          if ($item['defer']) {
            $js_element['#attributes']['defer'] = 'defer';
          }
          $query_string_separator = (strpos($item['data'], '?') !== FALSE) ? '&' : '?';
          $js_element['#attributes']['src'] = file_create_url($item['data']) . $query_string_separator . ($item['cache'] ? $query_string : REQUEST_TIME);
          $processed[$index++] = theme('html_tag', array('element' => $js_element));
        }
        else {
          // By increasing the index for each aggregated file, we maintain
          // the relative ordering of JS by weight.
          $key = 'aggregate' . $index;
          $processed[$key] = '';
          $files[$key][$item['data']] = $item;
        }
        break;

      case 'external':
        $js_element = $element;
        // Preprocessing for external JavaScript files is ignored.
        if ($item['defer']) {
          $js_element['#attributes']['defer'] = 'defer';
        }
        $js_element['#attributes']['src'] = $item['data'];
        $processed[$index++] = theme('html_tag', array('element' => $js_element));
        break;
    }
  }

  // Aggregate any remaining JS files that haven't already been output.
  if ($preprocess_js && count($files) > 0) {
    foreach ($files as $key => $file_set) {
      $uri = drupal_build_js_cache($file_set);
      // Only include the file if was written successfully. Errors are logged
      // using watchdog.
      if ($uri) {
        $preprocess_file = file_create_url($uri);
        $js_element = $element;
        $js_element['#attributes']['src'] = $preprocess_file;
        $processed[$key] = theme('html_tag', array('element' => $js_element));
      }
    }
  }

  // Keep the order of JS files consistent as some are preprocessed and others are not.
  // Make sure any inline or JS setting variables appear last after libraries have loaded.
  return implode('', $processed) . $output;
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
 *
 * @return
 *   Will return FALSE if there were any missing library dependencies. TRUE will
 *   be returned if all library dependencies were met.
 *
 * @see drupal_add_library()
 * @see drupal_add_js()
 * @see drupal_add_css()
 * @see drupal_render()
 */
function drupal_process_attached($elements, $weight = JS_DEFAULT, $dependency_check = FALSE) {
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
 * Adds JavaScript to change the state of an element based on another element.
 *
 * A "state" means a certain property on a DOM element, such as "visible" or
 * "checked". A state can be applied to an element, depending on the state of
 * another element on the page. In general, states depend on HTML attributes and
 * DOM element properties, which change due to user interaction.
 *
 * Since states are driven by JavaScript only, it is important to understand
 * that all states are applied on presentation only, none of the states force
 * any server-side logic, and that they will not be applied for site visitors
 * without JavaScript support. All modules implementing states have to make
 * sure that the intended logic also works without JavaScript being enabled.
 *
 * #states is an associative array in the form of:
 * @code
 * array(
 *   STATE1 => CONDITIONS_ARRAY1,
 *   STATE2 => CONDITIONS_ARRAY2,
 *   ...
 * )
 * @endcode
 * Each key is the name of a state to apply to the element, such as 'visible'.
 * Each value is a list of conditions that denote when the state should be
 * applied.
 *
 * Multiple different states may be specified to act on complex conditions:
 * @code
 * array(
 *   'visible' => CONDITIONS,
 *   'checked' => OTHER_CONDITIONS,
 * )
 * @endcode
 *
 * Every condition is a key/value pair, whose key is a jQuery selector that
 * denotes another element on the page, and whose value is an array of
 * conditions, which must bet met on that element:
 * @code
 * array(
 *   'visible' => array(
 *     JQUERY_SELECTOR => REMOTE_CONDITIONS,
 *     JQUERY_SELECTOR => REMOTE_CONDITIONS,
 *     ...
 *   ),
 * )
 * @endcode
 * All conditions must be met for the state to be applied.
 *
 * Each remote condition is a key/value pair specifying conditions on the other
 * element that need to be met to apply the state to the element:
 * @code
 * array(
 *   'visible' => array(
 *     ':input[name="remote_checkbox"]' => array('checked' => TRUE),
 *   ),
 * )
 * @endcode
 *
 * For example, to show a textfield only when a checkbox is checked:
 * @code
 * $form['toggle_me'] = array(
 *   '#type' => 'checkbox',
 *   '#title' => t('Tick this box to type'),
 * );
 * $form['settings'] = array(
 *   '#type' => 'textfield',
 *   '#states' => array(
 *     // Only show this field when the 'toggle_me' checkbox is enabled.
 *     'visible' => array(
 *       ':input[name="toggle_me"]' => array('checked' => TRUE),
 *     ),
 *   ),
 * );
 * @endcode
 *
 * The following states may be applied to an element:
 * - enabled
 * - disabled
 * - visible
 * - invisible
 * - checked
 * - unchecked
 * - expanded
 * - collapsed
 *
 * The following states may be used in remote conditions:
 * - enabled
 * - disabled
 * - visible
 * - invisible
 * - checked
 * - unchecked
 * - value
 *
 * The following states exist for both states and remote conditions, but are not
 * fully implemented and may not change anything on the element:
 * - required
 * - optional
 * - relevant
 * - irrelevant
 * - valid
 * - invalid
 * - touched
 * - untouched
 * - filled
 * - empty
 * - readwrite
 * - readonly
 *
 * When referencing select lists and radio buttons in remote conditions, a
 * 'value' condition must be used:
 * @code
 *   '#states' => array(
 *     // Show the settings if 'bar' has been selected for 'foo'.
 *     'visible' => array(
 *       ':input[name="foo"]' => array('value' => 'bar'),
 *     ),
 *   ),
 * @endcode
 *
 * @param $elements
 *   A renderable array element having a #states property as described above.
 *
 * @see form_example_states_form()
 */
function drupal_process_states(&$elements) {
  $elements['#attached']['js']['misc/states.js'] = array('weight' => JS_LIBRARY + 1);
  $elements['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => array('states' => array('#' . $elements['#id'] => $elements['#states'])),
  );
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

  if (!isset($libraries[$module])) {
    // Retrieve all libraries associated with the module.
    $module_libraries = module_invoke($module, 'library');
    if (empty($module_libraries)) {
      $module_libraries = array();
    }
    // Allow modules to alter the module's registered libraries.
    drupal_alter('library', $module_libraries, $module);

    foreach ($module_libraries as $key => $data) {
      if (is_array($data)) {
        // Add default elements to allow for easier processing.
        $module_libraries[$key] += array('dependencies' => array(), 'js' => array(), 'css' => array());
        foreach ($module_libraries[$key]['js'] as $file => $options) {
          $module_libraries[$key]['js'][$file]['version'] = $module_libraries[$key]['version'];
        }
      }
    }
    $libraries[$module] = $module_libraries;
  }
  if (empty($libraries[$module][$name])) {
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
    drupal_add_js('misc/jquery.cookie.js', array('weight' => JS_DEFAULT - 2));
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
 * Aggregates JavaScript files into a cache file in the files directory.
 *
 * The file name for the JavaScript cache file is generated from the hash of
 * the aggregated contents of the files in $files. This forces proxies and
 * browsers to download new JavaScript when the JavaScript changes.
 *
 * The cache file name is retrieved on a page load via a lookup variable that
 * contains an associative array. The array key is the hash of the names in
 * $files while the value is the cache file name. The cache file is generated
 * in two cases. First, if there is no file name value for the key, which will
 * happen if a new file name has been added to $files or after the lookup
 * variable is emptied to force a rebuild of the cache.  Second, the cache
 * file is generated if it is missing on disk. Old cache files are not deleted
 * immediately when the lookup variable is emptied, but are deleted after a set
 * period by drupal_delete_file_if_stale(). This ensures that files referenced
 * by a cached page will still be available.
 *
 * @param $files
 *   An array of JavaScript files to aggregate and compress into one file.
 *
 * @return
 *   The URI of the cache file, or FALSE if the file could not be saved.
 */
function drupal_build_js_cache($files) {
  $contents = '';
  $uri = '';
  $map = variable_get('drupal_js_cache_files', array());
  $key = hash('sha256', serialize($files));
  if (isset($map[$key])) {
    $uri = $map[$key];
  }

  if (empty($uri) || !file_exists($uri)) {
    // Build aggregate JS file.
    foreach ($files as $path => $info) {
      if ($info['preprocess']) {
        // Append a ';' and a newline after each JS file to prevent them from running together.
        $contents .= file_get_contents($path) . ";\n";
      }
    }
    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = 'js_' . drupal_hash_base64($contents) . '.js';
    // Create the js/ within the files folder.
    $jspath = 'public://js';
    $uri = $jspath . '/' . $filename;
    // Create the JS file.
    file_prepare_directory($jspath, FILE_CREATE_DIRECTORY);
    if (!file_unmanaged_save_data($contents, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }
    $map[$key] = $uri;
    variable_set('drupal_js_cache_files', $map);
  }
  return $uri;
}

/**
 * Deletes old cached JavaScript files and variables.
 */
function drupal_clear_js_cache() {
  variable_del('javascript_parsed');
  variable_del('drupal_js_cache_files');
  file_scan_directory('public://js', '/.*/', array('callback' => 'drupal_delete_file_if_stale'));
}

/**
 * Converts a PHP variable into its Javascript equivalent.
 *
 * We use HTML-safe strings, i.e. with <, > and & escaped.
 *
 * @see drupal_json_decode()
 * @ingroup php_wrappers
 */
function drupal_json_encode($var) {
  // json_encode() does not escape <, > and &, so we do it with str_replace().
  return str_replace(array('<', '>', '&'), array('\u003c', '\u003e', '\u0026'), json_encode($var));
}

/**
 * Converts an HTML-safe JSON string into its PHP equivalent.
 *
 * @see drupal_json_encode()
 * @ingroup php_wrappers
 */
function drupal_json_decode($var) {
  return json_decode($var, TRUE);
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
  // We are returning JSON, so tell the browser.
  drupal_add_http_header('Content-Type', 'application/json');

  if (isset($var)) {
    echo drupal_json_encode($var);
  }
}

/**
 * Get a salt useful for hardening against SQL injection.
 *
 * @return
 *   A salt based on information in settings.php, not in the database.
 */
function drupal_get_hash_salt() {
  global $drupal_hash_salt, $databases;
  // If the $drupal_hash_salt variable is empty, a hash of the serialized
  // database credentials is used as a fallback salt.
  return empty($drupal_hash_salt) ? hash('sha256', serialize($databases)) : $drupal_hash_salt;
}

/**
 * Ensure the private key variable used to generate tokens is set.
 *
 * @return
 *   The private key.
 */
function drupal_get_private_key() {
  if (!($key = variable_get('drupal_private_key', 0))) {
    $key = drupal_hash_base64(drupal_random_bytes(55));
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
  return drupal_hmac_base64($value, session_id() . drupal_get_private_key() . drupal_get_hash_salt());
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
  return (($skip_anonymous && $user->uid == 0) || ($token == drupal_get_token($value)));
}

function _drupal_bootstrap_full() {
  $called = &drupal_static(__FUNCTION__);

  if ($called) {
    return;
  }
  $called = 1;
  require_once DRUPAL_ROOT . '/' . variable_get('path_inc', 'includes/path.inc');
  require_once DRUPAL_ROOT . '/includes/theme.inc';
  require_once DRUPAL_ROOT . '/includes/pager.inc';
  require_once DRUPAL_ROOT . '/' . variable_get('menu_inc', 'includes/menu.inc');
  require_once DRUPAL_ROOT . '/includes/tablesort.inc';
  require_once DRUPAL_ROOT . '/includes/file.inc';
  require_once DRUPAL_ROOT . '/includes/unicode.inc';
  require_once DRUPAL_ROOT . '/includes/image.inc';
  require_once DRUPAL_ROOT . '/includes/form.inc';
  require_once DRUPAL_ROOT . '/includes/mail.inc';
  require_once DRUPAL_ROOT . '/includes/actions.inc';
  require_once DRUPAL_ROOT . '/includes/ajax.inc';
  require_once DRUPAL_ROOT . '/includes/token.inc';
  require_once DRUPAL_ROOT . '/includes/errors.inc';

  // Detect string handling method
  unicode_check();
  // Undo magic quotes
  fix_gpc_magic();
  // Load all enabled modules
  module_load_all();
  // Make sure all stream wrappers are registered.
  file_get_stream_wrappers();

  $test_info = &$GLOBALS['drupal_test_info'];
  if (!empty($test_info['in_child_site'])) {
    // Running inside the simpletest child site, log fatal errors to test
    // specific file directory.
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
 * If page_compression is enabled, a gzipped version of the page is stored in
 * the cache to avoid compressing the output on each request. The cache entry
 * is unzipped in the relatively rare event that the page is requested by a
 * client without gzip support.
 *
 * Page compression requires the PHP zlib extension
 * (http://php.net/manual/en/ref.zlib.php).
 *
 * @see drupal_page_header()
 */
function drupal_page_set_cache() {
  global $base_root;

  if (drupal_page_is_cacheable()) {
    $cache = (object) array(
      'cid' => $base_root . request_uri(),
      'data' => array(
        'path' => $_GET['q'],
        'body' => ob_get_clean(),
        'title' => drupal_get_title(),
        'headers' => array(),
      ),
      'expire' => CACHE_TEMPORARY,
      'created' => REQUEST_TIME,
    );

    // Restore preferred header names based on the lower-case names returned
    // by drupal_get_http_header().
    $header_names = _drupal_set_preferred_header_name();
    foreach (drupal_get_http_header() as $name_lower => $value) {
      $cache->data['headers'][$header_names[$name_lower]] = $value;
      if ($name_lower == 'expires') {
        // Use the actual timestamp from an Expires header if available.
        $cache->expire = strtotime($value);
      }
    }

    if ($cache->data['body']) {
      if (variable_get('page_compression', TRUE) && extension_loaded('zlib')) {
        $cache->data['body'] = gzencode($cache->data['body'], 9, FORCE_GZIP);
      }
      cache_set($cache->cid, $cache->data, 'cache_page', $cache->expire);
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

  // Prevent session information from being saved while cron is running.
  drupal_save_session(FALSE);

  // Force the current user to anonymous to ensure consistent permissions on
  // cron runs.
  $original_user = $GLOBALS['user'];
  $GLOBALS['user'] = drupal_anonymous_user();

  // Try to allocate enough time to run all the hook_cron implementations.
  drupal_set_time_limit(240);

  $return = FALSE;
  // Grab the defined cron queues.
  $queues = module_invoke_all('cron_queue_info');
  drupal_alter('cron_queue_info', $queues);

  // Try to acquire cron lock.
  if (!lock_acquire('cron', 240.0)) {
    // Cron is still running normally.
    watchdog('cron', 'Attempting to re-run cron while it is already running.', array(), WATCHDOG_WARNING);
  }
  else {
    // Make sure every queue exists. There is no harm in trying to recreate an
    // existing queue.
    foreach ($queues as $queue_name => $info) {
      DrupalQueue::get($queue_name)->createQueue();
    }
    // Register shutdown callback
    drupal_register_shutdown_function('drupal_cron_cleanup');

    // Iterate through the modules calling their cron handlers (if any):
    module_invoke_all('cron');

    // Record cron time
    variable_set('cron_last', REQUEST_TIME);
    watchdog('cron', 'Cron run completed.', array(), WATCHDOG_NOTICE);

    // Release cron lock.
    lock_release('cron');

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
  // Restore the user.
  $GLOBALS['user'] = $original_user;
  drupal_save_session(TRUE);

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
 * Returns information about system object files (modules, themes, etc.).
 *
 * This function is used to find all or some system object files (module files,
 * theme files, etc.) that exist on the site. It searches in several locations,
 * depending on what type of object you are looking for. For instance, if you
 * are looking for modules and call:
 * @code
 * drupal_system_listing("/\.module$/", "modules", 'name', 0);
 * @endcode
 * this function will search the site-wide modules directory (i.e., /modules/),
 * your install profile's directory (i.e.,
 * /profiles/your_site_profile/modules/), the all-sites directory (i.e.,
 * /sites/all/modules/), and your site-specific directory (i.e.,
 * /sites/your_site_dir/modules/), in that order, and return information about
 * all of the files ending in .module in those directories.
 *
 * The information is returned in an associative array, which can be keyed on
 * the file name ($key = 'filename'), the file name without the extension ($key
 * = 'name'), or the full file stream URI ($key = 'uri'). If you use a key of
 * 'filename' or 'name', files found later in the search will take precedence
 * over files found earlier; if you choose a key of 'uri', you will get all
 * files found.
 *
 * @param string $mask
 *   The preg_match() regular expression for the files to find.
 * @param string $directory
 *   The subdirectory name in which the files are found. For example,
 *   'modules' will search in sub-directories of the top-level /modules
 *   directory, sub-directories of /sites/all/modules/, etc.
 * @param string $key
 *   The key to be used for the associative array returned. Possible values are
 *   'uri', for the file's URI; 'filename', for the basename of the file; and
 *   'name' for the name of the file without the extension. If you choose 'name'
 *   or 'filename', only the highest-precedence file will be returned.
 * @param int $min_depth
 *   Minimum depth of directories to return files from, relative to each
 *   directory searched. For instance, a minimum depth of 2 would find modules
 *   inside /modules/node/tests, but not modules directly in /modules/node.
 *
 * @return array
 *   An associative array of file objects, keyed on the chosen key. Each element
 *   in the array is an object containing file information, with properties:
 *   - 'uri': Full URI of the file.
 *   - 'filename': File name.
 *   - 'name': Name of file without the extension.
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

  // Get current list of items
  if (!function_exists('file_scan_directory')) {
    require_once DRUPAL_ROOT . '/includes/file.inc';
  }
  foreach ($searchdir as $dir) {
    $files = array_merge($files, file_scan_directory($dir, $mask, array('key' => $key, 'min_depth' => $min_depth)));
  }

  return $files;
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
 *   If called without $content, a renderable array representing the body of
 *   the page.
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
 * #pre_render callback to render #browsers into #prefix and #suffix.
 *
 * @param $elements
 *   A render array with a '#browsers' property. The '#browsers' property can
 *   contain any or all of the following keys:
 *   - 'IE': If FALSE, the element is not rendered by Internet Explorer. If
 *     TRUE, the element is rendered by Internet Explorer. Can also be a string
 *     containing an expression for Internet Explorer to evaluate as part of a
 *     conditional comment. For example, this can be set to 'lt IE 7' for the
 *     element to be rendered in Internet Explorer 6, but not in Internet
 *     Explorer 7 or higher. Defaults to TRUE.
 *   - '!IE': If FALSE, the element is not rendered by browsers other than
 *     Internet Explorer. If TRUE, the element is rendered by those browsers.
 *     Defaults to TRUE.
 *   Examples:
 *   - To render an element in all browsers, '#browsers' can be left out or set
 *     to array('IE' => TRUE, '!IE' => TRUE).
 *   - To render an element in Internet Explorer only, '#browsers' can be set
 *     to array('!IE' => FALSE).
 *   - To render an element in Internet Explorer 6 only, '#browsers' can be set
 *     to array('IE' => 'lt IE 7', '!IE' => FALSE).
 *   - To render an element in Internet Explorer 8 and higher and in all other
 *     browsers, '#browsers' can be set to array('IE' => 'gte IE 8').
 *
 * @return
 *   The passed in element with markup for conditional comments potentially
 *   added to '#prefix' and '#suffix'.
 */
function drupal_pre_render_conditional_comments($elements) {
  $browsers = isset($elements['#browsers']) ? $elements['#browsers'] : array();
  $browsers += array(
    'IE' => TRUE,
    '!IE' => TRUE,
  );

  // If rendering in all browsers, no need for conditional comments.
  if ($browsers['IE'] === TRUE && $browsers['!IE']) {
    return $elements;
  }

  // Determine the conditional comment expression for Internet Explorer to
  // evaluate.
  if ($browsers['IE'] === TRUE) {
    $expression = 'IE';
  }
  elseif ($browsers['IE'] === FALSE) {
    $expression = '!IE';
  }
  else {
    $expression = $browsers['IE'];
  }

  // Wrap the element's potentially existing #prefix and #suffix properties with
  // conditional comment markup. The conditional comment expression is evaluated
  // by Internet Explorer only. To control the rendering by other browsers,
  // either the "downlevel-hidden" or "downlevel-revealed" technique must be
  // used. See http://en.wikipedia.org/wiki/Conditional_comment for details.
  $elements += array(
    '#prefix' => '',
    '#suffix' => '',
  );
  if (!$browsers['!IE']) {
    // "downlevel-hidden".
    $elements['#prefix'] = "\n<!--[if $expression]>\n" . $elements['#prefix'];
    $elements['#suffix'] .= "<![endif]-->\n";
  }
  else {
    // "downlevel-revealed".
    $elements['#prefix'] = "\n<!--[if $expression]><!-->\n" . $elements['#prefix'];
    $elements['#suffix'] .= "<!--<![endif]-->\n";
  }

  return $elements;
}

/**
 * #pre_render callback to render a link into #markup.
 *
 * Doing so during pre_render gives modules a chance to alter the link parts.
 *
 * @param $elements
 *   A structured array whose keys form the arguments to l():
 *   - #title: The link text to pass as argument to l().
 *   - #href: The URL path component to pass as argument to l().
 *   - #options: (optional) An array of options to pass to l().
 *
 * @return
 *   The passed in elements containing a rendered link in '#markup'.
 */
function drupal_pre_render_link($elements) {
  $options = isset($elements['#options']) ? $elements['#options'] : array();
  $elements['#markup'] = l($elements['#title'], $elements['#href'], $options);
  return $elements;
}

/**
 * #pre_render callback to append contents in #markup to #children.
 *
 * This needs to be a #pre_render callback, because eventually assigned
 * #theme_wrappers will expect the element's rendered content in #children.
 * Note that if also a #theme is defined for the element, then the result of
 * the theme callback will override #children.
 *
 * @see drupal_render()
 *
 * @param $elements
 *   A structured array using the #markup key.
 *
 * @return
 *   The passed in elements, but #markup appended to #children.
 */
function drupal_pre_render_markup($elements) {
  $elements['#children'] = $elements['#markup'];
  return $elements;
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
 * @see element_info()
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
 *       is set, the cache ID is created automatically from these keys. See
 *       drupal_render_cid_create().
 *    - 'granularity' (optional): Define the cache granularity using binary
 *       combinations of the cache granularity constants, e.g. DRUPAL_CACHE_PER_USER
 *       to cache for each user separately or
 *       DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE to cache separately for each
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
 * drupal_render() flags each element with a '#printed' status to indicate that
 * the element has been rendered, which allows individual elements of a given
 * array to be rendered independently and prevents them from being rendered
 * more than once on subsequent calls to drupal_render() (e.g., as part of a
 * larger array). If the same array or array element is passed more than once
 * to drupal_render(), it simply returns a NULL value.
 *
 * @param $elements
 *   The structured array describing the data to be rendered.
 * @return
 *   The rendered HTML.
 */
function drupal_render(&$elements) {
  // Early-return nothing if user does not have access.
  if (empty($elements) || (isset($elements['#access']) && !$elements['#access'])) {
    return;
  }

  // Do not print elements twice.
  if (!empty($elements['#printed'])) {
    return;
  }

  // Try to fetch the element's markup from cache and return.
  if (isset($elements['#cache']) && $cached_output = drupal_render_cache_get($elements)) {
    return $cached_output;
  }

  // If #markup is not empty, set #type. This allows to specify just #markup on
  // an element without setting #type.
  if (!empty($elements['#markup']) && !isset($elements['#type'])) {
    $elements['#type'] = 'markup';
  }

  // If the default values for this element have not been loaded yet, populate
  // them.
  if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
    $elements += element_info($elements['#type']);
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

  // Allow #pre_render to abort rendering.
  if (!empty($elements['#printed'])) {
    return;
  }

  // Get the children of the element, sorted by weight.
  $children = element_children($elements, TRUE);

  // Initialize this element's #children, unless a #pre_render callback already
  // preset #children.
  if (!isset($elements['#children'])) {
    $elements['#children'] = '';
  }
  // Call the element's #theme function if it is set. Then any children of the
  // element have to be rendered there.
  if (isset($elements['#theme'])) {
    $elements['#children'] = theme($elements['#theme'], $elements);
  }
  // If #theme was not set and the element has children, render them now.
  // This is the same process as drupal_render_children() but is inlined
  // for speed.
  if ($elements['#children'] == '') {
    foreach ($children as $key) {
      $elements['#children'] .= drupal_render($elements[$key]);
    }
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
  if (!empty($elements['#states'])) {
    drupal_process_states($elements);
  }

  // Add additional libraries, CSS, JavaScript an other custom
  // attached data associated with this element.
  if (!empty($elements['#attached'])) {
    drupal_process_attached($elements);
  }

  $prefix = isset($elements['#prefix']) ? $elements['#prefix'] : '';
  $suffix = isset($elements['#suffix']) ? $elements['#suffix'] : '';
  $output = $prefix . $elements['#children'] . $suffix;

  // Cache the processed element if #cache is set.
  if (isset($elements['#cache'])) {
    drupal_render_cache_set($output, $elements);
  }

  $elements['#printed'] = TRUE;
  return $output;
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
    if (!empty($element[$key])) {
      $output .= drupal_render($element[$key]);
    }
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
    if (isset($cache->data['#attached'])) {
      drupal_process_attached($cache->data);
    }
    // Return the rendered output.
    return $cache->data['#markup'];
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
function drupal_render_cache_set(&$markup, $elements) {
  // Create the cache ID for the element.
  if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD')) || !$cid = drupal_render_cid_create($elements)) {
    return FALSE;
  }

  // Cache implementations are allowed to modify the markup, to support
  // replacing markup with edge-side include commands. The supporting cache
  // backend will store the markup in some other key (like
  // $data['#real-value']) and return an include command instead. When the
  // ESI command is executed by the content accelerator, the real value can
  // be retrieved and used.
  $data['#markup'] = &$markup;
  // Persist attached data associated with this element.
  if (isset($elements['#attached'])) {
    $data['#attached'] = $elements['#attached'];
  }
  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'cache';
  $expire = isset($elements['#cache']['expire']) ? $elements['#cache']['expire'] : CACHE_PERMANENT;
  cache_set($cid, $data, $bin, $expire);
}

/**
 * Prepare an element for caching based on a query. This smart caching strategy
 * saves Drupal from querying and rendering to HTML when the underlying query is
 * unchanged.
 *
 * Expensive queries should use the query builder to create the query and then
 * call this function. Executing the query and formatting results should happen
 * in a #pre_render callback.
 *
 * @param $query
 *   A select query object as returned by db_select().
 * @param $function
 *   The name of the function doing this caching. A _pre_render suffix will be
 *   added to this string and is also part of the cache key in
 *   drupal_render_cache_set() and drupal_render_cache_get().
 * @param $expire
 *   The cache expire time, passed eventually to cache_set().
 * @param $granularity
 *   One or more granularity constants passed to drupal_render_cid_parts().
 *
 * @return
 *   A renderable array with the following keys and values:
 *   - #query: The passed in $query.
 *   - #pre_render: $function with a _pre_render suffix.
 *   - #cache: An associative array prepared for drupal_render_cache_set().
 */
function drupal_render_cache_by_query($query, $function, $expire = CACHE_TEMPORARY, $granularity = NULL) {
  $cache_keys = array_merge(array($function), drupal_render_cid_parts($granularity));
  $query->preExecute();
  $cache_keys[] = hash('sha256', serialize(array((string) $query, $query->getArguments())));
  return array(
    '#query' => $query,
    '#pre_render' => array($function . '_pre_render'),
    '#cache' => array(
      'keys' => $cache_keys,
      'expire' => $expire,
    ),
  );
}

/**


/**
 * Helper function for building cache ids.
 *
 * @param $granularity
 *   One or more cache granularity constants, e.g. DRUPAL_CACHE_PER_USER to cache
 *   for each user separately or DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE to
 *   cache separately for each page and role.
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
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__);
  }
  $cache = &$drupal_static_fast['cache'];

  if (!isset($cache)) {
    $cache = module_invoke_all('element_info');
    foreach ($cache as $element_type => $info) {
      $cache[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    drupal_alter('element_info', $cache);
  }

  return isset($cache[$type]) ? $cache[$type] : array();
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
 * Return the visibile children of an element.
 *
 * @param $elements
 *   The parent element.
 * @return
 *   The array keys of the element's visible children.
 */
function element_get_visible_children(array $elements) {
  $visible_children = array();

  foreach (element_children($elements) as $key) {
    $child = $elements[$key];

    // Skip un-accessible children.
    if (isset($child['#access']) && !$child['#access']) {
      continue;
    }

    // Skip value and hidden elements, since they are not rendered.
    if (isset($child['#type']) && in_array($child['#type'], array('value', 'hidden'))) {
      continue;
    }

    $visible_children[$key] = $child;
  }

  return array_keys($visible_children);
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
    'link' => array(
      'variables' => array('text' => NULL, 'path' => NULL, 'options' => array()),
    ),
    'links' => array(
      'variables' => array('links' => NULL, 'attributes' => array('class' => array('links')), 'heading' => array()),
    ),
    'image' => array(
      // HTML 4 and XHTML 1.0 always require an alt attribute. The HTML 5 draft
      // allows the alt attribute to be omitted in some cases. Therefore,
      // default the alt attribute to an empty string, but allow code calling
      // theme('image') to pass explicit NULL for it to be omitted. Usually,
      // neither omission nor an empty string satisfies accessibility
      // requirements, so it is strongly encouraged for code calling
      // theme('image') to pass a meaningful value for the alt variable.
      // - http://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
      // - http://www.w3.org/TR/xhtml1/dtds.html
      // - http://dev.w3.org/html5/spec/Overview.html#alt
      // The title attribute is optional in all cases, so it is omitted by
      // default.
      'variables' => array('path' => NULL, 'alt' => '', 'title' => NULL, 'attributes' => array(), 'getsize' => TRUE),
    ),
    'breadcrumb' => array(
      'variables' => array('breadcrumb' => NULL),
    ),
    'help' => array(
      'variables' => array(),
    ),
    'table' => array(
      'variables' => array('header' => NULL, 'rows' => NULL, 'attributes' => array(), 'caption' => NULL, 'colgroups' => array(), 'sticky' => TRUE, 'empty' => ''),
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
    'username' => array(
      'variables' => array('account' => NULL),
    ),
    'progress_bar' => array(
      'variables' => array('percent' => NULL, 'message' => NULL),
    ),
    'indentation' => array(
      'variables' => array('size' => 1),
    ),
    'html_tag' => array(
      'render element' => 'element',
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
    'form_required_marker' => array(
      'arguments' => array('element' => NULL),
    ),
    'form_element_label' => array(
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
 * Creates all tables in a module's hook_schema() implementation.
 *
 * Note: This function does not pass the module's schema through
 * hook_schema_alter(). The module's tables will be created exactly as the
 * module defines them.
 *
 * @param $module
 *   The module for which the tables will be created.
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
 * Saves a record to the database based upon the schema.
 *
 * Default values are filled in for missing items, and 'serial' (auto increment)
 * types are filled in with IDs.
 *
 * @param $table
 *   The name of the table; this must be defined by a hook_schema()
 *   implementation.
 * @param $object
 *   An object or array representing the record to write, passed in by
 *   reference. The function will fill in defaults from the schema and add an
 *   ID value to serial fields.
 * @param $primary_keys
 *   If this is an update, specify the primary keys' field names. If this is a
 *   new record, you must not provide this value. If there is only 1 field in
 *   the key, you may pass in a string; if there are multiple fields in the key,
 *   pass in an array.
 *
 * @return
 *   Failure to write a record will return FALSE. Otherwise SAVED_NEW or
 *   SAVED_UPDATED is returned depending on the operation performed. The $object
 *   parameter will contain values for any serial fields defined by the $table.
 *   For example, $object->nid or $object['nid'] will be populated after
 *   inserting a new a new node.
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
    if ($info['type'] == 'serial') {
      // Skip serial types if we are updating.
      if (!empty($primary_keys)) {
        continue;
      }
      // Track serial field so we can helpfully populate them after the query.
      // NOTE: Each table should come with one serial field only.
      $serial = $field;
    }

    if (!property_exists($object, $field)) {
      // Skip fields that are not provided, unless we are inserting and a
      // default value is provided by the schema.
      if (!empty($primary_keys) || !isset($info['default'])) {
        continue;
      }
      $object->$field = $info['default'];
    }

    // Build array of fields to update or insert.
    if (empty($info['serialize'])) {
      $fields[$field] = $object->$field;
    }
    elseif (isset($object->$field)) {
      $fields[$field] = serialize($object->$field);
    }
    else {
      $fields[$field] = '';
    }

    // Type cast to proper datatype, except when the value is NULL and the
    // column allows this.
    //
    // MySQL PDO silently casts e.g. FALSE and '' to 0 when inserting the value
    // into an integer column, but PostgreSQL PDO does not. Also type cast NULL
    // when the column does not allow this.
    if (!is_null($object->$field) || !empty($info['not null'])) {
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
    // We are doing an insert.
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
  if ($query_return = $query->execute()) {
    if (isset($serial)) {
      // If the database was not told to return the last insert id, it will be
      // because we already know it.
      if (isset($options) && $options['return'] != Database::RETURN_INSERT_ID) {
        $object->$serial = $fields[$serial];
      }
      else {
        $object->$serial = $query_return;
      }
    }
  }
  // If we have a single-field primary key but got no insert ID, the
  // query failed. Note that we explicitly check for FALSE, because
  // a valid update query which doesn't change any values will return
  // zero (0) affected rows.
  elseif ($query_return === FALSE && count($primary_keys) == 1) {
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
  $constants = get_defined_constants();

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
 * Severity levels, as defined in RFC 3164: http://www.ietf.org/rfc/rfc3164.txt.
 *
 * @return
 *   Array of the possible severity levels for log messages.
 *
 * @see watchdog()
 */
function watchdog_severity_levels() {
  return array(
    WATCHDOG_EMERGENCY => t('emergency'),
    WATCHDOG_ALERT     => t('alert'),
    WATCHDOG_CRITICAL  => t('critical'),
    WATCHDOG_ERROR     => t('error'),
    WATCHDOG_WARNING   => t('warning'),
    WATCHDOG_NOTICE    => t('notice'),
    WATCHDOG_INFO      => t('info'),
    WATCHDOG_DEBUG     => t('debug'),
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

  // Rebuild the theme data. Note that the module data is rebuilt above, as
  // part of registry_rebuild().
  system_rebuild_theme_data();
  drupal_theme_rebuild();

  menu_rebuild();
  node_types_rebuild();

  // Don't clear cache_form - in-progress form submissions may break.
  // Ordered so clearing the page cache will always be the last action.
  $core = array('cache', 'cache_filter', 'cache_bootstrap', 'cache_page');
  $cache_tables = array_merge(module_invoke_all('flush_caches'), $core);
  foreach ($cache_tables as $table) {
    cache_clear_all('*', $table, TRUE);
  }
}

/**
 * Helper function to change query-strings on css/js files.
 *
 * Changes the character added to all css/js files as dummy query-string, so
 * that all browsers are forced to reload fresh files.
 */
function _drupal_flush_css_js() {
  // The timestamp is converted to base 36 in order to make it more compact.
  variable_set('css_js_query_string', base_convert(REQUEST_TIME, 10, 36));
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
 *   caused the incompatibility.
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
  global $language;

  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['entity_info'] = &drupal_static(__FUNCTION__);
  }
  $entity_info = &$drupal_static_fast['entity_info'];

  // hook_entity_info() includes translated strings, so each language is cached
  // separately.
  $langcode = $language->language;

  if (empty($entity_info)) {
    if ($cache = cache_get("entity_info:$langcode")) {
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
          'field cache' => TRUE,
          'load hook' => $name . '_load',
          'bundles' => array(),
          'view modes' => array(),
          'entity keys' => array(),
          'translation' => array(),
        );
        $entity_info[$name]['entity keys'] += array(
          'revision' => '',
          'bundle' => '',
        );
        foreach ($entity_info[$name]['view modes'] as $view_mode => $view_mode_info) {
          $entity_info[$name]['view modes'][$view_mode] += array(
            'custom settings' => FALSE,
          );
        }
        // If no bundle key is provided, assume a single bundle, named after
        // the entity type.
        if (empty($entity_info[$name]['entity keys']['bundle']) && empty($entity_info[$name]['bundles'])) {
          $entity_info[$name]['bundles'] = array($name => array('label' => $entity_info[$name]['label']));
        }
      }
      // Let other modules alter the entity info.
      drupal_alter('entity_info', $entity_info);
      cache_set("entity_info:$langcode", $entity_info);
    }
  }

  if (empty($entity_type)) {
    return $entity_info;
  }
  elseif (isset($entity_info[$entity_type])) {
    return $entity_info[$entity_type];
  }
}

/**
 * Resets the cached information about entity types.
 */
function entity_info_cache_clear() {
  drupal_static_reset('entity_get_info');
  // Clear all languages.
  cache_clear_all('entity_info:', 'cache', TRUE);
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
 *   0: primary id of the entity
 *   1: revision id of the entity, or NULL if $entity_type is not versioned
 *   2: bundle name of the entity
 */
function entity_extract_ids($entity_type, $entity) {
  $info = entity_get_info($entity_type);
  // Objects being created might not have id/vid yet.
  $id = isset($entity->{$info['entity keys']['id']}) ? $entity->{$info['entity keys']['id']} : NULL;
  $vid = ($info['entity keys']['revision'] && isset($entity->{$info['entity keys']['revision']})) ? $entity->{$info['entity keys']['revision']} : NULL;
  // If no bundle key provided, then we assume a single bundle, named after the
  // entity type.
  $bundle = $info['entity keys']['bundle'] ? $entity->{$info['entity keys']['bundle']} : $entity_type;
  return array($id, $vid, $bundle);
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
 *   2: bundle name of the entity, or NULL if $entity_type has no bundles
 * @return
 *   An entity structure, initialized with the ids provided.
 */
function entity_create_stub_entity($entity_type, $ids) {
  $entity = new stdClass();
  $info = entity_get_info($entity_type);
  $entity->{$info['entity keys']['id']} = $ids[0];
  if (!empty($info['entity keys']['revision']) && !is_null($ids[1])) {
    $entity->{$info['entity keys']['revision']} = $ids[1];
  }
  if (!empty($info['entity keys']['bundle']) && !is_null($ids[2])) {
    $entity->{$info['entity keys']['bundle']} = $ids[2];
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
 * Invoke hook_entity_prepare_view().
 *
 * If adding a new entity similar to nodes, comments or users, you should
 * invoke this function during the ENTITY_build_content() or
 * ENTITY_view_multiple() phases of rendering to allow other modules to alter
 * the objects during this phase. This is needed for situations where
 * information needs to be loaded outside of ENTITY_load() - particularly
 * when loading entities into one another - i.e. a user object into a node, due
 * to the potential for unwanted side-effects such as caching and infinite
 * recursion. By convention, entity_prepare_view() is called after
 * field_attach_prepare_view() to allow entity level hooks to act on content
 * loaded by field API.
 * @see hook_entity_prepare_view()
 *
 * @param $entity_type
 *   The type of entity, i.e. 'node', 'user'.
 * @param $entities
 *   The entity objects which are being prepared for view, keyed by object ID.
 */
function entity_prepare_view($entity_type, $entities) {
  // To ensure hooks are only run once per entity, check for an
  // entity_view_prepared flag and only process items without it.
  // @todo: resolve this more generally for both entity and field level hooks.
  $prepare = array();
  foreach ($entities as $id => $entity) {
    if (empty($entity->entity_view_prepared)) {
      // Add this entity to the items to be prepared.
      $prepare[$id] = $entity;

      // Mark this item as prepared.
      $entity->entity_view_prepared = TRUE;
    }
  }

  if (!empty($prepare)) {
    module_invoke_all('entity_prepare_view', $prepare, $entity_type);
  }
}

/**
 * Returns the uri elements of an entity.
 *
 * @param $entity_type
 *   The entity type; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity for which to generate a path.
 * @return
 *   An array containing the 'path' and 'options' keys used to build the uri of
 *   the entity, and matching the signature of url(). NULL if the entity has no
 *   uri of its own.
 */
function entity_uri($entity_type, $entity) {
  // This check enables the URI of an entity to be easily overridden from what
  // the callback for the entity type or bundle would return, and it helps
  // minimize performance overhead when entity_uri() is called multiple times
  // for the same entity.
  if (!isset($entity->uri)) {
    $info = entity_get_info($entity_type);
    list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

    // A bundle-specific callback takes precedence over the generic one for the
    // entity type.
    if (isset($info['bundles'][$bundle]['uri callback'])) {
      $uri_callback = $info['bundles'][$bundle]['uri callback'];
    }
    elseif (isset($info['uri callback'])) {
      $uri_callback = $info['uri callback'];
    }
    else {
      $uri_callback = NULL;
    }

    // Invoke the callback to get the URI. If there is no callback, set the
    // entity's 'uri' property to FALSE to indicate that it is known to not have
    // a URI.
    if (isset($uri_callback) && function_exists($uri_callback)) {
      $entity->uri = $uri_callback($entity);
      if (!isset($entity->uri['options'])) {
        $entity->uri['options'] = array();
      }
      // Pass the entity data to url() so that alter functions do not need to
      // lookup this entity again.
      $entity->uri['options']['entity_type'] = $entity_type;
      $entity->uri['options']['entity'] = $entity;
    }
    else {
      $entity->uri = FALSE;
    }
  }
  return $entity->uri ? $entity->uri : NULL;
}

/**
 * Helper function for attaching field API validation to entity forms.
 */
function entity_form_field_validate($entity_type, $form, &$form_state) {
  // All field attach API functions act on an entity object, but during form
  // validation, we don't have one. $form_state contains the entity as it was
  // prior to processing the current form submission, and we must not update it
  // until we have fully validated the submitted input. Therefore, for
  // validation, act on a pseudo entity created out of the form values.
  $pseudo_entity = (object) $form_state['values'];
  field_attach_form_validate($entity_type, $pseudo_entity, $form, $form_state);
}

/**
 * Helper function for copying submitted values to entity properties for simple entity forms.
 *
 * During the submission handling of an entity form's "Save", "Preview", and
 * possibly other buttons, the form state's entity needs to be updated with the
 * submitted form values. Each entity form implements its own
 * $form['#builder_function'] for doing this, appropriate for the particular
 * entity and form. Many of these entity builder functions can call this helper
 * function to re-use its logic of copying $form_state['values'][PROPERTY]
 * values to $entity->PROPERTY for all entries in $form_state['values'] that are
 * not field data, and calling field_attach_submit() to copy field data.
 *
 * For some entity forms (e.g., forms with complex non-field data and forms that
 * simultaneously edit multiple entities), this behavior may be inappropriate,
 * so the #builder_function for such forms needs to implement the required
 * functionality instead of calling this function. 
 */
function entity_form_submit_build_entity($entity_type, $entity, $form, &$form_state) {
  $info = entity_get_info($entity_type);
  list(, , $bundle) = entity_extract_ids($entity_type, $entity);

  // Copy top-level form values that are not for fields to entity properties,
  // without changing existing entity properties that are not being edited by
  // this form. Copying field values must be done using field_attach_submit().
  $values_excluding_fields = $info['fieldable'] ? array_diff_key($form_state['values'], field_info_instances($entity_type, $bundle)) : $form_state['values'];
  foreach ($values_excluding_fields as $key => $value) {
    $entity->$key = $value;
  }

  // Copy field values to the entity.
  if ($info['fieldable']) {
    field_attach_submit($entity_type, $entity, $form, $form_state);
  }
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
 * Retrieves a list of all available archivers.
 *
 * @see hook_archiver_info()
 * @see hook_archiver_info_alter()
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
