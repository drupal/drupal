<?php

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

use Drupal\Component\Utility\OpCodeCache;

// Change the directory to the Drupal root.
chdir('..');
// Store the Drupal root path.
$root_path = realpath('');

/**
 * Global flag to indicate the site is in installation mode.
 *
 * The constant is defined using define() instead of const so that PHP
 * versions prior to 5.3 can display proper PHP requirements instead of causing
 * a fatal error.
 */
define('MAINTENANCE_MODE', 'install');

// Exit early if an incompatible PHP version is in use, so that the user sees a
// helpful error message rather than a white screen from any fatal errors due to
// the incompatible version. The minimum version is also hardcoded (instead of
// \Drupal::MINIMUM_PHP), to avoid any fatal errors that might result from
// loading the autoloader or core/lib/Drupal.php. Note: Remember to update the
// hardcoded minimum PHP version below (both in the version_compare() call and
// in the printed message to the user) whenever \Drupal::MINIMUM_PHP is
// updated.
if (version_compare(PHP_VERSION, '8.1.0') < 0) {
  print 'Your PHP installation is too old. Refer to the <a href="https://www.drupal.org/docs/system-requirements/php-requirements">Drupal PHP requirements</a> for the currently recommended PHP version for this release. See <a href="https://php.net/supported-versions.php">PHP\'s version support documentation</a> for more information on PHP\'s own support schedule.';
  exit;
}

// Initialize the autoloader.
$class_loader = require_once $root_path . '/autoload.php';

// If OPCache is in use, ensure opcache.save_comments is enabled.
if (OpCodeCache::isEnabled() && !ini_get('opcache.save_comments')) {
  print 'Systems with OPcache installed must have <a href="http://php.net/manual/opcache.configuration.php#ini.opcache.save-comments">opcache.save_comments</a> enabled.';
  exit();
}

// Set the Drupal custom error handler.
require_once $root_path . '/core/includes/errors.inc';
set_error_handler('_drupal_error_handler');
set_exception_handler('_drupal_exception_handler');

// Start the installer.
require_once $root_path . '/core/includes/install.core.inc';
install_drupal($class_loader);
