<?php

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Settings;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Timer;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\ClassLoader\ClassLoader;
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\RuntimeException as DependencyInjectionRuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\Language;
use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * @file
 * Functions that need to be loaded on every Drupal request.
 */

/**
 * The current system version.
 */
const VERSION = '8.0-dev';

/**
 * Core API compatibility.
 */
const DRUPAL_CORE_COMPATIBILITY = '8.x';

/**
 * Minimum supported version of PHP.
 */
const DRUPAL_MINIMUM_PHP = '5.3.10';

/**
 * Minimum recommended value of PHP memory_limit.
 */
const DRUPAL_MINIMUM_PHP_MEMORY_LIMIT = '32M';

/**
 * Error reporting level: display no errors.
 */
const ERROR_REPORTING_HIDE = 'hide';

/**
 * Error reporting level: display errors and warnings.
 */
const ERROR_REPORTING_DISPLAY_SOME = 'some';

/**
 * Error reporting level: display all messages.
 */
const ERROR_REPORTING_DISPLAY_ALL = 'all';

/**
 * Error reporting level: display all messages, plus backtrace information.
 */
const ERROR_REPORTING_DISPLAY_VERBOSE = 'verbose';

/**
 * @defgroup logging_severity_levels Logging severity levels
 * @{
 * Logging severity levels as defined in RFC 3164.
 *
 * The WATCHDOG_* constant definitions correspond to the logging severity levels
 * defined in RFC 3164, section 4.1.1. PHP supplies predefined LOG_* constants
 * for use in the syslog() function, but their values on Windows builds do not
 * correspond to RFC 3164. The associated PHP bug report was closed with the
 * comment, "And it's also not a bug, as Windows just have less log levels,"
 * and "So the behavior you're seeing is perfectly normal."
 *
 * @see http://www.faqs.org/rfcs/rfc3164.html
 * @see http://bugs.php.net/bug.php?id=18090
 * @see http://php.net/manual/function.syslog.php
 * @see http://php.net/manual/network.constants.php
 * @see watchdog()
 * @see watchdog_severity_levels()
 */

/**
 * Log message severity -- Emergency: system is unusable.
 */
const WATCHDOG_EMERGENCY = 0;

/**
 * Log message severity -- Alert: action must be taken immediately.
 */
const WATCHDOG_ALERT = 1;

/**
 * Log message severity -- Critical conditions.
 */
const WATCHDOG_CRITICAL = 2;

/**
 * Log message severity -- Error conditions.
 */
const WATCHDOG_ERROR = 3;

/**
 * Log message severity -- Warning conditions.
 */
const WATCHDOG_WARNING = 4;

/**
 * Log message severity -- Normal but significant conditions.
 */
const WATCHDOG_NOTICE = 5;

/**
 * Log message severity -- Informational messages.
 */
const WATCHDOG_INFO = 6;

/**
 * Log message severity -- Debug-level messages.
 */
const WATCHDOG_DEBUG = 7;

/**
 * @} End of "defgroup logging_severity_levels".
 */

/**
 * First bootstrap phase: initialize configuration.
 */
const DRUPAL_BOOTSTRAP_CONFIGURATION = 0;

/**
 * Second bootstrap phase, initalize a kernel.
 */
const DRUPAL_BOOTSTRAP_KERNEL = 1;

/**
 * Third bootstrap phase: try to serve a cached page.
 */
const DRUPAL_BOOTSTRAP_PAGE_CACHE = 2;

/**
 * Fourth bootstrap phase: initialize database layer.
 */
const DRUPAL_BOOTSTRAP_DATABASE = 3;

/**
 * Fifth bootstrap phase: initialize the variable system.
 */
const DRUPAL_BOOTSTRAP_VARIABLES = 4;

/**
 * Sixth bootstrap phase: initialize session handling.
 */
const DRUPAL_BOOTSTRAP_SESSION = 5;

/**
 * Eighth bootstrap phase: load code for subsystems and modules.
 */
const DRUPAL_BOOTSTRAP_CODE = 6;

/**
 * Final bootstrap phase: initialize language, path, theme, and modules.
 */
const DRUPAL_BOOTSTRAP_FULL = 7;

/**
 * Role ID for anonymous users; should match what's in the "role" table.
 */
const DRUPAL_ANONYMOUS_RID = 'anonymous';

/**
 * Role ID for authenticated users; should match what's in the "role" table.
 */
const DRUPAL_AUTHENTICATED_RID = 'authenticated';

/**
 * The number of bytes in a kilobyte.
 *
 * For more information, visit http://en.wikipedia.org/wiki/Kilobyte.
 */
const DRUPAL_KILOBYTE = 1024;

/**
 * Time of the current request in seconds elapsed since the Unix Epoch.
 *
 * This differs from $_SERVER['REQUEST_TIME'], which is stored as a float
 * since PHP 5.4.0. Float timestamps confuse most PHP functions
 * (including date_create()).
 *
 * @see http://php.net/manual/reserved.variables.server.php
 * @see http://php.net/manual/function.time.php
 */
define('REQUEST_TIME', (int) $_SERVER['REQUEST_TIME']);

/**
 * Flag for drupal_set_title(); text is not sanitized, so run check_plain().
 */
const CHECK_PLAIN = 0;

/**
 * Flag for drupal_set_title(); text has already been sanitized.
 */
const PASS_THROUGH = -1;

/**
 * Regular expression to match PHP function names.
 *
 * @see http://php.net/manual/language.functions.php
 */
const DRUPAL_PHP_FUNCTION_PATTERN = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

/**
 * $config_directories key for active directory.
 *
 * @see config_get_config_directory()
 */
const CONFIG_ACTIVE_DIRECTORY = 'active';

/**
 * $config_directories key for staging directory.
 *
 * @see config_get_config_directory()
 */
const CONFIG_STAGING_DIRECTORY = 'staging';

/**
 * Defines the root directory of the Drupal installation.
 *
 * This strips two levels of directories off the current directory.
 */
define('DRUPAL_ROOT', dirname(dirname(__DIR__)));

/**
 * @deprecated as of Drupal 8.0.
 * @see \Drupal\Component\Utility\Timer::start
 */
function timer_start($name) {
  Timer::start($name);
}

/**
 * @deprecated as of Drupal 8.0.
 * @see \Drupal\Component\Utility\Timer::read
 */
function timer_read($name) {
  return Timer::read($name);
}

/**
 * @deprecated as of Drupal 8.0.
 * @see \Drupal\Component\Utility\Timer::stop
 */
function timer_stop($name) {
  return Timer::stop($name);
}

/**
 * Returns the appropriate configuration directory.
 *
 * Returns the configuration path based on the site's hostname, port, and
 * pathname. Uses find_conf_path() to find the current configuration directory.
 * See default.settings.php for examples on how the URL is converted to a
 * directory.
 *
 * @param bool $require_settings
 *   Only configuration directories with an existing settings.php file
 *   will be recognized. Defaults to TRUE. During initial installation,
 *   this is set to FALSE so that Drupal can detect a matching directory,
 *   then create a new settings.php file in it.
 * @param bool $reset
 *   Force a full search for matching directories even if one had been
 *   found previously. Defaults to FALSE.
 *
 * @return
 *   The path of the matching directory.
 *
 * @see default.settings.php
 */
function conf_path($require_settings = TRUE, $reset = FALSE) {
  $conf_path = &drupal_static(__FUNCTION__, '');

  if ($conf_path && !$reset) {
    return $conf_path;
  }

  // Check for a simpletest override.
  if ($simpletest_conf_path = _drupal_simpletest_conf_path()) {
    $conf_path = $simpletest_conf_path;
    return $conf_path;
  }

  // Otherwise, use the normal $conf_path.
  $script_name = $_SERVER['SCRIPT_NAME'];
  if (!$script_name) {
    $script_name = $_SERVER['SCRIPT_FILENAME'];
  }
  $http_host = $_SERVER['HTTP_HOST'];
  $conf_path = find_conf_path($http_host, $script_name, $require_settings);
  return $conf_path;
}

/**
 * Determines whether to use an overridden value for conf_path().
 *
 * Simpletest may provide a secondary, test-specific settings.php file to load
 * after the primary one used by the parent site and override its variables.
 * - If the child settings.php does not override $conf_path, then this function
 * returns FALSE and conf_path() returns the directory of the primary
 * settings.php.
 * - If the child settings.php does override $conf_path, then
 * _drupal_load_test_overrides() sets the 'simpletest_conf_path' setting, and
 * this function returns that to conf_path(), causing installations and
 * upgrades to act on that one.
 *
 * @return string|false
 *   The overridden $conf_path, or FALSE if the $conf_path should not currently
 *   be overridden.
 *
 * @see conf_path()
 * @see _drupal_load_test_overrides()
 */
function _drupal_simpletest_conf_path() {
  // Ensure that the settings object is available. conf_path() is called once
  // before the Settings class is included, and at that point it should still
  // load the primary $conf_path. See drupal_settings_initialize().
  if (!class_exists('Drupal\Component\Utility\Settings', FALSE)) {
    return FALSE;
  }

  // If no $simpletest_conf_path is set, use the normal $conf_path.
  if (!($simpletest_conf_path = settings()->get('simpletest_conf_path'))) {
    return FALSE;
  }

  // Ensure that this is actually a simpletest request. We can't check this
  // before settings.php is loaded.
  if (!drupal_valid_test_ua()) {
    return FALSE;
  }

  // When the $simpletest_conf_path is set in a valid test request,
  // return that path.
  return $simpletest_conf_path;
}

/**
 * Finds the appropriate configuration directory for a given host and path.
 *
 * Finds a matching configuration directory file by stripping the website's
 * hostname from left to right and pathname from right to left. By default,
 * the directory must contain a 'settings.php' file for it to match. If the
 * parameter $require_settings is set to FALSE, then a directory without a
 * 'settings.php' file will match as well. The first configuration
 * file found will be used and the remaining ones will be ignored. If no
 * configuration file is found, returns a default value '$confdir/default'. See
 * default.settings.php for examples on how the URL is converted to a directory.
 *
 * If a file named sites.php is present in the $confdir, it will be loaded
 * prior to scanning for directories. That file can define aliases in an
 * associative array named $sites. The array is written in the format
 * '<port>.<domain>.<path>' => 'directory'. As an example, to create a
 * directory alias for http://www.drupal.org:8080/mysite/test whose configuration
 * file is in sites/example.com, the array should be defined as:
 * @code
 * $sites = array(
 *   '8080.www.drupal.org.mysite.test' => 'example.com',
 * );
 * @endcode
 *
 * @param $http_host
 *   The hostname and optional port number, e.g. "www.example.com" or
 *   "www.example.com:8080".
 * @param $script_name
 *   The part of the URL following the hostname, including the leading slash.
 * @param $require_settings
 *   Defaults to TRUE. If TRUE, then only match directories with a
 *   'settings.php' file. Otherwise match any directory.
 *
 * @return
 *   The path of the matching configuration directory.
 *
 * @see default.settings.php
 * @see example.sites.php
 * @see conf_path()
 */
function find_conf_path($http_host, $script_name, $require_settings = TRUE) {
  // Determine whether multi-site functionality is enabled.
  if (!file_exists(DRUPAL_ROOT . '/sites/sites.php')) {
    return 'sites/default';
  }

  $sites = array();
  include DRUPAL_ROOT . '/sites/sites.php';

  $uri = explode('/', $script_name);
  $server = explode('.', implode('.', array_reverse(explode(':', rtrim($http_host, '.')))));
  for ($i = count($uri) - 1; $i > 0; $i--) {
    for ($j = count($server); $j > 0; $j--) {
      $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
      if (isset($sites[$dir]) && file_exists(DRUPAL_ROOT . '/sites/' . $sites[$dir])) {
        $dir = $sites[$dir];
      }
      if (file_exists(DRUPAL_ROOT . '/sites/' . $dir . '/settings.php') || (!$require_settings && file_exists(DRUPAL_ROOT . '/sites/' . $dir))) {
        return "sites/$dir";
      }
    }
  }
  return 'sites/default';
}

