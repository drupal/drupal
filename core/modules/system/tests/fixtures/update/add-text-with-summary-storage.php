<?php

/**
 * @file
 * Adds a text_with_summary field storage to the fixture database.
 */

use Drupal\Core\Database\Database;

$field_storage = [
  'uuid' => '61d16478-4c4a-467e-8829-5e7e8d1d720a',
  'langcode' => 'en',
  'status' => TRUE,
  'dependencies' => ['module' => ['node', 'text']],
  'id' => 'node.body',
  'field_name' => 'body',
  'entity_type' => 'node',
  'type' => 'text_with_summary',
  'settings' => [],
  'module' => 'text',
  'locked' => FALSE,
  'cardinality' => 1,
  'translatable' => TRUE,
  'indexes' => [],
  'persist_with_no_fields' => TRUE,
  'custom_storage' => FALSE,
];

Database::getConnection()->merge('config')
  ->keys(['collection' => '', 'name' => 'field.storage.node.body'])
  ->fields([
    'collection' => '',
    'name' => 'field.storage.node.body',
    'data' => serialize($field_storage),
  ])
  ->execute();
