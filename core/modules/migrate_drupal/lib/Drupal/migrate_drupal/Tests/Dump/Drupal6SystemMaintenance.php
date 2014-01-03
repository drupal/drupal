<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemMaintenance.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing system.maintenance.yml migration.
 */
class Drupal6SystemMaintenance {

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
      'name' => 'site_offline',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'site_offline_message',
      'value' => 's:94:"Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.";',
    ))
    ->execute();
  }
}