/**
 * Returns the path of a configuration directory.
 *
 * @param string $type
 *   (optional) The type of config directory to return. Drupal core provides
 *   'active' and 'staging'. Defaults to CONFIG_ACTIVE_DIRECTORY.
 *
 * @return string
 *   The configuration directory path.
 */
function config_get_config_directory($type = CONFIG_ACTIVE_DIRECTORY) {
  global $config_directories;

  if (!empty($config_directories[$type])) {
    // Allow a configuration directory path to be outside of webroot.
    if (empty($config_directories[$type]['absolute'])) {
      $path = conf_path() . '/files/' . $config_directories[$type]['path'];
    }
    else {
      $path = $config_directories[$type]['path'];
    }
  }
  else {
    throw new Exception(format_string('The configuration directory type %type does not exist.', array('%type' => $type)));
  }
  return $path;
}

/**
 * Sets appropriate server variables needed for command line scripts to work.
 *
 * This function can be called by command line scripts before bootstrapping
 * Drupal, to ensure that the page loads with the desired server parameters.
 * This is because many parts of Drupal assume that they are running in a web
 * browser and therefore use information from the global PHP $_SERVER variable
 * that does not get set when Drupal is run from the command line.
 *
 * In many cases, the default way in which this function populates the $_SERVER
 * variable is sufficient, and it can therefore be called without passing in
 * any input. However, command line scripts running on a multisite installation
 * (or on any installation that has settings.php stored somewhere other than
 * the sites/default folder) need to pass in the URL of the site to allow
 * Drupal to detect the correct location of the settings.php file. Passing in
 * the 'url' parameter is also required for functions like request_uri() to
 * return the expected values.
 *
 * Most other parameters do not need to be passed in, but may be necessary in
 * some cases; for example, if Drupal::request()->getClientIP()
 * needs to return anything but the standard localhost value ('127.0.0.1'),
 * the command line script should pass in the desired value via the
 * 'REMOTE_ADDR' key.
 *
 * @param $variables
 *   (optional) An associative array of variables within $_SERVER that should
 *   be replaced. If the special element 'url' is provided in this array, it
 *   will be used to populate some of the server defaults; it should be set to
 *   the URL of the current page request, excluding any $_GET request but
 *   including the script name (e.g., http://www.example.com/mysite/index.php).
 *
 * @see conf_path()
 * @see request_uri()
 * @see \Symfony\Component\HttpFoundation\Request::getClientIP()
 */
function drupal_override_server_variables($variables = array()) {
  // Allow the provided URL to override any existing values in $_SERVER.
  if (isset($variables['url'])) {
    $url = parse_url($variables['url']);
    if (isset($url['host'])) {
      $_SERVER['HTTP_HOST'] = $url['host'];
    }
    if (isset($url['path'])) {
      $_SERVER['SCRIPT_NAME'] = $url['path'];
    }
    unset($variables['url']);
  }
  // Define default values for $_SERVER keys. These will be used if $_SERVER
  // does not already define them and no other values are passed in to this
  // function.
  $defaults = array(
    'HTTP_HOST' => 'localhost',
    'SCRIPT_NAME' => NULL,
    'REMOTE_ADDR' => '127.0.0.1',
    'REQUEST_METHOD' => 'GET',
    'SERVER_NAME' => NULL,
    'SERVER_SOFTWARE' => NULL,
    'HTTP_USER_AGENT' => NULL,
  );
  // Replace elements of the $_SERVER array, as appropriate.
  $_SERVER = $variables + $_SERVER + $defaults;
}

/**
 * Initializes the PHP environment.
 */
function drupal_environment_initialize() {
  // Make sure the \Drupal class is available.
  require_once DRUPAL_ROOT . '/core/lib/Drupal.php';

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

  // @todo Refactor with the Symfony Request object.
  _current_path(request_path());

  // Enforce E_STRICT, but allow users to set levels not part of E_STRICT.
  error_reporting(E_STRICT | E_ALL | error_reporting());

  // Override PHP settings required for Drupal to work properly.
  // sites/default/default.settings.php contains more runtime settings.
  // The .htaccess file contains settings that cannot be changed at runtime.

  // Deny execution with enabled "magic quotes" (both GPC and runtime).
  if (get_magic_quotes_gpc() || get_magic_quotes_runtime()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
    print "PHP's 'magic_quotes_gpc' and 'magic_quotes_runtime' settings are not supported and must be disabled.";
    exit;
  }

  // Use session cookies, not transparent sessions that puts the session id in
  // the query string.
  ini_set('session.use_cookies', '1');
  ini_set('session.use_only_cookies', '1');
  ini_set('session.use_trans_sid', '0');
  // Don't send HTTP headers using PHP's session handler.
  // Send an empty string to disable the cache limiter.
  ini_set('session.cache_limiter', '');
  // Use httponly session cookies.
  ini_set('session.cookie_httponly', '1');

  // Set sane locale settings, to ensure consistent string, dates, times and
  // numbers handling.
  setlocale(LC_ALL, 'C');
}

/**
 * Validates that a hostname (for example $_SERVER['HTTP_HOST']) is safe.
 *
 * @return
 *  TRUE if only containing valid characters, or FALSE otherwise.
 */
function drupal_valid_http_host($host) {
  return preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host);
}

/**
 * Sets the base URL, cookie domain, and session name from configuration.
 */
function drupal_settings_initialize() {
  global $base_url, $base_path, $base_root, $script_path;

  // Export these settings.php variables to the global namespace.
  global $databases, $cookie_domain, $conf, $db_prefix, $drupal_hash_salt, $base_secure_url, $base_insecure_url, $config_directories;
  $conf = array();

  // Make conf_path() available as local variable in settings.php.
  $conf_path = conf_path();
  if (is_readable(DRUPAL_ROOT . '/' . $conf_path . '/settings.php')) {
    include_once DRUPAL_ROOT . '/' . $conf_path . '/settings.php';
  }
  require_once DRUPAL_ROOT . '/core/lib/Drupal/Component/Utility/Settings.php';
  new Settings(isset($settings) ? $settings : array());
  $is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';

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
    $http_protocol = $is_https ? 'https' : 'http';
    $base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];

    $base_url = $base_root;

    // For a request URI of '/index.php/foo', $_SERVER['SCRIPT_NAME'] is
    // '/index.php', whereas $_SERVER['PHP_SELF'] is '/index.php/foo'.
    if ($dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/')) {
      // Remove "core" directory if present, allowing install.php, update.php,
      // and others to auto-detect a base path.
      $core_position = strrpos($dir, '/core');
      if ($core_position !== FALSE && strlen($dir) - 5 == $core_position) {
        $base_path = substr($dir, 0, $core_position);
      }
      else {
        $base_path = $dir;
      }
      $base_url .= $base_path;
      $base_path .= '/';
    }
    else {
      $base_path = '/';
    }
  }
  $base_secure_url = str_replace('http://', 'https://', $base_url);
  $base_insecure_url = str_replace('https://', 'http://', $base_url);

  // Determine the path of the script relative to the base path, and add a
  // trailing slash. This is needed for creating URLs to Drupal pages.
  if (!isset($script_path)) {
    $script_path = '';
    // We don't expect scripts outside of the base path, but sanity check
    // anyway.
    if (strpos($_SERVER['SCRIPT_NAME'], $base_path) === 0) {
      $script_path = substr($_SERVER['SCRIPT_NAME'], strlen($base_path)) . '/';
      // If the request URI does not contain the script name, then clean URLs
      // are in effect and the script path can be similarly dropped from URL
      // generation. For servers that don't provide $_SERVER['REQUEST_URI'], we
      // do not know the actual URI requested by the client, and request_uri()
      // returns a URI with the script name, resulting in non-clean URLs unless
      // there's other code that intervenes.
      if (strpos(request_uri(TRUE) . '/', $base_path . $script_path) !== 0) {
        $script_path = '';
      }
      // @todo Temporary BC for install.php, update.php, and other scripts.
      //   - http://drupal.org/node/1547184
      //   - http://drupal.org/node/1546082
      if ($script_path !== 'index.php/') {
        $script_path = '';
      }
    }
  }

  if ($cookie_domain) {
    // If the user specifies the cookie domain, also use it for session name.
    $session_name = $cookie_domain;
  }
  else {
    // Otherwise use $base_url as session name, without the protocol
    // to use the same session identifiers across HTTP and HTTPS.
    list( , $session_name) = explode('://', $base_url, 2);
    // HTTP_HOST can be modified by a visitor, but we already sanitized it
    // in drupal_settings_initialize().
    if (!empty($_SERVER['HTTP_HOST'])) {
      $cookie_domain = $_SERVER['HTTP_HOST'];
      // Strip leading periods, www., and port numbers from cookie domain.
      $cookie_domain = ltrim($cookie_domain, '.');
      if (strpos($cookie_domain, 'www.') === 0) {
        $cookie_domain = substr($cookie_domain, 4);
      }
      $cookie_domain = explode(':', $cookie_domain);
      $cookie_domain = '.' . $cookie_domain[0];
    }
  }
  // Per RFC 2109, cookie domains must contain at least one dot other than the
  // first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
  if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
    ini_set('session.cookie_domain', $cookie_domain);
  }
  // To prevent session cookies from being hijacked, a user can configure the
  // SSL version of their website to only transfer session cookies via SSL by
  // using PHP's session.cookie_secure setting. The browser will then use two
  // separate session cookies for the HTTPS and HTTP versions of the site. So we
  // must use different session identifiers for HTTPS and HTTP to prevent a
  // cookie collision.
  if ($is_https) {
    ini_set('session.cookie_secure', TRUE);
  }
  $prefix = ini_get('session.cookie_secure') ? 'SSESS' : 'SESS';
  session_name($prefix . substr(hash('sha256', $session_name), 0, 32));
}

/**
 * Returns and optionally sets the filename for a system resource.
 *
 * The filename, whether provided, cached, or retrieved from the database, is
 * only returned if the file exists.
 *
 * This function plays a key role in allowing Drupal's resources (modules
 * and themes) to be located in different places depending on a site's
 * configuration. For example, a module 'foo' may legally be be located
 * in any of these three places:
 *
 * core/modules/foo/foo.module
 * modules/foo/foo.module
 * sites/example.com/modules/foo/foo.module
 *
 * Calling drupal_get_filename('module', 'foo') will give you one of
 * the above, depending on where the module is located.
 *
 * @param $type
 *   The type of the item (i.e. theme, theme_engine, module, profile).
 * @param $name
 *   The name of the item for which the filename is requested.
 * @param $filename
 *   The filename of the item if it is to be set explicitly rather
 *   than by consulting the database.
 *
 * @return
 *   The filename of the requested item or NULL if the item is not found.
 */
