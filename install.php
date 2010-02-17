<?php
// $Id: install.php,v 1.237 2010/02/17 04:19:51 webchick Exp $

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

// Start the installer.
require_once DRUPAL_ROOT . '/includes/install.core.inc';
install_drupal();
