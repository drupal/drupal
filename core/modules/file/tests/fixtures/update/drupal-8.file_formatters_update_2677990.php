<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2677990.
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;
use Drupal\field\Entity\FieldStorageConfig;

$connection = Database::getConnection();

// Configuration for a file field storage for generic display.
$field_file_generic_2677990 = Yaml::decode(file_get_contents(__DIR__ . '/field.storage.node.field_file_generic_2677990.yml'));

// Configuration for a file field storage for table display.
$field_file_table_2677990 = Yaml::decode(file_get_contents(__DIR__ . '/field.storage.node.field_file_table_2677990.yml'));

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.' . $field_file_generic_2677990['id'],
    'data' => serialize($field_file_generic_2677990),
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.' . $field_file_table_2677990['id'],
    'data' => serialize($field_file_table_2677990),
  ])
  ->execute();
// We need to Update the registry of "last installed" field definitions.
$installed = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->execute()
  ->fetchField();
$installed = unserialize($installed);
$installed['field_file_generic_2677990'] = new FieldStorageConfig($field_file_generic_2677990);
$installed['field_file_table_2677990'] = new FieldStorageConfig($field_file_table_2677990);
$connection->update('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->fields([
    'value' => serialize($installed),
  ])
  ->execute();

// Configuration for a file field storage for generic display.
$field_file_generic_2677990 = Yaml::decode(file_get_contents(__DIR__ . '/field.field.node.article.field_file_generic_2677990.yml'));

// Configuration for a file field storage for table display.
$field_file_table_2677990 = Yaml::decode(file_get_contents(__DIR__ . '/field.field.node.article.field_file_table_2677990.yml'));

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.' . $field_file_generic_2677990['id'],
    'data' => serialize($field_file_generic_2677990),
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.' . $field_file_table_2677990['id'],
    'data' => serialize($field_file_table_2677990),
  ])
  ->execute();

// Configuration of the view mode to set the proper formatters.
$view_mode_2677990 = Yaml::decode(file_get_contents(__DIR__ . '/core.entity_view_display.node.article.default_2677990.yml'));

$connection->update('config')
  ->fields([
    'data' => serialize($view_mode_2677990),
  ])
  ->condition('name', 'core.entity_view_display.' . $view_mode_2677990['id'])
  ->execute();