function drupal_get_filename($type, $name, $filename = NULL) {
  // The location of files will not change during the request, so do not use
  // drupal_static().
  static $files = array(), $dirs = array();

  // Profiles are converted into modules in system_rebuild_module_data().
  // @todo Remove false-exposure of profiles as modules.
  $original_type = $type;
  if ($type == 'profile') {
    $type = 'module';
  }
  if (!isset($files[$type])) {
    $files[$type] = array();
  }

  if (!empty($filename) && file_exists($filename)) {
    $files[$type][$name] = $filename;
  }
  elseif (isset($files[$type][$name])) {
    // nothing
  }
  else {
    // Verify that we have an keyvalue service before using it. This is required
    // because this function is called during installation.
    // @todo Inject database connection into KeyValueStore\DatabaseStorage.
    if (($container = drupal_container()) && $container->has('keyvalue') && function_exists('db_query')) {
      if ($type == 'module') {
        if (empty($files[$type])) {
          $files[$type] = drupal_container()->get('module_handler')->getModuleList();
        }
        if (isset($files[$type][$name])) {
          return $files[$type][$name];
        }
      }
      try {
        $file_list = state()->get('system.' . $type . '.files');
        if ($file_list && isset($file_list[$name]) && file_exists(DRUPAL_ROOT . '/' . $file_list[$name])) {
          $files[$type][$name] = $file_list[$name];
        }
      }
      catch (Exception $e) {
        // The keyvalue service raised an exception because the backend might
        // be down. We have a fallback for this case so we hide the error
        // completely.
      }
    }
    // Fallback to searching the filesystem if the database could not find the
    // file or the file returned by the database is not found.
    if (!isset($files[$type][$name])) {
      // We have consistent directory naming: modules, themes...
      $dir = $type . 's';
      if ($type == 'theme_engine') {
        $dir = 'themes/engines';
        $extension = 'engine';
      }
      elseif ($type == 'theme') {
        $extension = 'info.yml';
      }
      // Profiles are converted into modules in system_rebuild_module_data().
      // @todo Remove false-exposure of profiles as modules.
      elseif ($original_type == 'profile') {
        $dir = 'profiles';
        $extension = 'profile';
      }
      else {
        $extension = $type;
      }

      if (!isset($dirs[$dir][$extension])) {
        $dirs[$dir][$extension] = TRUE;
        if (!function_exists('drupal_system_listing')) {
          require_once __DIR__ . '/common.inc';
        }
        // Scan the appropriate directories for all files with the requested
        // extension, not just the file we are currently looking for. This
        // prevents unnecessary scans from being repeated when this function is
        // called more than once in the same page request.
        $matches = drupal_system_listing("/^" . DRUPAL_PHP_FUNCTION_PATTERN . "\.$extension$/", $dir);
        foreach ($matches as $matched_name => $file) {
          $files[$type][$matched_name] = $file->uri;
        }
      }
    }
  }

  if (isset($files[$type][$name])) {
    return $files[$type][$name];
  }
}

/**
 * Returns a setting.
 *
 * Settings can be set in settings.php in the $settings array and requested
 * by this function. Settings should be used over configuration for read-only,
 * possibly low bootstrap configuration that is environment specific.
 *
 * @return \Drupal\Component\Utility\Settings
 *   The settings object.
 */
function settings() {
  return Settings::getSingleton();
}

/**
 * Loads the persistent variable table.
 *
 * The variable table is composed of values that have been saved in the table
 * with variable_set() as well as those explicitly specified in the
 * configuration file.
 */
function variable_initialize($conf = array()) {
  // NOTE: caching the variables improves performance by 20% when serving
  // cached pages.
  if ($cached = cache('bootstrap')->get('variables')) {
    $variables = $cached->data;
  }
  else {
    // Cache miss. Avoid a stampede.
    $name = 'variable_init';
    $lock = Drupal::lock();
    if (!$lock->acquire($name, 1)) {
      // Another request is building the variable cache.
      // Wait, then re-run this function.
      $lock->wait($name);
      return variable_initialize($conf);
    }
    else {
      // Proceed with variable rebuild.
      $variables = array_map('unserialize', db_query('SELECT name, value FROM {variable}')->fetchAllKeyed());
      cache('bootstrap')->set('variables', $variables);
      $lock->release($name);
    }
  }

  foreach ($conf as $name => $value) {
    $variables[$name] = $value;
  }

  return $variables;
}

/**
 * Returns a persistent variable.
 *
 * Case-sensitivity of the variable_* functions depends on the database
 * collation used. To avoid problems, always use lower case for persistent
 * variable names.
 *
 * @param $name
 *   The name of the variable to return.
 * @param $default
 *   The default value to use if this variable has never been set.
 *
 * @return
 *   The value of the variable. Unserialization is taken care of as necessary.
 *
 * @see variable_del()
 * @see variable_set()
 */
function variable_get($name, $default = NULL) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}

/**
 * Sets a persistent variable.
 *
 * Case-sensitivity of the variable_* functions depends on the database
 * collation used. To avoid problems, always use lower case for persistent
 * variable names.
 *
 * @param $name
 *   The name of the variable to set.
 * @param $value
 *   The value to set. This can be any PHP data type; these functions take care
 *   of serialization as necessary.
 *
 * @see variable_del()
 * @see variable_get()
 */
function variable_set($name, $value) {
  global $conf;

  db_merge('variable')->key(array('name' => $name))->fields(array('value' => serialize($value)))->execute();

  cache('bootstrap')->delete('variables');

  $conf[$name] = $value;
}

/**
 * Unsets a persistent variable.
 *
 * Case-sensitivity of the variable_* functions depends on the database
 * collation used. To avoid problems, always use lower case for persistent
 * variable names.
 *
 * @param $name
 *   The name of the variable to undefine.
 *
 * @see variable_get()
 * @see variable_set()
 */
function variable_del($name) {
  global $conf;

  db_delete('variable')
    ->condition('name', $name)
    ->execute();
  cache('bootstrap')->delete('variables');

  unset($conf[$name]);
}

/**
 * Retrieves the current page from the cache.
 *
 * Note: we do not serve cached pages to authenticated users, or to anonymous
 * users when $_SESSION is non-empty. $_SESSION may contain status messages
 * from a form submission, the contents of a shopping cart, or other user-
 * specific content that should not be cached and displayed to other users.
 *
 * @param $check_only
 *   (optional) Set to TRUE to only return whether a previous call found a
 *   cache entry.
 *
 * @return
 *   The cache object, if the page was found in the cache, NULL otherwise.
 */
function drupal_page_get_cache($check_only = FALSE) {
  global $base_root;
  static $cache_hit = FALSE;

  if ($check_only) {
    return $cache_hit;
  }

  if (drupal_page_is_cacheable()) {
    $cache = cache('page')->get($base_root . request_uri());
    if ($cache !== FALSE) {
      $cache_hit = TRUE;
    }
    return $cache;
  }
}

/**
 * Determines the cacheability of the current page.
 *
 * @param $allow_caching
 *   Set to FALSE if you want to prevent this page to get cached.
 *
 * @return
 *   TRUE if the current page can be cached, FALSE otherwise.
 */
function drupal_page_is_cacheable($allow_caching = NULL) {
  $allow_caching_static = &drupal_static(__FUNCTION__, TRUE);
  if (isset($allow_caching)) {
    $allow_caching_static = $allow_caching;
  }

  return $allow_caching_static && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD')
    && !drupal_is_cli();
}

/**
 * Invokes a bootstrap hook in all bootstrap modules that implement it.
 *
 * @param $hook
 *   The name of the bootstrap hook to invoke.
 *
 * @see bootstrap_hooks()
 */
function bootstrap_invoke_all($hook) {
  $module_handler = drupal_container()->get('module_handler');
  foreach ($module_handler->getBootstrapModules() as $module) {
    $module_handler->load($module);
    module_invoke($module, $hook);
  }
}

/**
 * Includes a file with the provided type and name.
 *
 * This prevents including a theme, engine, module, etc., more than once.
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
  if ($type == 'module' && drupal_container()->get('module_handler')->moduleExists($name)) {
    return drupal_container()->get('module_handler')->load($name);
  }

  // Once a file is included this can't be reversed during a request so do not
  // use drupal_static() here.
  static $files = array();

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
 * Sets an HTTP response header for the current page.
 *
 * Note: When sending a Content-Type header, always include a 'charset' type,
 * too. This is necessary to avoid security bugs (e.g. UTF-7 XSS).
 *
 * @param $name
 *   The HTTP header name, or the special 'Status' header name.
 * @param $value
 *   The HTTP header value; if equal to FALSE, the specified header is unset.
 *   If $name is 'Status', this is expected to be a status code followed by a
 *   reason phrase, e.g. "404 Not Found".
 * @param $append
 *   Whether to append the value to an existing header or to replace it.
 */
function drupal_add_http_header($name, $value, $append = FALSE) {
  // The headers as name/value pairs.
  $headers = &drupal_static('drupal_http_headers', array());

  $name_lower = strtolower($name);
  _drupal_set_preferred_header_name($name);

  if ($value === FALSE) {
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
 * Gets the HTTP response headers for the current page.
 *
 * @param $name
 *   An HTTP header name. If omitted, all headers are returned as name/value
 *   pairs. If an array value is FALSE, the header has been unset.
 *
 * @return
 *   A string containing the header value, or FALSE if the header has been set,
 *   or NULL if the header has not been set.
 */
function drupal_get_http_header($name = NULL) {
  $headers = &drupal_static('drupal_http_headers', array());
  if (isset($name)) {
    $name = strtolower($name);
    return isset($headers[$name]) ? $headers[$name] : NULL;
  }
  else {
    return $headers;
  }
}

/**
 * Sets the preferred name for the HTTP header.
 *
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
 * Sends the HTTP response headers that were previously set, adding defaults.
 *
 * Headers are set in drupal_add_http_header(). Default headers are not set
 * if they have been replaced or unset using drupal_add_http_header().
 *
 * @param array $default_headers
 *   (optional) An array of headers as name/value pairs.
 * @param bool $only_default
 *   (optional) If TRUE and headers have already been sent, send only the
 *   specified headers.
 */
function drupal_send_headers($default_headers = array(), $only_default = FALSE) {
  $headers_sent = &drupal_static(__FUNCTION__, FALSE);
  $headers = drupal_get_http_header();
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
    if ($name_lower == 'status') {
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $value);
    }
    // Skip headers that have been unset.
    elseif ($value !== FALSE) {
      header($header_names[$name_lower] . ': ' . $value);
    }
  }
}

/**
 * Sets HTTP headers in preparation for a page response.
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
 * Sets HTTP headers in preparation for a cached page response.
 *
 * The headers allow as much as possible in proxies and browsers without any
 * particular knowledge about the pages. Modules can override these headers
 * using drupal_add_http_header().
 *
 * If the request is conditional (using If-Modified-Since and If-None-Match),
 * and the conditions match those currently in the cache, a 304 Not Modified
 * response is sent.
 */
