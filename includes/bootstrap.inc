<?php

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
 * Indicates a notice-level watchdog event; these are normally notifications
 * of normal system events that have occurred and can usually be safely ignored.
 */
define('WATCHDOG_NOTICE', 0);

/**
 * Indicates a warning-level watchdog event; this can be triggered by an error
 * in a module that does not impact the overall functionality of the site.
 */
define('WATCHDOG_WARNING', 1);

/**
 * Indicates an error-level watchdog event; could be indicative of an attempt
 * to compromise the security of the site, or a serious system error.
 */
define('WATCHDOG_ERROR', 2);

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
 * Sixth bootstrap phase: load bootstrap.inc and module.inc, start
 * the variable system and try to serve a page from the cache.
 */
define('DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE', 5);

/**
 * Seventh bootstrap phase: set $_GET['q'] to Drupal path of request.
 */
define('DRUPAL_BOOTSTRAP_PATH', 6);

/**
 * Final bootstrap phase: Drupal is fully loaded; validate and fix
 * input data.
 */
define('DRUPAL_BOOTSTRAP_FULL', 7);

/**
 * Role ID for anonymous users; should match what's in the "role" table.
 */
define('DRUPAL_ANONYMOUS_RID', 1);

/**
 * Role ID for authenticated users; should match what's in the "role" table.
 */
define('DRUPAL_AUTHENTICATED_RID', 2);

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

  list($usec, $sec) = explode(' ', microtime());
  $timers[$name]['start'] = (float)$usec + (float)$sec;
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
    list($usec, $sec) = explode(' ', microtime());
    $stop = (float)$usec + (float)$sec;
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
 */
function conf_path() {
  static $conf = '';

  if ($conf) {
    return $conf;
  }

  $confdir = 'sites';
  $uri = explode('/', $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : $_SERVER['SCRIPT_FILENAME']);
  $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
  for ($i = count($uri) - 1; $i > 0; $i--) {
    for ($j = count($server); $j > 0; $j--) {
      $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
      if (file_exists("$confdir/$dir/settings.php")) {
        $conf = "$confdir/$dir";
        return $conf;
      }
    }
  }
  $conf = "$confdir/default";
  return $conf;
}

/**
 * Unsets all disallowed global variables. See $allowed for what's allowed.
 */
function drupal_unset_globals() {
  if (ini_get('register_globals')) {
    $allowed = array('_ENV' => 1, '_GET' => 1, '_POST' => 1, '_COOKIE' => 1, '_FILES' => 1, '_SERVER' => 1, '_REQUEST' => 1, 'access_check' => 1, 'GLOBALS' => 1);
    foreach ($GLOBALS as $key => $value) {
      if (!isset($allowed[$key])) {
        unset($GLOBALS[$key]);
      }
    }
  }
}

/**
 * Validate that a hostname (for example $_SERVER['HTTP_HOST']) is safe.
 *
 * As $_SERVER['HTTP_HOST'] is user input, ensure it only contains characters
 * allowed in hostnames.  See RFC 952 (and RFC 2181). $_SERVER['HTTP_HOST'] is
 * lowercased.
 *
 * @return
 *  TRUE if only containing valid characters, or FALSE otherwise.
 */
function drupal_valid_http_host($host) {
  return preg_match('/^\[?(?:[a-z0-9-:\]_]+\.?)+$/', $host);
}

/**
 * Loads the configuration and sets the base URL, cookie domain, and
 * session name correctly.
 */
