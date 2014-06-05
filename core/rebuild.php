<?php

/**
 * @file
 * Rebuilds all Drupal caches even when Drupal itself does not work.
 *
 * Needs a token query argument which can be calculated using the
 * scripts/rebuild_token_calculator.sh script.
 *
 * @see drupal_rebuild()
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

// Change the directory to the Drupal root.
chdir('..');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/bootstrap.inc';
require_once __DIR__ . '/includes/utility.inc';

drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

if (Settings::get('rebuild_access', FALSE) ||
  (isset($_GET['token'], $_GET['timestamp']) &&
    ((REQUEST_TIME - $_GET['timestamp']) < 300) &&
    ($_GET['token'] === Crypt::hmacBase64($_GET['timestamp'], Settings::get('hash_salt')))
  )) {

  drupal_rebuild();
  drupal_set_message('Cache rebuild complete.');
}

header('Location: ' . $GLOBALS['base_url']);