function drupal_serve_page_from_cache(stdClass $cache) {
  $config = config('system.performance');

  // Negotiate whether to use compression.
  $page_compression = $config->get('response.gzip') && extension_loaded('zlib');
  $return_compressed = $page_compression && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE;

  // Get headers. Keys are lower-case.
  $boot_headers = drupal_get_http_header();

  // Headers generated in this function, that may be replaced or unset using
  // drupal_add_http_headers(). Keys are mixed-case.
  $default_headers = array();

  foreach ($cache->data['headers'] as $name => $value) {
    // In the case of a 304 response, certain headers must be sent, and the
    // remaining may not (see RFC 2616, section 10.3.5).
    $name_lower = strtolower($name);
    if (in_array($name_lower, array('content-location', 'expires', 'cache-control', 'vary')) && !isset($boot_headers[$name_lower])) {
      drupal_add_http_header($name, $value);
      unset($cache->data['headers'][$name]);
    }
  }

  // If the client sent a session cookie, a cached copy will only be served
  // to that one particular client due to Vary: Cookie. Thus, do not set
  // max-age > 0, allowing the page to be cached by external proxies, when a
  // session cookie is present unless the Vary header has been replaced.
  $max_age = !isset($_COOKIE[session_name()]) || isset($boot_headers['vary']) ? $config->get('cache.page.max_age') : 0;
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
  foreach ($cache->data['headers'] as $name => $value) {
    drupal_add_http_header($name, $value);
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
  // revalidation.
  if (!isset($boot_headers['vary']) && !settings()->get('omit_vary_cookie')) {
    header('Vary: Cookie');
  }

  if ($page_compression) {
    header('Vary: Accept-Encoding', FALSE);
    // If page_compression is enabled, the cache contains gzipped data.
    if ($return_compressed) {
      // $cache->data['body'] is already gzip'ed, so make sure
      // zlib.output_compression does not compress it once more.
      ini_set('zlib.output_compression', '0');
      header('Content-Encoding: gzip');
    }
    else {
      // The client does not support compression, so unzip the data in the
      // cache. Strip the gzip header and run uncompress.
      $cache->data['body'] = gzinflate(substr(substr($cache->data['body'], 10), 0, -8));
    }
  }

  // Print the page.
  print $cache->data['body'];
}

/**
 * Defines the critical hooks that force modules to always be loaded.
 */
function bootstrap_hooks() {
  return array('watchdog');
}

/**
 * Translates a string to the current language or to a given language.
 *
 * The t() function serves two purposes. First, at run-time it translates
 * user-visible text into the appropriate language. Second, various mechanisms
 * that figure out what text needs to be translated work off t() -- the text
 * inside t() calls is added to the database of strings to be translated.
 * These strings are expected to be in English, so the first argument should
 * always be in English. To enable a fully-translatable site, it is important
 * that all human-readable text that will be displayed on the site or sent to
 * a user is passed through the t() function, or a related function. See the
 * @link http://drupal.org/node/322729 Localization API @endlink pages for
 * more information, including recommendations on how to break up or not
 * break up strings for translation.
 *
 * You should never use t() to translate variables, such as calling
 * @code t($text); @endcode, unless the text that the variable holds has been
 * passed through t() elsewhere (e.g., $text is one of several translated
 * literal strings in an array). It is especially important never to call
 * @code t($user_text); @endcode, where $user_text is some text that a user
 * entered - doing that can lead to cross-site scripting and other security
 * problems. However, you can use variable substitution in your string, to put
 * variable text such as user names or link URLs into translated text. Variable
 * substitution looks like this:
 * @code
 * $text = t("@name's blog", array('@name' => user_format_name($account)));
 * @endcode
 * Basically, you can put variables like @name into your string, and t() will
 * substitute their sanitized values at translation time. (See the
 * Localization API pages referenced above and the documentation of
 * format_string() for details about how to define variables in your string.)
 * Translators can then rearrange the string as necessary for the language
 * (e.g., in Spanish, it might be "blog de @name").
 *
 * During the Drupal installation phase, some resources used by t() wil not be
 * available to code that needs localization. See st() and get_t() for
 * alternatives.
 *
 * @param $string
 *   A string containing the English string to translate.
 * @param $args
 *   An associative array of replacements to make after translation. Based
 *   on the first character of the key, the value is escaped and/or themed.
 *   See format_string() for details.
 * @param $options
 *   An associative array of additional options, with the following elements:
 *   - 'langcode' (defaults to the current language): The language code to
 *     translate to a language other than what is used to display the page.
 *   - 'context' (defaults to the empty context): The context the source string
 *     belongs to.
 *
 * @return
 *   The translated string.
 *
 * @see st()
 * @see get_t()
 * @see format_string()
 * @ingroup sanitization
 */
function t($string, array $args = array(), array $options = array()) {
  static $custom_strings;

  // Merge in default.
  if (empty($options['langcode'])) {
    $options['langcode'] = language(Language::TYPE_INTERFACE)->langcode;
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
  elseif ($options['langcode'] != Language::LANGCODE_SYSTEM && ($options['langcode'] != 'en' || variable_get('locale_translate_english', FALSE)) && function_exists('locale')) {
    $string = locale($string, $options['context'], $options['langcode']);
  }
  if (empty($args)) {
    return $string;
  }
  else {
    return format_string($string, $args);
  }
}

/**
 * Formats a string for HTML display by replacing variable placeholders.
 *
 * @see \Drupal\Component\Utility\String::format()
 * @see t()
 * @ingroup sanitization
 */
function format_string($string, array $args = array()) {
  return String::format($string, $args);
}

/**
 * Encodes special characters in a plain-text string for display as HTML.
 *
 * @see \Drupal\Component\Utility\String::checkPlain()
 * @see drupal_validate_utf8()
 * @ingroup sanitization
 */
function check_plain($text) {
  return String::checkPlain($text);
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
 *
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
 * Returns the equivalent of Apache's $_SERVER['REQUEST_URI'] variable.
 *
 * Because $_SERVER['REQUEST_URI'] is only available on Apache, we generate an
 * equivalent using other environment variables.
 *
 * @todo The above comment is incorrect: http://drupal.org/node/1547294.
 */
function request_uri($omit_query_string = FALSE) {
  if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
  }
  else {
    if (isset($_SERVER['argv'][0])) {
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

  return $omit_query_string ? strtok($uri, '?') : $uri;
}

/**
 * Logs an exception.
 *
 * This is a wrapper function for watchdog() which automatically decodes an
 * exception.
 *
 * @param $type
 *   The category to which this message belongs.
 * @param $exception
 *   The exception that is going to be logged.
 * @param $message
 *   The message to store in the log. If empty, a text that contains all useful
 *   information about the passed-in exception is used.
 * @param $variables
 *   Array of variables to replace in the message on display or
 *   NULL if message is already translated or not possible to
 *   translate.
 * @param $severity
 *   The severity of the message, as per RFC 3164.
 * @param $link
 *   A link to associate with the message.
 *
 * @see watchdog()
 * @see _drupal_decode_exception()
 */
function watchdog_exception($type, Exception $exception, $message = NULL, $variables = array(), $severity = WATCHDOG_ERROR, $link = NULL) {

   // Use a default value if $message is not set.
   if (empty($message)) {
     // The exception message is run through check_plain() by _drupal_decode_exception().
     $message = '%type: !message in %function (line %line of %file).';
   }
   // $variables must be an array so that we can add the exception information.
   if (!is_array($variables)) {
     $variables = array();
   }

   require_once __DIR__ . '/errors.inc';
   $variables += _drupal_decode_exception($exception);
   watchdog($type, $message, $variables, $severity, $link);
}

/**
 * Logs a system message.
 *
 * @param $type
 *   The category to which this message belongs. Can be any string, but the
 *   general practice is to use the name of the module calling watchdog().
 * @param $message
 *   The message to store in the log. Keep $message translatable
 *   by not concatenating dynamic values into it! Variables in the
 *   message should be added by using placeholder strings alongside
 *   the variables argument to declare the value of the placeholders.
 *   See t() for documentation on how $message and $variables interact.
 * @param $variables
 *   Array of variables to replace in the message on display or array() if
 *   message is already translated or not possible to translate.
 * @param $severity
 *   The severity of the message; one of the following values as defined in
 *   @link http://www.faqs.org/rfcs/rfc3164.html RFC 3164: @endlink
 *   - WATCHDOG_EMERGENCY: Emergency, system is unusable.
 *   - WATCHDOG_ALERT: Alert, action must be taken immediately.
 *   - WATCHDOG_CRITICAL: Critical conditions.
 *   - WATCHDOG_ERROR: Error conditions.
 *   - WATCHDOG_WARNING: Warning conditions.
 *   - WATCHDOG_NOTICE: (default) Normal but significant conditions.
 *   - WATCHDOG_INFO: Informational messages.
 *   - WATCHDOG_DEBUG: Debug-level messages.
 * @param $link
 *   A link to associate with the message.
 *
 * @see watchdog_severity_levels()
 * @see hook_watchdog()
 */
function watchdog($type, $message, array $variables = NULL, $severity = WATCHDOG_NOTICE, $link = NULL) {
  global $user, $base_root;

  static $in_error_state = FALSE;

  // It is possible that the error handling will itself trigger an error. In that case, we could
  // end up in an infinite loop. To avoid that, we implement a simple static semaphore.
  if (!$in_error_state && function_exists('module_implements')) {
    $in_error_state = TRUE;

    // The user object may not exist in all conditions, so 0 is substituted if needed.
    $user_uid = isset($user->uid) ? $user->uid : 0;

    // Prepare the fields to be logged
    $log_entry = array(
      'type'        => $type,
      'message'     => $message,
      'variables'   => $variables,
      'severity'    => $severity,
      'link'        => $link,
      'user'        => $user,
      'uid'         => $user_uid,
      'request_uri' => $base_root . request_uri(),
      'referer'     => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
      'ip'          => Drupal::request()->getClientIP(),
      // Request time isn't accurate for long processes, use time() instead.
      'timestamp'   => time(),
    );

    // Call the logging hooks to log/process the message
    foreach (module_implements('watchdog') as $module) {
      $function = $module . '_watchdog';
      $function($log_entry);
    }

    // It is critical that the semaphore is only cleared here, in the parent
    // watchdog() call (not outside the loop), to prevent recursive execution.
    $in_error_state = FALSE;
  }
}

/**
 * Sets a message to display to the user.
 *
 * Messages are stored in a session variable and displayed in the page template
 * via the $messages theme variable.
 *
 * Example usage:
 * @code
 * drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
 * @endcode
 *
 * @param string $message
 *   (optional) The translated message to be displayed to the user. For
 *   consistency with other messages, it should begin with a capital letter and
 *   end with a period.
 * @param string $type
 *   (optional) The message's type. Defaults to 'status'. These values are
 *   supported:
 *   - 'status'
 *   - 'warning'
 *   - 'error'
 * @param bool $repeat
 *   (optional) If this is FALSE and the message is already set, then the
 *   message won't be repeated. Defaults to FALSE.
 *
 * @return array|null
 *   A multidimensional array with keys corresponding to the set message types.
 *   The indexed array values of each contain the set messages for that type.
 *   Or, if there are no messages set, the function returns NULL.
 *
 * @see drupal_get_messages()
 * @see theme_status_messages()
 */
function drupal_set_message($message = NULL, $type = 'status', $repeat = FALSE) {
  if ($message) {
    if (!isset($_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type] = array();
    }

    if ($repeat || !in_array($message, $_SESSION['messages'][$type])) {
      $_SESSION['messages'][$type][] = $message;
    }

    // Mark this page as being uncacheable.
    drupal_page_is_cacheable(FALSE);
  }

  // Messages not set when DB connection fails.
  return isset($_SESSION['messages']) ? $_SESSION['messages'] : NULL;
}

/**
 * Returns all messages that have been set with drupal_set_message().
 *
 * @param string $type
 *   (optional) Limit the messages returned by type. Defaults to NULL, meaning
 *   all types. These values are supported:
 *   - NULL
 *   - 'status'
 *   - 'warning'
 *   - 'error'
 * @param bool $clear_queue
 *   (optional) If this is TRUE, the queue will be cleared of messages of the
 *   type specified in the $type parameter. Otherwise the queue will be left
 *   intact. Defaults to TRUE.
 *
 * @return array
 *   A multidimensional array with keys corresponding to the set message types.
 *   The indexed array values of each contain the set messages for that type.
 *   The messages returned are limited to the type specified in the $type
 *   parameter. If there are no messages of the specified type, an empty array
 *   is returned.
 *
 * @see drupal_set_message()
 * @see theme_status_messages()
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
 * Gets the title of the current page.
 *
 * The title is displayed on the page and in the title bar.
 *
 * @return
 *   The current page's title.
 */
function drupal_get_title() {
  $title = drupal_set_title();

  // During a bootstrap, menu.inc is not included and thus we cannot provide a title.
  if (!isset($title) && function_exists('menu_get_active_title')) {
    $title = check_plain(menu_get_active_title());
  }

  return $title;
}

/**
 * Sets the title of the current page.
 *
 * The title is displayed on the page and in the title bar.
 *
 * @param $title
 *   Optional string value to assign to the page title; or if set to NULL
 *   (default), leaves the current title unchanged.
 * @param $output
 *   Optional flag - normally should be left as CHECK_PLAIN. Only set to
 *   PASS_THROUGH if you have already removed any possibly dangerous code
 *   from $title using a function like check_plain() or filter_xss(). With this
 *   flag the string will be passed through unchanged.
 *
 * @return
 *   The updated title of the current page.
 */
function drupal_set_title($title = NULL, $output = CHECK_PLAIN) {
  $stored_title = &drupal_static(__FUNCTION__);

  if (isset($title)) {
    $stored_title = ($output == PASS_THROUGH) ? $title : check_plain($title);
  }

  return $stored_title;
}

/**
 * Generates a default anonymous $user object.
 *
 * @return Drupal\user\Plugin\Core\Entity\User
 *   The user object.
 */
function drupal_anonymous_user() {
  $values = array(
    'uid' => 0,
    'hostname' => Drupal::request()->getClientIP(),
    'roles' => array(
      DRUPAL_ANONYMOUS_RID => DRUPAL_ANONYMOUS_RID,
    ),
  );
  return new User($values, 'user');
}

/**
 * Ensures Drupal is bootstrapped to the specified phase.
 *
 * In order to bootstrap Drupal from another PHP script, you can use this code:
 * @code
 *   define('DRUPAL_ROOT', '/path/to/drupal');
 *   require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
 *   drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
 * @endcode
 *
 * @param $phase
 *   A constant telling which phase to bootstrap to. When you bootstrap to a
 *   particular phase, all earlier phases are run automatically. Possible
 *   values:
 *   - DRUPAL_BOOTSTRAP_CONFIGURATION: Initializes configuration.
 *   - DRUPAL_BOOTSTRAP_PAGE_CACHE: Tries to serve a cached page.
 *   - DRUPAL_BOOTSTRAP_DATABASE: Initializes the database layer.
 *   - DRUPAL_BOOTSTRAP_VARIABLES: Initializes the variable system.
 *   - DRUPAL_BOOTSTRAP_SESSION: Initializes session handling.
 *   - DRUPAL_BOOTSTRAP_CODE: Loads code for subsystems and modules.
 *   - DRUPAL_BOOTSTRAP_FULL: Fully loads Drupal. Validates and fixes input
 *     data.
 * @param $new_phase
 *   A boolean, set to FALSE if calling drupal_bootstrap from inside a
 *   function called from drupal_bootstrap (recursion).
 *
 * @return
 *   The most recently completed phase.
 */
function drupal_bootstrap($phase = NULL, $new_phase = TRUE) {
  // Not drupal_static(), because does not depend on any run-time information.
  static $phases = array(
    DRUPAL_BOOTSTRAP_CONFIGURATION,
    DRUPAL_BOOTSTRAP_KERNEL,
    DRUPAL_BOOTSTRAP_PAGE_CACHE,
    DRUPAL_BOOTSTRAP_DATABASE,
    DRUPAL_BOOTSTRAP_VARIABLES,
    DRUPAL_BOOTSTRAP_SESSION,
    DRUPAL_BOOTSTRAP_CODE,
    DRUPAL_BOOTSTRAP_FULL,
  );
  // Not drupal_static(), because the only legitimate API to control this is to
  // call drupal_bootstrap() with a new phase parameter.
  static $final_phase = -1;
  // Not drupal_static(), because it's impossible to roll back to an earlier
  // bootstrap state.
  static $stored_phase = -1;

  // When not recursing, store the phase name so it's not forgotten during
  // recursion. Additionally, ensure that $final_phase is never rolled back to an
  // earlier bootstrap state.
  if ($new_phase && $phase > $final_phase) {
    $final_phase = $phase;
  }
  if (isset($phase)) {
    // Call a phase if it has not been called before and is below the requested
    // phase.
    while ($phases && $phase > $stored_phase && $final_phase > $stored_phase) {
      $current_phase = array_shift($phases);

      // This function is re-entrant. Only update the completed phase when the
      // current call actually resulted in a progress in the bootstrap process.
      if ($current_phase > $stored_phase) {
        $stored_phase = $current_phase;
      }

      switch ($current_phase) {
        case DRUPAL_BOOTSTRAP_CONFIGURATION:
          _drupal_bootstrap_configuration();
          break;

        case DRUPAL_BOOTSTRAP_KERNEL:
          _drupal_bootstrap_kernel();
          break;

        case DRUPAL_BOOTSTRAP_PAGE_CACHE:
          _drupal_bootstrap_page_cache();
          break;

        case DRUPAL_BOOTSTRAP_DATABASE:
          _drupal_bootstrap_database();
          break;

        case DRUPAL_BOOTSTRAP_VARIABLES:
          _drupal_bootstrap_variables();
          break;

        case DRUPAL_BOOTSTRAP_SESSION:
          require_once DRUPAL_ROOT . '/' . settings()->get('session_inc', 'core/includes/session.inc');
          drupal_session_initialize();
          break;

        case DRUPAL_BOOTSTRAP_CODE:
          require_once __DIR__ . '/common.inc';
          _drupal_bootstrap_code();
          break;

        case DRUPAL_BOOTSTRAP_FULL:
          _drupal_bootstrap_full();
          break;
      }
    }
  }
  return $stored_phase;
}

/**
 * Handles an entire PHP request.
 *
 * This function may be called by PHP scripts (e.g., Drupal's index.php) that
 * want Drupal to take over the entire PHP processing of the request. The only
 * expectation is that PHP's superglobals are initialized as desired (PHP does
 * this automatically, but some scripts might want to alter them) and that the
 * DRUPAL_ROOT constant is defined and set to the absolute server directory of
 * Drupal's codebase.
 *
 * Scripts and applications that want to invoke multiple Drupal requests within
 * a single PHP request, or Drupal request handling within some larger workflow,
 * should not call this function, but instead instantiate and use
 * \Drupal\Core\DrupalKernel as needed.
 *
 * @param boolean $test_only
 *   Whether to restrict handling to only requests invoked by SimpleTest.
 *
 * @see index.php
 */
function drupal_handle_request($test_only = FALSE) {
  // Initialize the environment, load settings.php, and activate a PSR-0 class
  // autoloader with required namespaces registered.
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

  // Exit if we should be in a test environment but aren't.
  if ($test_only && !drupal_valid_test_ua()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    exit;
  }

  // @todo Figure out how best to handle the Kernel constructor parameters.
  $kernel = new DrupalKernel('prod', FALSE, drupal_classloader(), !$test_only);

  // @todo Remove this once everything in the bootstrap has been
  //   converted to services in the DIC.
  $kernel->boot();
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);

  // Create a request object from the HttpFoundation.
  $request = Request::createFromGlobals();
  $response = $kernel->handle($request)->prepare($request)->send();

  $kernel->terminate($request, $response);
}

/**
 * Returns the time zone of the current user.
 */
function drupal_get_user_timezone() {
  global $user;
  $config = config('system.timezone');

  if ($config->get('user.configurable') && $user->uid && $user->timezone) {
    return $user->timezone;
  }
  else {
    // Ignore PHP strict notice if time zone has not yet been set in the php.ini
    // configuration.
    $config_data_default_timezone = $config->get('default');
    return !empty($config_data_default_timezone) ? $config_data_default_timezone : @date_default_timezone_get();
  }
}

/**
 * Gets a salt useful for hardening against SQL injection.
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
 * Provides custom PHP error handling.
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
 *   An array that points to the active symbol table at the point the error
 *   occurred.
 */
function _drupal_error_handler($error_level, $message, $filename, $line, $context) {
  require_once __DIR__ . '/errors.inc';
  _drupal_error_handler_real($error_level, $message, $filename, $line, $context);
}

/**
 * Provides custom PHP exception handling.
 *
 * Uncaught exceptions are those not enclosed in a try/catch block. They are
 * always fatal: the execution of the script will stop as soon as the exception
 * handler exits.
 *
 * @param $exception
 *   The exception object that was thrown.
 */
function _drupal_exception_handler($exception) {
  require_once __DIR__ . '/errors.inc';

  try {
    // Log the message to the watchdog and return an error page to the user.
    _drupal_log_error(_drupal_decode_exception($exception), TRUE);
  }
  catch (Exception $exception2) {
    // Another uncaught exception was thrown while handling the first one.
    // If we are displaying errors, then do so with no possibility of a further uncaught exception being thrown.
    if (error_displayable()) {
      print '<h1>Additional uncaught exception thrown while handling exception.</h1>';
      print '<h2>Original</h2><p>' . _drupal_render_exception_safe($exception) . '</p>';
      print '<h2>Additional</h2><p>' . _drupal_render_exception_safe($exception2) . '</p><hr />';
    }
  }
}

/**
 * Sets up the script environment and loads settings.php.
 */
function _drupal_bootstrap_configuration() {
  drupal_environment_initialize();
  // Initialize the configuration, including variables from settings.php.
  drupal_settings_initialize();

  // Make sure we are using the test database prefix in child Drupal sites.
  _drupal_initialize_db_test_prefix();

  // Activate the class loader.
  drupal_classloader();

  // Start a page timer:
  Timer::start('page');

  // Detect string handling method.
  Unicode::check();

  // Load the procedural configuration system helper functions.
  require_once __DIR__ . '/config.inc';

  // Set the Drupal custom error handler. (requires config())
  set_error_handler('_drupal_error_handler');
  set_exception_handler('_drupal_exception_handler');

  // Redirect the user to the installation script if Drupal has not been
  // installed yet (i.e., if no $databases array has been defined in the
  // settings.php file) and we are not already installing.
  if (empty($GLOBALS['databases']) && !drupal_installation_attempted()) {
    include_once __DIR__ . '/install.inc';
    install_goto('core/install.php');
  }
}

/**
 * Initialize the kernel / service container.
 */
function _drupal_bootstrap_kernel() {
  // Normally, index.php puts a container in drupal_container() by creating a
  // kernel. If there is no container yet, create one.
  if (!drupal_container()) {
    $kernel = new DrupalKernel('prod', FALSE, drupal_classloader());
    $kernel->boot();
  }
}

/**
 * Attempts to serve a page from the cache.
 */
function _drupal_bootstrap_page_cache() {
  global $user;

  // Allow specifying special cache handlers in settings.php, like
  // using memcached or files for storing cache information.
  require_once __DIR__ . '/cache.inc';
  foreach (variable_get('cache_backends', array()) as $include) {
    require_once DRUPAL_ROOT . '/' . $include;
  }
  // Check for a cache mode force from settings.php.
  if (settings()->get('page_cache_without_database')) {
    $cache_enabled = TRUE;
  }
  else {
    drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES, FALSE);
    $config = config('system.performance');
    $cache_enabled = $config->get('cache.page.use_internal');
  }
  // If there is no session cookie and cache is enabled (or forced), try
  // to serve a cached page.
  if (!isset($_COOKIE[session_name()]) && $cache_enabled) {
    // Make sure there is a user object because its timestamp will be checked.
    $user = drupal_anonymous_user();
    // Get the page from the cache.
    $cache = drupal_page_get_cache();
    // If there is a cached page, display it.
    if (is_object($cache)) {
      header('X-Drupal-Cache: HIT');
      // Restore the metadata cached with the page.
      _current_path($cache->data['path']);
      drupal_set_title($cache->data['title'], PASS_THROUGH);
      date_default_timezone_set(drupal_get_user_timezone());

      drupal_serve_page_from_cache($cache);
      // We are done.
      exit;
    }
    else {
      header('X-Drupal-Cache: MISS');
    }
  }
}

/**
 * In a test environment, get the test db prefix and set it in $databases.
 */
function _drupal_initialize_db_test_prefix() {
  // The user agent header is used to pass a database prefix in the request when
  // running tests. However, for security reasons, it is imperative that we
  // validate we ourselves made the request.
  if ($test_prefix = drupal_valid_test_ua()) {
    // Set the test run id for use in other parts of Drupal.
    $test_info = &$GLOBALS['drupal_test_info'];
    $test_info['test_run_id'] = $test_prefix;
    $test_info['in_child_site'] = TRUE;

    foreach ($GLOBALS['databases']['default'] as &$value) {
      // Extract the current default database prefix.
      if (!isset($value['prefix'])) {
        $current_prefix = '';
      }
      elseif (is_array($value['prefix'])) {
        $current_prefix = $value['prefix']['default'];
      }
      else {
        $current_prefix = $value['prefix'];
      }

      // Remove the current database prefix and replace it by our own.
      $value['prefix'] = array(
        'default' => $current_prefix . $test_prefix,
      );
    }
  }
}

/**
 * Initializes the database system by loading database.inc.
 */
function _drupal_bootstrap_database() {
  // Initialize the database system. Note that the connection
  // won't be initialized until it is actually requested.
  require_once __DIR__ . '/database.inc';
}

/**
 * Loads system variables and all enabled bootstrap modules.
 */
function _drupal_bootstrap_variables() {
  global $conf;

  // Load variables from the database, but do not overwrite variables set in settings.php.
  $conf = variable_initialize(isset($conf) ? $conf : array());
  // Load bootstrap modules.
  require_once __DIR__ . '/module.inc';
  drupal_container()->get('module_handler')->loadBootstrapModules();
}

/**
 * Returns the current bootstrap phase for this Drupal process.
 *
 * The current phase is the one most recently completed by drupal_bootstrap().
 *
 * @see drupal_bootstrap()
 */
function drupal_get_bootstrap_phase() {
  return drupal_bootstrap();
}

/**
 * Retrieves the Drupal Container to standardize object construction.
 *
 * @deprecated This function has been replaced by the \Drupal class. Use that
 *   instead.
 *
 * @return Symfony\Component\DependencyInjection\ContainerInterface|bool
 *   The instance of the ContainerInterface used to set up and maintain
 *   object instances or FALSE if none exist yet.
 */
function drupal_container() {
  return Drupal::getContainer();
}

/**
 * Returns the list of enabled modules.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->getModuleList().
 *
 * @see \Drupal\Core\Extension\ModuleHandler::getModuleList()
 */
function module_list() {
  $modules = array_keys(drupal_container()->get('module_handler')->getModuleList());
  return array_combine($modules, $modules);
}

/**
 * Determines which modules are implementing a hook.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->getImplementations($hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::getImplementations()
 */
function module_implements($hook) {
  return drupal_container()->get('module_handler')->getImplementations($hook);
}

/**
 * Invokes a hook in a particular module.
 *
 * All arguments are passed by value. Use drupal_alter() if you need to pass
 * arguments by reference.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->invoke($module, $hook, $args = array()).
 *
 * @see drupal_alter()
 * @see \Drupal\Core\Extension\ModuleHandler::invoke()
 */
function module_invoke($module, $hook) {
  $args = func_get_args();
  // Remove $module and $hook from the arguments.
  unset($args[0], $args[1]);
  return drupal_container()->get('module_handler')->invoke($module, $hook, $args);
}

/**
 * Invokes a hook in all enabled modules that implement it.
 *
 * All arguments are passed by value. Use drupal_alter() if you need to pass
 * arguments by reference.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->invokeAll($hook).
 *
 * @see drupal_alter()
 * @see \Drupal\Core\Extension\ModuleHandler::invokeAll()
 */
function module_invoke_all($hook) {
  $args = func_get_args();
  // Remove $hook from the arguments.
  array_shift($args);
  return drupal_container()->get('module_handler')->invokeAll($hook, $args);
}

/**
 * Passes alterable variables to specific hook_TYPE_alter() implementations.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->alter($hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::alter()
 */
function drupal_alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
  return drupal_container()->get('module_handler')->alter($type, $data, $context1, $context2);
}

/**
 * Determines whether a given module exists.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->moduleExists($hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::moduleExists()
 */
function module_exists($module) {
  return drupal_container()->get('module_handler')->moduleExists($module);
}

/**
 * Determines whether a module implements a hook.
 *
 * @deprecated as of Drupal 8.0. Use
 *   drupal_container()->get('module_handler')->implementsHook($module, $hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::implementsHook()
 */
function module_hook($module, $hook) {
  return drupal_container()->get('module_handler')->implementsHook($module, $hook);
}

/**
 * Returns the state storage service.
 *
 * Use this to store machine-generated data, local to a specific environment
 * that does not need deploying and does not need human editing; for example,
 * the last time cron was run. Data which needs to be edited by humans and
 * needs to be the same across development, production, etc. environments
 * (for example, the system maintenance message) should use config() instead.
 *
 * @return Drupal\Core\KeyValueStore\KeyValueStoreInterface
 */
function state() {
  return drupal_container()->get('keyvalue')->get('state');
}

/**
 * Returns the typed data manager service.
 *
 * Use the typed data manager service for creating typed data objects.
 *
 * @see Drupal\Core\TypedData\TypedDataManager::create()
 *
 * @return Drupal\Core\TypedData\TypedDataManager
 */
function typed_data() {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['manager'] = &drupal_static(__FUNCTION__);
  }
  if (!isset($drupal_static_fast['manager'])) {
    $drupal_static_fast['manager'] = drupal_container()->get('typed_data');
  }
  return $drupal_static_fast['manager'];
}

