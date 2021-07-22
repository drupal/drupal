<?php

/**
 * @file
 * Contains additions to drupal-9.0.0.filled.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/project/drupal/issues/2251789.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$configs = [
  'block.block.active_forum_topics',
  'block.block.new_forum_topics',
];

foreach ($configs as $config) {
  $yaml = Yaml::decode(file_get_contents(__DIR__ . '/' . $config . '.yml'));
  $connection->insert('config')
    ->fields(['collection', 'name', 'data'])
    ->values([
      'collection' => '',
      'name' => $config,
      'data' => serialize($yaml),
    ])
    ->execute();
}
