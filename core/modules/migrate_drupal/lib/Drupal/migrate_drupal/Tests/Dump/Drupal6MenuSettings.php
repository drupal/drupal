<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6MenuSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing menu.settings.yml migration.
 */
class Drupal6MenuSettings {

  /**
   * Sample database schema and values.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public static function load(Connection $database) {
    Drupal6DumpCommon::createVariable($database);
    $database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'menu_primary_links_source',
      'value' => 's:13:"primary-links";',
    ))
    ->values(array(
      'name' => 'menu_secondary_links_source',
      'value' => 's:15:"secondary-links";',
    ))
    ->values(array(
      'name' => 'menu_override_parent_selector',
      'value' => 'b:0;',
    ))
    ->execute();
  }
}
