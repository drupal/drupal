<?php
// $Id: install.php,v 1.238 2010/10/22 02:53:19 webchick Exp $

/**
 * @file
 * Initiates a browser-based installation of Drupal.
 */

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

/**
 * Global flag to indicate that site is in installation mode.
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
