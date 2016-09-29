<?php

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
 * @}
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
 * The default group for system CSS files added to the page.
 */
define('CSS_SYSTEM', -100);

/**
 * The default group for module CSS files added to the page.
 */
define('CSS_DEFAULT', 0);

/**
 * The default group for theme CSS files added to the page.
 */
define('CSS_THEME', 100);

/**
 * The default group for JavaScript and jQuery libraries added to the page.
 */
define('JS_LIBRARY', -100);

/**
 * The default group for module JavaScript code added to the page.
 */
define('JS_DEFAULT', 0);

/**
 * The default group for theme JavaScript code added to the page.
 */
define('JS_THEME', 100);

/**
 * Error code indicating that the request exceeded the specified timeout.
 *
 * @see drupal_http_request()
 */
define('HTTP_REQUEST_TIMEOUT', -1);

/**
 * @defgroup block_caching Block Caching
 * @{
 * Constants that define each block's caching state.
 *
 * Modules specify how their blocks can be cached in their hook_block_info()
 * implementations. Caching can be turned off (DRUPAL_NO_CACHE), managed by the
 * module declaring the block (DRUPAL_CACHE_CUSTOM), or managed by the core
 * Block module. If the Block module is managing the cache, you can specify that
 * the block is the same for every page and user (DRUPAL_CACHE_GLOBAL), or that
 * it can change depending on the page (DRUPAL_CACHE_PER_PAGE) or by user
 * (DRUPAL_CACHE_PER_ROLE or DRUPAL_CACHE_PER_USER). Page and user settings can
 * be combined with a bitwise-binary or operator; for example,
 * DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE means that the block can change
 * depending on the user role or page it is on.
 *
 * The block cache is cleared in cache_clear_all(), and uses the same clearing
 * policy than page cache (node, comment, user, taxonomy added or updated...).
 * Blocks requiring more fine-grained clearing might consider disabling the
 * built-in block cache (DRUPAL_NO_CACHE) and roll their own.
 *
 * Note that user 1 is excluded from block caching.
 */

/**
 * The block should not get cached.
 *
 * This setting should be used:
 * - For simple blocks (notably those that do not perform any db query), where
 *   querying the db cache would be more expensive than directly generating the
 *   content.
 * - For blocks that change too frequently.
 */
define('DRUPAL_NO_CACHE', -1);

/**
 * The block is handling its own caching in its hook_block_view().
 *
 * This setting is useful when time based expiration is needed or a site uses a
 * node access which invalidates standard block cache.
 */
define('DRUPAL_CACHE_CUSTOM', -2);

/**
 * The block or element can change depending on the user's roles.
 *
 * This is the default setting for blocks, used when the block does not specify
 * anything.
 */
define('DRUPAL_CACHE_PER_ROLE', 0x0001);

/**
 * The block or element can change depending on the user.
 *
 * This setting can be resource-consuming for sites with large number of users,
 * and thus should only be used when DRUPAL_CACHE_PER_ROLE is not sufficient.
 */
define('DRUPAL_CACHE_PER_USER', 0x0002);

/**
 * The block or element can change depending on the page being viewed.
 */
define('DRUPAL_CACHE_PER_PAGE', 0x0004);

/**
 * The block or element is the same for every user and page that it is visible.
 */
define('DRUPAL_CACHE_GLOBAL', 0x0008);

/**
 * @} End of "defgroup block_caching".
 */

/**
 * Adds content to a specified region.
 *
 * @param $region
 *   Page region the content is added to.
 * @param $data
 *   Content to be added.
 */
function drupal_add_region_content($region = NULL, $data = NULL) {
  static $content = array();

  if (isset($region) && isset($data)) {
    $content[$region][] = $data;
  }
  return $content;
}

/**
 * Gets assigned content for a given region.
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
 * Gets the name of the currently active installation profile.
 *
 * When this function is called during Drupal's initial installation process,
 * the name of the profile that's about to be installed is stored in the global
 * installation state. At all other times, the standard Drupal systems variable
 * table contains the name of the current profile, and we can call
 * variable_get() to determine what one is active.
 *
 * @return $profile
 *   The name of the installation profile.
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
 * Sets the breadcrumb trail for the current page.
 *
 * @param $breadcrumb
 *   Array of links, starting with "home" and proceeding up to but not including
 *   the current page.
 */
function drupal_set_breadcrumb($breadcrumb = NULL) {
  $stored_breadcrumb = &drupal_static(__FUNCTION__);

  if (isset($breadcrumb)) {
    $stored_breadcrumb = $breadcrumb;
  }
  return $stored_breadcrumb;
}

/**
 * Gets the breadcrumb trail for the current page.
 */
