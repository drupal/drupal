<?php

/**
 * @file
 * Partial database to mimic the installation of the block_test module.
 */

use Drupal\Core\Database\Database;
use Symfony\Component\Yaml\Yaml;

$connection = Database::getConnection();

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'block_test',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['block_test'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Install the block configuration.
$config = file_get_contents(__DIR__ . '/../../../../block/tests/modules/block_test/config/install/block.block.test_block.yml');
$config = Yaml::parse($config);
$connection->insert('config')
  ->fields(['data', 'name', 'collection'])
  ->values([
    'name' => 'block.block.test_block',
    'data' => serialize($config),
    'collection' => '',
  ])
  ->execute();
