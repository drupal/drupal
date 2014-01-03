<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\Drupal6SearchSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing forum.site.yml migration.
 */
class Drupal6SearchSettings {

  /**
   * Mock the database schema and values.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The mocked database connection.
   */
  public static function load(Connection $database) {
    Drupal6DumpCommon::createVariable($database);
    $database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'minimum_word_size',
      'value' => 's:1:"3";',
    ))
    ->values(array(
      'name' => 'overlap_cjk',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'search_cron_limit',
      'value' => 's:3:"100";',
    ))
    ->execute();
  }
}