/**
 * Returns the test prefix if this is an internal request from SimpleTest.
 *
 * @param string $new_prefix
 *   Internal use only. A new prefix to be stored. Passed in by tests that use
 *   the test runner from within a test.
 *
 * @return
 *   Either the simpletest prefix (the string "simpletest" followed by any
 *   number of digits) or FALSE if the user agent does not contain a valid
 *   HMAC and timestamp.
 */
function drupal_valid_test_ua($new_prefix = NULL) {
  static $test_prefix;

  if (isset($new_prefix)) {
    $test_prefix = $new_prefix;
  }
  if (isset($test_prefix)) {
    return $test_prefix;
  }

  if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^(simpletest\d+);(.+);(.+);(.+)$/", $_SERVER['HTTP_USER_AGENT'], $matches)) {
    list(, $prefix, $time, $salt, $hmac) = $matches;
    $check_string =  $prefix . ';' . $time . ';' . $salt;
    // We use the salt from settings.php to make the HMAC key, since
    // the database is not yet initialized and we can't access any Drupal variables.
    // The file properties add more entropy not easily accessible to others.
    $key = drupal_get_hash_salt() . filectime(__FILE__) . fileinode(__FILE__);
    $time_diff = REQUEST_TIME - $time;
    // We cant use Crypt::hmacBase64() yet because this can be called in very
    // early bootstrap when autoloader has not been initialized yet.
    $test_hmac = base64_encode(hash_hmac('sha256', $check_string, $key, TRUE));
    $test_hmac = strtr($test_hmac, array('+' => '-', '/' => '_', '=' => ''));
    // Since we are making a local request a 5 second time window is allowed,
    // and the HMAC must match.
    if ($time_diff >= 0 && $time_diff <= 5 && $hmac == $test_hmac) {
      $test_prefix = $prefix;
      _drupal_load_test_overrides($test_prefix);
      return $test_prefix;
    }
  }

  $test_prefix = FALSE;
  return $test_prefix;
}

