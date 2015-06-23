<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Event.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the event table.
 */
class Event extends DrupalDumpBase {

  public function load() {
    $this->createTable("event", array(
      'primary key' => array(
        'nid',
      ),
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'event_start' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '100',
        ),
        'event_end' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '100',
        ),
        'timezone' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'start_in_dst' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'end_in_dst' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'has_time' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '1',
        ),
        'has_end_date' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '1',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("event")->fields(array(
      'nid',
      'event_start',
      'event_end',
      'timezone',
      'start_in_dst',
      'end_in_dst',
      'has_time',
      'has_end_date',
    ))
    ->execute();
  }

}
#78a4722634eb7f3ca00a7ced49953072
