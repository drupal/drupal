<?php
// $Id$

/**
 * @file
 * Functions that need to be loaded on every Drupal request.
 */

/**
 * Indicates that the item should never be removed unless explicitly told to
 * using cache_clear_all() with a cache ID.
 */
define('CACHE_PERMANENT', 0);

/**
 * Indicates that the item should be removed at the next general cache wipe.
 */
define('CACHE_TEMPORARY', -1);

/**
 * Indicates that page caching is disabled.
 */
define('CACHE_DISABLED', 0);

/**
 * Indicates that page caching is enabled, using "normal" mode.
 */
define('CACHE_NORMAL', 1);

/**
 * Indicates that page caching is using "aggressive" mode. This bypasses
 * loading any modules for additional speed, which may break functionality in
 * modules that expect to be run on each page load.
 */
define('CACHE_AGGRESSIVE', 2);

/**
 * Log message severity -- Emergency: system is unusable.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_EMERG', 0);

/**
 * Log message severity -- Alert: action must be taken immediately.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_ALERT', 1);

/**
 * Log message severity -- Critical: critical conditions.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_CRITICAL', 2);

/**
 * Log message severity -- Error: error conditions.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_ERROR', 3);

/**
 * Log message severity -- Warning: warning conditions.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_WARNING', 4);

/**
 * Log message severity -- Notice: normal but significant condition.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_NOTICE', 5);

/**
 * Log message severity -- Informational: informational messages.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_INFO', 6);

/**
 * Log message severity -- Debug: debug-level messages.
 *
 * @see watchdog()
 * @see watchdog_severity_levels()
 */
define('WATCHDOG_DEBUG', 7);

/**
 * First bootstrap phase: initialize configuration.
 */
define('DRUPAL_BOOTSTRAP_CONFIGURATION', 0);

/**
 * Second bootstrap phase: try to call a non-database cache
 * fetch routine.
 */
define('DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE', 1);

/**
 * Third bootstrap phase: initialize database layer.
 */
define('DRUPAL_BOOTSTRAP_DATABASE', 2);

/**
 * Fourth bootstrap phase: identify and reject banned hosts.
 */
define('DRUPAL_BOOTSTRAP_ACCESS', 3);

/**
 * Fifth bootstrap phase: initialize session handling.
 */
define('DRUPAL_BOOTSTRAP_SESSION', 4);

/**
 * Sixth bootstrap phase: initialize the variable system.
 */
define('DRUPAL_BOOTSTRAP_VARIABLES', 5);

/**
 * Seventh bootstrap phase: load bootstrap.inc and module.inc, start
 * the variable system and try to serve a page from the cache.
 */
define('DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE', 6);

/**
 * Eighth bootstrap phase: find out language of the page.
 */
define('DRUPAL_BOOTSTRAP_LANGUAGE', 7);

/**
 * Nineth bootstrap phase: set $_GET['q'] to Drupal path of request.
 */
define('DRUPAL_BOOTSTRAP_PATH', 8);

/**
 * Final bootstrap phase: Drupal is fully loaded; validate and fix
 * input data.
 */
define('DRUPAL_BOOTSTRAP_FULL', 9);

/**
 * Role ID for anonymous users; should match what's in the "role" table.
 */
define('DRUPAL_ANONYMOUS_RID', 1);

/**
 * Role ID for authenticated users; should match what's in the "role" table.
 */
define('DRUPAL_AUTHENTICATED_RID', 2);

/**
 * The number of bytes in a kilobyte. For more information, visit
 * http://en.wikipedia.org/wiki/Kilobyte.
 */
define('DRUPAL_KILOBYTE', 1024);

/**
 * No language negotiation. The default language is used.
 */
define('LANGUAGE_NEGOTIATION_NONE', 0);

/**
 * Path based negotiation with fallback to default language
 * if no defined path prefix identified.
 */
define('LANGUAGE_NEGOTIATION_PATH_DEFAULT', 1);

/**
 * Path based negotiation with fallback to user preferences
 * and browser language detection if no defined path prefix
 * identified.
 */
define('LANGUAGE_NEGOTIATION_PATH', 2);

/**
 * Domain based negotiation with fallback to default language
 * if no language identified by domain.
 */
define('LANGUAGE_NEGOTIATION_DOMAIN', 3);

/**
 * Language written left to right. Possible value of $language->direction.
 */
define('LANGUAGE_LTR', 0);

/**
 * Language written right to left. Possible value of $language->direction.
 */
define('LANGUAGE_RTL', 1);

/**
 * For convenience, define a short form of the request time global.
 */
define('REQUEST_TIME', $_SERVER['REQUEST_TIME']);

/**
 * @name Title text filtering flags
 * @{
 * Flags for use in drupal_set_title().
 */

/**
 * Flag for drupal_set_title(); text is not sanitized, so run check_plain().
 */
define('CHECK_PLAIN', 0);

/**
 * Flag for drupal_set_title(); text has already been sanitized.
 */
define('PASS_THROUGH', -1);

/**
 * Signals that the registry lookup cache should be reset.
 */
define('REGISTRY_RESET_LOOKUP_CACHE', 1);

/**
 * Signals that the registry lookup cache should be written to storage.
 */
define('REGISTRY_WRITE_LOOKUP_CACHE', 2);

/**
 * @} End of "Title text filtering flags".
 */


/**
 * Start the timer with the specified name. If you start and stop
 * the same timer multiple times, the measured intervals will be
 * accumulated.
 *
 * @param name
 *   The name of the timer.
 */
function timer_start($name) {
  global $timers;

  $timers[$name]['start'] = microtime(TRUE);
  $timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
}

/**
 * Read the current timer value without stopping the timer.
 *
 * @param name
 *   The name of the timer.
 * @return
 *   The current timer value in ms.
 */
function timer_read($name) {
  global $timers;

  if (isset($timers[$name]['start'])) {
    $stop = microtime(TRUE);
    $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

    if (isset($timers[$name]['time'])) {
      $diff += $timers[$name]['time'];
    }
    return $diff;
  }
}

/**
 * Stop the timer with the specified name.
 *
 * @param name
 *   The name of the timer.
 * @return
 *   A timer array. The array contains the number of times the
 *   timer has been started and stopped (count) and the accumulated
 *   timer value in ms (time).
 */
function timer_stop($name) {
  global $timers;

  $timers[$name]['time'] = timer_read($name);
  unset($timers[$name]['start']);

  return $timers[$name];
}

