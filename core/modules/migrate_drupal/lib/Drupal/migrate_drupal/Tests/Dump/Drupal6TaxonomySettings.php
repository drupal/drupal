<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6TaxonomySettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing taxonomy.settings.yml migration.
 */
class Drupal6TaxonomySettings {

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
      'name' => 'taxonomy_override_selector',
      'value' => 'b:0;',
    ))
    ->values(array(
      'name' => 'taxonomy_terms_per_page_admin',
      'value' => 'i:100;',
    ))
    ->execute();
  }
}
