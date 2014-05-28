<?php
/**
 * @file
 * Functions that need to be loaded on every Drupal request.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Timer;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Title;
use Drupal\Core\Utility\Error;
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\RuntimeException as DependencyInjectionRuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Language\Language;
use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Minimum supported version of PHP.
 */
const DRUPAL_MINIMUM_PHP = '5.4.2';

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
 * Fourth bootstrap phase: load code for subsystems and modules.
 */
const DRUPAL_BOOTSTRAP_CODE = 3;

/**
 * Final bootstrap phase: initialize language, path, theme, and modules.
 */
const DRUPAL_BOOTSTRAP_FULL = 4;

/**
 * Role ID for anonymous users; should match what's in the "role" table.
 */
const DRUPAL_ANONYMOUS_RID = 'anonymous';

/**
 * Role ID for authenticated users; should match what's in the "role" table.
 */
const DRUPAL_AUTHENTICATED_RID = 'authenticated';

/**
 * The maximum number of characters in a module or theme name.
 */
const DRUPAL_EXTENSION_NAME_MAX_LENGTH = 50;

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
  static $conf_path;

  if (isset($conf_path) && !$reset) {
    return $conf_path;
  }

  // Check for a simpletest override.
  if ($test_prefix = drupal_valid_test_ua()) {
    $conf_path = 'sites/simpletest/' . substr($test_prefix, 10);
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
    return $config_directories[$type];
  }
  throw new \Exception(format_string('The configuration directory type %type does not exist.', array('%type' => $type)));
}

/**
 * Initializes the PHP environment.
 */
function drupal_environment_initialize() {
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
  // Export these settings.php variables to the global namespace.
  global $base_url, $cookie_domain, $config_directories, $config;
  $databases = array();
  $settings = array();
  $config = array();

  // Make conf_path() available as local variable in settings.php.
  $conf_path = conf_path();
  if (is_readable(DRUPAL_ROOT . '/' . $conf_path . '/settings.php')) {
    require DRUPAL_ROOT . '/' . $conf_path . '/settings.php';
  }
  // Initialize Database.
  Database::setMultipleConnectionInfo($databases);
  // Initialize Settings.
  new Settings($settings);
}

/**
 * Initializes global request variables.
 *
 * @todo D8: Eliminate this entirely in favor of Request object.
 */
function _drupal_request_initialize() {
  // Provided by settings.php.
  // @see drupal_settings_initialize()
  global $base_url, $cookie_domain;
  // Set and derived from $base_url by this function.
  global $base_path, $base_root, $script_path;
  global $base_secure_url, $base_insecure_url;

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
 * configuration. For example, a module 'foo' may legally be located
 * in any of these three places:
 *
 * core/modules/foo/foo.info.yml
 * modules/foo/foo.info.yml
 * sites/example.com/modules/foo/foo.info.yml
 *
 * Calling drupal_get_filename('module', 'foo') will give you one of
 * the above, depending on where the module is located.
 *
 * @param $type
 *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
 *   'theme_engine'.
 * @param $name
 *   The name of the item for which the filename is requested. Ignored for
 *   $type 'core'.
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
  static $files = array();

  // Type 'core' only exists to simplify application-level logic; it always maps
  // to the /core directory, whereas $name is ignored. It is only requested via
  // drupal_get_path(). /core/core.info.yml does not exist, but is required
  // since drupal_get_path() returns the dirname() of the returned pathname.
  if ($type === 'core') {
    return 'core/core.info.yml';
  }

  // Profiles are converted into modules in system_rebuild_module_data().
  // @todo Remove false-exposure of profiles as modules.
  $original_type = $type;
  if ($type == 'profile') {
    $type = 'module';
  }
  if (!isset($files[$type])) {
    $files[$type] = array();
  }

  if (isset($filename)) {
    $files[$type][$name] = $filename;
  }
  elseif (!isset($files[$type][$name])) {
    // If the pathname of the requested extension is not known, try to retrieve
    // the list of extension pathnames from various providers, checking faster
    // providers first.
    // Retrieve the current module list (derived from the service container).
    if ($type == 'module' && \Drupal::hasService('module_handler')) {
      foreach (\Drupal::moduleHandler()->getModuleList() as $module_name => $module) {
        $files[$type][$module_name] = $module->getPathname();
      }
    }
    // If still unknown, retrieve the file list prepared in state by
    // system_rebuild_module_data() and system_rebuild_theme_data().
    if (!isset($files[$type][$name]) && \Drupal::hasService('state')) {
      $files[$type] += \Drupal::state()->get('system.' . $type . '.files', array());
    }
    // If still unknown, perform a filesystem scan.
    if (!isset($files[$type][$name])) {
      $listing = new ExtensionDiscovery();
      // Prevent an infinite recursion by this legacy function.
      if ($original_type == 'profile') {
        $listing->setProfileDirectories(array());
      }
      foreach ($listing->scan($original_type) as $extension_name => $file) {
        $files[$type][$extension_name] = $file->getPathname();
      }
    }
  }

  if (isset($files[$type][$name])) {
    return $files[$type][$name];
  }
}