/**
 * Find the appropriate configuration directory.
 *
 * Try finding a matching configuration directory by stripping the website's
 * hostname from left to right and pathname from right to left. The first
 * configuration file found will be used; the remaining will ignored. If no
 * configuration file is found, return a default value '$confdir/default'.
 *
 * Example for a fictitious site installed at
 * http://www.drupal.org:8080/mysite/test/ the 'settings.php' is searched in
 * the following directories:
 *
 *  1. $confdir/8080.www.drupal.org.mysite.test
 *  2. $confdir/www.drupal.org.mysite.test
 *  3. $confdir/drupal.org.mysite.test
 *  4. $confdir/org.mysite.test
 *
 *  5. $confdir/8080.www.drupal.org.mysite
 *  6. $confdir/www.drupal.org.mysite
 *  7. $confdir/drupal.org.mysite
 *  8. $confdir/org.mysite
 *
 *  9. $confdir/8080.www.drupal.org
 * 10. $confdir/www.drupal.org
 * 11. $confdir/drupal.org
 * 12. $confdir/org
 *
 * 13. $confdir/default
 *
 * If a file named sites.php is present in the $confdir, it will be loaded
 * prior to scanning for directories. It should define an associative array
 * named $sites, which maps domains to directories. It should be in the form
 * of:
 *
 * $sites = array(
 *   'The url to alias' => 'A directory within the sites directory'
 * );
 *
 * For example:
 *
 * $sites = array(
 *   'devexample.com' => 'example.com',
 *   'localhost/example' => 'example.com',
 * );
 *
 * The above array will cause Drupal to look for a directory named
 * "example.com" in the sites directory whenever a request comes from
 * "example.com", "devexample.com", or "localhost/example". That is useful
 * on development servers, where the domain name may not be the same as the
 * domain of the live server. Since Drupal stores file paths into the database
 * (files, system table, etc.) this will ensure the paths are correct while
 * accessed on development servers.
 *
 * @param $require_settings
 *   Only configuration directories with an existing settings.php file
 *   will be recognized. Defaults to TRUE. During initial installation,
 *   this is set to FALSE so that Drupal can detect a matching directory,
 *   then create a new settings.php file in it.
 * @param reset
 *   Force a full search for matching directories even if one had been
 *   found previously.
 * @return
 *   The path of the matching directory.
 */
function conf_path($require_settings = TRUE, $reset = FALSE) {
  $conf = &drupal_static(__FUNCTION__, '');

  if ($conf && !$reset) {
    return $conf;
  }

  $confdir = 'sites';

  $sites = array();
  if (file_exists(DRUPAL_ROOT . '/' . $confdir . '/sites.php')) {
    // This will overwrite $sites with the desired mappings.
    include(DRUPAL_ROOT . '/' . $confdir . '/sites.php');
  }

  $uri = explode('/', $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : $_SERVER['SCRIPT_FILENAME']);
  $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
  for ($i = count($uri) - 1; $i > 0; $i--) {
    for ($j = count($server); $j > 0; $j--) {
      $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
      if (isset($sites[$dir]) && file_exists(DRUPAL_ROOT . '/' . $confdir . '/' . $sites[$dir])) {
        $dir = $sites[$dir];
      }
      if (file_exists(DRUPAL_ROOT . '/' . $confdir . '/' . $dir . '/settings.php') || (!$require_settings && file_exists(DRUPAL_ROOT . '/' . $confdir . '/' . $dir))) {
        $conf = "$confdir/$dir";
        return $conf;
      }
    }
  }
  $conf = "$confdir/default";
  return $conf;
}

/**
 * Initialize PHP environment.
 */
function drupal_environment_initialize() {
  if (!isset($_SERVER['HTTP_REFERER'])) {
    $_SERVER['HTTP_REFERER'] = '';
  }
  if (!isset($_SERVER['SERVER_PROTOCOL']) || ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1')) {
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
  }

  if (isset($_SERVER['HTTP_HOST'])) {
    // As HTTP_HOST is user input, ensure it only contains characters allowed
    // in hostnames. See RFC 952 (and RFC 2181).
    // $_SERVER['HTTP_HOST'] is lowercased here per specifications.
    $_SERVER['HTTP_HOST'] = strtolower($_SERVER['HTTP_HOST']);
    if (!drupal_valid_http_host($_SERVER['HTTP_HOST'])) {
      // HTTP_HOST is invalid, e.g. if containing slashes it may be an attack.
      header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
      exit;
    }
  }
  else {
    // Some pre-HTTP/1.1 clients will not send a Host header. Ensure the key is
    // defined for E_ALL compliance.
    $_SERVER['HTTP_HOST'] = '';
  }

  // Enforce E_ALL, but allow users to set levels not part of E_ALL.
  error_reporting(E_ALL | error_reporting());

  // Override PHP settings required for Drupal to work properly.
  // sites/default/default.settings.php contains more runtime settings.
  // The .htaccess file contains settings that cannot be changed at runtime.

  // Prevent PHP from generating HTML error messages.
  ini_set('html_errors', 0);
  // Don't escape quotes when reading files from the database, disk, etc.
  ini_set('magic_quotes_runtime', '0');
  // Use session cookies, not transparent sessions that puts the session id in
  // the query string.
  ini_set('session.use_cookies', '1');
  ini_set('session.use_only_cookies', '1');
  ini_set('session.use_trans_sid', '0');
  // Don't send HTTP headers using PHP's session handler.
  ini_set('session.cache_limiter', 'none');
  // Use httponly session cookies.
  ini_set('session.cookie_httponly', '1');
}

/**
 * Validate that a hostname (for example $_SERVER['HTTP_HOST']) is safe.
 *
 * @return
 *  TRUE if only containing valid characters, or FALSE otherwise.
 */
function drupal_valid_http_host($host) {
  return preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host);
}

/**
 * Loads the configuration and sets the base URL, cookie domain, and
 * session name correctly.
 */
function drupal_settings_initialize() {
  global $base_url, $base_path, $base_root;

  // Export the following settings.php variables to the global namespace
  global $databases, $db_prefix, $cookie_domain, $conf, $installed_profile, $update_free_access, $db_url;
  $conf = array();

  if (file_exists(DRUPAL_ROOT . '/' . conf_path() . '/settings.php')) {
    include_once DRUPAL_ROOT . '/' . conf_path() . '/settings.php';
  }

  if (isset($base_url)) {
    // Parse fixed base URL from settings.php.
    $parts = parse_url($base_url);
    if (!isset($parts['path'])) {
      $parts['path'] = '';
    }
    $base_path = $parts['path'] . '/';
    // Build $base_root (everything until first slash after "scheme://").
    $base_root = substr($base_url, 0, strlen($base_url) - strlen($parts['path']));
  }
  else {
    // Create base URL
    $base_root = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';

    $base_url = $base_root .= '://' . $_SERVER['HTTP_HOST'];

    // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
    // be modified by a visitor.
    if ($dir = trim(dirname($_SERVER['SCRIPT_NAME']), '\,/')) {
      $base_path = "/$dir";
      $base_url .= $base_path;
      $base_path .= '/';
    }
    else {
      $base_path = '/';
    }
  }

  if ($cookie_domain) {
    // If the user specifies the cookie domain, also use it for session name.
    $session_name = $cookie_domain;
  }
  else {
    // Otherwise use $base_url as session name, without the protocol
    // to use the same session identifiers across http and https.
    list( , $session_name) = explode('://', $base_url, 2);
    // We escape the hostname because it can be modified by a visitor.
    if (!empty($_SERVER['HTTP_HOST'])) {
      $cookie_domain = check_plain($_SERVER['HTTP_HOST']);
    }
  }
  // To prevent session cookies from being hijacked, a user can configure the
  // SSL version of their website to only transfer session cookies via SSL by
  // using PHP's session.cookie_secure setting. The browser will then use two
  // separate session cookies for the HTTPS and HTTP versions of the site. So we
  // must use different session identifiers for HTTPS and HTTP to prevent a
  // cookie collision.
  if (ini_get('session.cookie_secure')) {
    $session_name .= 'SSL';
  }
  // Strip leading periods, www., and port numbers from cookie domain.
  $cookie_domain = ltrim($cookie_domain, '.');
  if (strpos($cookie_domain, 'www.') === 0) {
    $cookie_domain = substr($cookie_domain, 4);
  }
  $cookie_domain = explode(':', $cookie_domain);
  $cookie_domain = '.' . $cookie_domain[0];
  // Per RFC 2109, cookie domains must contain at least one dot other than the
  // first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
  if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
    ini_set('session.cookie_domain', $cookie_domain);
  }
  session_name('SESS' . md5($session_name));
}

