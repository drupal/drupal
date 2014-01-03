<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SyslogSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing syslog.settings.yml migration.
 */
class Drupal6SyslogSettings {

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
      'name' => 'syslog_facility',
      'value' => 'i:128;',
    ))
    ->values(array(
      'name' => 'syslog_identity',
      'value' => 's:6:"drupal";',
    ))
    ->execute();
  }
}
