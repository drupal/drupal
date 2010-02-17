<?php
// $Id$

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
