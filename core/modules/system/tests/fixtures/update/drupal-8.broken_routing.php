<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;


$connection = Database::getConnection();

$config = unserialize($connection->query("SELECT data FROM {config} where name = :name", [':name' => 'core.extension'])->fetchField());
$config['module']['update_script_test'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($config)])
  ->condition('name', 'core.extension')
  ->execute();

$connection->insert('key_value')
  ->fields(['collection' => 'system.schema', 'name' => 'update_script_test', 'value' => serialize(8000)])
  ->execute();

