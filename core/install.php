<?php

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

// Change the directory to the Drupal root.
chdir('..');

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

/**
 * Global flag to indicate that site is in installation mode.
 */
define('MAINTENANCE_MODE', 'install');

// Exit early if running an incompatible PHP version to avoid fatal errors.
// The minimum version is specified explicitly, as DRUPAL_MINIMUM_PHP is not
// yet available. It is defined in bootstrap.inc, but it is not possible to
// load that file yet as it would cause a fatal error on older versions of PHP.
if (version_compare(PHP_VERSION, '5.3.2') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.3.2. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

// Start the installer.
require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
install_drupal();
