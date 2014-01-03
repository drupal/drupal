<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemCron.
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
    Drupal6DumpCommon::createVariable($database);
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