function conf_init() {
  global $base_url, $base_path, $base_root;

  // Export the following settings.php variables to the global namespace
  global $db_url, $db_prefix, $cookie_domain, $conf, $installed_profile;
  $conf = array();

  if (isset($_SERVER['HTTP_HOST'])) {
    // As HTTP_HOST is user input, ensure it only contains characters allowed
    // in hostnames. See RFC 952 (and RFC 2181).
    // $_SERVER['HTTP_HOST'] is lowercased here per specifications.
    $_SERVER['HTTP_HOST'] = strtolower($_SERVER['HTTP_HOST']);
    if (!drupal_valid_http_host($_SERVER['HTTP_HOST'])) {
      // HTTP_HOST is invalid, e.g. if containing slashes it may be an attack.
      header('HTTP/1.1 400 Bad Request');
      exit;
    }
  }
  else {
    // Some pre-HTTP/1.1 clients will not send a Host header. Ensure the key is
    // defined for E_ALL compliance.
    $_SERVER['HTTP_HOST'] = '';
  }

  include_once './'. conf_path() .'/settings.php';

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

    $base_url = $base_root .= '://'. $_SERVER['HTTP_HOST'];

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

  if (!$cookie_domain) {
    // If the $cookie_domain is empty, try to use the session.cookie_domain.
    $cookie_domain = ini_get('session.cookie_domain');
  }
  if ($cookie_domain) {
    // If the user specifies the cookie domain, also use it for session name.
    $session_name = $cookie_domain;
  }
  else {
    // Otherwise use $base_url as session name, without the protocol
    // to use the same session identifiers across http and https.
    list( , $session_name) = explode('://', $base_url, 2);
    // We try to set the cookie domain to the hostname.
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
  $cookie_domain = '.'. $cookie_domain[0];
  // Per RFC 2109, cookie domains must contain at least one dot other than the
  // first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
  if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
    ini_set('session.cookie_domain', $cookie_domain);
  }
  session_name('SESS'. md5($session_name));
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
  static $files = array();
  global $active_db;

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
  // the database.  This is required because this function is called both
  // before we have a database connection (i.e. during installation) and
  // when a database connection fails.
  elseif ($active_db && (($file = db_result(db_query("SELECT filename FROM {system} WHERE name = '%s' AND type = '%s'", $name, $type))) && file_exists($file))) {
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

  return $files[$type][$name];
}

/**
 * Load the persistent variable table.
 *
 * The variable table is composed of values that have been saved in the table
 * with variable_set() as well as those explicitly specified in the configuration
 * file.
 */
function variable_init($conf = array()) {
  // NOTE: caching the variables improves performance by 20% when serving cached pages.
  if ($cached = cache_get('variables', 'cache')) {
    $variables = unserialize($cached->data);
  }
  else {
    $result = db_query('SELECT * FROM {variable}');
    while ($variable = db_fetch_object($result)) {
      $variables[$variable->name] = unserialize($variable->value);
    }
    cache_set('variables', 'cache', serialize($variables));
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
function variable_get($name, $default) {
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

  db_lock_table('variable');
  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);
  db_query("INSERT INTO {variable} (name, value) VALUES ('%s', '%s')", $name, serialize($value));
  db_unlock_tables();

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

  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);
  cache_clear_all('variables', 'cache');

  unset($conf[$name]);
}

/**
 * Retrieve the current page from the cache.
 *
 * Note: we do not serve cached pages when status messages are waiting (from
 * a redirected form submission which was completed).
 */
function page_get_cache() {
  global $user, $base_root;

  $cache = NULL;

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET' && count(drupal_set_message()) == 0) {
    $cache = cache_get($base_root . request_uri(), 'cache_page');

    if (empty($cache)) {
      ob_start();
    }
  }

  return $cache;
}

/**
 * Call all init or exit hooks without including all modules.
 *
 * @param $hook
 *   The name of the bootstrap hook we wish to invoke.
 */
function bootstrap_invoke_all($hook) {
  foreach (module_list(TRUE, TRUE) as $module) {
    drupal_load('module', $module);
    module_invoke($module, $hook);
 }
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
  static $files = array();

  if (isset($files[$type][$name])) {
    return TRUE;
  }

  $filename = drupal_get_filename($type, $name);

  if ($filename) {
    include_once "./$filename";
    $files[$type][$name] = TRUE;

    return TRUE;
  }

  return FALSE;
}

/**
 * Set HTTP headers in preparation for a page response.
 *
 * Authenticated users are always given a 'no-cache' header, and will
 * fetch a fresh page on every request.  This prevents authenticated
 * users seeing locally cached pages that show them as logged out.
 *
 * @see page_set_cache
 */
function drupal_page_header() {
  header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
  header("Cache-Control: store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", FALSE);
}

/**
 * Set HTTP headers in preparation for a cached page response.
 *
 * The general approach here is that anonymous users can keep a local
 * cache of the page, but must revalidate it on every request.  Then,
 * they are given a '304 Not Modified' response as long as they stay
 * logged out and the page has not been modified.
 *
 */
function drupal_page_cache_header($cache) {
  // Set default values:
  $last_modified = gmdate('D, d M Y H:i:s', $cache->created) .' GMT';
  $etag = '"'.md5($last_modified).'"';

  // See if the client has provided the required HTTP headers:
  $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : FALSE;
  $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : FALSE;

  if ($if_modified_since && $if_none_match
      && $if_none_match == $etag // etag must match
      && $if_modified_since == $last_modified) {  // if-modified-since must match
    header('HTTP/1.1 304 Not Modified');
    // All 304 responses must send an etag if the 200 response for the same object contained an etag
    header("Etag: $etag");
    return;
  }

  // Send appropriate response:
  header("Last-Modified: $last_modified");
  header("ETag: $etag");

  // The following headers force validation of cache:
  header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
  header("Cache-Control: must-revalidate");

  // Determine if the browser accepts gzipped data.
  if (@strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === FALSE && function_exists('gzencode')) {
    // Strip the gzip header and run uncompress.
    $cache->data = gzinflate(substr(substr($cache->data, 10), 0, -8));
  }
  elseif (function_exists('gzencode')) {
    header('Content-Encoding: gzip');
  }

  // Send the original request's headers. We send them one after
  // another so PHP's header() function can deal with duplicate
  // headers.
  $headers = explode("\n", $cache->headers);
  foreach ($headers as $header) {
    header($header);
  }

  print $cache->data;
}

