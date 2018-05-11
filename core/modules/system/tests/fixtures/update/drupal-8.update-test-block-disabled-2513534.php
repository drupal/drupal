<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2513534.
 */

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// A disabled block.
$block_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/block.block.testfor2513534.yml'));

// A block placed in the default region.
$block_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/block.block.secondtestfor2513534.yml'));

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
  ->condition('name', 'theme:bartik')
  ->execute()
  ->fetchField();
$existing_blocks = unserialize($existing_blocks);

$connection->update('key_value')
  ->fields([
    'value' => serialize(array_merge($existing_blocks, ['block.block.testfor2513534', 'block.block.secondtestfor2513534'])),
  ])
  ->condition('collection', 'config.entity.key_store.block')
  ->condition('name', 'theme:bartik')
  ->execute();