/**
 * Gets the page cache cid for this request.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The request for this page.
 *
 * @return string
 *   The cid for this request.
 */
function drupal_page_cache_get_cid(Request $request) {
  $cid_parts = array(
    $request->getUri(),
    \Drupal::service('content_negotiation')->getContentType($request),
  );
  return sha1(implode(':', $cid_parts));
}

/**
 * Retrieves the current page from the cache.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The request for this page.
 *
 * @return \Symfony\Component\HttpFoundation\Response
 *   The response, if the page was found in the cache, NULL otherwise.
 */
function drupal_page_get_cache(Request $request) {
  $cache = \Drupal::cache('render')->get(drupal_page_cache_get_cid($request));
  if ($cache) {
    return $cache->data;
  }
}

/**
 * Determines the cacheability of the current page.
 *
 * Note: we do not serve cached pages to authenticated users, or to anonymous
 * users when $_SESSION is non-empty. $_SESSION may contain status messages
 * from a form submission, the contents of a shopping cart, or other user-
 * specific content that should not be cached and displayed to other users.
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Symfony\Component\HttpFoundation\Response->headers->set().
 *   See https://drupal.org/node/2181523.
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Symfony\Component\HttpFoundation\Response->headers->get().
 *   See https://drupal.org/node/2181523.
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
 * follow "common form" (see RFC 2616, section 4.2).
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   See https://drupal.org/node/2181523.
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   See https://drupal.org/node/2181523.
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
 * Also give each page a unique ETag. This should force clients to include both
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   See https://drupal.org/node/2181523.
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
function drupal_serve_page_from_cache(Response $response, Request $request) {
  // Only allow caching in the browser and prevent that the response is stored
  // by an external proxy server when the following conditions apply:
  // 1. There is a session cookie on the request.
  // 2. The Vary: Cookie header is on the response.
  // 3. The Cache-Control header does not contain the no-cache directive.
  if ($request->cookies->has(session_name()) &&
    in_array('Cookie', $response->getVary()) &&
    !$response->headers->hasCacheControlDirective('no-cache')) {

    $response->setPrivate();
  }

  // Negotiate whether to use compression.
  if ($response->headers->get('Content-Encoding') == 'gzip' && extension_loaded('zlib')) {
    if (strpos($request->headers->get('Accept-Encoding'), 'gzip') !== FALSE) {
      // The response content is already gzip'ed, so make sure
      // zlib.output_compression does not compress it once more.
      ini_set('zlib.output_compression', '0');
    }
    else {
      // The client does not support compression. Decompress the content and
      // remove the Content-Encoding header.
      $content = $response->getContent();
      $content = gzinflate(substr(substr($content, 10), 0, -8));
      $response->setContent($content);
      $response->headers->remove('Content-Encoding');
    }
  }

  // Perform HTTP revalidation.
  // @todo Use Response::isNotModified() as per https://drupal.org/node/2259489
  $last_modified = $response->getLastModified();
  if ($last_modified) {
    // See if the client has provided the required HTTP headers.
    $if_modified_since = $request->server->has('HTTP_IF_MODIFIED_SINCE') ? strtotime($request->server->get('HTTP_IF_MODIFIED_SINCE')) : FALSE;
    $if_none_match = $request->server->has('HTTP_IF_NONE_MATCH') ? stripslashes($request->server->get('HTTP_IF_NONE_MATCH')) : FALSE;

    if ($if_modified_since && $if_none_match
      && $if_none_match == $response->getEtag() // etag must match
      && $if_modified_since == $last_modified->getTimestamp()) {  // if-modified-since must match
      $response->setStatusCode(304);
      $response->setContent(NULL);

      // In the case of a 304 response, certain headers must be sent, and the
      // remaining may not (see RFC 2616, section 10.3.5).
      foreach (array_keys($response->headers->all()) as $name) {
        if (!in_array($name, array('content-location', 'expires', 'cache-control', 'vary'))) {
          $response->headers->remove($name);
        }
      }
    }
  }
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
 * @section sec_translating_vars Translating Variables
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
 * @see format_string()
 * @ingroup sanitization
 */
