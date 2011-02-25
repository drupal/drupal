<?php

/**
 * @file
 * Functions that need to be loaded on every Drupal request.
 */

define('CACHE_PERMANENT', 0);
define('CACHE_TEMPORARY', -1);

/**
 * Locate the appropriate configuration file.
 *
 * Try finding a matching configuration file by stripping the website's
 * URI from left to right.  If no configuration file is found, return the
 * default value, "conf".
 */
function conf_init() {
  $uri = $_SERVER['PHP_SELF'];

  $file = strtolower(strtr($_SERVER['HTTP_HOST'] . substr($uri, 0, strrpos($uri, '/')), '/:', '..'));

  while (strlen($file) > 4) {
    if (file_exists('includes/'. $file .'.php')) {
      return $file;
    }
    else {
      $file = substr($file, strpos($file, '.') + 1);
    }
  }

  return 'conf';
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

  db_query("DELETE FROM {variable} WHERE name = '%s'", $name);
  db_query("INSERT INTO {variable} (name, value) VALUES ('%s', '%s')", $name, serialize($value));
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
  $cache = db_fetch_object(db_query("SELECT data, created, headers FROM {cache} WHERE cid = '%s'", $key));
  if (isset($cache->data)) {
    $cache->data = db_decode_blob($cache->data);
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
  $data = db_encode_blob($data);

  db_query("UPDATE {cache} SET data = '%s', created = %d, expire = %d, headers = '%s' WHERE cid = '%s'", $data, time(), $expire, $headers, $cid);
  if (!db_affected_rows()) {
    @db_query("INSERT INTO {cache} (cid, data, created, expire, headers) VALUES ('%s', '%s', %d, %d, '%s')", $cid, $data, time(), $expire, $headers);
  }
}

/**
 * Expire data from the cache.
 *
 * @param $cid
 *   If set, the cache ID to delete. Otherwise, all cache entries that can expire
 *   are deleted.
 *
 * @param $wildcard
 *   If set to true, the $cid is treated as a substring to match rather than a
 *   complete ID.
 */
function cache_clear_all($cid = NULL, $wildcard = false) {
  if (empty($cid)) {
    db_query("DELETE FROM {cache} WHERE expire != %d AND expire < %d", CACHE_PERMANENT, time());
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
 * Store the current page in the cache.
 */
function page_set_cache() {
  global $user, $base_url;

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET') {
    // This will fail in some cases, see page_get_cache() for the explanation.
    if ($data = ob_get_contents()) {
      if (function_exists('gzencode')) {
        if (version_compare(phpversion(), '4.2', '>=')) {
          $data = gzencode($data, 9, FORCE_GZIP);
        }
        else {
          $data = gzencode($data, FORCE_GZIP);
        }
      }
      cache_set($base_url . request_uri(), $data, CACHE_TEMPORARY, drupal_get_headers());
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
  global $user, $base_url;

  $cache = NULL;

  if (!$user->uid && $_SERVER['REQUEST_METHOD'] == 'GET' && count(drupal_set_message()) == 0) {
    $cache = cache_get($base_url . request_uri());

    if (empty($cache)) {
      ob_start();
    }
  }

  return $cache;
}

/**
 * Set HTTP headers in preparation for a page response.
 */
function drupal_page_header() {
  if (variable_get('dev_timer', 0)) {
    timer_start();
  }

  if (variable_get('cache', 0)) {
    if ($cache = page_get_cache()) {
      bootstrap_invoke_all('init');
      // Set default values:
      $date = gmdate('D, d M Y H:i:s', $cache->created) .' GMT';
      $etag = '"'. md5($date) .'"';

      // Check http headers:
      $modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $date : NULL;
      if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($timestamp = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) != -1) {
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
  }
}

/**
 * Call all init or exit hooks without including all modules.
 *
 * @param $op
 *   The name of the bootstrap hook we wish to invoke.
 */
function bootstrap_invoke_all($op) {
  foreach (module_list(FALSE, TRUE) as $module) {
    module_load($module);
    module_invoke($module, $op);
  }
}

/**
 * Define the critical hooks that force modules to always be loaded.
 */
function bootstrap_hooks() {
  return array('init', 'exit');
}

/**
 * Given an internal Drupal path, return the alias set by the administrator.
 */
function drupal_get_path_alias($path) {
  if (($map = drupal_get_path_map()) && ($newpath = array_search($path, $map))) {
    return $newpath;
  }
  elseif (function_exists('conf_url_rewrite')) {
    return conf_url_rewrite($path, 'outgoing');
  }
  else {
    // No alias found. Return the normal path.
    return $path;
  }
}

/**
 * Return an array mapping path aliases to their internal Drupal paths.
 */
function drupal_get_path_map($action = '') {
  static $map = NULL;

  if ($action == 'rebuild') {
    $map = NULL;
  }

  if (is_null($map)) {
    $map = array();  // Make $map non-null in case no aliases are defined.
    $result = db_query('SELECT * FROM {url_alias}');
    while ($data = db_fetch_object($result)) {
      $map[$data->dst] = $data->src;
    }
  }

  return $map;
}

/**
 * Set the title of the current page, for display on the page and in the title bar.
 */
function drupal_set_title($title = NULL) {
  static $stored_title;

  if (isset($title)) {
    $stored_title = $title;
  }
  return $stored_title;
}

/**
 * Get the title of the current page, for display on the page and in the title bar.
 */
function drupal_get_title() {
  $title = drupal_set_title();

  if (!isset($title)) {
    // titles cannot be determined when serving a cached page
    if (function_exists('menu_get_active_title')) {
      $title = menu_get_active_title();
    }
  }

  return $title;
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
 * Return a component of the current Drupal path.
 *
 * When viewing a page at the path "admin/node/configure", for example, arg(0)
 * would return "admin", arg(1) would return "node", and arg(2) would return
 * "configure".
 *
 * Avoid use of this function where possible, as resulting code is hard to read.
 * Instead, attempt to use named arguments in menu callback functions. See the
 * explanation in menu.inc for how to construct callbacks that take arguments.
 */
function arg($index) {
  static $arguments, $q;

  if (empty($arguments) || $q != $_GET['q']) {
    $arguments = explode('/', $_GET['q']);
  }

  if (array_key_exists($index, $arguments)) {
    return $arguments[$index];
  }
}

/**
 * Prepare user input for use in a database query, preventing SQL injection attacks.
 */
function check_query($text) {
  return addslashes($text);
}

/**
 * Since request_uri() is only available on Apache, we generate an
 * equivalent using other environment vars.
 */
function request_uri() {

  if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
  }
  else {
    if (isset($_SERVER['argv'])) {
      $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
    }
    else {
      $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
    }
  }

  return $uri;
}

/**
 * Begin a global timer, for benchmarking of page execution time.
 */
function timer_start() {
  global $timer;
  list($usec, $sec) = explode(' ', microtime());
  $timer = (float)$usec + (float)$sec;
}

/**
 * Log a system message.
 *
 * @param $type
 *   The category to which this message belongs.
 * @param $message
 *   The message to store in the log.
 * @param $link
 *   A link to associate with the message.
 */
function watchdog($type, $message, $link = NULL) {
  global $user;
  db_query("INSERT INTO {watchdog} (uid, type, message, link, location, hostname, timestamp) VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d)", $user->uid, $type, $message, $link, request_uri(), $_SERVER['REMOTE_ADDR'], time());
}

/**
 * Set a message for the user to see.
 *
 * The message is stored in the session so that it can persist through a redirect.
 *
 * If called with no arguments, this function returns all set messages without
 * clearing them.
 */
function drupal_set_message($message = NULL, $type = 'status') {
  if (isset($message)) {
    if (!isset($_SESSION['messages'])) {
      $_SESSION['messages'] = array();
    }

    if (!isset($_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type] = array();
    }

    $_SESSION['messages'][$type][] = $message;
  }

  return $_SESSION['messages'];
}

/**
 * Return all messages that have been set.
 *
 * As a side effect, this function clears the message queue.
 */
function drupal_get_messages() {
  $messages = drupal_set_message();
  $_SESSION['messages'] = array();

  return $messages;
}

/**
 * Encode special characters in a plain-text string for display as HTML.
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES);
}

unset($conf);
$config = conf_init();

include_once "includes/$config.php";
include_once 'includes/database.inc';
include_once 'includes/session.inc';
include_once 'includes/module.inc';

// Initialize configuration variables, using values from conf.php if available.
$conf = variable_init(isset($conf) ? $conf : array());

?>