/**
 * Returns and optionally sets the filename for a system item (module,
 * theme, etc.). The filename, whether provided, cached, or retrieved
 * from the database, is only returned if the file exists.
 *
 * This function plays a key role in allowing Drupal's resources (modules
 * and themes) to be located in different places depending on a site's
 * configuration. For example, a module 'foo' may legally be be located
 * in any of these three places:
 *
 * modules/foo/foo.module
 * sites/all/modules/foo/foo.module
 * sites/example.com/modules/foo/foo.module
 *
 * Calling drupal_get_filename('module', 'foo') will give you one of
 * the above, depending on where the module is located.
 *
 * @param $type
 *   The type of the item (i.e. theme, theme_engine, module).
 * @param $name
 *   The name of the item for which the filename is requested.
 * @param $filename
 *   The filename of the item if it is to be set explicitly rather
 *   than by consulting the database.
 *
 * @return
 *   The filename of the requested item.
 */
function drupal_get_filename($type, $name, $filename = NULL) {
  $files = &drupal_static(__FUNCTION__, array());

  if (!isset($files[$type])) {
    $files[$type] = array();
  }

  if (!empty($filename) && file_exists($filename)) {
    $files[$type][$name] = $filename;
  }
  elseif (isset($files[$type][$name])) {
    // nothing
  }
  // Verify that we have an active database connection, before querying
  // the database. This is required because this function is called both
  // before we have a database connection (i.e. during installation) and
  // when a database connection fails.
  elseif (db_is_active() && (($file = db_query("SELECT filename FROM {system} WHERE name = :name AND type = :type", array(':name' => $name, ':type' => $type))->fetchField()) && file_exists($file))) {
    $files[$type][$name] = $file;
  }
  else {
    // Fallback to searching the filesystem if the database connection is
    // not established or the requested file is not found.
    $config = conf_path();
    $dir = (($type == 'theme_engine') ? 'themes/engines' : "${type}s");
    $file = (($type == 'theme_engine') ? "$name.engine" : "$name.$type");

    foreach (array("$config/$dir/$file", "$config/$dir/$name/$file", "$dir/$file", "$dir/$name/$file") as $file) {
      if (file_exists($file)) {
        $files[$type][$name] = $file;
        break;
      }
    }
  }

  if (isset($files[$type][$name])) {
    return $files[$type][$name];
  }
}

/**
 * Load the persistent variable table.
 *
 * The variable table is composed of values that have been saved in the table
 * with variable_set() as well as those explicitly specified in the configuration
 * file.
 */
function variable_initialize($conf = array()) {
  // NOTE: caching the variables improves performance by 20% when serving cached pages.
  if ($cached = cache_get('variables', 'cache')) {
    $variables = $cached->data;
  }
  else {
    $variables = array_map('unserialize', db_query('SELECT name, value FROM {variable}')->fetchAllKeyed());
    cache_set('variables', $variables);
  }

  foreach ($conf as $name => $value) {
    $variables[$name] = $value;
  }

  return $variables;
}

/**
 * Return a persistent variable.
 *
 * @param $name
 *   The name of the variable to return.
 * @param $default
 *   The default value to use if this variable has never been set.
 * @return
 *   The value of the variable.
 */
function variable_get($name, $default = NULL) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}

/**
 * Set a persistent variable.
 *
 * @param $name
 *   The name of the variable to set.
 * @param $value
 *   The value to set. This can be any PHP data type; these functions take care
 *   of serialization as necessary.
 */
function variable_set($name, $value) {
  global $conf;

  db_merge('variable')->key(array('name' => $name))->fields(array('value' => serialize($value)))->execute();

  cache_clear_all('variables', 'cache');

  $conf[$name] = $value;
}

/**
 * Unset a persistent variable.
 *
 * @param $name
 *   The name of the variable to undefine.
 */
function variable_del($name) {
  global $conf;

  db_delete('variable')
    ->condition('name', $name)
    ->execute();
  cache_clear_all('variables', 'cache');

  unset($conf[$name]);
}

/**
 * Retrieve the current page from the cache.
 *
 * Note: we do not serve cached pages to authenticated users, or to anonymous
 * users when $_SESSION is non-empty. $_SESSION may contain status messages
 * from a form submission, the contents of a shopping cart, or other user-
 * specific content that should not be cached and displayed to other users.
 *
 * @return
 *   The cache object, if the page was found in the cache, NULL otherwise.
 */
function drupal_page_get_cache() {
  global $base_root;

  if (drupal_page_is_cacheable()) {
    return cache_get($base_root . request_uri(), 'cache_page');
  }
}

/**
 * Determine the cacheability of the current page.
 *
 * @param $allow_caching
 *  Set to FALSE if you want to prevent this page to get cached.
 * @return
 *  TRUE if the current page can be cached, FALSE otherwise.
 */
function drupal_page_is_cacheable($allow_caching = NULL) {
  $allow_caching_static = &drupal_static(__FUNCTION__, TRUE);
  if (isset($allow_caching)) {
    $allow_caching_static = $allow_caching;
  }

  return $allow_caching_static && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD')
    && $_SERVER['SERVER_SOFTWARE'] !== 'PHP CLI';
}

/**
 * Includes a file with the provided type and name. This prevents
 * including a theme, engine, module, etc., more than once.
 *
 * @param $type
 *   The type of item to load (i.e. theme, theme_engine, module).
 * @param $name
 *   The name of the item to load.
 *
 * @return
 *   TRUE if the item is loaded or has already been loaded.
 */
function drupal_load($type, $name) {
  $files = &drupal_static(__FUNCTION__, array());

  if (isset($files[$type][$name])) {
    return TRUE;
  }

  $filename = drupal_get_filename($type, $name);

  if ($filename) {
    include_once DRUPAL_ROOT . '/' . $filename;
    $files[$type][$name] = TRUE;

    return TRUE;
  }

  return FALSE;
}