/**
 * Overrides low-level and environment-specific configuration.
 *
 * Very strictly for internal use only.
 *
 * Loads settings.php from the simpletest public files directory. These files
 * can change the global $conf, the global $config_directories, the return
 * value of conf_path(), and settings().
 *
 * @param string $test_prefix
 *   The simpletest prefix.
 */
function _drupal_load_test_overrides($test_prefix) {
  global $conf, $config_directories;

  // Do not use the parent site's config directories. Use only the child site's.
  // @see Drupal\simpletest\TestBase::prepareConfigDirectories()
  $path_prefix = 'simpletest/' . substr($test_prefix, 10);
  $config_directories = array();
  foreach (array(CONFIG_ACTIVE_DIRECTORY, CONFIG_STAGING_DIRECTORY) as $type) {
    $config_directories[$type] = array('path' => $path_prefix . '/config_' . $type);
  }

  // Check for and load a settings.php file in the simpletest files directory.
  $filename = conf_path() . '/files/' . $path_prefix . '/settings.php';
  if (file_exists($filename)) {
    $settings = settings()->getAll();
    $conf_path = &drupal_static('conf_path');
    // This can override $conf, $conf_path, $settings, and $config_directories.
    include $filename;
    // Keep the overriden $conf_path alive across drupal_static_reset() calls.
    // @see conf_path()
    $settings['simpletest_conf_path'] = $conf_path;
    new Settings($settings);
  }
}

/**
 * Generates a user agent string with a HMAC and timestamp for simpletest.
 */
function drupal_generate_test_ua($prefix) {
  static $key;

  if (!isset($key)) {
    // We use the salt from settings.php to make the HMAC key, since
    // the database is not yet initialized and we can't access any Drupal variables.
    // The file properties add more entropy not easily accessible to others.
    $key = drupal_get_hash_salt() . filectime(__FILE__) . fileinode(__FILE__);
  }
  // Generate a moderately secure HMAC based on the database credentials.
  $salt = uniqid('', TRUE);
  $check_string = $prefix . ';' . time() . ';' . $salt;
  return $check_string . ';' . Crypt::hmacBase64($check_string, $key);
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
  require_once __DIR__ . '/theme.maintenance.inc';
  _drupal_maintenance_theme();
}

/**
 * Returns TRUE if a Drupal installation is currently being attempted.
 */
function drupal_installation_attempted() {
  // This cannot rely on the MAINTENANCE_MODE constant, since that would prevent
  // tests from using the non-interactive installer, in which case Drupal
  // only happens to be installed within the same request, but subsequently
  // executed code does not involve the installer at all.
  // @see install_drupal()
  return isset($GLOBALS['install_state']) && empty($GLOBALS['install_state']['installation_finished']);
}

/**
 * Returns the name of the proper localization function.
 *
 * get_t() exists to support localization for code that might run during
 * the installation phase, when some elements of the system might not have
 * loaded.
 *
 * This would include implementations of hook_install(), which could run
 * during the Drupal installation phase, and might also be run during
 * non-installation time, such as while installing the module from the the
 * module administration page.
 *
 * Example usage:
 * @code
 *   $t = get_t();
 *   $translated = $t('translate this');
 * @endcode
 *
 * Use t() if your code will never run during the Drupal installation phase.
 * Use st() if your code will only run during installation and never any other
 * time. Use get_t() if your code could run in either circumstance.
 *
 * @see t()
 * @see st()
 * @ingroup sanitization
 */
function get_t() {
  static $t;
  // This is not converted to drupal_static because there is no point in
  // resetting this as it can not change in the course of a request.
  if (!isset($t)) {
    $t = drupal_installation_attempted() ? 'st' : 't';
  }
  return $t;
}

/**
 * Initializes all the defined language types.
 *
 * @see language()
 */
