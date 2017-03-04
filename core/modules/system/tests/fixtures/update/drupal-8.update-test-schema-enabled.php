<?php

/**
 * @file
 * Partial database to mimic the installation of the update_test_schema module.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Create the table.
$connection->schema()->createTable('update_test_schema_table', [
  'fields' => [
    'a' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
    ],
    'b' => [
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ],
  ],
]);

// Set the schema version.
$connection->merge('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'update_test_schema')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'update_test_schema',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['update_test_schema'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
