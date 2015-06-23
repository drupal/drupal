<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Watchdog.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the watchdog table.
 */
class Watchdog extends DrupalDumpBase {

  public function load() {
    $this->createTable("watchdog", array(
      'primary key' => array(
        'wid',
      ),
      'fields' => array(
        'wid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'message' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'variables' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
        'severity' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'link' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
          'default' => '',
        ),
        'location' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'referer' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
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
    $this->database->insert("watchdog")->fields(array(
      'wid',
      'uid',
      'type',
      'message',
      'variables',
      'severity',
      'link',
      'location',
      'referer',
      'hostname',
      'timestamp',
    ))
    ->execute();
  }

}
#4ed44ad720c25fedd451c16c0dafd6ab
