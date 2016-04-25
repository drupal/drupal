<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/507488.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Structure of a custom block with visibility settings.
$block_configs[] = \Drupal\Component\Serialization\Yaml::decode(file_get_contents(__DIR__ . '/block.block.testfor2569529.yml'));

foreach ($block_configs as $block_config) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'block.block.' . $block_config['id'],
      'data' => serialize($block_config),
    ])
    ->execute();
}

// Update the config entity query "index".
$existing_blocks = $connection->select('key_value')
    ->fields('key_value', ['value'])
    ->condition('collection', 'config.entity.key_store.block')
    ->condition('name', 'theme:seven')
    ->execute()
    ->fetchField();
$existing_blocks = unserialize($existing_blocks);

$connection->update('key_value')
  ->fields([
    'value' => serialize(array_merge($existing_blocks, ['block.block.seven_secondary_local_tasks']))
  ])
  ->condition('collection', 'config.entity.key_store.block')
  ->condition('name', 'theme:seven')
  ->execute();
