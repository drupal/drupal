<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemSite.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing system.site.yml migration.
 */
class Drupal6SystemSite {

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
      'name' => 'site_name',
      'value' => 's:9:"site_name";',
    ))
    ->values(array(
      'name' => 'site_mail',
      'value' => 's:21:"site_mail@example.com";',
    ))
    ->values(array(
      'name' => 'site_slogan',
      'value' => 's:13:"Migrate rocks";',
    ))
    ->values(array(
      'name' => 'site_frontpage',
      'value' => 's:4:"node";',
    ))
    ->values(array(
      'name' => 'site_403',
      'value' => 's:4:"user";',
    ))
    ->values(array(
      'name' => 'site_404',
      'value' => 's:14:"page-not-found";',
    ))
    ->values(array(
      'name' => 'admin_compact_mode',
      'value' => 'b:0;',
    ))
    ->execute();
  }
}