/**
 * Define the critical hooks that force modules to always be loaded.
 */
function bootstrap_hooks() {
  return array('init', 'exit');
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
 * Return the URI of the referring page.
 */
function referer_uri() {
  if (isset($_SERVER['HTTP_REFERER'])) {
    return $_SERVER['HTTP_REFERER'];
  }
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
 * This function exploits preg_match behaviour (since PHP 4.3.5) when used
 * with the u modifier, as a fast way to find invalid UTF-8. When the matched
 * string contains an invalid byte sequence, it will fail silently.
 *
 * preg_match may not fail on 4 and 5 octet sequences, even though they
 * are not supported by the specification.
 *
 * The specific preg_match behaviour is present since PHP 4.3.5.
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
      $uri = $_SERVER['SCRIPT_NAME'] .'?'. $_SERVER['argv'][0];
    }
    else {
      $uri = $_SERVER['SCRIPT_NAME'] .'?'. $_SERVER['QUERY_STRING'];
    }
  }
  // Prevent multiple slashes to avoid cross site requests via the FAPI.
  $uri = '/'. ltrim($uri, '/');

  return $uri;
}

/**
 * Log a system message.
 *
 * @param $type
 *   The category to which this message belongs.
 * @param $message
 *   The message to store in the log.
 * @param $severity
 *   The severity of the message. One of the following values:
 *   - WATCHDOG_NOTICE
 *   - WATCHDOG_WARNING
 *   - WATCHDOG_ERROR
 * @param $link
 *   A link to associate with the message.
 */
