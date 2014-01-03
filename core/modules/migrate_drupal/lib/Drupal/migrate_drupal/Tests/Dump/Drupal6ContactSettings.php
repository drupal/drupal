<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6ContactSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing contact.settings.yml migration.
 */
class Drupal6ContactSettings {

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
      'name' => 'contact_default_status',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'contact_hourly_threshold',
      'value' => 'i:3;',
    ))
    ->execute();
  }
}
