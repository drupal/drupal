<?php

/**
 * @file
 * Contains database additions for testing year 2038 update.
 */

use Drupal\Core\Database\Database;

// cspell:ignore destid sourceid

$connection = Database::getConnection();

// Add a migrate map table.
$connection->schema()->createTable('migrate_map_d7_file', [
  'fields' => [
    'source_ids_hash' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
    ],
    'sourceid1' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
    ],
    'destid1' => [
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'source_row_status' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
      'unsigned' => TRUE,
    ],
    'rollback_action' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
      'unsigned' => TRUE,
    ],
    'last_imported' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ],
    'hash' => [
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '64',
    ],
  ],
  'primary key' => [
    'source_ids_hash',
  ],
  'indexes' => [
    'source' => [
      'sourceid1',
    ],
  ],
  'mysql_character_set' => 'utf8mb4',
]);