/**
 * Set an HTTP response header for the current page.
 *
 * Note: When sending a Content-Type header, always include a 'charset' type,
 * too. This is necessary to avoid security bugs (e.g. UTF-7 XSS).
 *
 * @param $name
 *   The HTTP header name, or a status code followed by a reason phrase, e.g.
 *   "404 Not Found".
 * @param $value
 *   The HTTP header value; if omitted, the specified header is unset.
 * @param $append
 *   Whether to append the value to an existing header or to replace it.
 */
function drupal_set_header($name = NULL, $value = NULL, $append = FALSE) {
  // The headers as name/value pairs.
  $headers = &drupal_static(__FUNCTION__, array());

  if (!isset($name)) {
    return $headers;
  }

  // Save status codes using the special key ":status".
  if (preg_match('/^\d{3} /', $name)) {
    $value = $name;
    $name = $name_lower = ':status';
  }
  else {
    $name_lower = strtolower($name);
  }
  _drupal_set_preferred_header_name($name);

  if (!isset($value)) {
    $headers[$name_lower] = FALSE;
  }
  elseif (isset($headers[$name_lower]) && $append) {
    // Multiple headers with identical names may be combined using comma (RFC
    // 2616, section 4.2).
    $headers[$name_lower] .= ',' . $value;
  }
  else {
    $headers[$name_lower] = $value;
  }
  drupal_send_headers(array($name => $headers[$name_lower]), TRUE);
}

/**
 * Get the HTTP response headers for the current page.
 *
 * @param $name
 *   An HTTP header name. If omitted, all headers are returned as name/value
 *   pairs. If an array value is FALSE, the header has been unset.
 * @return
 *   A string containing the header value, or FALSE if the header has been set,
 *   or NULL if the header has not been set.
 */
function drupal_get_header($name = NULL) {
  $headers = drupal_set_header();
  if (isset($name)) {
    $name = strtolower($name);
    return isset($headers[$name]) ? $headers[$name] : NULL;
  }
  else {
    return $headers;
  }
}

/**
 * Header names are case-insensitive, but for maximum compatibility they should
 * follow "common form" (see RFC 2617, section 4.2).
 */
function _drupal_set_preferred_header_name($name = NULL) {
  static $header_names = array();

  if (!isset($name)) {
    return $header_names;
  }
  $header_names[strtolower($name)] = $name;
}

/**
 * Send the HTTP response headers previously set using drupal_set_header().
 * Add default headers, unless they have been replaced or unset using
 * drupal_set_header().
 *
 * @param $default_headers
 *   An array of headers as name/value pairs.
 * @param $single
 *   If TRUE and headers have already be sent, send only the specified header.
 */
function drupal_send_headers($default_headers = array(), $only_default = FALSE) {
  $headers_sent = &drupal_static(__FUNCTION__, FALSE);
  $headers = drupal_get_header();
  if ($only_default && $headers_sent) {
    $headers = array();
  }
  $headers_sent = TRUE;

  $header_names = _drupal_set_preferred_header_name();
  foreach ($default_headers as $name => $value) {
    $name_lower = strtolower($name);
    if (!isset($headers[$name_lower])) {
      $headers[$name_lower] = $value;
      $header_names[$name_lower] = $name;
    }
  }
  foreach ($headers as $name_lower => $value) {
    if ($name_lower == ':status') {
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $value);
    }
    // Skip headers that have been unset.
    elseif ($value) {
      header($header_names[$name_lower] . ': ' . $value);
    }
  }
}

/**
 * Set HTTP headers in preparation for a page response.
 *
 * Authenticated users are always given a 'no-cache' header, and will fetch a
 * fresh page on every request. This prevents authenticated users from seeing
 * locally cached pages.
 *
 * Also give each page a unique ETag. This will force clients to include both
 * an If-Modified-Since header and an If-None-Match header when doing
 * conditional requests for the page (required by RFC 2616, section 13.3.4),
 * making the validation more robust. This is a workaround for a bug in Mozilla
 * Firefox that is triggered when Drupal's caching is enabled and the user
 * accesses Drupal via an HTTP proxy (see
 * https://bugzilla.mozilla.org/show_bug.cgi?id=269303): When an authenticated
 * user requests a page, and then logs out and requests the same page again,
 * Firefox may send a conditional request based on the page that was cached
 * locally when the user was logged in. If this page did not have an ETag
 * header, the request only contains an If-Modified-Since header. The date will
 * be recent, because with authenticated users the Last-Modified header always
 * refers to the time of the request. If the user accesses Drupal via a proxy
 * server, and the proxy already has a cached copy of the anonymous page with an
 * older Last-Modified date, the proxy may respond with 304 Not Modified, making
 * the client think that the anonymous and authenticated pageviews are
 * identical.
 *
 * @see drupal_page_set_cache()
 */
function drupal_page_header() {
  $headers_sent = &drupal_static(__FUNCTION__, FALSE);
  if ($headers_sent) {
    return TRUE;
  }
  $headers_sent = TRUE;

  $default_headers = array(
    'Expires' => 'Sun, 19 Nov 1978 05:00:00 GMT',
    'Last-Modified' => gmdate(DATE_RFC1123, REQUEST_TIME),
    'Cache-Control' => 'no-cache, must-revalidate, post-check=0, pre-check=0',
    'ETag' => '"' . REQUEST_TIME . '"',
  );
  drupal_send_headers($default_headers);
}

/**
 * Set HTTP headers in preparation for a cached page response.
 *
 * The headers allow as much as possible in proxies and browsers without any
 * particular knowledge about the pages. Modules can override these headers
 * using drupal_set_header().
 *
 * If the request is conditional (using If-Modified-Since and If-None-Match),
 * and the conditions match those currently in the cache, a 304 Not Modified
 * response is sent.
 */
