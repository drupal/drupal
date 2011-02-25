<?php

/**
 * @file
 * Functions that need to be loaded on every Drupal request.
 */

define('CACHE_PERMANENT', 0);
define('CACHE_TEMPORARY', -1);

define('CACHE_DISABLED', 0);
define('CACHE_ENABLED', 1);

define('WATCHDOG_NOTICE', 0);
define('WATCHDOG_WARNING', 1);
define('WATCHDOG_ERROR', 2);

define('DRUPAL_BOOTSTRAP_DATABASE', 0);
define('DRUPAL_BOOTSTRAP_SESSION', 1);
define('DRUPAL_BOOTSTRAP_PAGE_CACHE', 2);
define('DRUPAL_BOOTSTRAP_PATH', 3);
define('DRUPAL_BOOTSTRAP_FULL', 4);

// these values should match the'role' table
define('DRUPAL_ANONYMOUS_RID', 1);
define('DRUPAL_AUTHENTICATED_RID', 2);

/**
 * Start the timer with the specified name.  If you start and stop
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

  list($usec, $sec) = explode(' ', microtime());
  $stop = (float)$usec + (float)$sec;
  $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

  return $timers[$name]['time'] + $diff;
}

/**
 * Stop the timer with the specified name.
 *
 * @param name
 *   The name of the timer.
 * @return
 *   A timer array.  The array contains the number of times the
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
 * configuration file found will be used, the remaining will ignored. If no
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
 * Loads the configuration and sets the base URL correctly.
 */
function conf_init() {
  global $db_url, $db_prefix, $base_url, $base_path, $base_root, $conf;
  $conf = array();
  require_once './'. conf_path() .'/settings.php';

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

    // As $_SERVER['HTTP_HOST'] is user input, ensure it only contains
    // characters allowed in hostnames.
    $base_url = $base_root .= '://'. preg_replace('/[^a-z0-9-:._]/i', '', $_SERVER['HTTP_HOST']);

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
}

/**
 * Returns and optionally sets the filename for a system item (module,
 * theme, etc.).  The filename, whether provided, cached, or retrieved
 * from the database, is only returned if the file exists.
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

  if (!isset($files[$type])) {
    $files[$type] = array();
  }

  if (!empty($filename) && file_exists($filename)) {
    $files[$type][$name] = $filename;
  }
  elseif (isset($files[$type][$name])) {
    // nothing
  }
  elseif (($file = db_result(db_query("SELECT filename FROM {system} WHERE name = '%s' AND type = '%s'", $name, $type))) && file_exists($file)) {
    $files[$type][$name] = $file;
  }
  else {
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
  // NOTE: caching the variables improves performance with 20% when serving cached pages.
  if ($cached = cache_get('variables')) {
    $variables = unserialize($cached->data);
  }
  else {
    $result = db_query('SELECT * FROM {variable}');
    while ($variable = db_fetch_object($result)) {
      $variables[$variable->name] = unserialize($variable->value);
    }
    cache_set('variables', serialize($variables));
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

  cache_clear_all('variables');

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
  cache_clear_all('variables');

  unset($conf[$name]);
}

/**
 * Return data from the persistent cache.
 *
 * @param $key
 *   The cache ID of the data to retrieve.
 */
function cache_get($key) {
  global $user;

  // Garbage collection necessary when enforcing a minimum cache lifetime
  $cache_flush = variable_get('cache_flush', 0);
  if ($cache_flush && ($cache_flush + variable_get('cache_lifetime', 0) <= time())) {
    // Time to flush old cache data
    db_query("DELETE FROM {cache} WHERE expire != %d AND expire <= %d", CACHE_PERMANENT, $cache_flush);
    variable_set('cache_flush', 0);
  }

  $cache = db_fetch_object(db_query("SELECT data, created, headers, expire FROM {cache} WHERE cid = '%s'", $key));
  if (isset($cache->data)) {
    // If the data is permanent or we're not enforcing a minimum cache lifetime
    // always return the cached data.
    if ($cache->expire == CACHE_PERMANENT || !variable_get('cache_lifetime', 0)) {
      $cache->data = db_decode_blob($cache->data);
    }
    // If enforcing a minimum cache lifetime, validate that the data is
    // currently valid for this user before we return it by making sure the
    // cache entry was created before the timestamp in the current session's
    // cache timer.  The cache variable is loaded into the $user object by
    // sess_read() in session.inc.
    else {
      if (isset($user->cache) && ($user->cache > $cache->created)) {
        // This cache data is too old and thus not valid for us, ignore it.
        return 0;
      }
      else {
        $cache->data = db_decode_blob($cache->data);
      }
    }
    return $cache;
  }
  return 0;
}

