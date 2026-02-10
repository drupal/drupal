<?php

/**
 * @file
 * Removes the history module from a fixture database.
 *
 * @todo remove this when https://www.drupal.org/i/3569127 lands.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Remove history from core.extension config.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();

if ($extensions) {
  $data = unserialize($extensions);
  if (isset($data['module']['history'])) {
    unset($data['module']['history']);
    $connection->update('config')
      ->fields(['data' => serialize($data)])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();
  }
}

// Remove history schema version.
$connection->delete('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'history')
  ->execute();

// Drop history table if exists.
if ($connection->schema()->tableExists('history')) {
  $connection->schema()->dropTable('history');
}