function drupal_serve_page_from_cache(stdClass $cache) {
  // Negotiate whether to use compression.
  $page_compression = variable_get('page_compression', TRUE) && extension_loaded('zlib');
  $return_compressed = $page_compression && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE;

  // Get headers set in hook_boot(). Keys are lower-case.
  $hook_boot_headers = drupal_get_header();

  // Headers generated in this function, that may be replaced or unset using
  // drupal_set_headers(). Keys are mixed-case.
  $default_headers = array();

  foreach ($cache->headers as $name => $value) {
    // In the case of a 304 response, certain headers must be sent, and the
    // remaining may not (see RFC 2616, section 10.3.5). Do not override
    // headers set in hook_boot().
    $name_lower = strtolower($name);
    if (in_array($name_lower, array('content-location', 'expires', 'cache-control', 'vary')) && !isset($hook_boot_headers[$name_lower])) {
      drupal_set_header($name, $value);
      unset($cache->headers[$name]);
    }
  }

  // If a cache is served from a HTTP proxy without hitting the web server,
  // the boot and exit hooks cannot be fired, so only allow caching in
  // proxies with aggressive caching. If the client send a session cookie, do
  // not bother caching the page in a public proxy, because the cached copy
  // will only be served to that particular user due to Vary: Cookie, unless
  // the Vary header has been replaced or unset in hook_boot() (see below).
  $max_age = variable_get('cache') == CACHE_AGGRESSIVE && (!isset($_COOKIE[session_name()]) || isset($hook_boot_headers['vary'])) ? variable_get('cache_lifetime', 0) : 0;
  $default_headers['Cache-Control'] = 'public, max-age=' . $max_age;

  // Entity tag should change if the output changes.
  $etag = '"' . $cache->created . '-' . intval($return_compressed) . '"';
  header('Etag: ' . $etag);

  // See if the client has provided the required HTTP headers.
  $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : FALSE;
  $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : FALSE;

  if ($if_modified_since && $if_none_match
      && $if_none_match == $etag // etag must match
      && $if_modified_since == $cache->created) {  // if-modified-since must match
    header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
    drupal_send_headers($default_headers);
    return;
  }

  // Send the remaining headers.
  foreach ($cache->headers as $name => $value) {
    drupal_set_header($name, $value);
  }

  $default_headers['Last-Modified'] = gmdate(DATE_RFC1123, $cache->created);

  // HTTP/1.0 proxies does not support the Vary header, so prevent any caching
  // by sending an Expires date in the past. HTTP/1.1 clients ignores the
  // Expires header if a Cache-Control: max-age= directive is specified (see RFC
  // 2616, section 14.9.3).
  $default_headers['Expires'] = 'Sun, 19 Nov 1978 05:00:00 GMT';

  drupal_send_headers($default_headers);

  // Allow HTTP proxies to cache pages for anonymous users without a session
  // cookie. The Vary header is used to indicates the set of request-header
  // fields that fully determines whether a cache is permitted to use the
  // response to reply to a subsequent request for a given URL without
  // revalidation. If a Vary header has been set in hook_boot(), it is assumed
  // that the module knows how to cache the page.
  if (!isset($hook_boot_headers['vary']) && !variable_get('omit_vary_cookie')) {
    header('Vary: Cookie');
  }

  if ($page_compression) {
    header('Vary: Accept-Encoding', FALSE);
    // If page_compression is enabled, the cache contains gzipped data.
    if ($return_compressed) {
      header('Content-Encoding: gzip');
    }
    else {
      // The client does not support compression, so unzip the data in the
      // cache. Strip the gzip header and run uncompress.
      $cache->data = gzinflate(substr(substr($cache->data, 10), 0, -8));
    }
  }

  print $cache->data;
}

/**
 * Unserializes and appends elements from a serialized string.
 *
 * @param $obj
 *   The object to which the elements are appended.
 * @param $field
 *   The attribute of $obj whose value should be unserialized.
 */
function drupal_unpack($obj, $field = 'data') {
  if ($obj->$field && $data = unserialize($obj->$field)) {
    foreach ($data as $key => $value) {
      if (!isset($obj->$key)) {
        $obj->$key = $value;
      }
    }
  }
  return $obj;
}

/**
 * Encode special characters in a plain-text string for display as HTML.
 *
 * Uses drupal_validate_utf8 to prevent cross site scripting attacks on
 * Internet Explorer 6.
 */
function check_plain($text) {
  return drupal_validate_utf8($text) ? htmlspecialchars($text, ENT_QUOTES) : '';
}

/**
 * Checks whether a string is valid UTF-8.
 *
 * All functions designed to filter input should use drupal_validate_utf8
 * to ensure they operate on valid UTF-8 strings to prevent bypass of the
 * filter.
 *
 * When text containing an invalid UTF-8 lead byte (0xC0 - 0xFF) is presented
 * as UTF-8 to Internet Explorer 6, the program may misinterpret subsequent
 * bytes. When these subsequent bytes are HTML control characters such as
 * quotes or angle brackets, parts of the text that were deemed safe by filters
 * end up in locations that are potentially unsafe; An onerror attribute that
 * is outside of a tag, and thus deemed safe by a filter, can be interpreted
 * by the browser as if it were inside the tag.
 *
 * The function does not return FALSE for strings containing character codes
 * above U+10FFFF, even though these are prohibited by RFC 3629.
 *
 * @param $text
 *   The text to check.
 * @return
 *   TRUE if the text is valid UTF-8, FALSE if not.
 */
function drupal_validate_utf8($text) {
  if (strlen($text) == 0) {
    return TRUE;
  }
  // With the PCRE_UTF8 modifier 'u', preg_match() fails silently on strings
  // containing invalid UTF-8 byte sequences. It does not reject character
  // codes above U+10FFFF (represented by 4 or more octets), though.
  return (preg_match('/^./us', $text) == 1);
}

/**
 * Since $_SERVER['REQUEST_URI'] is only available on Apache, we
 * generate an equivalent using other environment variables.
 */
function request_uri() {

  if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
  }
  else {
    if (isset($_SERVER['argv'])) {
      $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
    }
    elseif (isset($_SERVER['QUERY_STRING'])) {
      $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
    }
    else {
      $uri = $_SERVER['SCRIPT_NAME'];
    }
  }
  // Prevent multiple slashes to avoid cross site requests via the Form API.
  $uri = '/' . ltrim($uri, '/');

  return $uri;
}

/**
 * Log a system message.
 *
 * @param $type
 *   The category to which this message belongs.
 * @param $message
 *   The message to store in the log. Keep $message translatable
 *   by not concatenating dynamic values into it! Variables in the
 *   message should be added by using placeholder strings alongside
 *   the variables argument to declare the value of the placeholders.
 *   See t() for documentation on how $message and $variables interact.
 * @param $variables
 *   Array of variables to replace in the message on display or
 *   NULL if message is already translated or not possible to
 *   translate.
 * @param $severity
 *   The severity of the message, as per RFC 3164.
 * @param $link
 *   A link to associate with the message.
 *
 * @see watchdog_severity_levels()
 * @see hook_watchdog()
 */
function watchdog($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE, $link = NULL) {
  global $user, $base_root;

  static $in_error_state = FALSE;

  // It is possible that the error handling will itself trigger an error. In that case, we could
  // end up in an infinite loop. To avoid that, we implement a simple static semaphore.
  if (!$in_error_state) {
    $in_error_state = TRUE;

    // Prepare the fields to be logged
    $log_entry = array(
      'type'        => $type,
      'message'     => $message,
      'variables'   => $variables,
      'severity'    => $severity,
      'link'        => $link,
      'user'        => $user,
      'request_uri' => $base_root . request_uri(),
      'referer'     => $_SERVER['HTTP_REFERER'],
      'ip'          => ip_address(),
      'timestamp'   => REQUEST_TIME,
    );

    // Call the logging hooks to log/process the message
    foreach (module_implements('watchdog', TRUE) as $module) {
      module_invoke($module, 'watchdog', $log_entry);
    }

    // It is critical that the semaphore is only cleared here, in the parent
    // watchdog() call (not outside the loop), to prevent recursive execution.
    $in_error_state = FALSE;
  }
}

