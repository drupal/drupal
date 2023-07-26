<?php
// phpcs:ignoreFile

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->fields([
    'value' => 'i:8700;',
    'name' => 'media',
    'collection' => 'system.schema',
  ])
  ->condition('collection', 'system.schema')
  ->condition('name', 'media')
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['media'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add all media_removed_post_updates() as existing updates.
require_once __DIR__ . '/../../../../media/media.post_update.php';
$existing_updates = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();
$existing_updates = unserialize($existing_updates);
$existing_updates = array_merge(
  $existing_updates,
  array_keys(media_removed_post_updates())
);
$connection->update('key_value')
  ->fields(['value' => serialize($existing_updates)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();

// Create media.settings.
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'media.settings',
    'data' => serialize([
      'icon_base_uri' => 'public://media-icons/generic',
      'iframe_domain' => '',
      'oembed_providers_url' => 'https://oembed.com/providers.json',
      'standalone_url' => FALSE,
    ]),
  ])
  ->execute();
