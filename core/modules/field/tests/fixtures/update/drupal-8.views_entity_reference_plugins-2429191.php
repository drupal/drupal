<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of https://www.drupal.org/node/2429191.
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;

$connection = Database::getConnection();

// Configuration for a View with an "entity_reference selection" display.
$config = Yaml::decode(file_get_contents(__DIR__ . '/views.view.entity_reference_plugins_2429191.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'views.view.' . $config['id'],
    'data' => serialize($config),
  ])
  ->execute();

// Configuration for an entity_reference field storage using the View for
// selection.
$field_ref_views_select_2429191 = Yaml::decode(file_get_contents(__DIR__ . '/field.storage.node.field_ref_views_select_2429191.yml'));

// Configuration for an entity_reference field storage using the auto-create
// feature.
$field_ref_autocreate_2412569 = Yaml::decode(file_get_contents(__DIR__ . '/field.storage.node.field_ref_autocreate_2412569.yml'));

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.' . $field_ref_views_select_2429191['id'],
    'data' => serialize($field_ref_views_select_2429191),
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.' . $field_ref_autocreate_2412569['id'],
    'data' => serialize($field_ref_autocreate_2412569),
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
$installed['field_ref_views_select_2429191'] = new \Drupal\field\Entity\FieldStorageConfig($field_ref_views_select_2429191);
$installed['field_ref_autocreate_2412569'] = new \Drupal\field\Entity\FieldStorageConfig($field_ref_autocreate_2412569);
$connection->update('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'node.field_storage_definitions')
  ->fields([
    'value' => serialize($installed)
  ])
  ->execute();

// Configuration for an entity_reference field using the View for selection.
$field_ref_views_select_2429191 = Yaml::decode(file_get_contents(__DIR__ . '/field.field.node.article.field_ref_views_select_2429191.yml'));

// Configuration for an entity_reference field using the auto-create feature.
$field_ref_autocreate_2412569 = Yaml::decode(file_get_contents(__DIR__ . '/field.field.node.article.field_ref_autocreate_2412569.yml'));

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.' . $field_ref_views_select_2429191['id'],
    'data' => serialize($field_ref_views_select_2429191),
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.' . $field_ref_autocreate_2412569['id'],
    'data' => serialize($field_ref_autocreate_2412569),
  ])
  ->execute();
