<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2575421.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Enable test_stable theme.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$connection->update('config')
  ->fields([
    'data' => serialize(array_merge_recursive($extensions, ['theme' => ['test_stable' => 0]])),
  ])
  ->condition('name', 'core.extension')
  ->execute();