/**
 * Store data in the persistent cache.
 *
 * @param $cid
 *   The cache ID of the data to store.
 * @param $data
 *   The data to store in the cache. Complex data types must be serialized first.
 * @param $expire
 *   One of the following values:
 *   - CACHE_PERMANENT: Indicates that the item should never be removed unless
 *     explicitly told to using cache_clear_all() with a cache ID.
 *   - CACHE_TEMPORARY: Indicates that the item should be removed at the next
 *     general cache wipe.
 *   - A Unix timestamp: Indicates that the item should be kept at least until
 *     the given time, after which it behaves like CACHE_TEMPORARY.
 * @param $headers
 *   A string containing HTTP header information for cached pages.
 */
function cache_set($cid, $data, $expire = CACHE_PERMANENT, $headers = NULL) {
  db_lock_table('cache');
  db_query("UPDATE {cache} SET data = %b, created = %d, expire = %d, headers = '%s' WHERE cid = '%s'", $data, time(), $expire, $headers, $cid);
  if (!db_affected_rows()) {
    @db_query("INSERT INTO {cache} (cid, data, created, expire, headers) VALUES ('%s', %b, %d, %d, '%s')", $cid, $data, time(), $expire, $headers);
  }
  db_unlock_tables();
}

/**
 * Expire data from the cache.
 *
 * @param $cid
 *   If set, the cache ID to delete. Otherwise, all cache entries that can
 *   expire are deleted.
 *
 * @param $wildcard
 *   If set to true, the $cid is treated as a substring to match rather than a
 *   complete ID.
 */
