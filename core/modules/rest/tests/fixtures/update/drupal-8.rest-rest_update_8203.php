<?php

/**
 * @file
 * Contains database additions to drupal-8.bare.standard.php.gz for testing the
 * upgrade path of rest_update_8203().
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'rest',
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
$extensions['module']['rest'] = 0;
$extensions['module']['serialization'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Install the rest configuration.
$config = [
  'resources' => [
    'entity:node' => [
      'GET' => [
        'supported_formats' => ['json'],
        'supported_auth' => ['basic_auth'],
      ],
    ],
  ],
  'link_domain' => NULL,
];
$data = $connection->insert('config')
  ->fields([
    'name' => 'rest.settings',
    'data' => serialize($config),
    'collection' => '',
  ])
  ->execute();
