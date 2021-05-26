<?php

/**
 * @file
 * Add notification emails to the Update module.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$data = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('name', 'update.settings')
  ->execute()->fetchField();
$data = unserialize($data);
$data['notification']['emails'][] = 'graciepup@example.com';

$connection->update('config')
  ->fields([
    'data' => serialize($data),
  ])
  ->condition('name', 'update.settings')
  ->execute();