/**
 * Set a message which reflects the status of the performed operation.
 *
 * If the function is called with no arguments, this function returns all set
 * messages without clearing them.
 *
 * @param $message
 *   The message should begin with a capital letter and always ends with a
 *   period '.'.
 * @param $type
 *   The type of the message. One of the following values are possible:
 *   - 'status'
 *   - 'warning'
 *   - 'error'
 * @param $repeat
 *   If this is FALSE and the message is already set, then the message won't
 *   be repeated.
 */
function drupal_set_message($message = NULL, $type = 'status', $repeat = TRUE) {
  if ($message) {
    if (!isset($_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type] = array();
    }

    if ($repeat || !in_array($message, $_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type][] = $message;
    }

    // Mark this page has being not cacheable.
    drupal_page_is_cacheable(FALSE);
  }

  // Messages not set when DB connection fails.
  return isset($_SESSION['messages']) ? $_SESSION['messages'] : NULL;
}

/**
 * Return all messages that have been set.
 *
 * @param $type
 *   (optional) Only return messages of this type.
 * @param $clear_queue
 *   (optional) Set to FALSE if you do not want to clear the messages queue
 * @return
 *   An associative array, the key is the message type, the value an array
 *   of messages. If the $type parameter is passed, you get only that type,
 *   or an empty array if there are no such messages. If $type is not passed,
 *   all message types are returned, or an empty array if none exist.
 */
function drupal_get_messages($type = NULL, $clear_queue = TRUE) {
  if ($messages = drupal_set_message()) {
    if ($type) {
      if ($clear_queue) {
        unset($_SESSION['messages'][$type]);
      }
      if (isset($messages[$type])) {
        return array($type => $messages[$type]);
      }
    }
    else {
      if ($clear_queue) {
        unset($_SESSION['messages']);
      }
      return $messages;
    }
  }
  return array();
}

/**
 * Check to see if an IP address has been blocked.
 *
 * Blocked IP addresses are stored in the database by default. However for
 * performance reasons we allow an override in settings.php. This allows us
 * to avoid querying the database at this critical stage of the bootstrap if
 * an administrative interface for IP address blocking is not required.
 *
 * @param $ip string
 *   IP address to check.
 * @return bool
 *   TRUE if access is denied, FALSE if access is allowed.
 */
function drupal_is_denied($ip) {
  // Because this function is called on every page request, we first check
  // for an array of IP addresses in settings.php before querying the
  // database.
  $blocked_ips = variable_get('blocked_ips');
  if (isset($blocked_ips) && is_array($blocked_ips)) {
    return in_array($ip, $blocked_ips);
  }
  else {
    return (bool)db_query("SELECT 1 FROM {blocked_ips} WHERE ip = :ip", array(':ip' => $ip))->fetchField();
  }
}

/**
 * Generates a default anonymous $user object.
 *
 * @return Object - the user object.
 */
function drupal_anonymous_user($session = '') {
  $user = new stdClass();
  $user->uid = 0;
  $user->hostname = ip_address();
  $user->roles = array();
  $user->roles[DRUPAL_ANONYMOUS_RID] = 'anonymous user';
  $user->session = $session;
  $user->cache = 0;
  return $user;
}

/**
 * A string describing a phase of Drupal to load. Each phase adds to the
 * previous one, so invoking a later phase automatically runs the earlier
 * phases too. The most important usage is that if you want to access the
 * Drupal database from a script without loading anything else, you can
 * include bootstrap.inc, and call drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE).
 *
 * @param $phase
 *   A constant. Allowed values are:
 *     DRUPAL_BOOTSTRAP_CONFIGURATION: initialize configuration.
 *     DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE: try to call a non-database cache fetch routine.
 *     DRUPAL_BOOTSTRAP_DATABASE: initialize database layer.
 *     DRUPAL_BOOTSTRAP_ACCESS: identify and reject banned hosts.
 *     DRUPAL_BOOTSTRAP_SESSION: initialize session handling.
 *     DRUPAL_BOOTSTRAP_VARIABLES: initialize variable handling.
 *     DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE: load bootstrap.inc and module.inc, start
 *       the variable system and try to serve a page from the cache.
 *     DRUPAL_BOOTSTRAP_LANGUAGE: identify the language used on the page.
 *     DRUPAL_BOOTSTRAP_PATH: set $_GET['q'] to Drupal path of request.
 *     DRUPAL_BOOTSTRAP_FULL: Drupal is fully loaded, validate and fix input data.
 */
function drupal_bootstrap($phase = NULL) {
  $phases = &drupal_static(__FUNCTION__ . '_phases', array(
    DRUPAL_BOOTSTRAP_CONFIGURATION,
    DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE,
    DRUPAL_BOOTSTRAP_DATABASE,
    DRUPAL_BOOTSTRAP_ACCESS,
    DRUPAL_BOOTSTRAP_SESSION,
    DRUPAL_BOOTSTRAP_VARIABLES,
    DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE,
    DRUPAL_BOOTSTRAP_LANGUAGE,
    DRUPAL_BOOTSTRAP_PATH,
    DRUPAL_BOOTSTRAP_FULL,
  ));
  $completed_phase = &drupal_static(__FUNCTION__ . '_completed_phase', -1);

  if (isset($phase)) {
    while ($phases && $phase > $completed_phase) {
      $current_phase = array_shift($phases);
      _drupal_bootstrap($current_phase);
      $completed_phase = $current_phase;
    }
  }
  return $completed_phase;
}

/**
 * Return the current bootstrap phase for this Drupal process. The
 * current phase is the one most recently completed by
 * drupal_bootstrap().
 *
 * @see drupal_bootstrap
 */
function drupal_get_bootstrap_phase() {
  return drupal_bootstrap();
}

