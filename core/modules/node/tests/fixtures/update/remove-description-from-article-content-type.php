<?php

/**
 * @file
 * Empties the description of the `article` content type.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->condition('name', 'node.type.article')
  ->fields('config', ['data'])
  ->execute()
  ->fetchField();
$data = unserialize($data);
$data['description'] = "\n";
$connection->update('config')
  ->condition('name', 'node.type.article')
  ->fields([
    'data' => serialize($data),
  ])
  ->execute();
