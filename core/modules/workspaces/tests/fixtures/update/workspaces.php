<?php
// phpcs:ignoreFile

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeInterface;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->fields([
    'value' => 'i:10000;',
    'name' => 'workspaces',
    'collection' => 'system.schema',
  ])
  ->condition('collection', 'system.schema')
  ->condition('name', 'workspaces')
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['workspaces'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Add all workspaces_removed_post_updates() as existing updates.
require_once __DIR__ . '/../../../../workspaces/workspaces.post_update.php';
$existing_updates = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();
$existing_updates = unserialize($existing_updates);
$existing_updates = array_merge(
  $existing_updates,
  array_keys(workspaces_removed_post_updates())
);
$connection->update('key_value')
  ->fields(['value' => serialize($existing_updates)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();

// Create the 'workspace_association' table.
$spec = [
  'description' => 'Stores the association between entity revisions and their workspace.',
  'fields' => [
    'workspace' => [
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
      'description' => 'The workspace ID.',
    ],
    'target_entity_type_id' => [
      'type' => 'varchar_ascii',
      'length' => EntityTypeInterface::ID_MAX_LENGTH,
      'not null' => TRUE,
      'default' => '',
      'description' => 'The ID of the associated entity type.',
    ],
    'target_entity_id' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'description' => 'The ID of the associated entity.',
    ],
    'target_entity_revision_id' => [
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'description' => 'The revision ID of the associated entity.',
    ],
  ],
  'indexes' => [
    'target_entity_revision_id' => ['target_entity_revision_id'],
  ],
  'primary key' => ['workspace', 'target_entity_type_id', 'target_entity_id'],
];
$connection->schema()->createTable('workspace_association', $spec);