function _drupal_bootstrap($phase) {
  global $conf, $user;

  switch ($phase) {

    case DRUPAL_BOOTSTRAP_CONFIGURATION:
      drupal_environment_initialize();
      // Start a page timer:
      timer_start('page');
      // Initialize the configuration, including variables from settings.php.
      drupal_settings_initialize();
      break;

    case DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE:
      // Allow specifying special cache handlers in settings.php, like
      // using memcached or files for storing cache information.
      require_once DRUPAL_ROOT . '/' . variable_get('cache_inc', 'includes/cache.inc');
      // If the page_cache_fastpath is set to TRUE in settings.php and
      // page_cache_fastpath (implemented in the special implementation of
      // cache.inc) printed the page and indicated this with a returned TRUE
      // then we are done.
      if (variable_get('page_cache_fastpath', FALSE) && page_cache_fastpath()) {
        exit;
      }
      break;

    case DRUPAL_BOOTSTRAP_DATABASE:
      // Initialize the database system. Note that the connection
      // won't be initialized until it is actually requested.
      require_once DRUPAL_ROOT . '/includes/database/database.inc';
      // Register autoload functions so that we can access classes and interfaces.
      spl_autoload_register('drupal_autoload_class');
      spl_autoload_register('drupal_autoload_interface');
      break;

    case DRUPAL_BOOTSTRAP_ACCESS:
      // Deny access to blocked IP addresses - t() is not yet available.
      if (drupal_is_denied(ip_address())) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
        print 'Sorry, ' . check_plain(ip_address()) . ' has been banned.';
        exit();
      }
      break;

    case DRUPAL_BOOTSTRAP_SESSION:
      require_once DRUPAL_ROOT . '/' . variable_get('session_inc', 'includes/session.inc');
      drupal_session_initialize();
      break;

    case DRUPAL_BOOTSTRAP_VARIABLES:
      // Load variables from the database, but do not overwrite variables set in settings.php.
      $conf = variable_initialize(isset($conf) ? $conf : array());
      break;

    case DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE:
      $cache_mode = variable_get('cache', CACHE_DISABLED);

      // Get the page from the cache.
      if ($cache_mode != CACHE_DISABLED) {
        $cache = drupal_page_get_cache();
      }
      else {
        $cache = FALSE;
      }

      // If the skipping of the bootstrap hooks is not enforced, call hook_boot.
      if (!$cache || $cache_mode != CACHE_AGGRESSIVE) {
        // Load module handling.
        require_once DRUPAL_ROOT . '/includes/module.inc';
        module_invoke_all('boot');
      }

      // If there is a cached page, display it.
      if ($cache) {
        header('X-Drupal-Cache: HIT');
        drupal_serve_page_from_cache($cache);
        // If the skipping of the bootstrap hooks is not enforced, call hook_exit.
        if ($cache_mode != CACHE_AGGRESSIVE) {
          module_invoke_all('exit');
        }
        // We are done.
        exit;
      }

      if (!$cache && drupal_page_is_cacheable()) {
        header('X-Drupal-Cache: MISS');
      }

      // Prepare for non-cached page workflow.
      if ($_SERVER['SERVER_SOFTWARE'] !== 'PHP CLI') {
        ob_start();
        drupal_page_header();
      }
      break;

    case DRUPAL_BOOTSTRAP_LANGUAGE:
      drupal_language_initialize();
      break;

    case DRUPAL_BOOTSTRAP_PATH:
      require_once DRUPAL_ROOT . '/includes/path.inc';
      // Initialize $_GET['q'] prior to loading modules and invoking hook_init().
      drupal_path_initialize();
      break;

    case DRUPAL_BOOTSTRAP_FULL:
      require_once DRUPAL_ROOT . '/includes/common.inc';
      _drupal_bootstrap_full();
      break;
  }
}

/**
 * Enables use of the theme system without requiring database access.
 *
 * Loads and initializes the theme system for site installs, updates and when
 * the site is in maintenance mode. This also applies when the database fails.
 *
 * @see _drupal_maintenance_theme()
 */
function drupal_maintenance_theme() {
  require_once DRUPAL_ROOT . '/includes/theme.maintenance.inc';
  _drupal_maintenance_theme();
}

/**
 * Return the name of the localization function. Use in code that needs to
 * run both during installation and normal operation.
 */
function get_t() {
  static $t;
  // This is not converted to drupal_static because there is no point in
  // resetting this as it can not change in the course of a request.
  if (!isset($t)) {
    $t = function_exists('install_main') ? 'st' : 't';
  }
  return $t;
}

/**
 *  Choose a language for the current page, based on site and user preferences.
 */
function drupal_language_initialize() {
  global $language, $user;

  // Ensure the language is correctly returned, even without multilanguage support.
  // Useful for eg. XML/HTML 'lang' attributes.
  if (variable_get('language_count', 1) == 1) {
    $language = language_default();
  }
  else {
    include_once DRUPAL_ROOT . '/includes/language.inc';
    $language = language_initialize();
  }
}

/**
 * Get a list of languages set up indexed by the specified key
 *
 * @param $field The field to index the list with.
 */
function language_list($field = 'language') {
  $languages = &drupal_static(__FUNCTION__);
  // Init language list
  if (!isset($languages)) {
    if (variable_get('language_count', 1) > 1 || module_exists('locale')) {
      $languages['language'] = db_query('SELECT * FROM {languages} ORDER BY weight ASC, name ASC')->fetchAllAssoc('language');
    }
    else {
      // No locale module, so use the default language only.
      $default = language_default();
      $languages['language'][$default->language] = $default;
    }
  }

  // Return the array indexed by the right field
  if (!isset($languages[$field])) {
    $languages[$field] = array();
    foreach ($languages['language'] as $lang) {
      // Some values should be collected into an array
      if (in_array($field, array('enabled', 'weight'))) {
        $languages[$field][$lang->$field][$lang->language] = $lang;
      }
      else {
        $languages[$field][$lang->$field] = $lang;
      }
    }
  }
  return $languages[$field];
}

/**
 * Default language used on the site
 *
 * @param $property
 *   Optional property of the language object to return
 */
function language_default($property = NULL) {
  $language = variable_get('language_default', (object) array('language' => 'en', 'name' => 'English', 'native' => 'English', 'direction' => 0, 'enabled' => 1, 'plurals' => 0, 'formula' => '', 'domain' => '', 'prefix' => '', 'weight' => 0, 'javascript' => ''));
  return $property ? $language->$property : $language;
}

/**
 * If Drupal is behind a reverse proxy, we use the X-Forwarded-For header
 * instead of $_SERVER['REMOTE_ADDR'], which would be the IP address of
 * the proxy server, and not the client's. If Drupal is run in a cluster
 * we use the X-Cluster-Client-Ip header instead.
 *
 * @return
 *   IP address of client machine, adjusted for reverse proxy and/or cluster
 *   environments.
 */
function ip_address() {
  $ip_address = &drupal_static(__FUNCTION__);

  if (!isset($ip_address)) {
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (variable_get('reverse_proxy', 0)) {
      if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        // If an array of known reverse proxy IPs is provided, then trust
        // the XFF header if request really comes from one of them.
        $reverse_proxy_addresses = variable_get('reverse_proxy_addresses', array());
        if (!empty($reverse_proxy_addresses) && in_array($ip_address, $reverse_proxy_addresses, TRUE)) {
          // The "X-Forwarded-For" header is a comma+space separated list of IP addresses,
          // the left-most being the farthest downstream client. If there is more than
          // one proxy, we are interested in the most recent one (i.e. last one in the list).
          $ip_address_parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
          $ip_address = trim(array_pop($ip_address_parts));
        }
      }

      // When Drupal is run in a cluster environment, REMOTE_ADDR contains the IP
      // address of a server in the cluster, while the IP address of the client is
      // stored in HTTP_X_CLUSTER_CLIENT_IP.
      if (array_key_exists('HTTP_X_CLUSTER_CLIENT_IP', $_SERVER)) {
        $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
      }
    }
  }

  return $ip_address;
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
      // On some databases this function may be called before bootstrap has
      // been completed, so we force the functions we need to load just in case.
      if (drupal_function_exists('module_load_all_includes')) {

        // There is currently a bug in module_list() where it caches what it
        // was last called with, which is not always what you want.
        // module_load_all_includes() calls module_list(), but if this function
        // is called very early in the bootstrap process then it will be
        // uninitialized and therefore return no modules. Instead, we have to
        // "prime" module_list() here to to values we want, specifically
        // "yes rebuild the list and don't limit to bootstrap".
        // TODO: Remove this call after http://drupal.org/node/222109 is fixed.
        module_list(TRUE);
        module_load_all_includes('install');
      }

      require_once DRUPAL_ROOT . '/includes/common.inc';
      // Invoke hook_schema for all modules.
      foreach (module_implements('schema') as $module) {
        $current = module_invoke($module, 'schema');
        if (drupal_function_exists('_drupal_schema_initialize')) {
          _drupal_schema_initialize($module, $current);
        }

        $schema = array_merge($schema, $current);
      }

      if (drupal_function_exists('drupal_alter')) {
        drupal_alter('schema', $schema);
      }

      // If the schema is empty, avoid saving it: some database engines require
      // the schema to perform queries, and this could lead to infinite loops.
      if (!empty($schema) && (drupal_get_bootstrap_phase() == DRUPAL_BOOTSTRAP_FULL)) {
        cache_set('schema', $schema);
      }
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
 * @} End of "ingroup schemaapi".
 */