function drupal_language_initialize() {
  drupal_container()->get('language_manager')->init();
}

/**
 * Returns the language object for a given language type.
 *
 * @see Drupal\Core\Language\LanguageManager
 *
 * @param string $type
 *   The type of language object needed, e.g. Language::TYPE_INTERFACE.
 */
function language($type) {
  $container = drupal_container();
  if (!$container) {
    return language_default();
  }
  if (!$container->has('language_manager')) {
    // This happens in rare situations when the container has not been built by
    // a kernel and has no services, e.g., when t() is called during unit tests
    // for assertions.
    $container->register('language_manager', 'Drupal\Core\Language\LanguageManager');
  }
  return $container->get('language_manager')->getLanguage($type);
}

/**
 * Returns an array of the available language types.
 *
 * @return array
 *   An array of all language types where the keys of each are the language type
 *   name and its value is its configurability (TRUE/FALSE).
 */
function language_types_get_all() {
  return array_keys(variable_get('language_types', language_types_get_default()));
}
/**
 * Returns a list of the built-in language types.
 *
 * @return array
 *   An array of key-values pairs where the key is the language type name and
 *   the value is its configurability (TRUE/FALSE).
 */
function language_types_get_default() {
  return array(
    Language::TYPE_INTERFACE => TRUE,
    Language::TYPE_CONTENT => FALSE,
    Language::TYPE_URL => FALSE,
  );
}

/**
 * Returns TRUE if there is more than one language enabled.
 *
 * @return bool
 *   TRUE if more than one language is enabled.
 */
function language_multilingual() {
  // The "language_count" variable stores the number of enabled languages to
  // avoid unnecessarily querying the database when building the list of
  // enabled languages on monolingual sites.
  return variable_get('language_count', 1) > 1;
}

/**
 * Returns a list of languages set up on the site.
 *
 * @param $flags
 *   (optional) Specifies the state of the languages that have to be returned.
 *   It can be: Language::STATE_CONFIGURABLE, Language::STATE_LOCKED,
 *   Language::STATE_ALL.
 *
 * @return array
 *   An associative array of languages, keyed by the language code, ordered by
 *   weight ascending and name ascending.
 */
function language_list($flags = Language::STATE_CONFIGURABLE) {

  $languages = &drupal_static(__FUNCTION__);

  // Initialize master language list.
  if (!isset($languages)) {
    // Initialize local language list cache.
   $languages = array();

    // Fill in master language list based on current configuration.
    $default = language_default();
    if (language_multilingual() || module_exists('language')) {
      // Use language module configuration if available.
      $languages = db_query('SELECT * FROM {language} ORDER BY weight ASC, name ASC')->fetchAllAssoc('langcode', PDO::FETCH_ASSOC);

      // Initialize default property so callers have an easy reference and can
      // save the same object without data loss.
      foreach ($languages as $langcode => $info) {
        $info['default'] = ($langcode == $default->langcode);
        $languages[$langcode] = new Language($info);
      }
    }
    else {
      // No language module, so use the default language only.
      $languages = array($default->langcode => $default);
      // Add the special languages, they will be filtered later if needed.
      $languages += language_default_locked_languages($default->weight);
    }
  }

  // Filter the full list of languages based on the value of the $all flag. By
  // default we remove the locked languages, but the caller may request for
  // those languages to be added as well.
  $filtered_languages = array();

  // Add the site's default language if flagged as allowed value.
  if ($flags & Language::STATE_SITE_DEFAULT) {
    $default = isset($default) ? $default : language_default();
    // Rename the default language.
    $default->name = t("Site's default language (@lang_name)", array('@lang_name' => $default->name));
    $filtered_languages['site_default'] = $default;
  }

  foreach ($languages as $langcode => $language) {
    if (($language->locked && !($flags & Language::STATE_LOCKED)) || (!$language->locked && !($flags & Language::STATE_CONFIGURABLE))) {
      continue;
     }
    $filtered_languages[$langcode] = $language;
  }

  return $filtered_languages;
}

/**
 * Returns a list of the default locked languages.
 *
 * @param int $weight
 *   An integer value that is used as the start value for the weights of the
 *   locked languages.
 *
 * @return array
 *   An array of language objects.
 */
function language_default_locked_languages($weight = 0) {
  $locked_language = array(
    'default' => FALSE,
    'locked' => TRUE,
    'enabled' => TRUE,
  );

  $languages = array();
  $languages[Language::LANGCODE_NOT_SPECIFIED] = new Language(array(
    'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    'name' => t('Not specified'),
    'weight' => ++$weight,
  ) + $locked_language);
  $languages[Language::LANGCODE_NOT_APPLICABLE] = new Language(array(
    'langcode' => Language::LANGCODE_NOT_APPLICABLE,
    'name' => t('Not applicable'),
    'weight' => ++$weight,
  ) + $locked_language);
  return $languages;
}

/**
 * Loads a language object from the database.
 *
 * @param string $langcode
 *   The language code.
 *
 * @return Drupal\core\Language\Language | FALSE
 *   A fully-populated language object or FALSE.
 */
function language_load($langcode) {
  $languages = language_list(Language::STATE_ALL);
  return isset($languages[$langcode]) ? $languages[$langcode] : FALSE;
}

/**
 * Produced the printed name for a language for display.
 *
 * @param string $langcode
 *   The language code.
 *
 * @return string
 *   The printed name of the language.
 */
function language_name($langcode) {
  if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
    return t('None');
  }

  if ($language = language_load($langcode)) {
    return $language->name;
  }
  if (empty($langcode)) {
    return t('Unknown');
  }
  return t('Unknown (@langcode)', array('@langcode' => $langcode));
}

/**
 * Checks if a language is locked.
 *
 * @param string $langcode
 *   The language code.
 *
 * @return bool
 *   Returns whether the language is locked.
 */
function language_is_locked($langcode) {
  $language = language_load($langcode);
  return ($language ? $language->locked : FALSE);
}

/**
 * Returns the default language used on the site.
 *
 * @return Drupal\Core\Language\Language
 *   A language object.
 */
function language_default() {
  $info = variable_get('language_default', array(
    'langcode' => 'en',
    'name' => 'English',
    'direction' => 0,
    'weight' => 0,
    'locked' => 0,
  ));
  $info['default'] = TRUE;
  return new Language($info);
}

/**
 * Stores or retrieves the path derived during language negotiation.
 *
 * @param string $new_path
 *   The altered path.
 *
 * @todo Replace this with a path processor in language module. See
 *   http://drupal.org/node/1888424.
 */
function _language_resolved_path($new_path = NULL) {
  $path = &drupal_static(__FUNCTION__, NULL);
  if ($new_path === NULL) {
    return $path;
  }
  $path = $new_path;
  return $path;
}

/**
 * Returns the requested URL path of the page being viewed.
 *
 * Examples:
 * - http://example.com/node/306 returns "node/306".
 * - http://example.com/drupalfolder/node/306 returns "node/306" while
 *   base_path() returns "/drupalfolder/".
 * - http://example.com/path/alias (which is a path alias for node/306) returns
 *   "path/alias" as opposed to the internal path.
 * - http://example.com/index.php returns an empty string (meaning: front page).
 * - http://example.com/index.php?page=1 returns an empty string.
 *
 * @return
 *   The requested Drupal URL path.
 *
 * @see current_path()
 */
function request_path() {
  static $path;

  if (isset($path)) {
    return $path;
  }

  // Get the part of the URI between the base path of the Drupal installation
  // and the query string, and unescape it.
  $request_path = request_uri(TRUE);
  $base_path_len = strlen(rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/'));
  $path = substr(urldecode($request_path), $base_path_len + 1);

  // Depending on server configuration, the URI might or might not include the
  // script name. For example, the front page might be accessed as
  // http://example.com or as http://example.com/index.php, and the "user"
  // page might be accessed as http://example.com/user or as
  // http://example.com/index.php/user. Strip the script name from $path.
  $script = basename($_SERVER['SCRIPT_NAME']);
  if ($path == $script) {
    $path = '';
  }
  elseif (strpos($path, $script . '/') === 0) {
    $path = substr($path, strlen($script) + 1);
  }

  // Extra slashes can appear in URLs or under some conditions, added by Apache,
  // so normalize.
  $path = trim($path, '/');

  return $path;
}

/**
 * @todo This is a temporary function pending refactoring Drupal to use
 *   Symfony's Request object exclusively.
 */
function _current_path($path = NULL) {
  static $current_path = '';
  if (isset($path)) {
    $current_path = $path;
  }
  return $current_path;
}

/**
 * Returns a component of the current Drupal path.
 *
 * When viewing a page at the path "admin/structure/types", for example, arg(0)
 * returns "admin", arg(1) returns "structure", and arg(2) returns "types".
 *
 * Avoid use of this function where possible, as resulting code is hard to
 * read. In menu callback functions, attempt to use named arguments. See the
 * explanation in menu.inc for how to construct callbacks that take arguments.
 * When attempting to use this function to load an element from the current
 * path, e.g. loading the node on a node page, use menu_get_object() instead.
 *
 * @param $index
 *   The index of the component, where each component is separated by a '/'
 *   (forward-slash), and where the first component has an index of 0 (zero).
 * @param $path
 *   A path to break into components. Defaults to the path of the current page.
 *
 * @return
 *   The component specified by $index, or NULL if the specified component was
 *   not found. If called without arguments, it returns an array containing all
 *   the components of the current path.
 */
function arg($index = NULL, $path = NULL) {
  // Even though $arguments doesn't need to be resettable for any functional
  // reasons (the result of explode() does not depend on any run-time
  // information), it should be resettable anyway in case a module needs to
  // free up the memory used by it.
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['arguments'] = &drupal_static(__FUNCTION__);
  }
  $arguments = &$drupal_static_fast['arguments'];

  if (!isset($path)) {
    // @todo The public function current_path() is not available during early
    //   bootstrap.
    $path = _current_path();
  }
  if (!isset($arguments[$path])) {
    $arguments[$path] = explode('/', $path);
  }
  if (!isset($index)) {
    return $arguments[$path];
  }
  if (isset($arguments[$path][$index])) {
    return $arguments[$path][$index];
  }
}

/**
 * Initializes and returns the class loader.
 *
 * The class loader is responsible for lazy-loading all PSR-0 compatible
 * classes, interfaces, and traits (PHP 5.4 and later). It's only dependency
 * is DRUPAL_ROOT. Otherwise it may be called as early as possible.
 *
 * @param $class_loader
 *   The name of class loader to use. This can be used to change the class
 *   loader class when calling drupal_classloader() from settings.php. It is
 *   ignored otherwise.
 *
 * @return \Symfony\Component\ClassLoader\ClassLoader
 *   A ClassLoader class instance (or extension thereof).
 */
function drupal_classloader($class_loader = NULL) {
  // By default, use the ClassLoader which is best for development, as it does
  // not break when code is moved on the file system. However, as it is slow,
  // allow to use the APC class loader in production.
  static $loader;

  if (!isset($loader)) {

    // Include the Symfony ClassLoader for loading PSR-0-compatible classes.
    require_once DRUPAL_ROOT . '/core/vendor/symfony/class-loader/Symfony/Component/ClassLoader/ClassLoader.php';
    $loader = new ClassLoader();

    // Register the class loader.
    // When configured to use APC, the ApcClassLoader is registered instead.
    // Note that ApcClassLoader decorates ClassLoader and only provides the
    // findFile() method, but none of the others. The actual registry is still
    // in ClassLoader.
    if (!isset($class_loader)) {
      $class_loader = settings()->get('class_loader', 'default');
    }
    if ($class_loader === 'apc') {
      require_once DRUPAL_ROOT . '/core/vendor/symfony/class-loader/Symfony/Component/ClassLoader/ApcClassLoader.php';
      $apc_loader = new ApcClassLoader('drupal.' . drupal_get_hash_salt(), $loader);
      $apc_loader->register();
    }
    else {
      $loader->register();
    }

    // Register namespaces for vendor libraries managed by Composer.
    $prefixes_and_namespaces = require DRUPAL_ROOT . '/core/vendor/composer/autoload_namespaces.php';
    $loader->addPrefixes($prefixes_and_namespaces);

    // Register the loader with PHP.
    $loader->register();
  }
  return $loader;
}

