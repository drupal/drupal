<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Flood.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the flood table.
 */
class Flood extends DrupalDumpBase {

  public function load() {
    $this->createTable("flood", array(
      'primary key' => array(
        'fid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'event' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'hostname' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("flood")->fields(array(
      'fid',
      'event',
      'hostname',
      'timestamp',
    ))
    ->execute();
  }

}
#45aa7dba444d7433ed402ede2f8c3ed4
