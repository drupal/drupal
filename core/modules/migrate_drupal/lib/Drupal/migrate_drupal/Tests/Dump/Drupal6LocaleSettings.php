<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6LocaleSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing locale.settings.yml migration.
 */
class Drupal6LocaleSettings {

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
      'name' => 'locale_cache_strings',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'locale_js_directory',
      'value' => 's:9:"languages";',
    ))
    ->execute();
  }

}