/**
 * @ingroup registry
 * @{
 */

/**
 * Confirm that a function is available.
 *
 * If the function is already available, this function does nothing.
 * If the function is not available, it tries to load the file where the
 * function lives. If the file is not available, it returns false, so that it
 * can be used as a drop-in replacement for function_exists().
 *
 * @param $function
 *   The name of the function to check or load.
 * @return
 *   TRUE if the function is now available, FALSE otherwise.
 */
function drupal_function_exists($function) {
  static $checked = array();
  static $maintenance;

  if (!isset($maintenance)) {
    $maintenance = defined('MAINTENANCE_MODE');
  }

  if ($maintenance) {
    return function_exists($function);
  }

  if (isset($checked[$function])) {
    return $checked[$function];
  }
  $checked[$function] = FALSE;

  if (function_exists($function)) {
    $checked[$function] = TRUE;
    return TRUE;
  }

  $checked[$function] = _registry_check_code('function', $function);

  return $checked[$function];
}

/**
 * Confirm that an interface is available.
 *
 * This function parallels drupal_function_exists(), but is rarely
 * called directly. Instead, it is registered as an spl_autoload()
 * handler, and PHP calls it for us when necessary.
 *
 * @param $interface
 *   The name of the interface to check or load.
 * @return
 *   TRUE if the interface is currently available, FALSE otherwise.
 */
function drupal_autoload_interface($interface) {
  return _registry_check_code('interface', $interface);
}

/**
 * Confirm that a class is available.
 *
 * This function parallels drupal_function_exists(), but is rarely
 * called directly. Instead, it is registered as an spl_autoload()
 * handler, and PHP calls it for us when necessary.
 *
 * @param $class
 *   The name of the class to check or load.
 * @return
 *   TRUE if the class is currently available, FALSE otherwise.
 */
function drupal_autoload_class($class) {
  return _registry_check_code('class', $class);
}

/**
 * Helper to check for a resource in the registry.
 *
 * @param $type
 *   The type of resource we are looking up, or one of the constants
 *   REGISTRY_RESET_LOOKUP_CACHE or REGISTRY_WRITE_LOOKUP_CACHE, which
 *   signal that we should reset or write the cache, respectively.
 * @param $name
 *   The name of the resource, or NULL if either of the REGISTRY_* constants
 *   is passed in.
 * @return
 *   TRUE if the resource was found, FALSE if not.
 *   NULL if either of the REGISTRY_* constants is passed in as $type.
 */
function _registry_check_code($type, $name = NULL) {
  static $lookup_cache, $cache_update_needed;

  if (!isset($lookup_cache)) {
    $lookup_cache = array();
    if ($cache = cache_get('lookup_cache', 'cache_registry')) {
      $lookup_cache = $cache->data;
    }
  }

  // When we rebuild the registry, we need to reset this cache so
  // we don't keep lookups for resources that changed during the rebuild.
  if ($type == REGISTRY_RESET_LOOKUP_CACHE) {
    $cache_update_needed = TRUE;
    $lookup_cache = NULL;
    return;
  }

  // Called from drupal_page_footer, we write to permanent storage if there
  // changes to the lookup cache for this request.
  if ($type == REGISTRY_WRITE_LOOKUP_CACHE) {
    if ($cache_update_needed) {
      cache_set('lookup_cache', $lookup_cache, 'cache_registry');
    }
    return;
  }

  // $type can be one of 'function', 'interface' or 'class', so we only need the
  // first letter to keep the cache key unique.
  $cache_key = $type[0] . $name;
  if (isset($lookup_cache[$cache_key])) {
    if ($lookup_cache[$cache_key]) {
      require_once DRUPAL_ROOT . '/' . $lookup_cache[$cache_key];
    }
    return $lookup_cache[$cache_key];
  }

  // This function may get called when the default database is not active, but
  // there is no reason we'd ever want to not use the default database for
  // this query.
  $file = Database::getConnection('default', 'default')->query("SELECT filename FROM {registry} WHERE name = :name AND type = :type", array(
      ':name' => $name,
      ':type' => $type,
    ))
    ->fetchField();

  // Flag that we've run a lookup query and need to update the cache.
  $cache_update_needed = TRUE;

  // Misses are valuable information worth caching, so cache even if
  // $file is FALSE.
  $lookup_cache[$cache_key] = $file;

  if ($file) {
    require_once DRUPAL_ROOT . '/' . $file;
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Rescan all enabled modules and rebuild the registry.
 *
 * Rescans all code in modules or includes directory, storing a mapping of
 * each function, file, and hook implementation in the database.
 */
function registry_rebuild() {
  require_once DRUPAL_ROOT . '/includes/registry.inc';
  _registry_rebuild();
}

/**
 * @} End of "ingroup registry".
 */

/**
 * Central static variable storage.
 *
 * @param $name
 *   Globally unique name for the variable. For a function with only one static,
 *   variable, the function name (e.g. via the PHP magic __FUNCTION__ constant)
 *   is recommended. For a function with multiple static variables add a
 *   distinguishing suffix to the function name for each one.
 * @param $default_value
 *   Optional default value.
 * @param $reset
 *   TRUE to reset a specific named variable, or all variables if $name is NULL.
 *   Resetting every variable should only be used, for example, for running
 *   unit tests with a clean environment. Should be used only though via
 *   function drupal_static_reset().
 *
 * @return
 *   Returns a variable by reference if $reset is FALSE.
 */
function &drupal_static($name, $default_value = NULL, $reset = FALSE) {
  static $data = array();

  // Reset a single value, or all values.
  if ($reset) {
    if (isset($name)) {
      unset($data[$name]);
    }
    else {
      $data = array();
    }
    // We must return a reference to a variable.
    $dummy = NULL;
    return $dummy;
  }

  if (!isset($data[$name])) {
    $data[$name] = $default_value;
  }

  return $data[$name];
}

/**
 * Reset one or all centrally stored static variable(s).
 *
 * @param $name
 *   Name of the static variable to reset. Omit to reset all variables.
 */
function drupal_static_reset($name = NULL) {
  drupal_static($name, NULL, TRUE);
}
