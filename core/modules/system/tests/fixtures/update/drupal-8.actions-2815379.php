<?php

/**
 * @file
 * Contains database additions to for testing upgrade path for action plugins.
 *
 * @see https://www.drupal.org/node/2815379
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Structure of configured email, goto, and message actions.
$actions[] = Yaml::decode(file_get_contents(__DIR__ . '/system.action.goto_2815379.yml'));
$actions[] = Yaml::decode(file_get_contents(__DIR__ . '/system.action.message_2815379.yml'));
$actions[] = Yaml::decode(file_get_contents(__DIR__ . '/system.action.send_email_2815379.yml'));

foreach ($actions as $action) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'system.action.' . $action['id'],
      'data' => serialize($action),
    ])
    ->execute();
}

// Enable action module.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$connection->update('config')
  ->fields([
    'data' => serialize(array_merge_recursive($extensions, ['module' => ['action' => 0]])),
  ])
  ->condition('name', 'core.extension')
  ->execute();
