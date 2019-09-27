<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$config = unserialize($connection->query("SELECT data FROM {config} where name = :name", [':name' => 'system.theme'])->fetchField());
$config['admin'] = '0';
$connection->update('config')
  ->fields(['data' => serialize($config)])
  ->condition('name', 'system.theme')
  ->execute();
