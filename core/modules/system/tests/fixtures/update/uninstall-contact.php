<?php

/**
 * @file
 * Removes the contact module from a fixture database.
 *
 * @todo remove this when https://www.drupal.org/i/3569127 lands.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Remove contact from core.extension config.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();

if ($extensions) {
  $data = unserialize($extensions);
  if (isset($data['module']['contact'])) {
    unset($data['module']['contact']);
    $connection->update('config')
      ->fields(['data' => serialize($data)])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();
  }
}

// Remove contact schema version.
$connection->delete('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'contact')
  ->execute();

// Remove contact config entries.
$connection->delete('config')
  ->condition('name', 'contact.%', 'LIKE')
  ->execute();