function cache_clear_all($cid = NULL, $wildcard = false) {
  global $user;

  if (empty($cid)) {
    if (variable_get('cache_lifetime', 0)) {
      // We store the time in the current user's $user->cache variable which
      // will be saved into the sessions table by sess_write().  We then
      // simulate that the cache was flushed for this user by not returning
      // cached data that was cached before the timestamp.
      $user->cache = time();

      $cache_flush = variable_get('cache_flush', 0);
      if ($cache_flush == 0) {
        // This is the first request to clear the cache, start a timer.
        variable_set('cache_flush', time());
      }
      else if (time() > ($cache_flush + variable_get('cache_lifetime', 0))) {
        // Clear the cache for everyone, cache_flush_delay seconds have
        // passed since the first request to clear the cache.
        db_query("DELETE FROM {cache} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
        variable_set('cache_flush', 0);
      }
    }
    else {
      // No minimum cache lifetime, flush all temporary cache entries now.
      db_query("DELETE FROM {cache} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
    }
  }
  else {
    if ($wildcard) {
      db_query("DELETE FROM {cache} WHERE cid LIKE '%%%s%%'", $cid);
    }
    else {
      db_query("DELETE FROM {cache} WHERE cid = '%s'", $cid);
    }
  }
}

/**
 * Retrieve the current page from the cache.
 *
 * Note, we do not serve cached pages when status messages are waiting (from
 * a redirected form submission which was completed).
 * Because the output handler is not activated, the resulting page will not
 * get cached either.
 */
function page_get_cache() {
  global $user, $base_root;

  $cache = NULL;

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET' && count(drupal_set_message()) == 0) {
    $cache = cache_get($base_root . request_uri());

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
  foreach (module_list(FALSE, TRUE) as $module) {
    drupal_load('module', $module);
    module_invoke($module, $hook);
 }
}

/**
 * Includes a file with the provided type and name.  This prevents
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
 * The general approach here is that anonymous users can keep a local cache of
 * the page, but must revalidate it on every request. Then, they are given a
 * '304 Not Modified' response as long as they stay logged out and the page
 * has not been modified.
 * This prevents authenticated users seeing locally cached pages that show them
 * as logged out.
 * Authenticated users are always given a 'no-cache' header set, and will fetch
 * a fresh page on every request.
 *
 * @see page_set_cache
 */
function drupal_page_header() {
  if (variable_get('cache', 0) && $cache = page_get_cache()) {
    bootstrap_invoke_all('init');
    // Set default values:
    $date = gmdate('D, d M Y H:i:s', $cache->created) .' GMT';
    $etag = '"'. md5($date) .'"';

    // Check http headers:
    $modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $date : NULL;
    if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($timestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) > 0) {
      $modified_since = $cache->created <= $timestamp;
    }
    else {
      $modified_since = NULL;
    }
    $none_match = !empty($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] == $etag : NULL;

    // The type checking here is very important, be careful when changing entries.
    if (($modified_since !== NULL || $none_match !== NULL) && $modified_since !== false && $none_match !== false) {
      header('HTTP/1.0 304 Not Modified');
      exit();
    }

    // Send appropriate response:
    header("Last-Modified: $date");
    header("ETag: $etag");
    // The following headers force validation of cache
    header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
    header("Cache-Control: must-revalidate");

    // Determine if the browser accepts gzipped data.
    if (@strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false && function_exists('gzencode')) {
      // Strip the gzip header and run uncompress.
      $cache->data = gzinflate(substr(substr($cache->data, 10), 0, -8));
    }
    elseif (function_exists('gzencode')) {
      header('Content-Encoding: gzip');
    }

    // Send the original request's headers.  We send them one after
    // another so PHP's header() function can deal with duplicate
    // headers.
    $headers = explode("\n", $cache->headers);
    foreach ($headers as $header) {
      header($header);
    }

    print $cache->data;
    bootstrap_invoke_all('exit');
    exit();
  }
  else {
    header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
  }
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
 *
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
 * This function exploits preg_match behaviour (since PHP 4.3.5) when used with
 * the u modifier as a fast way to find invalid UTF-8. When the matched string
 * contains invalid byte sequences, it will fail silently.
 *
 * preg_match may not fail on 4 and 5 octet sequences, even though they
 * are not supported by the specification.
 *
 * The specific preg_match behaviour is present
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
 * Since request_uri() is only available on Apache, we generate an
 * equivalent using other environment variables.
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
 * As a side effect, this function clears the message queue.
 */
function drupal_get_messages() {
  if ($messages = drupal_set_message()) {
    unset($_SESSION['messages']);
  }

  return $messages;
}

/**
 * Perform an access check for a given mask and rule type. Rules are usually created via admin/access/rules page.
 */
function drupal_is_denied($type, $mask) {
  $allow = db_fetch_object(db_query("SELECT * FROM {access} WHERE status = 1 AND type = '%s' AND LOWER('%s') LIKE LOWER(mask)", $type, $mask));
  $deny = db_fetch_object(db_query("SELECT * FROM {access} WHERE status = 0 AND type = '%s' AND LOWER('%s') LIKE LOWER(mask)", $type, $mask));

  return $deny && !$allow;
}

/**
 * A string describing a phase of Drupal to load. Each phase adds to the
 * previous one, so invoking a later phase automatically runs the earlier
 * phases too. The most important usage is that if you want to access
 * Drupal database from a script without loading anything else, you can
 * include bootstrap.inc, and call drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE).
 *
 * @param $phase
 *   A constant. Allowed values are:
 *     DRUPAL_BOOTSTRAP_DATABASE: initialize database layer.
 *     DRUPAL_BOOTSTRAP_SESSION: initialize session handling.
 *     DRUPAL_BOOTSTRAP_PAGE_CACHE: load bootstrap.inc and module.inc, start
 *       the variable system and try to serve a page from the cache.
 *     DRUPAL_BOOTSTRAP_FULL: Drupal is fully loaded, validate and fix input
 *       data.
 */
function drupal_bootstrap($phase) {
  static $phases = array(DRUPAL_BOOTSTRAP_DATABASE, DRUPAL_BOOTSTRAP_SESSION, DRUPAL_BOOTSTRAP_PAGE_CACHE, DRUPAL_BOOTSTRAP_PATH, DRUPAL_BOOTSTRAP_FULL);

  while (!is_null($current_phase = array_shift($phases))) {
    _drupal_bootstrap($current_phase);
    if ($phase == $current_phase) {
      return;
    }
  }
}

function _drupal_bootstrap($phase) {
  global $conf;

  switch ($phase) {
    case DRUPAL_BOOTSTRAP_DATABASE:
      drupal_unset_globals();
      // Initialize the configuration
      conf_init();
      // Initialize the default database.
      require_once './includes/database.inc';
      db_set_active();
      break;

    case DRUPAL_BOOTSTRAP_SESSION:
      require_once './includes/session.inc';
      session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
      session_start();
      break;

    case DRUPAL_BOOTSTRAP_PAGE_CACHE:
      require_once './includes/module.inc';
      // Start a page timer:
      timer_start('page');

      // deny access to hosts which were banned. t() is not yet available.
      if (drupal_is_denied('host', $_SERVER['REMOTE_ADDR'])) {
        header('HTTP/1.0 403 Forbidden');
        print 'Sorry, '. $_SERVER['REMOTE_ADDR']. ' has been banned.';
        exit();
      }

      // Initialize configuration variables, using values from conf.php if available.
      $conf = variable_init(isset($conf) ? $conf : array());
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
  unicode_check();
  $theme = '';
}