function t($string, array $args = array(), array $options = array()) {
  return \Drupal::translation()->translate($string, $args, $options);
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
 * @see drupal_validate_utf8()
 * @ingroup sanitization
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\String::checkPlain().
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
 *
 * @see \Drupal\Component\Utility\Unicode::validateUtf8()
 */
function drupal_validate_utf8($text) {
  return Unicode::validateUtf8($text);
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
 * @see \Drupal\Core\Utility\Error::decodeException()
 */
function watchdog_exception($type, Exception $exception, $message = NULL, $variables = array(), $severity = WATCHDOG_ERROR, $link = NULL) {

   // Use a default value if $message is not set.
   if (empty($message)) {
     // The exception message is run through
     // \Drupal\Component\Utility\String::checkPlain() by
     // \Drupal\Core\Utility\Error:decodeException().
     $message = '%type: !message in %function (line %line of %file).';
   }
   // $variables must be an array so that we can add the exception information.
   if (!is_array($variables)) {
     $variables = array();
   }

   $variables += Error::decodeException($exception);
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
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::logger($channel)->log($severity, $message, $context), or any
 *   of the shortcut methods of \Psr\Log\LoggerTrait.
 *
 * @see watchdog_severity_levels()
 * @see hook_watchdog()
 */
function watchdog($type, $message, array $variables = array(), $severity = WATCHDOG_NOTICE, $link = NULL) {
  if ($link) {
    $variables['link'] = $link;
  }
  \Drupal::service('logger.factory')->get($type)->log($severity, $message, $variables);
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
 * Generates a default anonymous $user object.
 *
 * @return \Drupal\Core\Session\AccountInterface
 *   The user session object.
 *
 * @deprecated in Drupal 8.x-dev. Will be removed before Drupal 8.0 by
 *   https://drupal.org/node/2185315. Use
 *   \Drupal\Core\Session\AnonymousUserSession.
 */
function drupal_anonymous_user() {
  return new AnonymousUserSession();
}

/**
 * Ensures Drupal is bootstrapped to the specified phase.
 *
 * In order to bootstrap Drupal from another PHP script, you can use this code:
 * @code
 *   require_once '/path/to/drupal/core/vendor/autoload.php';
 *   require_once '/path/to/drupal/core/includes/bootstrap.inc';
 *   drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
 * @endcode
 *
 * @param $phase
 *   A constant telling which phase to bootstrap to. When you bootstrap to a
 *   particular phase, all earlier phases are run automatically. Possible
 *   values:
 *   - DRUPAL_BOOTSTRAP_CONFIGURATION: Initializes configuration.
 *   - DRUPAL_BOOTSTRAP_KERNEL: Initalizes a kernel.
 *   - DRUPAL_BOOTSTRAP_PAGE_CACHE: Tries to serve a cached page.
 *   - DRUPAL_BOOTSTRAP_CODE: Loads code for subsystems and modules.
 *   - DRUPAL_BOOTSTRAP_FULL: Fully loads Drupal. Validates and fixes input
 *     data.
 *
 * @return
 *   The most recently completed phase.
 */
function drupal_bootstrap($phase = NULL) {
  // Not drupal_static(), because does not depend on any run-time information.
  static $phases = array(
    DRUPAL_BOOTSTRAP_CONFIGURATION,
    DRUPAL_BOOTSTRAP_KERNEL,
    DRUPAL_BOOTSTRAP_PAGE_CACHE,
    DRUPAL_BOOTSTRAP_CODE,
    DRUPAL_BOOTSTRAP_FULL,
  );
  // Not drupal_static(), because the only legitimate API to control this is to
  // call drupal_bootstrap() with a new phase parameter.
  static $final_phase = -1;
  // Not drupal_static(), because it's impossible to roll back to an earlier
  // bootstrap state.
  static $stored_phase = -1;

  // Store the phase name so it's not forgotten during recursion. Additionally,
  // ensure that $final_phase is never rolled back to an earlier bootstrap
  // state.
  if ($phase > $final_phase) {
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

  $kernel = new DrupalKernel('prod', drupal_classloader(), !$test_only);

  // @todo Remove this once everything in the bootstrap has been
  //   converted to services in the DIC.
  $kernel->boot();

  // Create a request object from the HttpFoundation.
  $request = Request::createFromGlobals();
  $container = \Drupal::getContainer();
  $container->set('request', $request);
  $container->get('request_stack')->push($request);

  drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);

  $response = $kernel->handle($request)->prepare($request)->send();

  $kernel->terminate($request, $response);
}

/**
 * Returns the time zone of the current user.
 */
function drupal_get_user_timezone() {
  global $user;
  $config = \Drupal::config('system.date');

  if ($user && $config->get('timezone.user.configurable') && $user->isAuthenticated() && $user->getTimezone()) {
    return $user->getTimezone();
  }
  else {
    // Ignore PHP strict notice if time zone has not yet been set in the php.ini
    // configuration.
    $config_data_default_timezone = $config->get('timezone.default');
    return !empty($config_data_default_timezone) ? $config_data_default_timezone : @date_default_timezone_get();
  }
}

/**
 * Gets a salt useful for hardening against SQL injection.
 *
 * @return
 *   A salt based on information in settings.php, not in the database.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal\Core\Site\Settings::getHashSalt() instead.
 */
function drupal_get_hash_salt() {
  return Settings::getHashSalt();
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
    _drupal_log_error(Error::decodeException($exception), TRUE);
  }
  catch (Exception $exception2) {
    // Another uncaught exception was thrown while handling the first one.
    // If we are displaying errors, then do so with no possibility of a further uncaught exception being thrown.
    if (error_displayable()) {
      print '<h1>Additional uncaught exception thrown while handling exception.</h1>';
      print '<h2>Original</h2><p>' . Error::renderExceptionSafe($exception) . '</p>';
      print '<h2>Additional</h2><p>' . Error::renderExceptionSafe($exception2) . '</p><hr />';
    }
  }
}

/**
 * Sets up the script environment and loads settings.php.
 */
function _drupal_bootstrap_configuration() {
  drupal_environment_initialize();

  // Indicate that code is operating in a test child site.
  if ($test_prefix = drupal_valid_test_ua()) {
    // Only code that interfaces directly with tests should rely on this
    // constant; e.g., the error/exception handler conditionally adds further
    // error information into HTTP response headers that are consumed by
    // Simpletest's internal browser.
    define('DRUPAL_TEST_IN_CHILD_SITE', TRUE);

    // Log fatal errors to the test site directory.
    ini_set('log_errors', 1);
    ini_set('error_log', DRUPAL_ROOT . '/sites/simpletest/' . substr($test_prefix, 10) . '/error.log');
  }
  else {
    // Ensure that no other code defines this.
    define('DRUPAL_TEST_IN_CHILD_SITE', FALSE);
  }

  // Initialize the configuration, including variables from settings.php.
  drupal_settings_initialize();
  _drupal_request_initialize();

  // Activate the class loader.
  drupal_classloader();

  // Start a page timer:
  Timer::start('page');

  // Detect string handling method.
  Unicode::check();

  // Set the Drupal custom error handler. (requires \Drupal::config())
  set_error_handler('_drupal_error_handler');
  set_exception_handler('_drupal_exception_handler');

  // Redirect the user to the installation script if Drupal has not been
  // installed yet (i.e., if no $databases array has been defined in the
  // settings.php file) and we are not already installing.
  if (!Database::getConnectionInfo() && !drupal_installation_attempted() && !drupal_is_cli()) {
    include_once __DIR__ . '/install.inc';
    install_goto('core/install.php');
  }
}

/**
 * Initialize the kernel / service container.
 */
function _drupal_bootstrap_kernel() {
  // Normally, index.php puts a container in the Drupal class by creating a
  // kernel. If there is no container yet, create one.
  if (!\Drupal::getContainer()) {
    $kernel = new DrupalKernel('prod', drupal_classloader());
    $kernel->boot();
    $request = Request::createFromGlobals();
    $container = \Drupal::getContainer();
    $container->set('request', $request);
    $container->get('request_stack')->push($request);
  }
}

/**
 * Attempts to serve a page from the cache.
 */
function _drupal_bootstrap_page_cache() {
  require_once __DIR__ . '/database.inc';
  // Check for a cache mode force from settings.php.
  if (Settings::get('page_cache_without_database')) {
    $cache_enabled = TRUE;
  }
  else {
    $config = \Drupal::config('system.performance');
    $cache_enabled = $config->get('cache.page.use_internal');
  }

  $request = \Drupal::request();
  // If there is no session cookie and cache is enabled (or forced), try
  // to serve a cached page.
  if (!$request->cookies->has(session_name()) && $cache_enabled && drupal_page_is_cacheable()) {
    // Get the page from the cache.
    $response = drupal_page_get_cache($request);
    // If there is a cached page, display it.
    if ($response) {
      $response->headers->set('X-Drupal-Cache', 'HIT');

      drupal_serve_page_from_cache($response, $request);

      // We are done.
      $response->prepare($request);
      $response->send();
      exit;
    }
  }
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
 * Returns the list of enabled modules.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::moduleHandler()->getModuleList().
 *
 * @see \Drupal\Core\Extension\ModuleHandler::getModuleList()
 */
function module_list() {
  $modules = array_keys(\Drupal::moduleHandler()->getModuleList());
  return array_combine($modules, $modules);
}

/**
 * Determines which modules are implementing a hook.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::moduleHandler()->getImplementations($hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::getImplementations()
 */
function module_implements($hook) {
  return \Drupal::moduleHandler()->getImplementations($hook);
}

/**
 * Invokes a hook in a particular module.
 *
 * All arguments are passed by value. Use \Drupal::moduleHandler()->alter() if
 * you need to pass arguments by reference.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0. Use
 *   \Drupal::moduleHandler()->invoke($module, $hook, $args = array()).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::alter()
 * @see \Drupal\Core\Extension\ModuleHandler::invoke()
 */
function module_invoke($module, $hook) {
  $args = func_get_args();
  // Remove $module and $hook from the arguments.
  unset($args[0], $args[1]);
  return \Drupal::moduleHandler()->invoke($module, $hook, $args);
}

/**
 * Invokes a hook in all enabled modules that implement it.
 *
 * All arguments are passed by value. Use \Drupal::moduleHandler()->alter() if
 * you need to pass arguments by reference.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::moduleHandler()->invokeAll($hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::alter()
 * @see \Drupal\Core\Extension\ModuleHandler::invokeAll()
 */
function module_invoke_all($hook) {
  $args = func_get_args();
  // Remove $hook from the arguments.
  unset($args[0]);
  return \Drupal::moduleHandler()->invokeAll($hook, $args);
}

/**
 * Passes alterable variables to specific hook_TYPE_alter() implementations.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::moduleHandler()->alter($hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::alter()
 */
function drupal_alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
  return \Drupal::moduleHandler()->alter($type, $data, $context1, $context2);
}

/**
 * Determines whether a given module exists.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::moduleHandler()->moduleExists($module).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::moduleExists()
 */
function module_exists($module) {
  return \Drupal::moduleHandler()->moduleExists($module);
}

/**
 * Determines whether a module implements a hook.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::moduleHandler()->implementsHook($module, $hook).
 *
 * @see \Drupal\Core\Extension\ModuleHandler::implementsHook()
 */
function module_hook($module, $hook) {
  return \Drupal::moduleHandler()->implementsHook($module, $hook);
}

/**
 * Returns the test prefix if this is an internal request from SimpleTest.
 *
 * @param string $new_prefix
 *   Internal use only. A new prefix to be stored.
 *
 * @return string|FALSE
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
  // Unless the below User-Agent and HMAC validation succeeds, we are not in
  // a test environment.
  $test_prefix = FALSE;

  // Perform a basic check on the User-Agent HTTP request header first. Any
  // inbound request that uses the simpletest UA header needs to be validated.
  if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^(simpletest\d+);(.+);(.+);(.+)$/", $_SERVER['HTTP_USER_AGENT'], $matches)) {
    list(, $prefix, $time, $salt, $hmac) = $matches;
    $check_string =  $prefix . ';' . $time . ';' . $salt;
    // Read the hash salt prepared by drupal_generate_test_ua().
    // This function is called before settings.php is read and Drupal's error
    // handlers are set up. While Drupal's error handling may be properly
    // configured on production sites, the server's PHP error_reporting may not.
    // Ensure that no information leaks on production sites.
    $key_file = DRUPAL_ROOT . '/sites/simpletest/' . substr($prefix, 10) . '/.htkey';
    if (!is_readable($key_file)) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      exit;
    }
    $private_key = file_get_contents($key_file);
    // The file properties add more entropy not easily accessible to others.
    $key = $private_key . filectime(__FILE__) . fileinode(__FILE__);
    $time_diff = REQUEST_TIME - $time;
    $test_hmac = Crypt::hmacBase64($check_string, $key);
    // Since we are making a local request a 5 second time window is allowed,
    // and the HMAC must match.
    if ($time_diff >= 0 && $time_diff <= 5 && $hmac === $test_hmac) {
      $test_prefix = $prefix;
    }
  }
  return $test_prefix;
}

/**
 * Generates a user agent string with a HMAC and timestamp for simpletest.
 */
function drupal_generate_test_ua($prefix) {
  static $key, $last_prefix;

  if (!isset($key) || $last_prefix != $prefix) {
    $last_prefix = $prefix;
    $key_file = DRUPAL_ROOT . '/sites/simpletest/' . substr($prefix, 10) . '/.htkey';
    // When issuing an outbound HTTP client request from within an inbound test
    // request, then the outbound request has to use the same User-Agent header
    // as the inbound request. A newly generated private key for the same test
    // prefix would invalidate all subsequent inbound requests.
    // @see \Drupal\Core\Http\Plugin\SimpletestHttpRequestSubscriber
    if (DRUPAL_TEST_IN_CHILD_SITE && $parent_prefix = drupal_valid_test_ua()) {
      if ($parent_prefix != $prefix) {
        throw new \RuntimeException("Malformed User-Agent: Expected '$parent_prefix' but got '$prefix'.");
      }
      // If the file is not readable, a PHP warning is expected in this case.
      $private_key = file_get_contents($key_file);
    }
    else {
      // Generate and save a new hash salt for a test run.
      // Consumed by drupal_valid_test_ua() before settings.php is loaded.
      $private_key = Crypt::randomBytesBase64(55);
      file_put_contents($key_file, $private_key);
    }
    // The file properties add more entropy not easily accessible to others.
    $key = $private_key . filectime(__FILE__) . fileinode(__FILE__);
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
 * Returns the language object for a given language type.
 *
 * @see \Drupal\Core\Language\LanguageManager
 *
 * @param string $type
 *   The type of language object needed, e.g. Language::TYPE_INTERFACE.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::languageManager()->getCurrentLanguage().
 */
function language($type) {
  return \Drupal::languageManager()->getCurrentLanguage($type);
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::languageManager()->getLanguages().
 */
function language_list($flags = Language::STATE_CONFIGURABLE) {
  return \Drupal::languageManager()->getLanguages($flags);
}

/**
 * Loads a language object from the database.
 *
 * @param string $langcode
 *   The language code.
 *
 * @return \Drupal\core\Language\Language|null
 *   A fully-populated language object or NULL.
 *
 * @see \Drupal\Core\Language\LanguageManager::getLanguage()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::languageManager()->getLanguage().
 */
function language_load($langcode) {
  return \Drupal::languageManager()->getLanguage($langcode);
}

/**
 * Returns the default language used on the site.
 *
 * @return \Drupal\Core\Language\Language
 *   A language object.
 *
 * @see \Drupal\Core\Language\LanguageManager::getLanguage()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::languageManager()->getDefaultLanguage().
 */
function language_default() {
  return \Drupal::languageManager()->getDefaultLanguage();
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
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
 * @return \Composer\Autoload\ClassLoader
 *   A ClassLoader class instance (or extension thereof).
 */
function drupal_classloader($class_loader = NULL) {
  // By default, use the ClassLoader which is best for development, as it does
  // not break when code is moved on the file system. However, as it is slow,
  // allow to use the APC class loader in production.
  static $loader;

  if (!isset($loader)) {

    // Retrieve the Composer ClassLoader for loading classes.
    $loader = include __DIR__ . '/../vendor/autoload.php';

    // Register the class loader.
    // When configured to use APC, the ApcClassLoader is registered instead.
    // Note that ApcClassLoader decorates ClassLoader and only provides the
    // findFile() method, but none of the others. The actual registry is still
    // in ClassLoader.
    if (!isset($class_loader)) {
      $class_loader = Settings::get('class_loader', 'default');
    }
    if ($class_loader === 'apc') {
      require_once __DIR__ . '/../vendor/symfony/class-loader/Symfony/Component/ClassLoader/ApcClassLoader.php';
      $apc_loader = new ApcClassLoader('drupal.' . drupal_get_hash_salt(), $loader);
      $loader->unregister();
      $apc_loader->register();
    }
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
  $loader->addPsr4('Drupal\\' . $name . '\\', array(
    DRUPAL_ROOT . '/' . $path . '/lib/Drupal/' . $name,
    DRUPAL_ROOT . '/' . $path . '/src',
  ));
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
 * function mymodule_log_stream_handle($new_handle = NULL) {
 *   static $handle;
 *   if (isset($new_handle)) {
 *     $handle = $new_handle;
 *   }
 *   return $handle;
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
  return PHP_SAPI === 'cli';
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
    // Remove $callback from the arguments.
    unset($args[0]);
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
    // If using PHP-FPM then fastcgi_finish_request() will have been fired
    // preventing further output to the browser.
    if (!function_exists('fastcgi_finish_request')) {
      // If we are displaying errors, then do so with no possibility of a
      // further uncaught exception being thrown.
      require_once __DIR__ . '/errors.inc';
      if (error_displayable()) {
        print '<h1>Uncaught exception thrown in shutdown function.</h1>';
        print '<p>' . Error::renderExceptionSafe($exception) . '</p><hr />';
      }
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\Environment::checkMemoryLimit().
 */
function drupal_check_memory_limit($required, $memory_limit = NULL) {
  return Environment::checkMemoryLimit($required, $memory_limit);
}

/**
 * Get locking layer instance.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::lock().
 *
 * @return \Drupal\Core\Lock\LockBackendInterface
 */
function lock() {
  return \Drupal::lock();
}
