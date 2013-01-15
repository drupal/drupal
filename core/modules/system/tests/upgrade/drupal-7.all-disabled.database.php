<?php

/**
 * @file
 * Database additions for upgrade path tests when all non-required modules are
 * disabled.
 *
 * The drupal-7.filled.standard_all.database.php file is imported before
 * this dump, so the two form the database structure expected in tests
 * altogether.
 */

db_update('system')
  ->fields(array(
    'status' => 0,
  ))
  ->condition('type', 'module')
  ->condition('name', array('filter', 'field', 'field_sql_storage', 'entity',
    'system', 'text', 'user'), 'NOT IN')
  ->execute();

db_update('system')
  ->fields(array(
    'schema_version' => 0,
  ))
  ->condition('type', 'module')
  ->condition('name', 'update_test_1')
  ->execute();
