<?php
// $Id$

/**
 * @file
 * Handles incoming requests to fire off regularly-scheduled tasks (cron jobs).
 */

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', dirname(realpath(__FILE__)));

include_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
if (isset($_GET['cron_key']) && variable_get('cron_key', 'drupal') == $_GET['cron_key']) {
  drupal_cron_run();
}
else {
  watchdog('cron', 'Cron did not run because an invalid key used.', array(), WATCHDOG_NOTICE);
  drupal_access_denied();
}