function watchdog($type, $message, $severity = WATCHDOG_NOTICE, $link = NULL) {
  global $user, $base_root;

  $current_db = db_set_active();

  // Note: log the exact, entire absolute URL.
  $request_uri = $base_root . request_uri();

  db_query("INSERT INTO {watchdog} (uid, type, message, severity, link, location, referer, hostname, timestamp) VALUES (%d, '%s', '%s', %d, '%s', '%s', '%s', '%s', %d)", $user->uid, $type, $message, $severity, $link, $request_uri, referer_uri(), $_SERVER['REMOTE_ADDR'], time());

  if ($current_db) {
    db_set_active($current_db);
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
 *   - 'error'
 */
function drupal_set_message($message = NULL, $type = 'status') {
  if ($message) {
    if (!isset($_SESSION['messages'])) {
      $_SESSION['messages'] = array();
    }

    if (!isset($_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type] = array();
    }

    $_SESSION['messages'][$type][] = $message;
  }

  // messages not set when DB connection fails
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
 * Perform an access check for a given mask and rule type. Rules are usually
 * created via admin/user/rules page.
 *
 * If any allow rule matches, access is allowed. Otherwise, if any deny rule
 * matches, access is denied.  If no rule matches, access is allowed.
 *
 * @param $type string
 *   Type of access to check: Allowed values are:
 *     - 'host': host name or IP address
 *     - 'mail': e-mail address
 *     - 'user': username
 * @param $mask string
 *   String or mask to test: '_' matches any character, '%' matches any
 *   number of characters.
 * @return bool
 *   TRUE if access is denied, FALSE if access is allowed.
 */
function drupal_is_denied($type, $mask) {
  // Because this function is called for every page request, both cached
  // and non-cached pages, we tried to optimize it as much as possible.
  // We deny access if the only matching records in the {access} table have
  // status 0. If any have status 1, or if there are no matching records,
  // we allow access. So, select matching records in decreasing order of
  // 'status', returning NOT(status) for the first. If any have status 1,
  // they come first, and we return NOT(status) = 0 (allowed). Otherwise,
  // if we have some with status 0, we return 1 (denied). If no matching
  // records, we get no return from db_result, so we return (bool)NULL = 0
  // (allowed).
  // The use of ORDER BY / LIMIT is more efficient than "MAX(status) = 0"
  // in PostgreSQL <= 8.0.
  return (bool) db_result(db_query_range("SELECT CASE WHEN status=1 THEN 0 ELSE 1 END FROM {access} WHERE type = '%s' AND LOWER('%s') LIKE LOWER(mask) ORDER BY status DESC", $type, $mask, 0, 1));
}

/**
 * Generates a default anonymous $user object.
 *
 * @return Object - the user object.
 */
function drupal_anonymous_user($session = '') {
  $user = new stdClass();
  $user->uid = 0;
  $user->hostname = $_SERVER['REMOTE_ADDR'];
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
 *     DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE: load bootstrap.inc and module.inc, start
 *       the variable system and try to serve a page from the cache.
 *     DRUPAL_BOOTSTRAP_PATH: set $_GET['q'] to Drupal path of request.
 *     DRUPAL_BOOTSTRAP_FULL: Drupal is fully loaded, validate and fix input data.
 */
function drupal_bootstrap($phase) {
  static $phases = array(DRUPAL_BOOTSTRAP_CONFIGURATION, DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE, DRUPAL_BOOTSTRAP_DATABASE, DRUPAL_BOOTSTRAP_ACCESS, DRUPAL_BOOTSTRAP_SESSION, DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE, DRUPAL_BOOTSTRAP_PATH, DRUPAL_BOOTSTRAP_FULL);

  while (!empty($phases) && $phase >= $phases[0]) {
    $current_phase = array_shift($phases);
    _drupal_bootstrap($current_phase);
  }
}

function _drupal_bootstrap($phase) {
  global $conf;

  switch ($phase) {

    case DRUPAL_BOOTSTRAP_CONFIGURATION:
      drupal_unset_globals();
      // Initialize the configuration
      conf_init();
      break;

    case DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE:
      _drupal_cache_init($phase);
      break;

    case DRUPAL_BOOTSTRAP_DATABASE:
      // Initialize the default database.
      require_once './includes/database.inc';
      db_set_active();
      break;

    case DRUPAL_BOOTSTRAP_ACCESS:
      // Deny access to hosts which were banned - t() is not yet available.
      if (drupal_is_denied('host', $_SERVER['REMOTE_ADDR'])) {
        header('HTTP/1.1 403 Forbidden');
        print 'Sorry, '. $_SERVER['REMOTE_ADDR']. ' has been banned.';
        exit();
      }
      break;

    case DRUPAL_BOOTSTRAP_SESSION:
      require_once variable_get('session_inc', './includes/session.inc');
      session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy_sid', 'sess_gc');
      session_start();
      break;

    case DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE:
      // Initialize configuration variables, using values from settings.php if available.
      $conf = variable_init(isset($conf) ? $conf : array());

      _drupal_cache_init($phase);

      // Start a page timer:
      timer_start('page');

      drupal_page_header();
      break;

    case DRUPAL_BOOTSTRAP_PATH:
      require_once './includes/path.inc';
      // Initialize $_GET['q'] prior to loading modules and invoking hook_init().
      drupal_init_path();
      break;

    case DRUPAL_BOOTSTRAP_FULL:
      require_once './includes/common.inc';
      _drupal_bootstrap_full();
      break;
  }
}

/**
 * Initialize the caching strategy, which loads at different stages within
 * Drupal's bootstrap process.
 */
function _drupal_cache_init($phase) {
  require_once variable_get('cache_inc', './includes/cache.inc');

  if ($phase == DRUPAL_BOOTSTRAP_EARLY_PAGE_CACHE && variable_get('page_cache_fastpath', 0)) {
    if (page_cache_fastpath()) {
      exit();
    }
  }
  elseif ($phase == DRUPAL_BOOTSTRAP_LATE_PAGE_CACHE) {
    if ($cache = page_get_cache()) {
      if (variable_get('cache', CACHE_DISABLED) == CACHE_AGGRESSIVE) {
        drupal_page_cache_header($cache);
        exit();
      }
      elseif (variable_get('cache', CACHE_DISABLED) == CACHE_NORMAL) {
        require_once './includes/module.inc';
        bootstrap_invoke_all('init');
        drupal_page_cache_header($cache);
        bootstrap_invoke_all('exit');
        exit();
      }
    }
    require_once './includes/module.inc';
  }
}

/**
 * Enables use of the theme system without requiring database access. Since
 * there is not database access no theme will be enabled and the default
 * themeable functions will be called. Some themeable functions can not be used
 * without the full Drupal API loaded. For example, theme_page() is
 * unavailable and theme_maintenance_page() must be used in its place.
 */
function drupal_maintenance_theme() {
  global $theme;
  require_once './includes/path.inc';
  require_once './includes/theme.inc';
  require_once './includes/common.inc';
  require_once './includes/unicode.inc';
  require_once './modules/filter/filter.module';
  unicode_check();
  drupal_add_css(drupal_get_path('module', 'system') .'/defaults.css', 'module');
  drupal_add_css(drupal_get_path('module', 'system') .'/system.css', 'module');
  $theme = '';
}

/**
 * Return the name of the localisation function. Use in code that needs to
 * run both during installation and normal operation.
 */
function get_t() {
  static $t;
  if (is_null($t)) {
    $t = function_exists('install_main') ? 'st' : 't';
  }
  return $t;
}
