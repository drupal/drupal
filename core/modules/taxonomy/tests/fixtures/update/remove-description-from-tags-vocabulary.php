<?php

/**
 * @file
 * Empties the description of the `tags` vocabulary.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->condition('name', 'taxonomy.vocabulary.tags')
  ->fields('config', ['data'])
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['description'] = "\n";
$connection->update('config')
  ->condition('name', 'taxonomy.vocabulary.tags')
  ->fields([
    'data' => serialize($data),
  ])
  ->execute();
