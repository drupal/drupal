<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\Drupal6SystemCron.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing system.cron.yml migration.
 */
class Drupal6SystemCron {

  /**
   * Sample database schema and values.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public static function load(Connection $database) {
    $database->schema()->createTable('variable', array(
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ),
        'value' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
          'translatable' => TRUE,
        ),
      ),
      'primary key' => array(
        'name',
      ),
      'module' => 'system',
      'name' => 'variable',
    ));
    $database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'cron_threshold_warning',
      'value' => 'i:172800;',
    ))
    ->values(array(
      'name' => 'cron_threshold_error',
      'value' => 'i:1209600;',
    ))
    ->execute();
  }
}
