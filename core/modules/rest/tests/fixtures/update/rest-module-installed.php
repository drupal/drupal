<?php

/**
 * @file
 * Test fixture for \Drupal\Tests\rest\Functional\Update\RestExportAuthCorrectionUpdateTest.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'rest',
    'value' => 'i:8401;',
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
$extensions['module']['rest'] = 0;
$extensions['module']['serialization'] = 0;
$extensions['module']['basic_auth'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Create rest.settings.
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'rest.settings',
    'data' => 'a:1:{s:30:"bc_entity_resource_permissions";b:0;}',
  ])
  ->execute();
