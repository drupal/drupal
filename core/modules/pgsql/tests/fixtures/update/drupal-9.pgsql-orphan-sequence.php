<?php
// @codingStandardsIgnoreFile

use Drupal\Core\Database\Database;

$connection = Database::getConnection();
$db_type = $connection->databaseType();

// Creates a table, then adds a sequence without ownership to simulate tables
// that were altered from integer to serial columns.
$connection
  ->schema()
  ->createTable('pgsql_sequence_test', [
    'fields' => [
      'sequence_field' => [
        'type' => 'int',
        'not null' => TRUE,
        'unsigned' => TRUE,
      ],
    ],
    'primary key' => ['sequence_field'],
  ]);
$seq = $connection
  ->makeSequenceName('pgsql_sequence_test', 'sequence_field');
$connection->query('CREATE SEQUENCE ' . $seq);

// Enables the pgsql_test module so that the pgsql_sequence_test schema will
// be available.
$extensions = $connection
  ->query("SELECT data FROM {config} where name = 'core.extension'")
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['pgsql_test'] = 1;

$connection
  ->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('name', 'core.extension')
  ->execute();
$connection
  ->delete('cache_config')
  ->condition('cid', 'core.extension')
  ->execute();
