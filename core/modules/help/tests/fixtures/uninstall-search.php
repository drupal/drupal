<?php

/**
 * @file
 * Uninstalls the search module from a fixture database.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Uninstall search.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();

if ($extensions) {
  $data = unserialize($extensions);
  unset($data['module']['search']);
  $connection->update('config')
    ->fields(['data' => serialize($data)])
    ->condition('collection', '')
    ->condition('name', 'core.extension')
    ->execute();
}

$connection->schema()->dropTable('search_dataset');
$connection->schema()->dropTable('search_index');
$connection->schema()->dropTable('search_total');
$connection->delete('config')->condition('name', 'search.%', 'LIKE')->execute();
$config_to_delete = [
  'block.block.claro_help_search',
  'block.block.olivero_search_form_narrow',
  'block.block.olivero_search_form_wide',
];
$connection->delete('config')->condition('name', $config_to_delete, 'IN')->execute();
