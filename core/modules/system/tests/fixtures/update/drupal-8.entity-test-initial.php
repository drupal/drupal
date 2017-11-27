<?php
// @codingStandardsIgnoreFile

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Simulate an entity type that had previously set an initial key schema for a
// field.
$schema = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.storage_schema.sql')
  ->condition('name', 'entity_test_update.field_schema_data.name')
  ->execute()
  ->fetchField();

$schema = unserialize($schema);
$schema['entity_test_update']['fields']['name']['initial'] = 'test';

$connection->update('key_value')
  ->fields(['value' => serialize($schema)])
  ->condition('collection', 'entity.storage_schema.sql')
  ->condition('name', 'entity_test_update.field_schema_data.name')
  ->execute();
