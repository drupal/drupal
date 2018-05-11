<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2354889.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// A custom block with visibility settings.
$block_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/block.block.testfor2354889.yml'));

// A custom block without any visibility settings.
$block_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/block.block.secondtestfor2354889.yml'));

// A custom block with visibility settings that contain a non-existing context
// mapping.
$block_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/block.block.thirdtestfor2354889.yml'));

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
    'value' => serialize(array_merge($existing_blocks, ['block.block.testfor2354889', 'block.block.secondtestfor2354889', 'block.block.thirdtestfor2354889'])),
  ])
  ->condition('collection', 'config.entity.key_store.block')
  ->condition('name', 'theme:bartik')
  ->execute();
