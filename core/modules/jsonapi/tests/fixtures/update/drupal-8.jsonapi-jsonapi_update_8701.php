<?php

/**
 * @file
 * Contains database additions for testing jsonapi_update_8701()'s update path.
 *
 * @depends core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection',
    'name',
    'value',
  ])
  ->values([
    'collection' => 'system.schema',
    'name' => 'serialization',
    'value' => 'i:8401;',
  ])
  ->values([
    'collection' => 'system.schema',
    'name' => 'jsonapi',
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
$extensions['module']['serialization'] = 0;
$extensions['module']['jsonapi'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
