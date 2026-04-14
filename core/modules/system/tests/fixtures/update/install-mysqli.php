<?php

/**
 * @file
 * Installs the mysqli module on top of a fixture database.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Install mysqli.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();

if ($extensions) {
  $data = unserialize($extensions);
  $data['module']['mysqli'] = 0;
  $connection->update('config')
    ->fields(['data' => serialize($data)])
    ->condition('collection', '')
    ->condition('name', 'core.extension')
    ->execute();
}
