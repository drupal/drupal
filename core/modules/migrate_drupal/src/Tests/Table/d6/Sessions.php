<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Sessions.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the sessions table.
 */
class Sessions extends DrupalDumpBase {

  public function load() {
    $this->createTable("sessions", array(
      'fields' => array(
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'sid' => array(
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
        'cache' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'session' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'primary key' => array(
        'sid',
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("sessions")->fields(array(
      'uid',
      'sid',
      'hostname',
      'timestamp',
      'cache',
      'session',
    ))
    ->execute();
  }

}
#b7a70fcb91c8af507894a3593f34b7b4
