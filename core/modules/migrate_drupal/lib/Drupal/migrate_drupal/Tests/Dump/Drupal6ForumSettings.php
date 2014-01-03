<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6ForumSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing forum.site.yml migration.
 */
class Drupal6ForumSettings {

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
      'name' => 'forum_hot_topic',
      'value' => 's:2:"15";',
    ))
    ->values(array(
      'name' => 'forum_per_page',
      'value' => 's:2:"25";',
    ))
    ->values(array(
      'name' => 'forum_order',
      'value' => 's:1:"1";',
    ))
    ->values(array(
      'name' => 'forum_nav_vocabulary',
      'value' => 's:1:"1";',
    ))
    // 'forum_block_num_active' in D8.
    ->values(array(
      'name' => 'forum_block_num_0',
      'value' => 's:1:"5";',
    ))
    // 'forum_block_num_new' in D8.
    ->values(array(
      'name' => 'forum_block_num_1',
      'value' => 's:1:"5";',
    ))
    ->execute();
  }
}
