<?php

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

/**
 * Defines the root directory of the Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

/**
 * Global flag to indicate the site is in installation mode.
 */
define('MAINTENANCE_MODE', 'install');

// Exit early if running an incompatible PHP version to avoid fatal errors.
if (version_compare(PHP_VERSION, '5.2.4') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.2.4. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

// Start the installer.
require_once DRUPAL_ROOT . '/includes/install.core.inc';
install_drupal();
