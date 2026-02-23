<?php

/**
 * @file
 * Removes the ban module from a fixture database.
 *
 * @todo remove this when https://www.drupal.org/i/3569127 lands.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Remove ban from core.extension config.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();

if ($extensions) {
  $data = unserialize($extensions);
  if (isset($data['module']['ban'])) {
    unset($data['module']['ban']);
    $connection->update('config')
      ->fields(['data' => serialize($data)])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();
  }
}

// Remove ban schema version.
$connection->delete('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'ban')
  ->execute();

// Drop ban_ip table if exists.
if ($connection->schema()->tableExists('ban_ip')) {
  $connection->schema()->dropTable('ban_ip');
}