function drupal_get_breadcrumb() {
  $breadcrumb = drupal_set_breadcrumb();

  if (!isset($breadcrumb)) {
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
 * Adds output to the HEAD tag of the HTML page.
 *
 * This function can be called as long as the headers aren't sent. Pass no
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
 * Retrieves output to be displayed in the HEAD tag of the HTML page.
 */
function drupal_get_html_head() {
  $elements = drupal_add_html_head();
  drupal_alter('html_head', $elements);
  return drupal_render($elements);
}

/**
 * Adds a feed URL for the current page.
 *
 * This function can be called as long the HTML header hasn't been sent.
 *
 * @param $url
 *   An internal system path or a fully qualified external URL of the feed.
 * @param $title
 *   The title of the feed.
 */
function drupal_add_feed($url = NULL, $title = '') {
  $stored_feed_links = &drupal_static(__FUNCTION__, array());

  if (isset($url)) {
    $stored_feed_links[$url] = theme('feed_icon', array('url' => $url, 'title' => $title));

    drupal_add_html_head_link(array(
      'rel' => 'alternate',
      'type' => 'application/rss+xml',
      'title' => $title,
      // Force the URL to be absolute, for consistency with other <link> tags
      // output by Drupal.
      'href' => url($url, array('absolute' => TRUE)),
    ));
  }
  return $stored_feed_links;
}

/**
 * Gets the feed URLs for the current page.
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
 * Processes a URL query parameter array to remove unwanted elements.
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
 * Splits a URL-encoded query string into an array.
 *
 * @param $query
 *   The query string to split.
 *
 * @return
 *   An array of URL decoded couples $param_name => $value.
 */
function drupal_get_query_array($query) {
  $result = array();
  if (!empty($query)) {
    foreach (explode('&', $query) as $param) {
      $param = explode('=', $param, 2);
      $result[$param[0]] = isset($param[1]) ? rawurldecode($param[1]) : '';
    }
  }
  return $result;
}

/**
 * Parses an array into a valid, rawurlencoded query string.
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
 * Prepares a 'destination' URL query parameter for use with drupal_goto().
 *
 * Used to direct the user back to the referring page after completing a form.
 * By default the current URL is returned. If a destination exists in the
 * previous request, that destination is returned. As such, a destination can
 * persist across multiple pages.
 *
 * @return
 *   An associative array containing the key:
 *   - destination: The path provided via the destination query string or, if
 *     not available, the current path.
 *
 * @see current_path()
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
 * Parses a URL string into its path, query, and fragment components.
 *
 * This function splits both internal paths like @code node?b=c#d @endcode and
 * external URLs like @code https://example.com/a?b=c#d @endcode into their
 * component parts. See
 * @link http://tools.ietf.org/html/rfc3986#section-3 RFC 3986 @endlink for an
 * explanation of what the component parts are.
 *
 * Note that, unlike the RFC, when passed an external URL, this function
 * groups the scheme, authority, and path together into the path component.
 *
 * @param string $url
 *   The internal path or external URL string to parse.
 *
 * @return array
 *   An associative array containing:
 *   - path: The path component of $url. If $url is an external URL, this
 *     includes the scheme, authority, and path.
 *   - query: An array of query parameters from $url, if they exist.
 *   - fragment: The fragment component from $url, if it exists.
 *
 * @see drupal_goto()
 * @see l()
 * @see url()
 * @see http://tools.ietf.org/html/rfc3986
 *
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
 * Sends the user to a different page.
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
 *   (optional) A Drupal path or a full URL, which will be passed to url() to
 *   compute the redirect for the URL.
 * @param $options
 *   (optional) An associative array of additional URL options to pass to url().
 * @param $http_response_code
 *   (optional) The HTTP status code to use for the redirection, defaults to
 *   302. The valid values for 3xx redirection status codes are defined in
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

  // In some cases modules call drupal_goto(current_path()). We need to ensure
  // that such a redirect is not to an external URL.
  if ($path === current_path() && empty($options['external']) && url_is_external($path)) {
    // Force url() to generate a non-external URL.
    $options['external'] = FALSE;
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
 * Delivers a "site is under maintenance" message to the browser.
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
 * Delivers a "page not found" error to the browser.
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
 * Delivers an "access denied" error to the browser.
 *
 * Page callback functions wanting to report an "access denied" message should
 * return MENU_ACCESS_DENIED instead of calling drupal_access_denied(). However,
 * functions that are invoked in contexts where that return value might not
 * bubble up to menu_execute_active_handler() should call
 * drupal_access_denied().
 */
function drupal_access_denied() {
  drupal_deliver_page(MENU_ACCESS_DENIED);
}

/**
 * Performs an HTTP request.
 *
 * This is a flexible and powerful HTTP client implementation. Correctly
 * handles GET, POST, PUT or any other HTTP requests. Handles redirects.
 *
 * @param $url
 *   A string containing a fully qualified URI.
 * @param array $options
 *   (optional) An array that can have one or more of the following elements:
 *   - headers: An array containing request headers to send as name/value pairs.
 *   - method: A string containing the request method. Defaults to 'GET'.
 *   - data: A string containing the request body, formatted as
 *     'param=value&param=value&...'; to generate this, use http_build_query().
 *     Defaults to NULL.
 *   - max_redirects: An integer representing how many times a redirect
 *     may be followed. Defaults to 3.
 *   - timeout: A float representing the maximum number of seconds the function
 *     call may take. The default is 30 seconds. If a timeout occurs, the error
 *     code is set to the HTTP_REQUEST_TIMEOUT constant.
 *   - context: A context resource created with stream_context_create().
 *
 * @return object
 *   An object that can have one or more of the following components:
 *   - request: A string containing the request body that was sent.
 *   - code: An integer containing the response status code, or the error code
 *     if an error occurred.
 *   - protocol: The response protocol (e.g. HTTP/1.1 or HTTP/1.0).
 *   - status_message: The status message from the response, if a response was
 *     received.
 *   - redirect_code: If redirected, an integer containing the initial response
 *     status code.
 *   - redirect_url: If redirected, a string containing the URL of the redirect
 *     target.
 *   - error: If an error occurred, the error message. Otherwise not set.
 *   - headers: An array containing the response headers as name/value pairs.
 *     HTTP header names are case-insensitive (RFC 2616, section 4.2), so for
 *     easy access the array keys are returned in lower case.
 *   - data: A string containing the response body that was received.
 *
 * @see http_build_query()
 */
function drupal_http_request($url, array $options = array()) {
  // Allow an alternate HTTP client library to replace Drupal's default
  // implementation.
  $override_function = variable_get('drupal_http_request_function', FALSE);
  if (!empty($override_function) && function_exists($override_function)) {
    return $override_function($url, $options);
  }

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
    'timeout' => 30.0,
    'context' => NULL,
  );

  // Merge the default headers.
  $options['headers'] += array(
    'User-Agent' => 'Drupal (+http://drupal.org/)',
  );

  // stream_socket_client() requires timeout to be a float.
  $options['timeout'] = (float) $options['timeout'];

  // Use a proxy if one is defined and the host is not on the excluded list.
  $proxy_server = variable_get('proxy_server', '');
  if ($proxy_server && _drupal_http_use_proxy($uri['host'])) {
    // Set the scheme so we open a socket to the proxy server.
    $uri['scheme'] = 'proxy';
    // Set the path to be the full URL.
    $uri['path'] = $url;
    // Since the URL is passed as the path, we won't use the parsed query.
    unset($uri['query']);

    // Add in username and password to Proxy-Authorization header if needed.
    if ($proxy_username = variable_get('proxy_username', '')) {
      $proxy_password = variable_get('proxy_password', '');
      $options['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode($proxy_username . (!empty($proxy_password) ? ":" . $proxy_password : ''));
    }
    // Some proxies reject requests with any User-Agent headers, while others
    // require a specific one.
    $proxy_user_agent = variable_get('proxy_user_agent', '');
    // The default value matches neither condition.
    if ($proxy_user_agent === NULL) {
      unset($options['headers']['User-Agent']);
    }
    elseif ($proxy_user_agent) {
      $options['headers']['User-Agent'] = $proxy_user_agent;
    }
  }

  switch ($uri['scheme']) {
    case 'proxy':
      // Make the socket connection to a proxy server.
      $socket = 'tcp://' . $proxy_server . ':' . variable_get('proxy_port', 8080);
      // The Host header still needs to match the real request.
      $options['headers']['Host'] = $uri['host'];
      $options['headers']['Host'] .= isset($uri['port']) && $uri['port'] != 80 ? ':' . $uri['port'] : '';
      break;

    case 'http':
    case 'feed':
      $port = isset($uri['port']) ? $uri['port'] : 80;
      $socket = 'tcp://' . $uri['host'] . ':' . $port;
      // RFC 2616: "non-standard ports MUST, default ports MAY be included".
      // We don't add the standard port to prevent from breaking rewrite rules
      // checking the host that do not take into account the port number.
      $options['headers']['Host'] = $uri['host'] . ($port != 80 ? ':' . $port : '');
      break;

    case 'https':
      // Note: Only works when PHP is compiled with OpenSSL support.
      $port = isset($uri['port']) ? $uri['port'] : 443;
      $socket = 'ssl://' . $uri['host'] . ':' . $port;
      $options['headers']['Host'] = $uri['host'] . ($port != 443 ? ':' . $port : '');
      break;

    default:
      $result->error = 'invalid schema ' . $uri['scheme'];
      $result->code = -1003;
      return $result;
  }

  if (empty($options['context'])) {
    $fp = @stream_socket_client($socket, $errno, $errstr, $options['timeout']);
  }
  else {
    // Create a stream with context. Allows verification of a SSL certificate.
    $fp = @stream_socket_client($socket, $errno, $errstr, $options['timeout'], STREAM_CLIENT_CONNECT, $options['context']);
  }

  // Make sure the socket opened properly.
  if (!$fp) {
    // When a network error occurs, we use a negative number so it does not
    // clash with the HTTP status codes.
    $result->code = -$errno;
    $result->error = trim($errstr) ? trim($errstr) : t('Error opening socket @socket', array('@socket' => $socket));

    // Mark that this request failed. This will trigger a check of the web
    // server's ability to make outgoing HTTP requests the next time that
    // requirements checking is performed.
    // See system_requirements().
    variable_set('drupal_http_request_fails', TRUE);

    return $result;
  }

  // Construct the path to act on.
  $path = isset($uri['path']) ? $uri['path'] : '/';
  if (isset($uri['query'])) {
    $path .= '?' . $uri['query'];
  }

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
    $options['headers']['Authorization'] = 'Basic ' . base64_encode($uri['user'] . (isset($uri['pass']) ? ':' . $uri['pass'] : ':'));
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
  // Calculate how much time is left of the original timeout value.
  $timeout = $options['timeout'] - timer_read(__FUNCTION__) / 1000;
  if ($timeout > 0) {
    stream_set_timeout($fp, floor($timeout), floor(1000000 * fmod($timeout, 1)));
    fwrite($fp, $request);
  }

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
  // Be tolerant of malformed HTTP responses that separate header and body with
  // \n\n or \r\r instead of \r\n\r\n.
  list($response, $result->data) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
  $response = preg_split("/\r\n|\n|\r/", $response);

  // Parse the response status line.
  $response_status_array = _drupal_parse_response_status(trim(array_shift($response)));
  $result->protocol = $response_status_array['http_version'];
  $result->status_message = $response_status_array['reason_phrase'];
  $code = $response_status_array['response_code'];

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
    case 201: // Created
    case 202: // Accepted
    case 203: // Non-Authoritative Information
    case 204: // No Content
    case 205: // Reset Content
    case 206: // Partial Content
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
      if (!isset($result->redirect_url)) {
        $result->redirect_url = $location;
      }
      break;
    default:
      $result->error = $result->status_message;
  }

  return $result;
}

/**
 * Splits an HTTP response status line into components.
 *
 * See the @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html status line definition @endlink
 * in RFC 2616.
 *
 * @param string $respone
 *   The response status line, for example 'HTTP/1.1 500 Internal Server Error'.
 *
 * @return array
 *   Keyed array containing the component parts. If the response is malformed,
 *   all possible parts will be extracted. 'reason_phrase' could be empty.
 *   Possible keys:
 *   - 'http_version'
 *   - 'response_code'
 *   - 'reason_phrase'
 */
function _drupal_parse_response_status($response) {
  $response_array = explode(' ', trim($response), 3);
  // Set up empty values.
  $result = array(
    'reason_phrase' => '',
  );
  $result['http_version'] = $response_array[0];
  $result['response_code'] = $response_array[1];
  if (isset($response_array[2])) {
    $result['reason_phrase'] = $response_array[2];
  }
  return $result;
}

/**
 * Helper function for determining hosts excluded from needing a proxy.
 *
 * @return
 *   TRUE if a proxy should be used for this host.
 */
function _drupal_http_use_proxy($host) {
  $proxy_exceptions = variable_get('proxy_exceptions', array('localhost', '127.0.0.1'));
  return !in_array(strtolower($host), $proxy_exceptions, TRUE);
}

/**
 * @} End of "HTTP handling".
 */

/**
 * Strips slashes from a string or array of strings.
 *
 * Callback for array_walk() within fix_gpx_magic().
 *
 * @param $item
 *   An individual string or array of strings from superglobals.
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
 * Strips slashes from $_FILES items.
 *
 * Callback for array_walk() within fix_gpc_magic().
 *
 * The tmp_name key is skipped keys since PHP generates single backslashes for
 * file paths on Windows systems.
 *
 * @param $item
 *   An item from $_FILES.
 * @param $key
 *   The key for the item within $_FILES.
 *
 * @see http://php.net/manual/features.file-upload.php#42280
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
 * Fixes double-escaping caused by "magic quotes" in some PHP installations.
 *
 * @see _fix_gpc_magic()
 * @see _fix_gpc_magic_files()
 */
function fix_gpc_magic() {
  static $fixed = FALSE;
  if (!$fixed && ini_get('magic_quotes_gpc')) {
    array_walk($_GET, '_fix_gpc_magic');
    array_walk($_POST, '_fix_gpc_magic');
    array_walk($_COOKIE, '_fix_gpc_magic');
    array_walk($_REQUEST, '_fix_gpc_magic');
    array_walk($_FILES, '_fix_gpc_magic_files');
  }
  $fixed = TRUE;
}

/**
 * @defgroup validation Input validation
 * @{
 * Functions to validate user input.
 */

/**
 * Verifies the syntax of the given e-mail address.
 *
 * This uses the
 * @link http://php.net/manual/filter.filters.validate.php PHP e-mail validation filter. @endlink
 *
 * @param $mail
 *   A string containing an e-mail address.
 *
 * @return
 *   TRUE if the address is in a valid format.
 */
function valid_email_address($mail) {
  return (bool)filter_var($mail, FILTER_VALIDATE_EMAIL);
}

/**
 * Verifies the syntax of the given URL.
 *
 * This function should only be used on actual URLs. It should not be used for
 * Drupal menu paths, which can contain arbitrary characters.
 * Valid values per RFC 3986.
 * @param $url
 *   The URL to verify.
 * @param $absolute
 *   Whether the URL is absolute (beginning with a scheme such as "http:").
 *
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
 * Registers an event for the current visitor to the flood control mechanism.
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
 * Makes the flood control mechanism forget an event for the current visitor.
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
 * Checks whether a user is allowed to proceed with the specified event.
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
    $allowed_protocols = array_flip(variable_get('filter_allowed_protocols', array('ftp', 'http', 'https', 'irc', 'mailto', 'news', 'nntp', 'rtsp', 'sftp', 'ssh', 'tel', 'telnet', 'webcal')));
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
 * Strips dangerous protocols from a URI and encodes it for output to HTML.
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
 * Applies a very permissive XSS/HTML filter for admin-only use.
 *
 * Use only for fields where it is impractical to use the
 * whole filter system, but where some (mainly inline) mark-up
 * is desired (so check_plain() is not acceptable).
 *
 * Allows all tags that can be used inside an HTML body, save
 * for scripts and styles.
 */
function filter_xss_admin($string) {
  return filter_xss($string, array('a', 'abbr', 'acronym', 'address', 'article', 'aside', 'b', 'bdi', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'command', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'mark', 'menu', 'meter', 'nav', 'ol', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'small', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr', 'tt', 'u', 'ul', 'var', 'wbr'));
}

/**
 * Filters HTML to prevent cross-site-scripting (XSS) vulnerabilities.
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
 */
function filter_xss($string, $allowed_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd')) {
  // Only operate on valid UTF-8 strings. This is necessary to prevent cross
  // site scripting issues on Internet Explorer 6.
  if (!drupal_validate_utf8($string)) {
    return '';
  }
  // Store the text format.
  _filter_xss_split($allowed_tags, TRUE);
  // Remove NULL characters (ignored by some browsers).
  $string = str_replace(chr(0), '', $string);
  // Remove Netscape 4 JS entities.
  $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

  // Defuse all HTML entities.
  $string = str_replace('&', '&amp;', $string);
  // Change back only well-formed entities in our whitelist:
  // Decimal numeric entities.
  $string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
  // Hexadecimal numeric entities.
  $string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
  // Named entities.
  $string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);

  return preg_replace_callback('%
    (
    <(?=[^a-zA-Z!/])  # a lone <
    |                 # or
    <!--.*?-->        # a comment
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
 *
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
    // We matched a lone ">" character.
    return '&gt;';
  }
  elseif (strlen($string) == 1) {
    // We matched a lone "<" character.
    return '&lt;';
  }

  if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9\-]+)([^>]*)>?|(<!--.*?-->)$%', $string, $matches)) {
    // Seriously malformed.
    return '';
  }

  $slash = trim($matches[1]);
  $elem = &$matches[2];
  $attrlist = &$matches[3];
  $comment = &$matches[4];

  if ($comment) {
    $elem = '!--';
  }

  if (!isset($allowed_html[strtolower($elem)])) {
    // Disallowed HTML element.
    return '';
  }

  if ($comment) {
    return $comment;
  }

  if ($slash != '') {
    return "</$elem>";
  }

  // Is there a closing XHTML slash at the end of the attributes?
  $attrlist = preg_replace('%(\s?)/\s*$%', '\1', $attrlist, -1, $count);
  $xhtml_slash = $count ? ' /' : '';

  // Clean up attributes.
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
        // Attribute name, href for instance.
        if (preg_match('/^([-a-zA-Z]+)/', $attr, $match)) {
          $attrname = strtolower($match[1]);
          $skip = ($attrname == 'style' || substr($attrname, 0, 2) == 'on');
          $working = $mode = 1;
          $attr = preg_replace('/^[-a-zA-Z]+/', '', $attr);
        }
        break;

      case 1:
        // Equals sign or valueless ("selected").
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
        // Attribute value, a URL after href= for instance.
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
      // Not well formed; remove and try again.
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
 * Processes an HTML attribute value and strips dangerous protocols from URLs.
 *
 * @param $string
 *   The string with the attribute value.
 * @param $decode
 *   (deprecated) Whether to decode entities in the $string. Set to FALSE if the
 *   $string is in plain text, TRUE otherwise. Defaults to TRUE. This parameter
 *   is deprecated and will be removed in Drupal 8. To process a plain-text URI,
 *   call drupal_strip_dangerous_protocols() or check_url() instead.
 *
 * @return
 *   Cleaned up and HTML-escaped version of $string.
 */
function filter_xss_bad_protocol($string, $decode = TRUE) {
  // Get the plain text representation of the attribute value (i.e. its meaning).
  // @todo Remove the $decode parameter in Drupal 8, and always assume an HTML
  //   string that needs decoding.
  if ($decode) {
    if (!function_exists('decode_entities')) {
      require_once DRUPAL_ROOT . '/includes/unicode.inc';
    }

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
  global $language_content;
  $langcode = $langcode ? $langcode : $language_content->language;

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
 * Formats a single RSS item.
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
 * Formats XML elements.
 *
 * @param $array
 *   An array where each item represents an element and is either a:
 *   - (key => value) pair (<key>value</key>)
 *   - Associative array with fields:
 *     - 'key': element name
 *     - 'value': element contents
 *     - 'attributes': associative array of element attributes
 *     - 'encoded': TRUE if 'value' is already encoded
 *
 * In both cases, 'value' can be a simple string, or it can be another array
 * with the same format as $array itself for nesting.
 *
 * If 'encoded' is TRUE it is up to the caller to ensure that 'value' is either
 * entity-encoded or CDATA-escaped. Using this option is not recommended when
 * working with untrusted user input, since failing to escape the data
 * correctly has security implications.
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
          $output .= '>' . (is_array($value['value']) ? format_xml_elements($value['value']) : (!empty($value['encoded']) ? $value['value'] : check_plain($value['value']))) . '</' . $value['key'] . ">\n";
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
 * Formats a string containing a count of items.
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
 *     array('%old-type' => $info->old_type, '%new-type' => $info->new_type));
 * @endcode
 *
 * @param $count
 *   The item count to display.
 * @param $singular
 *   The string for the singular case. Make sure it is clear this is singular,
 *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
 *   use @count in the singular string.
 * @param $plural
 *   The string for the plural case. Make sure it is clear this is plural, to
 *   ease translation. Use @count in place of the item count, as in
 *   "@count new comments".
 * @param $args
 *   An associative array of replacements to make after translation. Instances
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or
 *   themed. See format_string(). Note that you do not need to include @count
 *   in this array; this replacement is done automatically for the plural case.
 * @param $options
 *   An associative array of additional options. See t() for allowed keys.
 *
 * @return
 *   A translated string.
 *
 * @see t()
 * @see format_string()
 */
function format_plural($count, $singular, $plural, array $args = array(), array $options = array()) {
  $args['@count'] = $count;
  if ($count == 1) {
    return t($singular, $args, $options);
  }

  // Get the plural index through the gettext formula.
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, isset($options['langcode']) ? $options['langcode'] : NULL) : -1;
  // If the index cannot be computed, use the plural as a fallback (which
  // allows for most flexiblity with the replaceable @count value).
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
 * Parses a given byte count.
 *
 * @param $size
 *   A size expressed as a number of bytes with optional SI or IEC binary unit
 *   prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8 bytes, 9mbytes).
 *
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
 * Generates a string representation for the given byte count.
 *
 * @param $size
 *   A size in bytes.
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 *
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
 * Formats a time interval with the requested granularity.
 *
 * @param $interval
 *   The length of the interval in seconds.
 * @param $granularity
 *   How many different units to display in the string.
 * @param $langcode
 *   Optional language code to translate to a language other than
 *   what is used to display the page.
 *
 * @return
 *   A translated string representation of the interval.
 */
function format_interval($interval, $granularity = 2, $langcode = NULL) {
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
    if ($interval >= $value) {
      $output .= ($output ? ' ' : '') . format_plural(floor($interval / $value), $key[0], $key[1], array(), array('langcode' => $langcode));
      $interval %= $value;
      $granularity--;
    }

    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : t('0 sec', array(), array('langcode' => $langcode));
}

/**
 * Formats a date, using a date type or a custom date format string.
 *
 * @param $timestamp
 *   A UNIX timestamp to format.
 * @param $type
 *   (optional) The format to use, one of:
 *   - 'short', 'medium', or 'long' (the corresponding built-in date formats).
 *   - The name of a date type defined by a module in hook_date_format_types(),
 *     if it's been assigned a format.
 *   - The machine name of an administrator-defined date format.
 *   - 'custom', to use $format.
 *   Defaults to 'medium'.
 * @param $format
 *   (optional) If $type is 'custom', a PHP date format string suitable for
 *   input to date(). Use a backslash to escape ordinary text, so it does not
 *   get interpreted as date format characters.
 * @param $timezone
 *   (optional) Time zone identifier, as described at
 *   http://php.net/manual/timezones.php Defaults to the time zone used to
 *   display the page.
 * @param $langcode
 *   (optional) Language code to translate to. Defaults to the language used to
 *   display the page.
 *
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
      // Retrieve the format of the custom $type passed.
      if ($type != 'medium') {
        $format = variable_get('date_format_' . $type, '');
      }
      // Fall back to 'medium'.
      if ($format === '') {
        $format = variable_get('date_format_medium', 'D, m/d/Y - H:i');
      }
      break;
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
 * Callback for use within hook_rdf_mapping() implementations.
 *
 * @param $date
 *   A UNIX timestamp.
 *
 * @return string
 *   An ISO8601 formatted date.
 */
function date_iso8601($date) {
  // The DATE_ISO8601 constant cannot be used here because it does not match
  // date('c') and produces invalid RDF markup.
  return date('c', $date);
}

/**
 * Translates a formatted date string.
 *
 * Callback for preg_replace_callback() within format_date().
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
 * This is also the label callback implementation of
 * callback_entity_info_label() for user_entity_info().
 *
 * By default, the passed-in object's 'name' property is used if it exists, or
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
 *     for the URL. If $options['language'] is omitted, the global $language_url
 *     will be used.
 *   - 'https': Whether this URL should point to a secure location. If not
 *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
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
 *   - 'entity_type': The entity type of the object that called url(). Only
 *     set if url() is invoked by entity_uri().
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
    $options['external'] = url_is_external($path);
  }

  // Preserve the original path before altering or aliasing.
  $original_path = $path;

  // Allow other modules to alter the outbound URL and options.
  drupal_alter('url_outbound', $path, $options, $original_path);

  if (isset($options['fragment']) && $options['fragment'] !== '') {
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

  // Strip leading slashes from internal paths to prevent them becoming external
  // URLs without protocol. /example.com should not be turned into
  // //example.com.
  $path = ltrim($path, '/');

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
 * Returns TRUE if a path is external to Drupal (e.g. http://example.com).
 *
 * If a path cannot be assessed by Drupal's menu handler, then we must
 * treat it as potentially insecure.
 *
 * @param $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo".
 *
 * @return
 *   Boolean TRUE or FALSE, where TRUE indicates an external path.
 */
function url_is_external($path) {
  $colonpos = strpos($path, ':');
  // Some browsers treat \ as / so normalize to forward slashes.
  $path = str_replace('\\', '/', $path);
  // If the path starts with 2 slashes then it is always considered an external
  // URL without an explicit protocol part.
  return (strpos($path, '//') === 0)
    // Leading control characters may be ignored or mishandled by browsers, so
    // assume such a path may lead to an external location. The \p{C} character
    // class matches all UTF-8 control, unassigned, and private characters.
    || (preg_match('/^\p{C}/u', $path) !== 0)
    // Avoid calling drupal_strip_dangerous_protocols() if there is any slash
    // (/), hash (#) or question_mark (?) before the colon (:) occurrence - if
    // any - as this would clearly mean it is not a URL.
    || ($colonpos !== FALSE
      && !preg_match('![/?#]!', substr($path, 0, $colonpos))
      && drupal_strip_dangerous_protocols($path) == $path);
}

/**
 * Formats an attribute string for an HTTP header.
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
 * Converts an associative array to an XML/HTML tag attribute string.
 *
 * Each array key and its value will be formatted into an attribute string.
 * If a value is itself an array, then its elements are concatenated to a single
 * space-delimited string (for example, a class attribute with multiple values).
 *
 * Attribute values are sanitized by running them through check_plain().
 * Attribute names are not automatically sanitized. When using user-supplied
 * attribute names, it is strongly recommended to allow only white-listed names,
 * since certain attributes carry security risks and can be abused.
 *
 * Examples of security aspects when using drupal_attributes:
 * @code
 *   // By running the value in the following statement through check_plain,
 *   // the malicious script is neutralized.
 *   drupal_attributes(array('title' => t('<script>steal_cookie();</script>')));
 *
 *   // The statement below demonstrates dangerous use of drupal_attributes, and
 *   // will return an onmouseout attribute with JavaScript code that, when used
 *   // as attribute in a tag, will cause users to be redirected to another site.
 *   //
 *   // In this case, the 'onmouseout' attribute should not be whitelisted --
 *   // you don't want users to have the ability to add this attribute or others
 *   // that take JavaScript commands.
 *   drupal_attributes(array('onmouseout' => 'window.location="http://malicious.com/";')));
 * @endcode
 *
 * @param $attributes
 *   An associative array of key-value pairs to be converted to attributes.
 *
 * @return
 *   A string ready for insertion in a tag (starts with a space).
 *
 * @ingroup sanitization
 */
function drupal_attributes(array $attributes = array()) {
  foreach ($attributes as $attribute => &$data) {
    $data = implode(' ', (array) $data);
    $data = $attribute . '="' . check_plain($data) . '"';
  }
  return $attributes ? ' ' . implode(' ', $attributes) : '';
}

/**
 * Formats an internal or external URL link as an HTML anchor tag.
 *
 * This function correctly handles aliased paths and adds an 'active' class
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
 * @param string $text
 *   The translated link text for the anchor tag.
 * @param string $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo". After the url() function is called to construct
 *   the URL from $path and $options, the resulting URL is passed through
 *   check_plain() before it is inserted into the HTML anchor tag, to ensure
 *   well-formed HTML. See url() for more information and notes.
 * @param array $options
 *   An associative array of additional options. Defaults to an empty array. It
 *   may contain the following elements.
 *   - 'attributes': An associative array of HTML attributes to apply to the
 *     anchor tag. If element 'class' is included, it must be an array; 'title'
 *     must be a string; other elements are more flexible, as they just need
 *     to work in a call to drupal_attributes($options['attributes']).
 *   - 'html' (default FALSE): Whether $text is HTML or just plain-text. For
 *     example, to make an image tag into a link, this must be set to TRUE, or
 *     you will see the escaped HTML image tag. $text is not sanitized if
 *     'html' is TRUE. The calling function must ensure that $text is already
 *     safe.
 *   - 'language': An optional language object. If the path being linked to is
 *     internal to the site, $options['language'] is used to determine whether
 *     the link is "active", or pointing to the current page (the language as
 *     well as the path must match). This element is also used by url().
 *   - Additional $options elements used by the url() function.
 *
 * @return string
 *   An HTML string containing a link to the given path.
 *
 * @see url()
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
      $registry = theme_get_registry(FALSE);
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
 * Delivers a page callback result to the browser in the appropriate format.
 *
 * This function is most commonly called by menu_execute_active_handler(), but
 * can also be called by error conditions such as drupal_not_found(),
 * drupal_access_denied(), and drupal_site_offline().
 *
 * When a user requests a page, index.php calls menu_execute_active_handler(),
 * which calls the 'page callback' function registered in hook_menu(). The page
 * callback function can return one of:
 * - NULL: to indicate no content.
 * - An integer menu status constant: to indicate an error condition.
 * - A string of HTML content.
 * - A renderable array of content.
 * Returning a renderable array rather than a string of HTML is preferred,
 * because that provides modules with more flexibility in customizing the final
 * result.
 *
 * When the page callback returns its constructed content to
 * menu_execute_active_handler(), this function gets called. The purpose of
 * this function is to determine the most appropriate 'delivery callback'
 * function to route the content to. The delivery callback function then
 * sends the content to the browser in the needed format. The default delivery
 * callback is drupal_deliver_html_page(), which delivers the content as an HTML
 * page, complete with blocks in addition to the content. This default can be
 * overridden on a per menu router item basis by setting 'delivery callback' in
 * hook_menu() or hook_menu_alter(), and can also be overridden on a per request
 * basis in hook_page_delivery_callback_alter().
 *
 * For example, the same page callback function can be used for an HTML
 * version of the page and an Ajax version of the page. The page callback
 * function just needs to decide what content is to be returned and the
 * delivery callback function will send it as an HTML page or an Ajax
 * response, as appropriate.
 *
 * In order for page callbacks to be reusable in different delivery formats,
 * they should not issue any "print" or "echo" statements, but instead just
 * return content.
 *
 * Also note that this function does not perform access checks. The delivery
 * callback function specified in hook_menu(), hook_menu_alter(), or
 * hook_page_delivery_callback_alter() will be called even if the router item
 * access checks fail. This is intentional (it is needed for JSON and other
 * purposes), but it has security implications. Do not call this function
 * directly unless you understand the security implications, and be careful in
 * writing delivery callbacks, so that they do not violate security. See
 * drupal_deliver_html_page() for an example of a delivery callback that
 * respects security.
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
    watchdog('delivery callback not found', 'callback %callback not found: %q.', array('%callback' => $delivery_callback, '%q' => $_GET['q']), WATCHDOG_ERROR);
  }
}

/**
 * Packages and sends the result of a page callback to the browser as HTML.
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

  // Send appropriate HTTP-Header for browsers and search engines.
  global $language;
  drupal_add_http_header('Content-Language', $language->language);

  // By default, do not allow the site to be rendered in an iframe on another
  // domain, but provide a variable to override this. If the code running for
  // this page request already set the X-Frame-Options header earlier, don't
  // overwrite it here.
  $frame_options = variable_get('x_frame_options', 'SAMEORIGIN');
  if ($frame_options && is_null(drupal_get_http_header('X-Frame-Options'))) {
    drupal_add_http_header('X-Frame-Options', $frame_options);
  }

  // Menu status constants are integers; page content is a string or array.
  if (is_int($page_callback_result)) {
    // @todo: Break these up into separate functions?
    switch ($page_callback_result) {
      case MENU_NOT_FOUND:
        // Print a 404 page.
        drupal_add_http_header('Status', '404 Not Found');

        watchdog('page not found', check_plain($_GET['q']), NULL, WATCHDOG_WARNING);

        // Check for and return a fast 404 page if configured.
        drupal_fast_404();

        // Keep old path for reference, and to allow forms to redirect to it.
        if (!isset($_GET['destination'])) {
          // Make sure that the current path is not interpreted as external URL.
          if (!url_is_external($_GET['q'])) {
            $_GET['destination'] = $_GET['q'];
          }
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
          $return = t('The requested page "@path" could not be found.', array('@path' => request_uri()));
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
          // Make sure that the current path is not interpreted as external URL.
          if (!url_is_external($_GET['q'])) {
            $_GET['destination'] = $_GET['q'];
          }
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
 * Performs end-of-request tasks.
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
  drupal_file_scan_write_cache();
  system_run_automated_cron();
}

/**
 * Performs end-of-request tasks.
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
 * Forms an associative array from a linear array.
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
 * If the current time limit is not unlimited it is possible to decrease the
 * total time limit if the sum of the new time limit and the current time spent
 * running the script is inferior to the original time limit. It is inherent to
 * the way set_time_limit() works, it should rather be called with an
 * appropriate value every time you need to allocate a certain amount of time
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
    $current = ini_get('max_execution_time');
    // Do not set time limit if it is currently unlimited.
    if ($current != 0) {
      @set_time_limit($time_limit);
    }
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
 *   The path to the requested item or an empty string if the item is not found.
 */
function drupal_get_path($type, $name) {
  return dirname(drupal_get_filename($type, $name));
}

/**
 * Returns the base URL path (i.e., directory) of the Drupal installation.
 *
 * base_path() adds a "/" to the beginning and end of the returned path if the
 * path is not empty. At the very least, this will return "/".
 *
 * Examples:
 * - http://example.com returns "/" because the path is empty.
 * - http://example.com/drupal/folder returns "/drupal/folder/".
 */
function base_path() {
  return $GLOBALS['base_path'];
}

/**
 * Adds a LINK tag with a distinct 'rel' attribute to the page's HEAD.
 *
 * This function can be called as long the HTML header hasn't been sent, which
 * on normal pages is up through the preprocess step of theme('html'). Adding
 * a link will overwrite a prior link with the exact same 'rel' and 'href'
 * attributes.
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
 * If CSS aggregation/compression is enabled, all cascading style sheets added
 * with $options['preprocess'] set to TRUE will be merged into one aggregate
 * file and compressed by removing all extraneous white space.
 * Preprocessed inline stylesheets will not be aggregated into this single file;
 * instead, they are just compressed upon output on the page. Externally hosted
 * stylesheets are never aggregated or compressed.
 *
 * The reason for aggregating the files is outlined quite thoroughly here:
 * http://www.die.net/musings/page_load_time/ "Load fewer external objects. Due
 * to request overhead, one bigger file just loads faster than two smaller ones
 * half its size."
 *
 * $options['preprocess'] should be only set to TRUE when a file is required for
 * all typical visitors and most pages of a site. It is critical that all
 * preprocessed files are added unconditionally on every page, even if the
 * files do not happen to be needed on a page. This is normally done by calling
 * drupal_add_css() in a hook_init() implementation.
 *
 * Non-preprocessed files should only be added to the page when they are
 * actually needed.
 *
 * @param $data
 *   (optional) The stylesheet data to be added, depending on what is passed
 *   through to the $options['type'] parameter:
 *   - 'file': The path to the CSS file relative to the base_path(), or a
 *     stream wrapper URI. For example: "modules/devel/devel.css" or
 *     "public://generated_css/stylesheet_1.css". Note that Modules should
 *     always prefix the names of their CSS files with the module name; for
 *     example, system-menus.css rather than simply menus.css. Themes can
 *     override module-supplied CSS files based on their filenames, and this
 *     prefixing helps prevent confusing name collisions for theme developers.
 *     See drupal_get_css() where the overrides are performed. Also, if the
 *     direction of the current language is right-to-left (Hebrew, Arabic,
 *     etc.), the function will also look for an RTL CSS file and append it to
 *     the list. The name of this file should have an '-rtl.css' suffix. For
 *     example, a CSS file called 'mymodule-name.css' will have a
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
 *   - 'group': A number identifying the group in which to add the stylesheet.
 *     Available constants are:
 *     - CSS_SYSTEM: Any system-layer CSS.
 *     - CSS_DEFAULT: (default) Any module-layer CSS.
 *     - CSS_THEME: Any theme-layer CSS.
 *     The group number serves as a weight: the markup for loading a stylesheet
 *     within a lower weight group is output to the page before the markup for
 *     loading a stylesheet within a higher weight group, so CSS within higher
 *     weight groups take precendence over CSS within lower weight groups.
 *   - 'every_page': For optimal front-end performance when aggregation is
 *     enabled, this should be set to TRUE if the stylesheet is present on every
 *     page of the website for users for whom it is present at all. This
 *     defaults to FALSE. It is set to TRUE for stylesheets added via module and
 *     theme .info files. Modules that add stylesheets within hook_init()
 *     implementations, or from other code that ensures that the stylesheet is
 *     added to all website pages, should also set this flag to TRUE. All
 *     stylesheets within the same group that have the 'every_page' flag set to
 *     TRUE and do not have 'preprocess' set to FALSE are aggregated together
 *     into a single aggregate file, and that aggregate file can be reused
 *     across a user's entire site visit, leading to faster navigation between
 *     pages. However, stylesheets that are only needed on pages less frequently
 *     visited, can be added by code that only runs for those particular pages,
 *     and that code should not set the 'every_page' flag. This minimizes the
 *     size of the aggregate file that the user needs to download when first
 *     visiting the website. Stylesheets without the 'every_page' flag are
 *     aggregated into a separate aggregate file. This other aggregate file is
 *     likely to change from page to page, and each new aggregate file needs to
 *     be downloaded when first encountered, so it should be kept relatively
 *     small by ensuring that most commonly needed stylesheets are added to
 *     every page.
 *   - 'weight': The weight of the stylesheet specifies the order in which the
 *     CSS will appear relative to other stylesheets with the same group and
 *     'every_page' flag. The exact ordering of stylesheets is as follows:
 *     - First by group.
 *     - Then by the 'every_page' flag, with TRUE coming before FALSE.
 *     - Then by weight.
 *     - Then by the order in which the CSS was added. For example, all else
 *       being the same, a stylesheet added by a call to drupal_add_css() that
 *       happened later in the page request gets added to the page after one for
 *       which drupal_add_css() happened earlier in the page request.
 *   - 'media': The media type for the stylesheet, e.g., all, print, screen.
 *     Defaults to 'all'.
 *   - 'preprocess': If TRUE and CSS aggregation/compression is enabled, the
 *     styles will be aggregated and compressed. Defaults to TRUE.
 *   - 'browsers': An array containing information specifying which browsers
 *     should load the CSS item. See drupal_pre_render_conditional_comments()
 *     for details.
 *
 * @return
 *   An array of queued cascading stylesheets.
 *
 * @see drupal_get_css()
 */
function drupal_add_css($data = NULL, $options = NULL) {
  $css = &drupal_static(__FUNCTION__, array());
  $count = &drupal_static(__FUNCTION__ . '_count', 0);

  // If the $css variable has been reset with drupal_static_reset(), there is
  // no longer any CSS being tracked, so set the counter back to 0 also.
  if (count($css) === 0) {
    $count = 0;
  }

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
      'group' => CSS_DEFAULT,
      'weight' => 0,
      'every_page' => FALSE,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => $data,
      'browsers' => array(),
    );
    $options['browsers'] += array(
      'IE' => TRUE,
      '!IE' => TRUE,
    );

    // Files with a query string cannot be preprocessed.
    if ($options['type'] === 'file' && $options['preprocess'] && strpos($options['data'], '?') !== FALSE) {
      $options['preprocess'] = FALSE;
    }

    // Always add a tiny value to the weight, to conserve the insertion order.
    $options['weight'] += $count / 1000;
    $count++;

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
 * Returns a themed representation of all stylesheets to attach to the page.
 *
 * It loads the CSS in order, with 'module' first, then 'theme' afterwards.
 * This ensures proper cascading of styles so themes can easily override
 * module styles through CSS selectors.
 *
 * Themes may replace module-defined CSS files by adding a stylesheet with the
 * same filename. For example, themes/bartik/system-menus.css would replace
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
 * @param $skip_alter
 *   (optional) If set to TRUE, this function skips calling drupal_alter() on
 *   $css, useful when the calling function passes a $css array that has already
 *   been altered.
 *
 * @return
 *   A string of XHTML CSS tags.
 *
 * @see drupal_add_css()
 */
function drupal_get_css($css = NULL, $skip_alter = FALSE) {
  if (!isset($css)) {
    $css = drupal_add_css();
  }

  // Allow modules and themes to alter the CSS items.
  if (!$skip_alter) {
    drupal_alter('css', $css);
  }

  // Sort CSS items, so that they appear in the correct order.
  uasort($css, 'drupal_sort_css_js');

  // Provide the page with information about the individual CSS files used,
  // information not otherwise available when CSS aggregation is enabled. The
  // setting is attached later in this function, but is set here, so that CSS
  // files removed below are still considered "used" and prevented from being
  // added in a later AJAX request.
  // Skip if no files were added to the page or jQuery.extend() will overwrite
  // the Drupal.settings.ajaxPageState.css object with an empty array.
  if (!empty($css)) {
    // Cast the array to an object to be on the safe side even if not empty.
    $setting['ajaxPageState']['css'] = (object) array_fill_keys(array_keys($css), 1);
  }

  // Remove the overridden CSS files. Later CSS files override former ones.
  $previous_item = array();
  foreach ($css as $key => $item) {
    if ($item['type'] == 'file') {
      // If defined, force a unique basename for this file.
      $basename = isset($item['basename']) ? $item['basename'] : drupal_basename($item['data']);
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

  if (!empty($setting)) {
    $styles['#attached']['js'][] = array('type' => 'setting', 'data' => $setting);
  }

  return drupal_render($styles);
}

/**
 * Sorts CSS and JavaScript resources.
 *
 * Callback for uasort() within:
 * - drupal_get_css()
 * - drupal_get_js()
 *
 * This sort order helps optimize front-end performance while providing modules
 * and themes with the necessary control for ordering the CSS and JavaScript
 * appearing on a page.
 *
 * @param $a
 *   First item for comparison. The compared items should be associative arrays
 *   of member items from drupal_add_css() or drupal_add_js().
 * @param $b
 *   Second item for comparison.
 *
 * @see drupal_add_css()
 * @see drupal_add_js()
 */
function drupal_sort_css_js($a, $b) {
  // First order by group, so that, for example, all items in the CSS_SYSTEM
  // group appear before items in the CSS_DEFAULT group, which appear before
  // all items in the CSS_THEME group. Modules may create additional groups by
  // defining their own constants.
  if ($a['group'] < $b['group']) {
    return -1;
  }
  elseif ($a['group'] > $b['group']) {
    return 1;
  }
  // Within a group, order all infrequently needed, page-specific files after
  // common files needed throughout the website. Separating this way allows for
  // the aggregate file generated for all of the common files to be reused
  // across a site visit without being cut by a page using a less common file.
  elseif ($a['every_page'] && !$b['every_page']) {
    return -1;
  }
  elseif (!$a['every_page'] && $b['every_page']) {
    return 1;
  }
  // Finally, order by weight.
  elseif ($a['weight'] < $b['weight']) {
    return -1;
  }
  elseif ($a['weight'] > $b['weight']) {
    return 1;
  }
  else {
    return 0;
  }
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
 * same type, media, and browsers, if needed to accommodate other items in
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
 * @see system_element_info()
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
        // Help ensure maximum reuse of aggregate files by only grouping
        // together items that share the same 'group' value and 'every_page'
        // flag. See drupal_add_css() for details about that.
        $group_keys = $item['preprocess'] ? array($item['type'], $item['group'], $item['every_page'], $item['media'], $item['browsers']) : FALSE;
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
 * @see system_element_info()
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

  // For inline CSS to validate as XHTML, all CSS containing XHTML needs to be
  // wrapped in CDATA. To make that backwards compatible with HTML 4, we need to
  // comment out the CDATA-tag.
  $embed_prefix = "\n<!--/*--><![CDATA[/*><!--*/\n";
  $embed_suffix = "\n/*]]>*/-->\n";

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
            // A theme's .info file may have an entry for a file that doesn't
            // exist as a way of overriding a module or base theme CSS file from
            // being added to the page. Normally, file_exists() calls that need
            // to run for every page request should be minimized, but this one
            // is okay, because it only runs when CSS aggregation is disabled.
            // On a server under heavy enough load that file_exists() calls need
            // to be minimized, CSS aggregation should be enabled, in which case
            // this code is not run. When aggregation is enabled,
            // drupal_load_stylesheet() checks file_exists(), but only when
            // building the aggregate file, which is then reused for many page
            // requests.
            if (file_exists($item['data'])) {
              // The dummy query string needs to be added to the URL to control
              // browser-caching. IE7 does not support a media type on the
              // @import statement, so we instead specify the media for the
              // group on the STYLE tag.
              $import[] = '@import url("' . check_plain(file_create_url($item['data']) . '?' . $query_string) . '");';
            }
          }
          // In addition to IE's limit of 31 total CSS inclusion tags, it also
          // has a limit of 31 @import statements per STYLE tag.
          while (!empty($import)) {
            $import_batch = array_slice($import, 0, 31);
            $import = array_slice($import, 31);
            $element = $style_element_defaults;
            // This simplifies the JavaScript regex, allowing each line
            // (separated by \n) to be treated as a completely different string.
            // This means that we can use ^ and $ on one line at a time, and not
            // worry about style tags since they'll never match the regex.
            $element['#value'] = "\n" . implode("\n", $import_batch) . "\n";
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
            // We do not check file_exists() here, because this code runs for
            // files whose 'preprocess' is set to FALSE, and therefore, even
            // when aggregation is enabled, and we want to avoid needlessly
            // taxing a server that may be under heavy load. The file_exists()
            // performed above for files whose 'preprocess' is TRUE is done for
            // the benefit of theme .info files, but code that deals with files
            // whose 'preprocess' is FALSE is responsible for ensuring the file
            // exists.
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
          $element['#value_prefix'] = $embed_prefix;
          $element['#value_suffix'] = $embed_suffix;
          $element['#attributes']['media'] = $group['media'];
          $element['#browsers'] = $group['browsers'];
          $elements[] = $element;
        }
        else {
          foreach ($group['items'] as $item) {
            $element = $style_element_defaults;
            $element['#value'] = $item['data'];
            $element['#value_prefix'] = $embed_prefix;
            $element['#value_suffix'] = $embed_suffix;
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
 * variable is emptied to force a rebuild of the cache. Second, the cache file
 * is generated if it is missing on disk. Old cache files are not deleted
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
  // Create a new array so that only the file names are used to create the hash.
  // This prevents new aggregates from being created unnecessarily.
  $css_data = array();
  foreach ($css as $css_file) {
    $css_data[] = $css_file['data'];
  }
  $key = hash('sha256', serialize($css_data));
  if (isset($map[$key])) {
    $uri = $map[$key];
  }

  if (empty($uri) || !file_exists($uri)) {
    // Build aggregate CSS file.
    foreach ($css as $stylesheet) {
      // Only 'file' stylesheets can be aggregated.
      if ($stylesheet['type'] == 'file') {
        $contents = drupal_load_stylesheet($stylesheet['data'], TRUE);

        // Build the base URL of this CSS file: start with the full URL.
        $css_base_url = file_create_url($stylesheet['data']);
        // Move to the parent.
        $css_base_url = substr($css_base_url, 0, strrpos($css_base_url, '/'));
        // Simplify to a relative URL if the stylesheet URL starts with the
        // base URL of the website.
        if (substr($css_base_url, 0, strlen($GLOBALS['base_root'])) == $GLOBALS['base_root']) {
          $css_base_url = substr($css_base_url, strlen($GLOBALS['base_root']));
        }

        _drupal_build_css_path(NULL, $css_base_url . '/');
        // Anchor all paths in the CSS with its base URL, ignoring external and absolute paths.
        $data .= preg_replace_callback('/url\(\s*[\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\s*\)/i', '_drupal_build_css_path', $contents);
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
    // If CSS gzip compression is enabled, clean URLs are enabled (which means
    // that rewrite rules are working) and the zlib extension is available then
    // create a gzipped version of this file. This file is served conditionally
    // to browsers that accept gzip using .htaccess rules.
    if (variable_get('css_gzip_compression', TRUE) && variable_get('clean_url', 0) && extension_loaded('zlib')) {
      if (!file_exists($uri . '.gz') && !file_unmanaged_save_data(gzencode($data, 9, FORCE_GZIP), $uri . '.gz', FILE_EXISTS_REPLACE)) {
        return FALSE;
      }
    }
    // Save the updated map.
    $map[$key] = $uri;
    variable_set('drupal_css_cache_files', $map);
  }
  return $uri;
}

/**
 * Prefixes all paths within a CSS file for drupal_build_css_cache().
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
 * @param $reset_basepath
 *   Used internally to facilitate recursive resolution of @import commands.
 *
 * @return
 *   Contents of the stylesheet, including any resolved @import commands.
 */
function drupal_load_stylesheet($file, $optimize = NULL, $reset_basepath = TRUE) {
  // These statics are not cache variables, so we don't use drupal_static().
  static $_optimize, $basepath;
  if ($reset_basepath) {
    $basepath = '';
  }
  // Store the value of $optimize for preg_replace_callback with nested
  // @import loops.
  if (isset($optimize)) {
    $_optimize = $optimize;
  }

  // Stylesheets are relative one to each other. Start by adding a base path
  // prefix provided by the parent stylesheet (if necessary).
  if ($basepath && !file_uri_scheme($file)) {
    $file = $basepath . '/' . $file;
  }
  // Store the parent base path to restore it later.
  $parent_base_path = $basepath;
  // Set the current base path to process possible child imports.
  $basepath = dirname($file);

  // Load the CSS stylesheet. We suppress errors because themes may specify
  // stylesheets in their .info file that don't exist in the theme's path,
  // but are merely there to disable certain module CSS files.
  $content = '';
  if ($contents = @file_get_contents($file)) {
    // Return the processed stylesheet.
    $content = drupal_load_stylesheet_content($contents, $_optimize);
  }

  // Restore the parent base path as the file and its childen are processed.
  $basepath = $parent_base_path;
  return $content;
}

/**
 * Processes the contents of a stylesheet for aggregation.
 *
 * @param $contents
 *   The contents of the stylesheet.
 * @param $optimize
 *   (optional) Boolean whether CSS contents should be minified. Defaults to
 *   FALSE.
 *
 * @return
 *   Contents of the stylesheet including the imported stylesheets.
 */
function drupal_load_stylesheet_content($contents, $optimize = FALSE) {
  // Remove multiple charset declarations for standards compliance (and fixing Safari problems).
  $contents = preg_replace('/^@charset\s+[\'"](\S*?)\b[\'"];/i', '', $contents);

  if ($optimize) {
    // Perform some safe CSS optimizations.
    // Regexp to match comment blocks.
    $comment     = '/\*[^*]*\*+(?:[^/*][^*]*\*+)*/';
    // Regexp to match double quoted strings.
    $double_quot = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
    // Regexp to match single quoted strings.
    $single_quot = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";
    // Strip all comment blocks, but keep double/single quoted strings.
    $contents = preg_replace(
      "<($double_quot|$single_quot)|$comment>Ss",
      "$1",
      $contents
    );
    // Remove certain whitespace.
    // There are different conditions for removing leading and trailing
    // whitespace.
    // @see http://php.net/manual/regexp.reference.subpatterns.php
    $contents = preg_replace('<
      # Strip leading and trailing whitespace.
        \s*([@{};,])\s*
      # Strip only leading whitespace from:
      # - Closing parenthesis: Retain "@media (bar) and foo".
      | \s+([\)])
      # Strip only trailing whitespace from:
      # - Opening parenthesis: Retain "@media (bar) and foo".
      # - Colon: Retain :pseudo-selectors.
      | ([\(:])\s+
    >xS',
      // Only one of the three capturing groups will match, so its reference
      // will contain the wanted value and the references for the
      // two non-matching groups will be replaced with empty strings.
      '$1$2$3',
      $contents
    );
    // End the file with a new line.
    $contents = trim($contents);
    $contents .= "\n";
  }

  // Replaces @import commands with the actual stylesheet content.
  // This happens recursively but omits external files.
  $contents = preg_replace_callback('/@import\s*(?:url\(\s*)?[\'"]?(?![a-z]+:)(?!\/\/)([^\'"\()]+)[\'"]?\s*\)?\s*;/', '_drupal_load_stylesheet', $contents);
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
  $file = drupal_load_stylesheet($filename, NULL, FALSE);

  // Determine the file's directory.
  $directory = dirname($filename);
  // If the file is in the current directory, make sure '.' doesn't appear in
  // the url() path.
  $directory = $directory == '.' ? '' : $directory .'/';

  // Alter all internal url() paths. Leave external paths alone. We don't need
  // to normalize absolute paths here (i.e. remove folder/... segments) because
  // that will be done later.
  return preg_replace('/url\(\s*([\'"]?)(?![a-z]+:|\/+)([^\'")]+)([\'"]?)\s*\)/i', 'url(\1' . $directory . '\2\3)', $file);
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
 * Prepares a string for use as a CSS identifier (element, class, or ID name).
 *
 * http://www.w3.org/TR/CSS21/syndata.html#characters shows the syntax for valid
 * CSS identifiers (including element names, classes, and IDs in selectors.)
 *
 * @param $identifier
 *   The identifier to clean.
 * @param $filter
 *   An array of string replacements to use on the identifier.
 *
 * @return
 *   The cleaned identifier.
 */
function drupal_clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => '')) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['allow_css_double_underscores'] = &drupal_static(__FUNCTION__ . ':allow_css_double_underscores');
  }
  $allow_css_double_underscores = &$drupal_static_fast['allow_css_double_underscores'];
  if (!isset($allow_css_double_underscores)) {
    $allow_css_double_underscores = variable_get('allow_css_double_underscores', FALSE);
  }

  // Preserve BEM-style double-underscores depending on custom setting.
  if ($allow_css_double_underscores) {
    $filter['__'] = '__';
  }

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
 * Prepares a string for use as a valid class name.
 *
 * Do not pass one string containing multiple classes as they will be
 * incorrectly concatenated with dashes, i.e. "one two" will become "one-two".
 *
 * @param $class
 *   The class name to clean.
 *
 * @return
 *   The cleaned class name.
 */
function drupal_html_class($class) {
  // The output of this function will never change, so this uses a normal
  // static instead of drupal_static().
  static $classes = array();

  if (!isset($classes[$class])) {
    $classes[$class] = drupal_clean_css_identifier(drupal_strtolower($class));
  }
  return $classes[$class];
}

/**
 * Prepares a string for use as a valid HTML ID and guarantees uniqueness.
 *
 * This function ensures that each passed HTML ID value only exists once on the
 * page. By tracking the already returned ids, this function enables forms,
 * blocks, and other content to be output multiple times on the same page,
 * without breaking (X)HTML validation.
 *
 * For already existing IDs, a counter is appended to the ID string. Therefore,
 * JavaScript and CSS code should not rely on any value that was generated by
 * this function and instead should rely on manually added CSS classes or
 * similarly reliable constructs.
 *
 * Two consecutive hyphens separate the counter from the original ID. To manage
 * uniqueness across multiple Ajax requests on the same page, Ajax requests
 * POST an array of all IDs currently present on the page, which are used to
 * prime this function's cache upon first invocation.
 *
 * To allow reverse-parsing of IDs submitted via Ajax, any multiple consecutive
 * hyphens in the originally passed $id are replaced with a single hyphen.
 *
 * @param $id
 *   The ID to clean.
 *
 * @return
 *   The cleaned ID.
 */
function drupal_html_id($id) {
  // If this is an Ajax request, then content returned by this page request will
  // be merged with content already on the base page. The HTML IDs must be
  // unique for the fully merged content. Therefore, initialize $seen_ids to
  // take into account IDs that are already in use on the base page.
  $seen_ids_init = &drupal_static(__FUNCTION__ . ':init');
  if (!isset($seen_ids_init)) {
    // Ideally, Drupal would provide an API to persist state information about
    // prior page requests in the database, and we'd be able to add this
    // function's $seen_ids static variable to that state information in order
    // to have it properly initialized for this page request. However, no such
    // page state API exists, so instead, ajax.js adds all of the in-use HTML
    // IDs to the POST data of Ajax submissions. Direct use of $_POST is
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
      if (isset($_POST['ajax_html_ids'][0]) && strpos($_POST['ajax_html_ids'][0], ',') === FALSE) {
        $ajax_html_ids = $_POST['ajax_html_ids'];
      }
      else {
        // jquery.form.js may send the server a comma-separated string as the
        // first element of an array (see http://drupal.org/node/1575060), so
        // we need to convert it to an array in that case.
        $ajax_html_ids = explode(',', $_POST['ajax_html_ids'][0]);
      }
      foreach ($ajax_html_ids as $seen_id) {
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
  // Ajax requests.
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
 * drag-and-drop or dynamic Ajax loading, in a theme-independent way.
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
 *   $() being the jQuery function. Wrap your code in
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
 *   drupal_add_js(array('myModule' => array('key' => 'value')), 'setting');
 * @endcode
 *
 * Calling drupal_static_reset('drupal_add_js') will clear all JavaScript added
 * so far.
 *
 * If JavaScript aggregation is enabled, all JavaScript files added with
 * $options['preprocess'] set to TRUE will be merged into one aggregate file.
 * Preprocessed inline JavaScript will not be aggregated into this single file.
 * Externally hosted JavaScripts are never aggregated.
 *
 * The reason for aggregating the files is outlined quite thoroughly here:
 * http://www.die.net/musings/page_load_time/ "Load fewer external objects. Due
 * to request overhead, one bigger file just loads faster than two smaller ones
 * half its size."
 *
 * $options['preprocess'] should be only set to TRUE when a file is required for
 * all typical visitors and most pages of a site. It is critical that all
 * preprocessed files are added unconditionally on every page, even if the
 * files are not needed on a page. This is normally done by calling
 * drupal_add_js() in a hook_init() implementation.
 *
 * Non-preprocessed files should only be added to the page when they are
 * actually needed.
 *
 * @param $data
 *   (optional) If given, the value depends on the $options parameter, or
 *   $options['type'] if $options is passed as an associative array:
 *   - 'file': Path to the file relative to base_path().
 *   - 'inline': The JavaScript code that should be placed in the given scope.
 *   - 'external': The absolute path to an external JavaScript file that is not
 *     hosted on the local server. These files will not be aggregated if
 *     JavaScript aggregation is enabled.
 *   - 'setting': An associative array with configuration options. The array is
 *     merged directly into Drupal.settings. All modules should wrap their
 *     actual configuration settings in another variable to prevent conflicts in
 *     the Drupal.settings namespace. Items added with a string key will replace
 *     existing settings with that key; items with numeric array keys will be
 *     added to the existing settings array.
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
 *   - group: A number identifying the group in which to add the JavaScript.
 *     Available constants are:
 *     - JS_LIBRARY: Any libraries, settings, or jQuery plugins.
 *     - JS_DEFAULT: Any module-layer JavaScript.
 *     - JS_THEME: Any theme-layer JavaScript.
 *     The group number serves as a weight: JavaScript within a lower weight
 *     group is presented on the page before JavaScript within a higher weight
 *     group.
 *   - every_page: For optimal front-end performance when aggregation is
 *     enabled, this should be set to TRUE if the JavaScript is present on every
 *     page of the website for users for whom it is present at all. This
 *     defaults to FALSE. It is set to TRUE for JavaScript files that are added
 *     via module and theme .info files. Modules that add JavaScript within
 *     hook_init() implementations, or from other code that ensures that the
 *     JavaScript is added to all website pages, should also set this flag to
 *     TRUE. All JavaScript files within the same group and that have the
 *     'every_page' flag set to TRUE and do not have 'preprocess' set to FALSE
 *     are aggregated together into a single aggregate file, and that aggregate
 *     file can be reused across a user's entire site visit, leading to faster
 *     navigation between pages. However, JavaScript that is only needed on
 *     pages less frequently visited, can be added by code that only runs for
 *     those particular pages, and that code should not set the 'every_page'
 *     flag. This minimizes the size of the aggregate file that the user needs
 *     to download when first visiting the website. JavaScript without the
 *     'every_page' flag is aggregated into a separate aggregate file. This
 *     other aggregate file is likely to change from page to page, and each new
 *     aggregate file needs to be downloaded when first encountered, so it
 *     should be kept relatively small by ensuring that most commonly needed
 *     JavaScript is added to every page.
 *   - weight: A number defining the order in which the JavaScript is added to
 *     the page relative to other JavaScript with the same 'scope', 'group',
 *     and 'every_page' value. In some cases, the order in which the JavaScript
 *     is presented on the page is very important. jQuery, for example, must be
 *     added to the page before any jQuery code is run, so jquery.js uses the
 *     JS_LIBRARY group and a weight of -20, jquery.once.js (a library drupal.js
 *     depends on) uses the JS_LIBRARY group and a weight of -19, drupal.js uses
 *     the JS_LIBRARY group and a weight of -1, other libraries use the
 *     JS_LIBRARY group and a weight of 0 or higher, and all other scripts use
 *     one of the other group constants. The exact ordering of JavaScript is as
 *     follows:
 *     - First by scope, with 'header' first, 'footer' last, and any other
 *       scopes provided by a custom theme coming in between, as determined by
 *       the theme.
 *     - Then by group.
 *     - Then by the 'every_page' flag, with TRUE coming before FALSE.
 *     - Then by weight.
 *     - Then by the order in which the JavaScript was added. For example, all
 *       else being the same, JavaScript added by a call to drupal_add_js() that
 *       happened later in the page request gets added to the page after one for
 *       which drupal_add_js() happened earlier in the page request.
 *   - requires_jquery: Set this to FALSE if the JavaScript you are adding does
 *     not have a dependency on jQuery. Defaults to TRUE, except for JavaScript
 *     settings where it defaults to FALSE. This is used on sites that have the
 *     'javascript_always_use_jquery' variable set to FALSE; on those sites, if
 *     all the JavaScript added to the page by drupal_add_js() does not have a
 *     dependency on jQuery, then for improved front-end performance Drupal
 *     will not add jQuery and related libraries and settings to the page.
 *   - defer: If set to TRUE, the defer attribute is set on the <script>
 *     tag. Defaults to FALSE.
 *   - cache: If set to FALSE, the JavaScript file is loaded anew on every page
 *     call; in other words, it is not cached. Used only when 'type' references
 *     a JavaScript file. Defaults to TRUE.
 *   - preprocess: If TRUE and JavaScript aggregation is enabled, the script
 *     file will be aggregated. Defaults to TRUE.
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
  $jquery_added = &drupal_static(__FUNCTION__ . ':jquery_added', FALSE);

  // If the $javascript variable has been reset with drupal_static_reset(),
  // jQuery and related files will have been removed from the list, so set the
  // variable back to FALSE to indicate they have not yet been added.
  if (empty($javascript)) {
    $jquery_added = FALSE;
  }

  // Construct the options, taking the defaults into consideration.
  if (isset($options)) {
    if (!is_array($options)) {
      $options = array('type' => $options);
    }
  }
  else {
    $options = array();
  }
  if (isset($options['type']) && $options['type'] == 'setting') {
    $options += array('requires_jquery' => FALSE);
  }
  $options += drupal_js_defaults($data);

  // Preprocess can only be set if caching is enabled.
  $options['preprocess'] = $options['cache'] ? $options['preprocess'] : FALSE;

  // Tweak the weight so that files of the same weight are included in the
  // order of the calls to drupal_add_js().
  $options['weight'] += count($javascript) / 1000;

  if (isset($data)) {
    // Add jquery.js, drupal.js, and related files and settings if they have
    // not been added yet. However, if the 'javascript_always_use_jquery'
    // variable is set to FALSE (indicating that the site does not want jQuery
    // automatically added on all pages) then only add it if a file or setting
    // that requires jQuery is being added also.
    if (!$jquery_added && (variable_get('javascript_always_use_jquery', TRUE) || $options['requires_jquery'])) {
      $jquery_added = TRUE;
      // url() generates the prefix using hook_url_outbound_alter(). Instead of
      // running the hook_url_outbound_alter() again here, extract the prefix
      // from url().
      url('', array('prefix' => &$prefix));
      $default_javascript = array(
        'settings' => array(
          'data' => array(
            array('basePath' => base_path()),
            array('pathPrefix' => empty($prefix) ? '' : $prefix),
          ),
          'type' => 'setting',
          'scope' => 'header',
          'group' => JS_LIBRARY,
          'every_page' => TRUE,
          'weight' => 0,
        ),
        'misc/drupal.js' => array(
          'data' => 'misc/drupal.js',
          'type' => 'file',
          'scope' => 'header',
          'group' => JS_LIBRARY,
          'every_page' => TRUE,
          'weight' => -1,
          'requires_jquery' => TRUE,
          'preprocess' => TRUE,
          'cache' => TRUE,
          'defer' => FALSE,
        ),
      );
      $javascript = drupal_array_merge_deep($javascript, $default_javascript);
      // Register all required libraries.
      drupal_add_library('system', 'jquery', TRUE);
      drupal_add_library('system', 'jquery.once', TRUE);
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
        // so the same JavaScript file is not added twice.
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
 *
 * @see drupal_get_js()
 * @see drupal_add_js()
 */
function drupal_js_defaults($data = NULL) {
  return array(
    'type' => 'file',
    'group' => JS_DEFAULT,
    'every_page' => FALSE,
    'weight' => 0,
    'requires_jquery' => TRUE,
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
 * @param $skip_alter
 *   (optional) If set to TRUE, this function skips calling drupal_alter() on
 *   $javascript, useful when the calling function passes a $javascript array
 *   that has already been altered.
 *
 * @return
 *   All JavaScript code segments and includes for the scope as HTML tags.
 *
 * @see drupal_add_js()
 * @see locale_js_alter()
 * @see drupal_js_defaults()
 */
function drupal_get_js($scope = 'header', $javascript = NULL, $skip_alter = FALSE) {
  if (!isset($javascript)) {
    $javascript = drupal_add_js();
  }

  // If no JavaScript items have been added, or if the only JavaScript items
  // that have been added are JavaScript settings (which don't do anything
  // without any JavaScript code to use them), then no JavaScript code should
  // be added to the page.
  if (empty($javascript) || (isset($javascript['settings']) && count($javascript) == 1)) {
    return '';
  }

  // Allow modules to alter the JavaScript.
  if (!$skip_alter) {
    drupal_alter('js', $javascript);
  }

  // Filter out elements of the given scope.
  $items = array();
  foreach ($javascript as $key => $item) {
    if ($item['scope'] == $scope) {
      $items[$key] = $item;
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

  // For inline JavaScript to validate as XHTML, all JavaScript containing
  // XHTML needs to be wrapped in CDATA. To make that backwards compatible
  // with HTML 4, we need to comment out the CDATA-tag.
  $embed_prefix = "\n<!--//--><![CDATA[//><!--\n";
  $embed_suffix = "\n//--><!]]>\n";

  // Since JavaScript may look for arguments in the URL and act on them, some
  // third-party code might require the use of a different query string.
  $js_version_string = variable_get('drupal_js_version_query_string', 'v=');

  // Sort the JavaScript so that it appears in the correct order.
  uasort($items, 'drupal_sort_css_js');

  // Provide the page with information about the individual JavaScript files
  // used, information not otherwise available when aggregation is enabled.
  $setting['ajaxPageState']['js'] = array_fill_keys(array_keys($items), 1);
  unset($setting['ajaxPageState']['js']['settings']);
  drupal_add_js($setting, 'setting');

  // If we're outputting the header scope, then this might be the final time
  // that drupal_get_js() is running, so add the setting to this output as well
  // as to the drupal_add_js() cache. If $items['settings'] doesn't exist, it's
  // because drupal_get_js() was intentionally passed a $javascript argument
  // stripped off settings, potentially in order to override how settings get
  // output, so in this case, do not add the setting to this output.
  if ($scope == 'header' && isset($items['settings'])) {
    $items['settings']['data'][] = $setting;
  }

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
        $js_element['#value'] = 'jQuery.extend(Drupal.settings, ' . drupal_json_encode(drupal_array_merge_deep_array($item['data'])) . ");";
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
          // the relative ordering of JS by weight. We also set the key such
          // that groups are split by items sharing the same 'group' value and
          // 'every_page' flag. While this potentially results in more aggregate
          // files, it helps make each one more reusable across a site visit,
          // leading to better front-end performance of a website as a whole.
          // See drupal_add_js() for details.
          $key = 'aggregate_' . $item['group'] . '_' . $item['every_page'] . '_' . $index;
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
 * Adds attachments to a render() structure.
 *
 * Libraries, JavaScript, CSS and other types of custom structures are attached
 * to elements using the #attached property. The #attached property is an
 * associative array, where the keys are the attachment types and the values are
 * the attached data. For example:
 * @code
 * $build['#attached'] = array(
 *   'js' => array(drupal_get_path('module', 'taxonomy') . '/taxonomy.js'),
 *   'css' => array(drupal_get_path('module', 'taxonomy') . '/taxonomy.css'),
 * );
 * @endcode
 *
 * 'js', 'css', and 'library' are types that get special handling. For any
 * other kind of attached data, the array key must be the full name of the
 * callback function and each value an array of arguments. For example:
 * @code
 * $build['#attached']['drupal_add_http_header'] = array(
 *   array('Content-Type', 'application/rss+xml; charset=utf-8'),
 * );
 * @endcode
 *
 * External 'js' and 'css' files can also be loaded. For example:
 * @code
 * $build['#attached']['js'] = array(
 *   'http://code.jquery.com/jquery-1.4.2.min.js' => array(
 *     'type' => 'external',
 *   ),
 * );
 * @endcode
 *
 * @param $elements
 *   The structured array describing the data being rendered.
 * @param $group
 *   The default group of JavaScript and CSS being added. This is only applied
 *   to the stylesheets and JavaScript items that don't have an explicit group
 *   assigned to them.
 * @param $dependency_check
 *   When TRUE, will exit if a given library's dependencies are missing. When
 *   set to FALSE, will continue to add the libraries, even though one or more
 *   dependencies are missing. Defaults to FALSE.
 * @param $every_page
 *   Set to TRUE to indicate that the attachments are added to every page on the
 *   site. Only attachments with the every_page flag set to TRUE can participate
 *   in JavaScript/CSS aggregation.
 *
 * @return
 *   FALSE if there were any missing library dependencies; TRUE if all library
 *   dependencies were met.
 *
 * @see drupal_add_library()
 * @see drupal_add_js()
 * @see drupal_add_css()
 * @see drupal_render()
 */
function drupal_process_attached($elements, $group = JS_DEFAULT, $dependency_check = FALSE, $every_page = NULL) {
  // Add defaults to the special attached structures that should be processed differently.
  $elements['#attached'] += array(
    'library' => array(),
    'js' => array(),
    'css' => array(),
  );

  // Add the libraries first.
  $success = TRUE;
  foreach ($elements['#attached']['library'] as $library) {
    if (drupal_add_library($library[0], $library[1], $every_page) === FALSE) {
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
      // Apply the default group if it isn't explicitly given.
      if (!isset($options['group'])) {
        $options['group'] = $group;
      }
      // Set the every_page flag if one was passed.
      if (isset($every_page)) {
        $options['every_page'] = $every_page;
      }
      call_user_func('drupal_add_' . $type, $data, $options);
    }
    unset($elements['#attached'][$type]);
  }

  // Add additional types of attachments specified in the render() structure.
  // Libraries, JavaScript and CSS have been added already, as they require
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
 * - required
 * - optional
 * - visible
 * - invisible
 * - checked
 * - unchecked
 * - expanded
 * - collapsed
 *
 * The following states may be used in remote conditions:
 * - empty
 * - filled
 * - checked
 * - unchecked
 * - expanded
 * - collapsed
 * - value
 *
 * The following states exist for both elements and remote conditions, but are
 * not fully implemented and may not change anything on the element:
 * - relevant
 * - irrelevant
 * - valid
 * - invalid
 * - touched
 * - untouched
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
  $elements['#attached']['library'][] = array('system', 'drupal.states');
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
 * depending module, without having to add all files of the library separately.
 * Each library is only loaded once.
 *
 * @param $module
 *   The name of the module that registered the library.
 * @param $name
 *   The name of the library to add.
 * @param $every_page
 *   Set to TRUE if this library is added to every page on the site. Only items
 *   with the every_page flag set to TRUE can participate in aggregation.
 *
 * @return
 *   TRUE if the library was successfully added; FALSE if the library or one of
 *   its dependencies could not be added.
 *
 * @see drupal_get_library()
 * @see hook_library()
 * @see hook_library_alter()
 */
function drupal_add_library($module, $name, $every_page = NULL) {
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
      $added[$module][$name] = drupal_process_attached($elements, JS_LIBRARY, TRUE, $every_page);
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
 * @param $name
 *   (optional) The name of a registered library to retrieve. By default, all
 *   libraries registered by $module are returned.
 *
 * @return
 *   The definition of the requested library, if $name was passed and it exists,
 *   or FALSE if it does not exist. If no $name was passed, an associative array
 *   of libraries registered by $module is returned (which may be empty).
 *
 * @see drupal_add_library()
 * @see hook_library()
 * @see hook_library_alter()
 *
 * @todo The purpose of drupal_get_*() is completely different to other page
 *   requisite API functions; find and use a different name.
 */
function drupal_get_library($module, $name = NULL) {
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
  if (isset($name)) {
    if (!isset($libraries[$module][$name])) {
      $libraries[$module][$name] = FALSE;
    }
    return $libraries[$module][$name];
  }
  return $libraries[$module];
}

/**
 * Assists in adding the tableDrag JavaScript behavior to a themed table.
 *
 * Draggable tables should be used wherever an outline or list of sortable items
 * needs to be arranged by an end-user. Draggable tables are very flexible and
 * can manipulate the value of form elements placed within individual columns.
 *
 * To set up a table to use drag and drop in place of weight select-lists or in
 * place of a form that contains parent relationships, the form must be themed
 * into a table. The table must have an ID attribute set. If using
 * theme_table(), the ID may be set as follows:
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
 * Each row of the table must also have a class of "draggable" in order to
 * enable the drag handles:
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
 * the block regions on the admin/structure/block page), a separate subgroup
 * class must also be added to differentiate the groups.
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
 * provide enough information about the sibling and parent relationships. See
 * theme_menu_overview_form() for an example creating a table containing parent
 * relationships.
 *
 * Note that this function should be called from the theme layer, such as in a
 * .tpl.php file, theme_ function, or in a template_preprocess function, not in
 * a form declaration. Though the same JavaScript could be added to the page
 * using drupal_add_js() directly, this function helps keep template files
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
    drupal_add_library('system', 'jquery.cookie');
    drupal_add_js('misc/tabledrag.js', array('weight' => -1));
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
 * variable is emptied to force a rebuild of the cache. Second, the cache file
 * is generated if it is missing on disk. Old cache files are not deleted
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
  // Create a new array so that only the file names are used to create the hash.
  // This prevents new aggregates from being created unnecessarily.
  $js_data = array();
  foreach ($files as $file) {
    $js_data[] = $file['data'];
  }
  $key = hash('sha256', serialize($js_data));
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
    if (!file_exists($uri) && !file_unmanaged_save_data($contents, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }
    // If JS gzip compression is enabled, clean URLs are enabled (which means
    // that rewrite rules are working) and the zlib extension is available then
    // create a gzipped version of this file. This file is served conditionally
    // to browsers that accept gzip using .htaccess rules.
    if (variable_get('js_gzip_compression', TRUE) && variable_get('clean_url', 0) && extension_loaded('zlib')) {
      if (!file_exists($uri . '.gz') && !file_unmanaged_save_data(gzencode($contents, 9, FORCE_GZIP), $uri . '.gz', FILE_EXISTS_REPLACE)) {
        return FALSE;
      }
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
 * Converts a PHP variable into its JavaScript equivalent.
 *
 * We use HTML-safe strings, with several characters escaped.
 *
 * @see drupal_json_decode()
 * @see drupal_json_encode_helper()
 * @ingroup php_wrappers
 */
function drupal_json_encode($var) {
  // The PHP version cannot change within a request.
  static $php530;

  if (!isset($php530)) {
    $php530 = version_compare(PHP_VERSION, '5.3.0', '>=');
  }

  if ($php530) {
    // Encode <, >, ', &, and " using the json_encode() options parameter.
    return json_encode($var, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
  }

  // json_encode() escapes <, >, ', &, and " using its options parameter, but
  // does not support this parameter prior to PHP 5.3.0.  Use a helper instead.
  include_once DRUPAL_ROOT . '/includes/json-encode.inc';
  return drupal_json_encode_helper($var);
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
 * Returns data in JSON format.
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
 * Ensures the private key variable used to generate tokens is set.
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
 * Generates a token based on $value, the user session, and the private key.
 *
 * @param $value
 *   An additional value to base the token on.
 *
 * The generated token is based on the session ID of the current user. Normally,
 * anonymous users do not have a session, so the generated token will be
 * different on every page request. To generate a token for users without a
 * session, manually start a session prior to calling this function.
 *
 * @return string
 *   A 43-character URL-safe token for validation, based on the user session ID,
 *   the hash salt provided from drupal_get_hash_salt(), and the
 *   'drupal_private_key' configuration variable.
 *
 * @see drupal_get_hash_salt()
 */
function drupal_get_token($value = '') {
  return drupal_hmac_base64($value, session_id() . drupal_get_private_key() . drupal_get_hash_salt());
}

/**
 * Validates a token based on $value, the user session, and the private key.
 *
 * @param $token
 *   The token to be validated.
 * @param $value
 *   An additional value to base the token on.
 * @param $skip_anonymous
 *   Set to true to skip token validation for anonymous users.
 *
 * @return
 *   True for a valid token, false for an invalid token. When $skip_anonymous
 *   is true, the return value will always be true for anonymous users.
 */
function drupal_valid_token($token, $value = '', $skip_anonymous = FALSE) {
  global $user;
  return (($skip_anonymous && $user->uid == 0) || ($token === drupal_get_token($value)));
}

function _drupal_bootstrap_full() {
  static $called = FALSE;

  if ($called) {
    return;
  }
  $called = TRUE;
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
  // Reset drupal_alter() and module_implements() static caches as these
  // include implementations for vital modules only when called early on
  // in the bootstrap.
  drupal_static_reset('drupal_alter');
  drupal_static_reset('module_implements');
  // Make sure all stream wrappers are registered.
  file_get_stream_wrappers();
  // Ensure mt_rand is reseeded, to prevent random values from one page load
  // being exploited to predict random values in subsequent page loads.
  $seed = unpack("L", drupal_random_bytes(4));
  mt_srand($seed[1]);

  $test_info = &$GLOBALS['drupal_test_info'];
  if (!empty($test_info['in_child_site'])) {
    // Running inside the simpletest child site, log fatal errors to test
    // specific file directory.
    ini_set('log_errors', 1);
    ini_set('error_log', 'public://error.log');
  }

  // Initialize $_GET['q'] prior to invoking hook_init().
  drupal_path_initialize();

  // Let all modules take action before the menu system handles the request.
  // We do not want this while running update.php.
  if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
    // Prior to invoking hook_init(), initialize the theme (potentially a custom
    // one for this page), so that:
    // - Modules with hook_init() implementations that call theme() or
    //   theme_get_registry() don't initialize the incorrect theme.
    // - The theme can have hook_*_alter() implementations affect page building
    //   (e.g., hook_form_alter(), hook_node_view_alter(), hook_page_alter()),
    //   ahead of when rendering starts.
    menu_set_custom_theme();
    drupal_theme_initialize();
    module_invoke_all('init');
  }
}

/**
 * Stores the current page in the cache.
 *
 * If page_compression is enabled, a gzipped version of the page is stored in
 * the cache to avoid compressing the output on each request. The cache entry
 * is unzipped in the relatively rare event that the page is requested by a
 * client without gzip support.
 *
 * Page compression requires the PHP zlib extension
 * (http://php.net/manual/ref.zlib.php).
 *
 * @see drupal_page_header()
 */
function drupal_page_set_cache() {
  global $base_root;

  if (drupal_page_is_cacheable()) {

    // Check whether the current page might be compressed.
    $page_compressed = variable_get('page_compression', TRUE) && extension_loaded('zlib');

    $cache = (object) array(
      'cid' => $base_root . request_uri(),
      'data' => array(
        'path' => $_GET['q'],
        'body' => ob_get_clean(),
        'title' => drupal_get_title(),
        'headers' => array(),
        // We need to store whether page was compressed or not,
        // because by the time it is read, the configuration might change.
        'page_compressed' => $page_compressed,
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
      if ($page_compressed) {
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
 * Do not call this function from a test. Use $this->cronRun() instead.
 *
 * @return bool
 *   TRUE if cron ran successfully and FALSE if cron is already running.
 */
function drupal_cron_run() {
  // Allow execution to continue even if the request gets canceled.
  @ignore_user_abort(TRUE);

  // Prevent session information from being saved while cron is running.
  $original_session_saving = drupal_save_session();
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

    // Iterate through the modules calling their cron handlers (if any):
    foreach (module_implements('cron') as $module) {
      // Do not let an exception thrown by one module disturb another.
      try {
        module_invoke($module, 'cron');
      }
      catch (Exception $e) {
        watchdog_exception('cron', $e);
      }
    }

    // Record cron time.
    variable_set('cron_last', REQUEST_TIME);
    watchdog('cron', 'Cron run completed.', array(), WATCHDOG_NOTICE);

    // Release cron lock.
    lock_release('cron');

    // Return TRUE so other functions can check if it did run successfully
    $return = TRUE;
  }

  foreach ($queues as $queue_name => $info) {
    if (!empty($info['skip on cron'])) {
      // Do not run if queue wants to skip.
      continue;
    }
    $callback = $info['worker callback'];
    $end = time() + (isset($info['time']) ? $info['time'] : 15);
    $queue = DrupalQueue::get($queue_name);
    while (time() < $end && ($item = $queue->claimItem())) {
      try {
        call_user_func($callback, $item->data);
        $queue->deleteItem($item);
      }
      catch (Exception $e) {
        // In case of exception log it and leave the item in the queue
        // to be processed again later.
        watchdog_exception('cron', $e);
      }
    }
  }
  // Restore the user.
  $GLOBALS['user'] = $original_user;
  drupal_save_session($original_session_saving);

  return $return;
}

/**
 * DEPRECATED: Shutdown function: Performs cron cleanup.
 *
 * This function is deprecated because the 'cron_semaphore' variable it
 * references no longer exists. It is therefore no longer used as a shutdown
 * function by Drupal core.
 *
 * @deprecated
 */
function drupal_cron_cleanup() {
  // See if the semaphore is still locked.
  if (variable_get('cron_semaphore', FALSE)) {
    watchdog('cron', 'Cron run exceeded the time limit and was aborted.', array(), WATCHDOG_WARNING);

    // Release cron semaphore.
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
 * your installation profile's directory (i.e.,
 * /profiles/your_site_profile/modules/), the all-sites directory (i.e.,
 * /sites/all/modules/), and your site-specific directory (i.e.,
 * /sites/your_site_dir/modules/), in that order, and return information about
 * all of the files ending in .module in those directories.
 *
 * The information is returned in an associative array, which can be keyed on
 * the file name ($key = 'filename'), the file name without the extension ($key
 * = 'name'), or the full file stream URI ($key = 'uri'). If you use a key of
 * 'filename' or 'name', files found later in the search will take precedence
 * over files found earlier (unless they belong to a module or theme not
 * compatible with Drupal core); if you choose a key of 'uri', you will get all
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

  $searchdir = array($directory);
  $files = array();

  // The 'profiles' directory contains pristine collections of modules and
  // themes as organized by a distribution. It is pristine in the same way
  // that /modules is pristine for core; users should avoid changing anything
  // there in favor of sites/all or sites/<domain> directories.
  $profiles = array();
  $profile = drupal_get_profile();
  // For SimpleTest to be able to test modules packaged together with a
  // distribution we need to include the profile of the parent site (in which
  // test runs are triggered).
  if (drupal_valid_test_ua()) {
    $testing_profile = variable_get('simpletest_parent_profile', FALSE);
    if ($testing_profile && $testing_profile != $profile) {
      $profiles[] = $testing_profile;
    }
  }
  // In case both profile directories contain the same extension, the actual
  // profile always has precedence.
  $profiles[] = $profile;
  foreach ($profiles as $profile) {
    if (file_exists("profiles/$profile/$directory")) {
      $searchdir[] = "profiles/$profile/$directory";
    }
  }

  // Always search sites/all/* as well as the global directories.
  $searchdir[] = 'sites/all/' . $directory;

  if (file_exists("$config/$directory")) {
    $searchdir[] = "$config/$directory";
  }

  // Get current list of items.
  if (!function_exists('file_scan_directory')) {
    require_once DRUPAL_ROOT . '/includes/file.inc';
  }
  foreach ($searchdir as $dir) {
    $files_to_add = file_scan_directory($dir, $mask, array('key' => $key, 'min_depth' => $min_depth));

    // Duplicate files found in later search directories take precedence over
    // earlier ones, so we want them to overwrite keys in our resulting
    // $files array.
    // The exception to this is if the later file is from a module or theme not
    // compatible with Drupal core. This may occur during upgrades of Drupal
    // core when new modules exist in core while older contrib modules with the
    // same name exist in a directory such as sites/all/modules/.
    foreach (array_intersect_key($files_to_add, $files) as $file_key => $file) {
      // If it has no info file, then we just behave liberally and accept the
      // new resource on the list for merging.
      if (file_exists($info_file = dirname($file->uri) . '/' . $file->name . '.info')) {
        // Get the .info file for the module or theme this file belongs to.
        $info = drupal_parse_info_file($info_file);

        // If the module or theme is incompatible with Drupal core, remove it
        // from the array for the current search directory, so it is not
        // overwritten when merged with the $files array.
        if (isset($info['core']) && $info['core'] != DRUPAL_CORE_COMPATIBILITY) {
          unset($files_to_add[$file_key]);
        }
      }
    }
    $files = array_merge($files, $files_to_add);
  }

  return $files;
}

/**
 * Sets the main page content value for later use.
 *
 * Given the nature of the Drupal page handling, this will be called once with
 * a string or array. We store that and return it later as the block is being
 * displayed.
 *
 * @param $content
 *   A string or renderable array representing the body of the page.
 *
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
 *   The passed-in element with markup for conditional comments potentially
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
 *   The passed-in elements containing a rendered link in '#markup'.
 */
function drupal_pre_render_link($element) {
  // By default, link options to pass to l() are normally set in #options.
  $element += array('#options' => array());
  // However, within the scope of renderable elements, #attributes is a valid
  // way to specify attributes, too. Take them into account, but do not override
  // attributes from #options.
  if (isset($element['#attributes'])) {
    $element['#options'] += array('attributes' => array());
    $element['#options']['attributes'] += $element['#attributes'];
  }

  // This #pre_render callback can be invoked from inside or outside of a Form
  // API context, and depending on that, a HTML ID may be already set in
  // different locations. #options should have precedence over Form API's #id.
  // #attributes have been taken over into #options above already.
  if (isset($element['#options']['attributes']['id'])) {
    $element['#id'] = $element['#options']['attributes']['id'];
  }
  elseif (isset($element['#id'])) {
    $element['#options']['attributes']['id'] = $element['#id'];
  }

  // Conditionally invoke ajax_pre_render_element(), if #ajax is set.
  if (isset($element['#ajax']) && !isset($element['#ajax_processed'])) {
    // If no HTML ID was found above, automatically create one.
    if (!isset($element['#id'])) {
      $element['#id'] = $element['#options']['attributes']['id'] = drupal_html_id('ajax-link');
    }
    // If #ajax['path] was not specified, use the href as Ajax request URL.
    if (!isset($element['#ajax']['path'])) {
      $element['#ajax']['path'] = $element['#href'];
      $element['#ajax']['options'] = $element['#options'];
    }
    $element = ajax_pre_render_element($element);
  }

  $element['#markup'] = l($element['#title'], $element['#href'], $element['#options']);
  return $element;
}

/**
 * #pre_render callback that collects child links into a single array.
 *
 * This function can be added as a pre_render callback for a renderable array,
 * usually one which will be themed by theme_links(). It iterates through all
 * unrendered children of the element, collects any #links properties it finds,
 * merges them into the parent element's #links array, and prevents those
 * children from being rendered separately.
 *
 * The purpose of this is to allow links to be logically grouped into related
 * categories, so that each child group can be rendered as its own list of
 * links if drupal_render() is called on it, but calling drupal_render() on the
 * parent element will still produce a single list containing all the remaining
 * links, regardless of what group they were in.
 *
 * A typical example comes from node links, which are stored in a renderable
 * array similar to this:
 * @code
 * $node->content['links'] = array(
 *   '#theme' => 'links__node',
 *   '#pre_render' => array('drupal_pre_render_links'),
 *   'comment' => array(
 *     '#theme' => 'links__node__comment',
 *     '#links' => array(
 *       // An array of links associated with node comments, suitable for
 *       // passing in to theme_links().
 *     ),
 *   ),
 *   'statistics' => array(
 *     '#theme' => 'links__node__statistics',
 *     '#links' => array(
 *       // An array of links associated with node statistics, suitable for
 *       // passing in to theme_links().
 *     ),
 *   ),
 *   'translation' => array(
 *     '#theme' => 'links__node__translation',
 *     '#links' => array(
 *       // An array of links associated with node translation, suitable for
 *       // passing in to theme_links().
 *     ),
 *   ),
 * );
 * @endcode
 *
 * In this example, the links are grouped by functionality, which can be
 * helpful to themers who want to display certain kinds of links independently.
 * For example, adding this code to node.tpl.php will result in the comment
 * links being rendered as a single list:
 * @code
 * print render($content['links']['comment']);
 * @endcode
 *
 * (where $node->content has been transformed into $content before handing
 * control to the node.tpl.php template).
 *
 * The pre_render function defined here allows the above flexibility, but also
 * allows the following code to be used to render all remaining links into a
 * single list, regardless of their group:
 * @code
 * print render($content['links']);
 * @endcode
 *
 * In the above example, this will result in the statistics and translation
 * links being rendered together in a single list (but not the comment links,
 * which were rendered previously on their own).
 *
 * Because of the way this function works, the individual properties of each
 * group (for example, a group-specific #theme property such as
 * 'links__node__comment' in the example above, or any other property such as
 * #attributes or #pre_render that is attached to it) are only used when that
 * group is rendered on its own. When the group is rendered together with other
 * children, these child-specific properties are ignored, and only the overall
 * properties of the parent are used.
 */
function drupal_pre_render_links($element) {
  $element += array('#links' => array());
  foreach (element_children($element) as $key) {
    $child = &$element[$key];
    // If the child has links which have not been printed yet and the user has
    // access to it, merge its links in to the parent.
    if (isset($child['#links']) && empty($child['#printed']) && (!isset($child['#access']) || $child['#access'])) {
      $element['#links'] += $child['#links'];
      // Mark the child as having been printed already (so that its links
      // cannot be mistakenly rendered twice).
      $child['#printed'] = TRUE;
    }
  }
  return $element;
}

/**
 * #pre_render callback to append contents in #markup to #children.
 *
 * This needs to be a #pre_render callback, because eventually assigned
 * #theme_wrappers will expect the element's rendered content in #children.
 * Note that if also a #theme is defined for the element, then the result of
 * the theme callback will override #children.
 *
 * @param $elements
 *   A structured array using the #markup key.
 *
 * @return
 *   The passed-in elements, but #markup appended to #children.
 *
 * @see drupal_render()
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
 *   - #type: Value is always 'page'. This pushes the theming through
 *     page.tpl.php (required).
 *   - #show_messages: Suppress drupal_get_message() items. Used by Batch
 *     API (optional).
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
 * Renderable arrays have two kinds of key/value pairs: properties and
 * children. Properties have keys starting with '#' and their values influence
 * how the array will be rendered. Children are all elements whose keys do not
 * start with a '#'. Their values should be renderable arrays themselves,
 * which will be rendered during the rendering of the parent array. The markup
 * provided by the children is typically inserted into the markup generated by
 * the parent array.
 *
 * HTML generation for a renderable array, and the treatment of any children,
 * is controlled by two properties containing theme functions, #theme and
 * #theme_wrappers.
 *
 * #theme is the theme function called first. If it is set and the element has
 * any children, it is the responsibility of the theme function to render
 * these children. For elements that are not allowed to have any children,
 * e.g. buttons or textfields, the theme function can be used to render the
 * element itself. If #theme is not present and the element has children, each
 * child is itself rendered by a call to drupal_render(), and the results are
 * concatenated.
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
 * - 'keys': An array of one or more keys that identify the element. If 'keys'
 *   is set, the cache ID is created automatically from these keys. See
 *   drupal_render_cid_create().
 * - 'granularity' (optional): Define the cache granularity using binary
 *   combinations of the cache granularity constants, e.g.
 *   DRUPAL_CACHE_PER_USER to cache for each user separately or
 *   DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE to cache separately for each
 *   page and role. If not specified the element is cached globally for each
 *   theme and language.
 * - 'cid': Specify the cache ID directly. Either 'keys' or 'cid' is required.
 *   If 'cid' is set, 'keys' and 'granularity' are ignored. Use only if you
 *   have special requirements.
 * - 'expire': Set to one of the cache lifetime constants.
 * - 'bin': Specify a cache bin to cache the element in. Defaults to 'cache'.
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
 * to drupal_render(), it simply returns an empty string.
 *
 * @param array $elements
 *   The structured array describing the data to be rendered.
 *
 * @return string
 *   The rendered HTML.
 */
function drupal_render(&$elements) {
  // Early-return nothing if user does not have access.
  if (empty($elements) || (isset($elements['#access']) && !$elements['#access'])) {
    return '';
  }

  // Do not print elements twice.
  if (!empty($elements['#printed'])) {
    return '';
  }

  // Try to fetch the element's markup from cache and return.
  if (isset($elements['#cache'])) {
    $cached_output = drupal_render_cache_get($elements);
    if ($cached_output !== FALSE) {
      return $cached_output;
    }
  }

  // If #markup is set, ensure #type is set. This allows to specify just #markup
  // on an element without setting #type.
  if (isset($elements['#markup']) && !isset($elements['#type'])) {
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
    return '';
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
 * Renders children of an element and concatenates them.
 *
 * @param array $element
 *   The structured array whose children shall be rendered.
 * @param array $children_keys
 *   (optional) If the keys of the element's children are already known, they
 *   can be passed in to save another run of element_children().
 *
 * @return string
 *   The rendered HTML of all children of the element.

 * @see drupal_render()
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
 * Renders an element.
 *
 * This function renders an element using drupal_render(). The top level
 * element is shown with show() before rendering, so it will always be rendered
 * even if hide() had been previously used on it.
 *
 * @param $element
 *   The element to be rendered.
 *
 * @return
 *   The rendered element.
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
 * Hides an element from later rendering.
 *
 * The first time render() or drupal_render() is called on an element tree,
 * as each element in the tree is rendered, it is marked with a #printed flag
 * and the rendered children of the element are cached. Subsequent calls to
 * render() or drupal_render() will not traverse the child tree of this element
 * again: they will just use the cached children. So if you want to hide an
 * element, be sure to call hide() on the element before its parent tree is
 * rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param $element
 *   The element to be hidden.
 *
 * @return
 *   The element.
 *
 * @see render()
 * @see show()
 */
function hide(&$element) {
  $element['#printed'] = TRUE;
  return $element;
}

/**
 * Shows a hidden element for later rendering.
 *
 * You can also use render($element), which shows the element while rendering
 * it.
 *
 * The first time render() or drupal_render() is called on an element tree,
 * as each element in the tree is rendered, it is marked with a #printed flag
 * and the rendered children of the element are cached. Subsequent calls to
 * render() or drupal_render() will not traverse the child tree of this element
 * again: they will just use the cached children. So if you want to show an
 * element, be sure to call show() on the element before its parent tree is
 * rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param $element
 *   The element to be shown.
 *
 * @return
 *   The element.
 *
 * @see render()
 * @see hide()
 */
function show(&$element) {
  $element['#printed'] = FALSE;
  return $element;
}

/**
 * Gets the rendered output of a renderable element from the cache.
 *
 * @param $elements
 *   A renderable array.
 *
 * @return
 *   A markup string containing the rendered content of the element, or FALSE
 *   if no cached copy of the element is available.
 *
 * @see drupal_render()
 * @see drupal_render_cache_set()
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
 * Caches the rendered output of a renderable element.
 *
 * This is called by drupal_render() if the #cache property is set on an
 * element.
 *
 * @param $markup
 *   The rendered output string of $elements.
 * @param $elements
 *   A renderable array.
 *
 * @see drupal_render_cache_get()
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
  $attached = drupal_render_collect_attached($elements, TRUE);
  if ($attached) {
    $data['#attached'] = $attached;
  }
  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'cache';
  $expire = isset($elements['#cache']['expire']) ? $elements['#cache']['expire'] : CACHE_PERMANENT;
  cache_set($cid, $data, $bin, $expire);
}

/**
 * Collects #attached for an element and its children into a single array.
 *
 * When caching elements, it is necessary to collect all libraries, JavaScript
 * and CSS into a single array, from both the element itself and all child
 * elements. This allows drupal_render() to add these back to the page when the
 * element is returned from cache.
 *
 * @param $elements
 *   The element to collect #attached from.
 * @param $return
 *   Whether to return the attached elements and reset the internal static.
 *
 * @return
 *   The #attached array for this element and its descendants.
 */
function drupal_render_collect_attached($elements, $return = FALSE) {
  $attached = &drupal_static(__FUNCTION__, array());

  // Collect all #attached for this element.
  if (isset($elements['#attached'])) {
    foreach ($elements['#attached'] as $key => $value) {
      if (!isset($attached[$key])) {
        $attached[$key] = array();
      }
      $attached[$key] = array_merge($attached[$key], $value);
    }
  }
  if ($children = element_children($elements)) {
    foreach ($children as $child) {
      drupal_render_collect_attached($elements[$child]);
    }
  }

  // If this was the first call to the function, return all attached elements
  // and reset the static cache.
  if ($return) {
    $return = $attached;
    $attached = array();
    return $return;
  }
}

/**
 * Prepares an element for caching based on a query.
 *
 * This smart caching strategy saves Drupal from querying and rendering to HTML
 * when the underlying query is unchanged.
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
 *   - #query: The passed-in $query.
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
 * Returns cache ID parts for building a cache ID.
 *
 * @param $granularity
 *   One or more cache granularity constants. For example, to cache separately
 *   for each user, use DRUPAL_CACHE_PER_USER. To cache separately for each
 *   page and role, use the expression:
 *   @code
 *   DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE
 *   @endcode
 *
 * @return
 *   An array of cache ID parts, always containing the active theme. If the
 *   locale module is enabled it also contains the active language. If
 *   $granularity was passed in, more parts are added.
 */
function drupal_render_cid_parts($granularity = NULL) {
  global $theme, $base_root, $user;

  $cid_parts[] = $theme;
  // If Locale is enabled but we have only one language we do not need it as cid
  // part.
  if (drupal_multilingual()) {
    foreach (language_types_configurable() as $language_type) {
      $cid_parts[] = $GLOBALS[$language_type]->language;
    }
  }

  if (!empty($granularity)) {
    $cache_per_role = $granularity & DRUPAL_CACHE_PER_ROLE;
    $cache_per_user = $granularity & DRUPAL_CACHE_PER_USER;
    // User 1 has special permissions outside of the role system, so when
    // caching per role is requested, it should cache per user instead.
    if ($user->uid == 1 && $cache_per_role) {
      $cache_per_user = TRUE;
      $cache_per_role = FALSE;
    }
    // 'PER_ROLE' and 'PER_USER' are mutually exclusive. 'PER_USER' can be a
    // resource drag for sites with many users, so when a module is being
    // equivocal, we favor the less expensive 'PER_ROLE' pattern.
    if ($cache_per_role) {
      $cid_parts[] = 'r.' . implode(',', array_keys($user->roles));
    }
    elseif ($cache_per_user) {
      $cid_parts[] = "u.$user->uid";
    }

    if ($granularity & DRUPAL_CACHE_PER_PAGE) {
      $cid_parts[] = $base_root . request_uri();
    }
  }

  return $cid_parts;
}

/**
 * Creates the cache ID for a renderable element.
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
 * Array sorting callback; sorts elements by title.
 */
function element_sort_by_title($a, $b) {
  $a_title = (is_array($a) && isset($a['#title'])) ? $a['#title'] : '';
  $b_title = (is_array($b) && isset($b['#title'])) ? $b['#title'] : '';
  return strnatcasecmp($a_title, $b_title);
}

/**
 * Retrieves the default properties for the defined element type.
 *
 * @param $type
 *   An element type as defined by hook_element_info().
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
 * Retrieves a single property for the defined element type.
 *
 * @param $type
 *   An element type as defined by hook_element_info().
 * @param $property_name
 *   The property within the element type that should be returned.
 * @param $default
 *   (Optional) The value to return if the element type does not specify a
 *   value for the property. Defaults to NULL.
 */
function element_info_property($type, $property_name, $default = NULL) {
  return (($info = element_info($type)) && array_key_exists($property_name, $info)) ? $info[$property_name] : $default;
}

/**
 * Sorts a structured array by the 'weight' element.
 *
 * Note that the sorting is by the 'weight' array element, not by the render
 * element property '#weight'.
 *
 * Callback for uasort() used in various functions.
 *
 * @param $a
 *   First item for comparison. The compared items should be associative arrays
 *   that optionally include a 'weight' element. For items without a 'weight'
 *   element, a default value of 0 will be used.
 * @param $b
 *   Second item for comparison.
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
 * Array sorting callback; sorts elements by 'title' key.
 */
function drupal_sort_title($a, $b) {
  if (!isset($b['title'])) {
    return -1;
  }
  if (!isset($a['title'])) {
    return 1;
  }
  return strcasecmp($a['title'], $b['title']);
}

/**
 * Checks if the key is a property.
 */
function element_property($key) {
  return $key[0] == '#';
}

/**
 * Gets properties of a structured array element (keys beginning with '#').
 */
function element_properties($element) {
  return array_filter(array_keys((array) $element), 'element_property');
}

/**
 * Checks if the key is a child.
 */
function element_child($key) {
  return !isset($key[0]) || $key[0] != '#';
}

/**
 * Identifies the children of an element array, optionally sorted by weight.
 *
 * The children of a element array are those key/value pairs whose key does
 * not start with a '#'. See drupal_render() for details.
 *
 * @param $elements
 *   The element array whose children are to be identified.
 * @param $sort
 *   Boolean to indicate whether the children should be sorted by weight.
 *
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
    if ($key === '' || $key[0] !== '#') {
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
 * Returns the visible children of an element.
 *
 * @param $elements
 *   The parent element.
 *
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
 * Sets HTML attributes based on element properties.
 *
 * @param $element
 *   The renderable element to process.
 * @param $map
 *   An associative array whose keys are element property names and whose values
 *   are the HTML attribute names to set for corresponding the property; e.g.,
 *   array('#propertyname' => 'attributename'). If both names are identical
 *   except for the leading '#', then an attribute name value is sufficient and
 *   no property name needs to be specified.
 */
function element_set_attributes(array &$element, array $map) {
  foreach ($map as $property => $attribute) {
    // If the key is numeric, the attribute name needs to be taken over.
    if (is_int($property)) {
      $property = '#' . $attribute;
    }
    // Do not overwrite already existing attributes.
    if (isset($element[$property]) && !isset($element['#attributes'][$attribute])) {
      $element['#attributes'][$attribute] = $element[$property];
    }
  }
}

/**
 * Recursively computes the difference of arrays with additional index check.
 *
 * This is a version of array_diff_assoc() that supports multidimensional
 * arrays.
 *
 * @param array $array1
 *   The array to compare from.
 * @param array $array2
 *   The array to compare to.
 *
 * @return array
 *   Returns an array containing all the values from array1 that are not present
 *   in array2.
 */
function drupal_array_diff_assoc_recursive($array1, $array2) {
  $difference = array();

  foreach ($array1 as $key => $value) {
    if (is_array($value)) {
      if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
        $difference[$key] = $value;
      }
      else {
        $new_diff = drupal_array_diff_assoc_recursive($value, $array2[$key]);
        if (!empty($new_diff)) {
          $difference[$key] = $new_diff;
        }
      }
    }
    elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
      $difference[$key] = $value;
    }
  }

  return $difference;
}

/**
 * Sets a value in a nested array with variable depth.
 *
 * This helper function should be used when the depth of the array element you
 * are changing may vary (that is, the number of parent keys is variable). It
 * is primarily used for form structures and renderable arrays.
 *
 * Example:
 * @code
 * // Assume you have a 'signature' element somewhere in a form. It might be:
 * $form['signature_settings']['signature'] = array(
 *   '#type' => 'text_format',
 *   '#title' => t('Signature'),
 * );
 * // Or, it might be further nested:
 * $form['signature_settings']['user']['signature'] = array(
 *   '#type' => 'text_format',
 *   '#title' => t('Signature'),
 * );
 * @endcode
 *
 * To deal with the situation, the code needs to figure out the route to the
 * element, given an array of parents that is either
 * @code array('signature_settings', 'signature') @endcode in the first case or
 * @code array('signature_settings', 'user', 'signature') @endcode in the second
 * case.
 *
 * Without this helper function the only way to set the signature element in one
 * line would be using eval(), which should be avoided:
 * @code
 * // Do not do this! Avoid eval().
 * eval('$form[\'' . implode("']['", $parents) . '\'] = $element;');
 * @endcode
 *
 * Instead, use this helper function:
 * @code
 * drupal_array_set_nested_value($form, $parents, $element);
 * @endcode
 *
 * However if the number of array parent keys is static, the value should always
 * be set directly rather than calling this function. For instance, for the
 * first example we could just do:
 * @code
 * $form['signature_settings']['signature'] = $element;
 * @endcode
 *
 * @param $array
 *   A reference to the array to modify.
 * @param $parents
 *   An array of parent keys, starting with the outermost key.
 * @param $value
 *   The value to set.
 * @param $force
 *   (Optional) If TRUE, the value is forced into the structure even if it
 *   requires the deletion of an already existing non-array parent value. If
 *   FALSE, PHP throws an error if trying to add into a value that is not an
 *   array. Defaults to FALSE.
 *
 * @see drupal_array_get_nested_value()
 */
function drupal_array_set_nested_value(array &$array, array $parents, $value, $force = FALSE) {
  $ref = &$array;
  foreach ($parents as $parent) {
    // PHP auto-creates container arrays and NULL entries without error if $ref
    // is NULL, but throws an error if $ref is set, but not an array.
    if ($force && isset($ref) && !is_array($ref)) {
      $ref = array();
    }
    $ref = &$ref[$parent];
  }
  $ref = $value;
}

/**
 * Retrieves a value from a nested array with variable depth.
 *
 * This helper function should be used when the depth of the array element being
 * retrieved may vary (that is, the number of parent keys is variable). It is
 * primarily used for form structures and renderable arrays.
 *
 * Without this helper function the only way to get a nested array value with
 * variable depth in one line would be using eval(), which should be avoided:
 * @code
 * // Do not do this! Avoid eval().
 * // May also throw a PHP notice, if the variable array keys do not exist.
 * eval('$value = $array[\'' . implode("']['", $parents) . "'];");
 * @endcode
 *
 * Instead, use this helper function:
 * @code
 * $value = drupal_array_get_nested_value($form, $parents);
 * @endcode
 *
 * A return value of NULL is ambiguous, and can mean either that the requested
 * key does not exist, or that the actual value is NULL. If it is required to
 * know whether the nested array key actually exists, pass a third argument that
 * is altered by reference:
 * @code
 * $key_exists = NULL;
 * $value = drupal_array_get_nested_value($form, $parents, $key_exists);
 * if ($key_exists) {
 *   // ... do something with $value ...
 * }
 * @endcode
 *
 * However if the number of array parent keys is static, the value should always
 * be retrieved directly rather than calling this function. For instance:
 * @code
 * $value = $form['signature_settings']['signature'];
 * @endcode
 *
 * @param $array
 *   The array from which to get the value.
 * @param $parents
 *   An array of parent keys of the value, starting with the outermost key.
 * @param $key_exists
 *   (optional) If given, an already defined variable that is altered by
 *   reference.
 *
 * @return
 *   The requested nested value. Possibly NULL if the value is NULL or not all
 *   nested parent keys exist. $key_exists is altered by reference and is a
 *   Boolean that indicates whether all nested parent keys exist (TRUE) or not
 *   (FALSE). This allows to distinguish between the two possibilities when NULL
 *   is returned.
 *
 * @see drupal_array_set_nested_value()
 */
function &drupal_array_get_nested_value(array &$array, array $parents, &$key_exists = NULL) {
  $ref = &$array;
  foreach ($parents as $parent) {
    if (is_array($ref) && array_key_exists($parent, $ref)) {
      $ref = &$ref[$parent];
    }
    else {
      $key_exists = FALSE;
      $null = NULL;
      return $null;
    }
  }
  $key_exists = TRUE;
  return $ref;
}

/**
 * Determines whether a nested array contains the requested keys.
 *
 * This helper function should be used when the depth of the array element to be
 * checked may vary (that is, the number of parent keys is variable). See
 * drupal_array_set_nested_value() for details. It is primarily used for form
 * structures and renderable arrays.
 *
 * If it is required to also get the value of the checked nested key, use
 * drupal_array_get_nested_value() instead.
 *
 * If the number of array parent keys is static, this helper function is
 * unnecessary and the following code can be used instead:
 * @code
 * $value_exists = isset($form['signature_settings']['signature']);
 * $key_exists = array_key_exists('signature', $form['signature_settings']);
 * @endcode
 *
 * @param $array
 *   The array with the value to check for.
 * @param $parents
 *   An array of parent keys of the value, starting with the outermost key.
 *
 * @return
 *   TRUE if all the parent keys exist, FALSE otherwise.
 *
 * @see drupal_array_get_nested_value()
 */
function drupal_array_nested_key_exists(array $array, array $parents) {
  // Although this function is similar to PHP's array_key_exists(), its
  // arguments should be consistent with drupal_array_get_nested_value().
  $key_exists = NULL;
  drupal_array_get_nested_value($array, $parents, $key_exists);
  return $key_exists;
}

/**
 * Provides theme registration for themes across .inc files.
 */
function drupal_common_theme() {
  return array(
    // From theme.inc.
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
      'variables' => array('path' => NULL, 'width' => NULL, 'height' => NULL, 'alt' => '', 'title' => NULL, 'attributes' => array()),
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
    // From theme.maintenance.inc.
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
    // From pager.inc.
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
    // From menu.inc.
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
      'variables' => array('primary' => array(), 'secondary' => array()),
    ),
    // From form.inc.
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
    'exposed_filters' => array(
      'render element' => 'form',
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
      'render element' => 'element',
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
 * @addtogroup schemaapi
 * @{
 */

/**
 * Creates all tables defined in a module's hook_schema().
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
  _drupal_schema_initialize($schema, $module, FALSE);

  foreach ($schema as $name => $table) {
    db_create_table($name, $table);
  }
}

/**
 * Removes all tables defined in a module's hook_schema().
 *
 * Note: This function does not pass the module's schema through
 * hook_schema_alter(). The module's tables will be created exactly as the
 * module defines them.
 *
 * @param $module
 *   The module for which the tables will be removed.
 *
 * @return
 *   An array of arrays with the following key/value pairs:
 *    - success: a boolean indicating whether the query succeeded.
 *    - query: the SQL query(s) executed, passed through check_plain().
 */
function drupal_uninstall_schema($module) {
  $schema = drupal_get_schema_unprocessed($module);
  _drupal_schema_initialize($schema, $module, FALSE);

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
 * definitions won't be cached. To retrieve the schema after
 * hook_schema_alter() has been invoked use drupal_get_schema().
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

  if (isset($table) && isset($schema[$table])) {
    return $schema[$table];
  }
  elseif (!empty($schema)) {
    return $schema;
  }
  return array();
}

/**
 * Fills in required default values for table definitions from hook_schema().
 *
 * @param $schema
 *   The schema definition array as it was returned by the module's
 *   hook_schema().
 * @param $module
 *   The module for which hook_schema() was invoked.
 * @param $remove_descriptions
 *   (optional) Whether to additionally remove 'description' keys of all tables
 *   and fields to improve performance of serialize() and unserialize().
 *   Defaults to TRUE.
 */
function _drupal_schema_initialize(&$schema, $module, $remove_descriptions = TRUE) {
  // Set the name and module key for all tables.
  foreach ($schema as $name => &$table) {
    if (empty($table['module'])) {
      $table['module'] = $module;
    }
    if (!isset($table['name'])) {
      $table['name'] = $name;
    }
    if ($remove_descriptions) {
      unset($table['description']);
      foreach ($table['fields'] as &$field) {
        unset($field['description']);
      }
    }
  }
}

/**
 * Retrieves the type for every field in a table schema.
 *
 * @param $table
 *   The name of the table from which to retrieve type information.
 *
 * @return
 *   An array of types, keyed by field name.
 */
function drupal_schema_field_types($table) {
  $table_schema = drupal_get_schema($table);
  $field_types = array();
  foreach ($table_schema['fields'] as $field_name => $field_info) {
    $field_types[$field_name] = isset($field_info['type']) ? $field_info['type'] : NULL;
  }
  return $field_types;
}

/**
 * Retrieves a list of fields from a table schema.
 *
 * The returned list is suitable for use in an SQL query.
 *
 * @param $table
 *   The name of the table from which to retrieve fields.
 * @param
 *   An optional prefix to to all fields.
 *
 * @return An array of fields.
 */
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
 * Saves (inserts or updates) a record to the database based upon the schema.
 *
 * Do not use drupal_write_record() within hook_update_N() functions, since the
 * database schema cannot be relied upon when a user is running a series of
 * updates. Instead, use db_insert() or db_update() to save the record.
 *
 * @param $table
 *   The name of the table; this must be defined by a hook_schema()
 *   implementation.
 * @param $record
 *   An object or array representing the record to write, passed in by
 *   reference. If inserting a new record, values not provided in $record will
 *   be populated in $record and in the database with the default values from
 *   the schema, as well as a single serial (auto-increment) field (if present).
 *   If updating an existing record, only provided values are updated in the
 *   database, and $record is not modified.
 * @param $primary_keys
 *   To indicate that this is a new record to be inserted, omit this argument.
 *   If this is an update, this argument specifies the primary keys' field
 *   names. If there is only 1 field in the key, you may pass in a string; if
 *   there are multiple fields in the key, pass in an array.
 *
 * @return
 *   If the record insert or update failed, returns FALSE. If it succeeded,
 *   returns SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
 */
function drupal_write_record($table, &$record, $primary_keys = array()) {
  // Standardize $primary_keys to an array.
  if (is_string($primary_keys)) {
    $primary_keys = array($primary_keys);
  }

  $schema = drupal_get_schema($table);
  if (empty($schema)) {
    return FALSE;
  }

  $object = (object) $record;
  $fields = array();

  // Go through the schema to determine fields to write.
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

    // Skip field if it is in $primary_keys as it is unnecessary to update a
    // field to the value it is already set to.
    if (in_array($field, $primary_keys)) {
      continue;
    }

    if (!property_exists($object, $field)) {
      // Skip fields that are not provided, default values are already known
      // by the database.
      continue;
    }

    // Build array of fields to update or insert.
    if (empty($info['serialize'])) {
      $fields[$field] = $object->$field;
    }
    else {
      $fields[$field] = serialize($object->$field);
    }

    // Type cast to proper datatype, except when the value is NULL and the
    // column allows this.
    //
    // MySQL PDO silently casts e.g. FALSE and '' to 0 when inserting the value
    // into an integer column, but PostgreSQL PDO does not. Also type cast NULL
    // when the column does not allow this.
    if (isset($object->$field) || !empty($info['not null'])) {
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

  // If we are inserting, populate empty fields with default values.
  if (empty($primary_keys)) {
    foreach ($schema['fields'] as $field => $info) {
      if (isset($info['default']) && !property_exists($object, $field)) {
        $object->$field = $info['default'];
      }
    }
  }

  // If we began with an array, convert back.
  if (is_array($record)) {
    $record = (array) $object;
  }

  return $return;
}

/**
 * @} End of "addtogroup schemaapi".
 */

/**
 * Parses Drupal module and theme .info files.
 *
 * Info files are NOT for placing arbitrary theme and module-specific settings.
 * Use variable_get() and variable_set() for that.
 *
 * Information stored in a module .info file:
 * - name: The real name of the module for display purposes.
 * - description: A brief description of the module.
 * - dependencies: An array of dependency strings. Each is in the form
 *   'project:module (versions)'; with the following meanings:
 *   - project: (optional) Project shortname, recommended to ensure uniqueness,
 *     if the module is part of a project hosted on drupal.org. If omitted,
 *     also omit the : that follows. The project name is currently ignored by
 *     Drupal core but is used for automated testing.
 *   - module: (required) Module shortname within the project.
 *   - (versions): Optional version information, consisting of one or more
 *     comma-separated operator/value pairs or simply version numbers, which
 *     can contain "x" as a wildcard. Examples: (>=7.22, <7.28), (7.x-3.x).
 * - package: The name of the package of modules this module belongs to.
 *
 * See forum.info for an example of a module .info file.
 *
 * Information stored in a theme .info file:
 * - name: The real name of the theme for display purposes.
 * - description: Brief description.
 * - screenshot: Path to screenshot relative to the theme's .info file.
 * - engine: Theme engine; typically phptemplate.
 * - base: Name of a base theme, if applicable; e.g., base = zen.
 * - regions: Listed regions; e.g., region[left] = Left sidebar.
 * - features: Features available; e.g., features[] = logo.
 * - stylesheets: Theme stylesheets; e.g., stylesheets[all][] = my-style.css.
 * - scripts: Theme scripts; e.g., scripts[] = my-script.js.
 *
 * See bartik.info for an example of a theme .info file.
 *
 * @param $filename
 *   The file we are parsing. Accepts file with relative or absolute path.
 *
 * @return
 *   The info array.
 *
 * @see drupal_parse_info_format()
 */
function drupal_parse_info_file($filename) {
  $info = &drupal_static(__FUNCTION__, array());

  if (!isset($info[$filename])) {
    if (!file_exists($filename)) {
      $info[$filename] = array();
    }
    else {
      $data = file_get_contents($filename);
      $info[$filename] = drupal_parse_info_format($data);
    }
  }
  return $info[$filename];
}

/**
 * Parses data in Drupal's .info format.
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
 *
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
      // Fetch the key and value string.
      $i = 0;
      foreach (array('key', 'value1', 'value2', 'value3') as $var) {
        $$var = isset($match[++$i]) ? $match[$i] : '';
      }
      $value = stripslashes(substr($value1, 1, -1)) . stripslashes(substr($value2, 1, -1)) . $value3;

      // Parse array syntax.
      $keys = preg_split('/\]?\[/', rtrim($key, ']'));
      $last = array_pop($keys);
      $parent = &$info;

      // Create nested arrays.
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
      if (preg_match('/^\w+$/i', $value) && defined($value)) {
        $value = constant($value);
      }

      // Insert actual value.
      if ($last == '') {
        $last = count($parent);
      }
      $parent[$last] = $value;
    }
  }

  return $info;
}

/**
 * Returns a list of severity levels, as defined in RFC 3164.
 *
 * @return
 *   Array of the possible severity levels for log messages.
 *
 * @see http://www.ietf.org/rfc/rfc3164.txt
 * @see watchdog()
 * @ingroup logging_severity_levels
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
 * Explodes a string of tags into an array.
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
 * Implodes an array of tags into a string.
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
 * Flushes all cached data on the site.
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

  entity_info_cache_clear();
  node_types_rebuild();
  // node_menu() defines menu items based on node types so it needs to come
  // after node types are rebuilt.
  menu_rebuild();

  // Synchronize to catch any actions that were added or removed.
  actions_synchronize();

  // Don't clear cache_form - in-progress form submissions may break.
  // Ordered so clearing the page cache will always be the last action.
  $core = array('cache', 'cache_path', 'cache_filter', 'cache_bootstrap', 'cache_page');
  $cache_tables = array_merge(module_invoke_all('flush_caches'), $core);
  foreach ($cache_tables as $table) {
    cache_clear_all('*', $table, TRUE);
  }

  // Rebuild the bootstrap module list. We do this here so that developers
  // can get new hook_boot() implementations registered without having to
  // write a hook_update_N() function.
  _system_update_bootstrap_status();
}

/**
 * Changes the dummy query string added to all CSS and JavaScript files.
 *
 * Changing the dummy query string appended to CSS and JavaScript files forces
 * all browsers to reload fresh files.
 */
function _drupal_flush_css_js() {
  // The timestamp is converted to base 36 in order to make it more compact.
  variable_set('css_js_query_string', base_convert(REQUEST_TIME, 10, 36));
}

/**
 * Outputs debug information.
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
  $string = check_plain($print_r ? print_r($data, TRUE) : var_export($data, TRUE));

  // Display values with pre-formatting to increase readability.
  $string = '<pre>' . $string . '</pre>';

  trigger_error(trim($label ? "$label: $string" : $string));
}

/**
 * Parses a dependency for comparison by drupal_check_incompatibility().
 *
 * @param $dependency
 *   A dependency string, which specifies a module dependency, and optionally
 *   the project it comes from and versions that are supported. Supported
 *   formats include:
 *   - 'module'
 *   - 'project:module'
 *   - 'project:module (>=version, version)'
 *
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
  $value = array();
  // Split out the optional project name.
  if (strpos($dependency, ':')) {
    list($project_name, $dependency) = explode(':', $dependency);
    $value['project'] = $project_name;
  }
  // We use named subpatterns and support every op that version_compare
  // supports. Also, op is optional and defaults to equals.
  $p_op = '(?P<operation>!=|==|=|<|<=|>|>=|<>)?';
  // Core version is always optional: 7.x-2.x and 2.x is treated the same.
  $p_core = '(?:' . preg_quote(DRUPAL_CORE_COMPATIBILITY) . '-)?';
  $p_major = '(?P<major>\d+)';
  // By setting the minor version to x, branches can be matched.
  $p_minor = '(?P<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
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
 * Checks whether a version is compatible with a given dependency.
 *
 * @param $v
 *   The parsed dependency structure from drupal_parse_dependency().
 * @param $current_version
 *   The version to check against (like 4.2).
 *
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
 * @param $entity_type
 *   The entity type, e.g. node, for which the info shall be returned, or NULL
 *   to return an array with info about all types.
 *
 * @see hook_entity_info()
 * @see hook_entity_info_alter()
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
        // Prepare entity schema fields SQL info for
        // DrupalEntityControllerInterface::buildQuery().
        if (isset($entity_info[$name]['base table'])) {
          $entity_info[$name]['base table field types'] = drupal_schema_field_types($entity_info[$name]['base table']);
          $entity_info[$name]['schema_fields_sql']['base table'] = drupal_schema_fields_sql($entity_info[$name]['base table']);
          if (isset($entity_info[$name]['revision table'])) {
            $entity_info[$name]['schema_fields_sql']['revision table'] = drupal_schema_fields_sql($entity_info[$name]['revision table']);
          }
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
 *
 * @return
 *   A numerically indexed array (not a hash table) containing these
 *   elements:
 *   - 0: Primary ID of the entity.
 *   - 1: Revision ID of the entity, or NULL if $entity_type is not versioned.
 *   - 2: Bundle name of the entity, or NULL if $entity_type has no bundles.
 */
function entity_extract_ids($entity_type, $entity) {
  $info = entity_get_info($entity_type);

  // Objects being created might not have id/vid yet.
  $id = isset($entity->{$info['entity keys']['id']}) ? $entity->{$info['entity keys']['id']} : NULL;
  $vid = ($info['entity keys']['revision'] && isset($entity->{$info['entity keys']['revision']})) ? $entity->{$info['entity keys']['revision']} : NULL;

  if (!empty($info['entity keys']['bundle'])) {
    // Explicitly fail for malformed entities missing the bundle property.
    if (!isset($entity->{$info['entity keys']['bundle']}) || $entity->{$info['entity keys']['bundle']} === '') {
      throw new EntityMalformedException(t('Missing bundle property on entity of type @entity_type.', array('@entity_type' => $entity_type)));
    }
    $bundle = $entity->{$info['entity keys']['bundle']};
  }
  else {
    // The entity type provides no bundle key: assume a single bundle, named
    // after the entity type.
    $bundle = $entity_type;
  }

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
 *   A numerically indexed array, as returned by entity_extract_ids().
 *
 * @return
 *   An entity structure, initialized with the ids provided.
 *
 * @see entity_extract_ids()
 */
function entity_create_stub_entity($entity_type, $ids) {
  $entity = new stdClass();
  $info = entity_get_info($entity_type);
  $entity->{$info['entity keys']['id']} = $ids[0];
  if (!empty($info['entity keys']['revision']) && isset($ids[1])) {
    $entity->{$info['entity keys']['revision']} = $ids[1];
  }
  if (!empty($info['entity keys']['bundle']) && isset($ids[2])) {
    $entity->{$info['entity keys']['bundle']} = $ids[2];
  }
  return $entity;
}

/**
 * Load entities from the database.
 *
 * The entities are stored in a static memory cache, and will not require
 * database access if loaded again during the same page request.
 *
 * The actual loading is done through a class that has to implement the
 * DrupalEntityControllerInterface interface. By default,
 * DrupalDefaultEntityController is used. Entity types can specify that a
 * different class should be used by setting the 'controller class' key in
 * hook_entity_info(). These classes can either implement the
 * DrupalEntityControllerInterface interface, or, most commonly, extend the
 * DrupalDefaultEntityController class. See node_entity_info() and the
 * NodeController in node.module as an example.
 *
 * @param $entity_type
 *   The entity type to load, e.g. node or user.
 * @param $ids
 *   An array of entity IDs, or FALSE to load all entities.
 * @param $conditions
 *   (deprecated) An associative array of conditions on the base table, where
 *   the keys are the database fields and the values are the values those
 *   fields must have. Instead, it is preferable to use EntityFieldQuery to
 *   retrieve a list of entity IDs loadable by this function.
 * @param $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return
 *   An array of entity objects indexed by their ids. When no results are
 *   found, an empty array is returned.
 *
 * @todo Remove $conditions in Drupal 8.
 *
 * @see hook_entity_info()
 * @see DrupalEntityControllerInterface
 * @see DrupalDefaultEntityController
 * @see EntityFieldQuery
 */
function entity_load($entity_type, $ids = FALSE, $conditions = array(), $reset = FALSE) {
  if ($reset) {
    entity_get_controller($entity_type)->resetCache();
  }
  return entity_get_controller($entity_type)->load($ids, $conditions);
}

/**
 * Loads the unchanged, i.e. not modified, entity from the database.
 *
 * Unlike entity_load() this function ensures the entity is directly loaded from
 * the database, thus bypassing any static cache. In particular, this function
 * is useful to determine changes by comparing the entity being saved to the
 * stored entity.
 *
 * @param $entity_type
 *   The entity type to load, e.g. node or user.
 * @param $id
 *   The ID of the entity to load.
 *
 * @return
 *   The unchanged entity, or FALSE if the entity cannot be loaded.
 */
function entity_load_unchanged($entity_type, $id) {
  entity_get_controller($entity_type)->resetCache(array($id));
  $result = entity_get_controller($entity_type)->load(array($id));
  return reset($result);
}

/**
 * Gets the entity controller for an entity type.
 *
 * @return DrupalEntityControllerInterface
 *   The entity controller object for the specified entity type.
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
 *
 * @param $entity_type
 *   The type of entity, i.e. 'node', 'user'.
 * @param $entities
 *   The entity objects which are being prepared for view, keyed by object ID.
 * @param $langcode
 *   (optional) A language code to be used for rendering. Defaults to the global
 *   content language of the current request.
 *
 * @see hook_entity_prepare_view()
 */
function entity_prepare_view($entity_type, $entities, $langcode = NULL) {
  if (!isset($langcode)) {
    $langcode = $GLOBALS['language_content']->language;
  }

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
    module_invoke_all('entity_prepare_view', $prepare, $entity_type, $langcode);
  }
}

/**
 * Invoke hook_entity_view_mode_alter().
 *
 * If adding a new entity similar to nodes, comments or users, you should invoke
 * this function during the ENTITY_build_content() or ENTITY_view_multiple()
 * phases of rendering to allow other modules to alter the view mode during this
 * phase. This function needs to be called before field_attach_prepare_view() to
 * ensure that the correct content is loaded by field API.
 *
 * @param $entity_type
 *   The type of entity, i.e. 'node', 'user'.
 * @param $entities
 *   The entity objects which are being prepared for view, keyed by object ID.
 * @param $view_mode
 *   The original view mode e.g. 'full', 'teaser'...
 * @param $langcode
 *   (optional) A language code to be used for rendering. Defaults to the global
 *   content language of the current request.
 * @return
 *   An associative array with arrays of entities keyed by view mode.
 *
 * @see hook_entity_view_mode_alter()
 */
function entity_view_mode_prepare($entity_type, $entities, $view_mode, $langcode = NULL) {
  if (!isset($langcode)) {
    $langcode = $GLOBALS['language_content']->language;
  }

  // To ensure hooks are never run after field_attach_prepare_view() only
  // process items without the entity_view_prepared flag.
  $entities_by_view_mode = array();
  foreach ($entities as $id => $entity) {
    $entity_view_mode = $view_mode;
    if (empty($entity->entity_view_prepared)) {

      // Allow modules to change the view mode.
      $context = array(
        'entity_type' => $entity_type,
        'entity' => $entity,
        'langcode' => $langcode,
      );
      drupal_alter('entity_view_mode', $entity_view_mode, $context);
    }

    $entities_by_view_mode[$entity_view_mode][$id] = $entity;
  }

  return $entities_by_view_mode;
}

/**
 * Returns the URI elements of an entity.
 *
 * @param $entity_type
 *   The entity type; e.g. 'node' or 'user'.
 * @param $entity
 *   The entity for which to generate a path.
 * @return
 *   An array containing the 'path' and 'options' keys used to build the URI of
 *   the entity, and matching the signature of url(). NULL if the entity has no
 *   URI of its own.
 */
function entity_uri($entity_type, $entity) {
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
    return NULL;
  }

  // Invoke the callback to get the URI. If there is no callback, return NULL.
  if (isset($uri_callback) && function_exists($uri_callback)) {
    $uri = $uri_callback($entity);
    // Pass the entity data to url() so that alter functions do not need to
    // lookup this entity again.
    $uri['options']['entity_type'] = $entity_type;
    $uri['options']['entity'] = $entity;
    return $uri;
  }
}

/**
 * Returns the label of an entity.
 *
 * See the 'label callback' component of the hook_entity_info() return value
 * for more information.
 *
 * @param $entity_type
 *   The entity type; e.g., 'node' or 'user'.
 * @param $entity
 *   The entity for which to generate the label.
 *
 * @return
 *   The entity label, or FALSE if not found.
 */
function entity_label($entity_type, $entity) {
  $label = FALSE;
  $info = entity_get_info($entity_type);
  if (isset($info['label callback']) && function_exists($info['label callback'])) {
    $label = $info['label callback']($entity, $entity_type);
  }
  elseif (!empty($info['entity keys']['label']) && isset($entity->{$info['entity keys']['label']})) {
    $label = $entity->{$info['entity keys']['label']};
  }

  return $label;
}

/**
 * Returns the language of an entity.
 *
 * @param $entity_type
 *   The entity type; e.g., 'node' or 'user'.
 * @param $entity
 *   The entity for which to get the language.
 *
 * @return
 *   A valid language code or NULL if the entity has no language support.
 */
function entity_language($entity_type, $entity) {
  $info = entity_get_info($entity_type);

  // Invoke the callback to get the language. If there is no callback, try to
  // get it from a property of the entity, otherwise NULL.
  if (isset($info['language callback']) && function_exists($info['language callback'])) {
    $langcode = $info['language callback']($entity_type, $entity);
  }
  elseif (!empty($info['entity keys']['language']) && isset($entity->{$info['entity keys']['language']})) {
    $langcode = $entity->{$info['entity keys']['language']};
  }
  else {
    // The value returned in D8 would be LANGUAGE_NONE, we cannot use it here to
    // preserve backward compatibility. In fact this function has been
    // introduced very late in the D7 life cycle, mainly as the proper default
    // for field_attach_form(). By returning LANGUAGE_NONE when no language
    // information is available, we would introduce a potentially BC-breaking
    // API change, since field_attach_form() defaults to the default language
    // instead of LANGUAGE_NONE. Moreover this allows us to distinguish between
    // entities that have no language specified from ones that do not have
    // language support at all.
    $langcode = NULL;
  }

  return $langcode;
}

/**
 * Attaches field API validation to entity forms.
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
 * Copies submitted values to entity properties for simple entity forms.
 *
 * During the submission handling of an entity form's "Save", "Preview", and
 * possibly other buttons, the form state's entity needs to be updated with the
 * submitted form values. Each entity form implements its own builder function
 * for doing this, appropriate for the particular entity and form, whereas
 * modules may specify additional builder functions in $form['#entity_builders']
 * for copying the form values of added form elements to entity properties.
 * Many of the main entity builder functions can call this helper function to
 * re-use its logic of copying $form_state['values'][PROPERTY] values to
 * $entity->PROPERTY for all entries in $form_state['values'] that are not field
 * data, and calling field_attach_submit() to copy field data. Apart from that
 * this helper invokes any additional builder functions that have been specified
 * in $form['#entity_builders'].
 *
 * For some entity forms (e.g., forms with complex non-field data and forms that
 * simultaneously edit multiple entities), this behavior may be inappropriate,
 * so the builder function for such forms needs to implement the required
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

  // Invoke all specified builders for copying form values to entity properties.
  if (isset($form['#entity_builders'])) {
    foreach ($form['#entity_builders'] as $function) {
      $function($entity_type, $entity, $form, $form_state);
    }
  }

  // Copy field values to the entity.
  if ($info['fieldable']) {
    field_attach_submit($entity_type, $entity, $form, $form_state);
  }
}

/**
 * Performs one or more XML-RPC request(s).
 *
 * Usage example:
 * @code
 * $result = xmlrpc('http://example.com/xmlrpc.php', array(
 *   'service.methodName' => array($parameter, $second, $third),
 * ));
 * @endcode
 *
 * @param $url
 *   An absolute URL of the XML-RPC endpoint.
 * @param $args
 *   An associative array whose keys are the methods to call and whose values
 *   are the arguments to pass to the respective method. If multiple methods
 *   are specified, a system.multicall is performed.
 * @param $options
 *   (optional) An array of options to pass along to drupal_http_request().
 *
 * @return
 *   For one request:
 *     Either the return value of the method on success, or FALSE.
 *     If FALSE is returned, see xmlrpc_errno() and xmlrpc_error_msg().
 *   For multiple requests:
 *     An array of results. Each result will either be the result
 *     returned by the method called, or an xmlrpc_error object if the call
 *     failed. See xmlrpc_error().
 */
function xmlrpc($url, $args, $options = array()) {
  require_once DRUPAL_ROOT . '/includes/xmlrpc.inc';
  return _xmlrpc($url, $args, $options);
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
 * Returns a string of supported archive extensions.
 *
 * @return
 *   A space-separated string of extensions suitable for use by the file
 *   validation system.
 */
function archiver_get_extensions() {
  $valid_extensions = array();
  foreach (archiver_get_info() as $archive) {
    foreach ($archive['extensions'] as $extension) {
      foreach (explode('.', $extension) as $part) {
        if (!in_array($part, $valid_extensions)) {
          $valid_extensions[] = $part;
        }
      }
    }
  }
  return implode(' ', $valid_extensions);
}

/**
 * Creates the appropriate archiver for the specified file.
 *
 * @param $file
 *   The full path of the archive file. Note that stream wrapper paths are
 *   supported, but not remote ones.
 *
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
 * Assembles the Drupal Updater registry.
 *
 * An Updater is a class that knows how to update various parts of the Drupal
 * file system, for example to update modules that have newer releases, or to
 * install a new theme.
 *
 * @return
 *   The Drupal Updater class registry.
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

/**
 * Assembles the Drupal FileTransfer registry.
 *
 * @return
 *   The Drupal FileTransfer class registry.
 *
 * @see FileTransfer
 * @see hook_filetransfer_info()
 * @see hook_filetransfer_info_alter()
 */
function drupal_get_filetransfer_info() {
  $info = &drupal_static(__FUNCTION__);
  if (!isset($info)) {
    // Since we have to manually set the 'file path' default for each
    // module separately, we can't use module_invoke_all().
    $info = array();
    foreach (module_implements('filetransfer_info') as $module) {
      $function = $module . '_filetransfer_info';
      if (function_exists($function)) {
        $result = $function();
        if (isset($result) && is_array($result)) {
          foreach ($result as &$values) {
            if (empty($values['file path'])) {
              $values['file path'] = drupal_get_path('module', $module);
            }
          }
          $info = array_merge_recursive($info, $result);
        }
      }
    }
    drupal_alter('filetransfer_info', $info);
    uasort($info, 'drupal_sort_weight');
  }
  return $info;
}
