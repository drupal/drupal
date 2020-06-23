<?php

/**
 * @file
 * Database to mimic the installation of the update_test_schema module.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'update_test_schema')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'update_test_schema',
    'value' => 's:4:"8901";',
  ])
  ->execute();
