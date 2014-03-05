<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

// Change the directory to the Drupal root.
chdir('../../..');

// Load the Drupal bootstrap.
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(dirname(__DIR__)) . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_KERNEL);

if (\Drupal::config('statistics.settings')->get('count_content_views')) {
  $nid = filter_input(INPUT_POST, 'nid', FILTER_VALIDATE_INT);
  if ($nid) {
    \Drupal::database()->merge('node_counter')
      ->key('nid', $nid)
      ->fields(array(
        'daycount' => 1,
        'totalcount' => 1,
        'timestamp' => REQUEST_TIME,
      ))
      ->expression('daycount', 'daycount + 1')
      ->expression('totalcount', 'totalcount + 1')
      ->execute();
  }
}