/**
 * Registers an additional namespace.
 *
 * @param string $name
 *   The namespace component to register; e.g., 'node'.
 * @param string $path
 *   The relative path to the Drupal component in the filesystem.
 */
function drupal_classloader_register($name, $path) {
  $loader = drupal_classloader();
  $loader->addPrefix('Drupal\\' . $name, DRUPAL_ROOT . '/' . $path . '/lib');
}

/**
 * Provides central static variable storage.
 *
 * All functions requiring a static variable to persist or cache data within
 * a single page request are encouraged to use this function unless it is
 * absolutely certain that the static variable will not need to be reset during
 * the page request. By centralizing static variable storage through this
 * function, other functions can rely on a consistent API for resetting any
 * other function's static variables.
 *
 * Example:
 * @code
 * function example_list($field = 'default') {
 *   $examples = &drupal_static(__FUNCTION__);
 *   if (!isset($examples)) {
 *     // If this function is being called for the first time after a reset,
 *     // query the database and execute any other code needed to retrieve
 *     // information.
 *     ...
 *   }
 *   if (!isset($examples[$field])) {
 *     // If this function is being called for the first time for a particular
 *     // index field, then execute code needed to index the information already
 *     // available in $examples by the desired field.
 *     ...
 *   }
 *   // Subsequent invocations of this function for a particular index field
 *   // skip the above two code blocks and quickly return the already indexed
 *   // information.
 *   return $examples[$field];
 * }
 * function examples_admin_overview() {
 *   // When building the content for the overview page, make sure to get
 *   // completely fresh information.
 *   drupal_static_reset('example_list');
 *   ...
 * }
 * @endcode
 *
 * In a few cases, a function can have certainty that there is no legitimate
 * use-case for resetting that function's static variable. This is rare,
 * because when writing a function, it's hard to forecast all the situations in
 * which it will be used. A guideline is that if a function's static variable
 * does not depend on any information outside of the function that might change
 * during a single page request, then it's ok to use the "static" keyword
 * instead of the drupal_static() function.
 *
 * Example:
 * @code
 * function actions_do(...) {
 *   // $stack tracks the number of recursive calls.
 *   static $stack;
 *   $stack++;
 *   if ($stack > variable_get('action_max_stack', 35)) {
 *     ...
 *     return;
 *   }
 *   ...
 *   $stack--;
 * }
 * @endcode
 *
 * In a few cases, a function needs a resettable static variable, but the
 * function is called many times (100+) during a single page request, so
 * every microsecond of execution time that can be removed from the function
 * counts. These functions can use a more cumbersome, but faster variant of
 * calling drupal_static(). It works by storing the reference returned by
 * drupal_static() in the calling function's own static variable, thereby
 * removing the need to call drupal_static() for each iteration of the function.
 * Conceptually, it replaces:
 * @code
 * $foo = &drupal_static(__FUNCTION__);
 * @endcode
 * with:
 * @code
 * // Unfortunately, this does not work.
 * static $foo = &drupal_static(__FUNCTION__);
 * @endcode
 * However, the above line of code does not work, because PHP only allows static
 * variables to be initializied by literal values, and does not allow static
 * variables to be assigned to references.
 * - http://php.net/manual/language.variables.scope.php#language.variables.scope.static
 * - http://php.net/manual/language.variables.scope.php#language.variables.scope.references
 * The example below shows the syntax needed to work around both limitations.
 * For benchmarks and more information, see http://drupal.org/node/619666.
 *
 * Example:
 * @code
 * function user_access($string, $account = NULL) {
 *   // Use the advanced drupal_static() pattern, since this is called very often.
 *   static $drupal_static_fast;
 *   if (!isset($drupal_static_fast)) {
 *     $drupal_static_fast['perm'] = &drupal_static(__FUNCTION__);
 *   }
 *   $perm = &$drupal_static_fast['perm'];
 *   ...
 * }
 * @endcode
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
 *   function drupal_static_reset() and the return value should not be used in
 *   this case.
 *
 * @return
 *   Returns a variable by reference.
 *
 * @see drupal_static_reset()
 */
function &drupal_static($name, $default_value = NULL, $reset = FALSE) {
  static $data = array(), $default = array();
  // First check if dealing with a previously defined static variable.
  if (isset($data[$name]) || array_key_exists($name, $data)) {
    // Non-NULL $name and both $data[$name] and $default[$name] statics exist.
    if ($reset) {
      // Reset pre-existing static variable to its default value.
      $data[$name] = $default[$name];
    }
    return $data[$name];
  }
  // Neither $data[$name] nor $default[$name] static variables exist.
  if (isset($name)) {
    if ($reset) {
      // Reset was called before a default is set and yet a variable must be
      // returned.
      return $data;
    }
    // First call with new non-NULL $name. Initialize a new static variable.
    $default[$name] = $data[$name] = $default_value;
    return $data[$name];
  }
  // Reset all: ($name == NULL). This needs to be done one at a time so that
  // references returned by earlier invocations of drupal_static() also get
  // reset.
  foreach ($default as $name => $value) {
    $data[$name] = $value;
  }
  // As the function returns a reference, the return should always be a
  // variable.
  return $data;
}

/**
 * Resets one or all centrally stored static variable(s).
 *
 * @param $name
 *   Name of the static variable to reset. Omit to reset all variables.
 */
function drupal_static_reset($name = NULL) {
  drupal_static($name, NULL, TRUE);
}

/**
 * Detects whether the current script is running in a command-line environment.
 */
function drupal_is_cli() {
  return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
}

/**
 * Formats text for emphasized display in a placeholder inside a sentence.
 *
 * @see \Drupal\Component\Utility\String::placeholder()
 */
function drupal_placeholder($text) {
  return String::placeholder($text);
}

/**
 * Registers a function for execution on shutdown.
 *
 * Wrapper for register_shutdown_function() that catches thrown exceptions to
 * avoid "Exception thrown without a stack frame in Unknown".
 *
 * @param $callback
 *   The shutdown function to register.
 * @param ...
 *   Additional arguments to pass to the shutdown function.
 *
 * @return
 *   Array of shutdown functions to be executed.
 *
 * @see register_shutdown_function()
 * @ingroup php_wrappers
 */
function &drupal_register_shutdown_function($callback = NULL) {
  // We cannot use drupal_static() here because the static cache is reset during
  // batch processing, which breaks batch handling.
  static $callbacks = array();

  if (isset($callback)) {
    // Only register the internal shutdown function once.
    if (empty($callbacks)) {
      register_shutdown_function('_drupal_shutdown_function');
    }
    $args = func_get_args();
    array_shift($args);
    // Save callback and arguments
    $callbacks[] = array('callback' => $callback, 'arguments' => $args);
  }
  return $callbacks;
}

/**
 * Executes registered shutdown functions.
 */
function _drupal_shutdown_function() {
  $callbacks = &drupal_register_shutdown_function();

  // Set the CWD to DRUPAL_ROOT as it is not guaranteed to be the same as it
  // was in the normal context of execution.
  chdir(DRUPAL_ROOT);

  try {
    while (list($key, $callback) = each($callbacks)) {
      call_user_func_array($callback['callback'], $callback['arguments']);
    }
  }
  catch (Exception $exception) {
    // If we are displaying errors, then do so with no possibility of a further
    // uncaught exception being thrown.
    require_once __DIR__ . '/errors.inc';
    if (error_displayable()) {
      print '<h1>Uncaught exception thrown in shutdown function.</h1>';
      print '<p>' . _drupal_render_exception_safe($exception) . '</p><hr />';
    }
    error_log($exception);
  }
}

/**
 * Compares the memory required for an operation to the available memory.
 *
 * @param $required
 *   The memory required for the operation, expressed as a number of bytes with
 *   optional SI or IEC binary unit prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8bytes,
 *   9mbytes).
 * @param $memory_limit
 *   (optional) The memory limit for the operation, expressed as a number of
 *   bytes with optional SI or IEC binary unit prefix (e.g. 2, 3K, 5MB, 10G,
 *   6GiB, 8bytes, 9mbytes). If no value is passed, the current PHP
 *   memory_limit will be used. Defaults to NULL.
 *
 * @return
 *   TRUE if there is sufficient memory to allow the operation, or FALSE
 *   otherwise.
 */
function drupal_check_memory_limit($required, $memory_limit = NULL) {
  if (!isset($memory_limit)) {
    $memory_limit = ini_get('memory_limit');
  }

  // There is sufficient memory if:
  // - No memory limit is set.
  // - The memory limit is set to unlimited (-1).
  // - The memory limit is greater than or equal to the memory required for
  //   the operation.
  return ((!$memory_limit) || ($memory_limit == -1) || (parse_size($memory_limit) >= parse_size($required)));
}

/**
 * @defgroup lock Locking mechanisms
 * @{
 * Functions to coordinate long-running operations across requests.
 *
 * In most environments, multiple Drupal page requests (a.k.a. threads or
 * processes) will execute in parallel. This leads to potential conflicts or
 * race conditions when two requests execute the same code at the same time. A
 * common example of this is a rebuild like menu_router_rebuild() where we
 * invoke many hook implementations to get and process data from all active
 * modules, and then delete the current data in the database to insert the new
 * afterwards.
 *
 * This is a cooperative, advisory lock system. Any long-running operation
 * that could potentially be attempted in parallel by multiple requests should
 * try to acquire a lock before proceeding. By obtaining a lock, one request
 * notifies any other requests that a specific operation is in progress which
 * must not be executed in parallel.
 *
 * To use this API, pick a unique name for the lock. A sensible choice is the
 * name of the function performing the operation. A very simple example use of
 * this API:
 * @code
 * function mymodule_long_operation() {
 *   if (lock()->acquire('mymodule_long_operation')) {
 *     // Do the long operation here.
 *     // ...
 *     lock()->release('mymodule_long_operation');
 *   }
 * }
 * @endcode
 *
 * If a function acquires a lock it should always release it when the
 * operation is complete by calling lock()->release(), as in the example.
 *
 * A function that has acquired a lock may attempt to renew a lock (extend the
 * duration of the lock) by calling lock()->acquire() again during the operation.
 * Failure to renew a lock is indicative that another request has acquired
 * the lock, and that the current operation may need to be aborted.
 *
 * If a function fails to acquire a lock it may either immediately return, or
 * it may call lock()->wait() if the rest of the current page request requires
 * that the operation in question be complete. After lock()->wait() returns,
 * the function may again attempt to acquire the lock, or may simply allow the
 * page request to proceed on the assumption that a parallel request completed
 * the operation.
 *
 * lock()->acquire() and lock()->wait() will automatically break (delete) a lock
 * whose duration has exceeded the timeout specified when it was acquired.
 */

/**
 * Get locking layer instance.
 *
 * @deprecated Use Drupal::lock() instead, or even better have the lock service
 *   injected into your object.
 *
 * @return Drupal\Core\Lock\LockBackendInterface
 */
function lock() {
  return Drupal::lock();
}

/**
 * @} End of "defgroup lock".
 */
