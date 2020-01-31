<?php

/**
 * @file
 * Contains database additions to for testing upgrade path for action settings.
 *
 * @see https://www.drupal.org/node/3022401
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$action_settings = Yaml::decode(file_get_contents(__DIR__ . '/action.settings_3022401.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'action.settings',
    'data' => serialize($action_settings),
  ])
  ->execute();

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
