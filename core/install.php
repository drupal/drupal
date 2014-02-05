<?php

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

// Change the directory to the Drupal root.
chdir('..');

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Global flag to indicate the site is in installation mode.
 *
 * The constant is defined using define() instead of const so that PHP
 * versions prior to 5.3 can display proper PHP requirements instead of causing
 * a fatal error.
 */
define('MAINTENANCE_MODE', 'install');

// Exit early if running an incompatible PHP version to avoid fatal errors.
// The minimum version is specified explicitly, as DRUPAL_MINIMUM_PHP is not
// yet available. It is defined in bootstrap.inc, but it is not possible to
// load that file yet as it would cause a fatal error on older versions of PHP.
if (version_compare(PHP_VERSION, '5.3.10') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.3.10. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

// Exit early if the PHP option safe_mode is enabled to avoid fatal errors.
// @todo Remove this check once we require PHP > 5.4 as safe mode is deprecated
// in PHP 5.3 and completely removed in PHP 5.4.
if (ini_get('safe_mode')) {
  print 'Your PHP installation has safe_mode enabled. Drupal requires the safe_mode option to be turned off. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

// Start the installer.
require_once __DIR__ . '/includes/install.core.inc';
install_drupal();
