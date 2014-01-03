<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemFilter.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

use Drupal\Core\Database\Connection;

/**
 * Database dump for testing system.filter.yml migration.
 */
class Drupal6SystemFilter {

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
      'name' => 'filter_allowed_protocols',
      'value' => 'a:13:{i:0;s:4:"http";i:1;s:5:"https";i:2;s:3:"ftp";i:3;s:4:"news";i:4;s:4:"nntp";i:5;s:3:"tel";i:6;s:6:"telnet";i:7;s:6:"mailto";i:8;s:3:"irc";i:9;s:3:"ssh";i:10;s:4:"sftp";i:11;s:6:"webcal";i:12;s:4:"rtsp";}',
    ))
    ->execute();
  }

}
