<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Sessions.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

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
          'length' => '128',
        ),
        'ssid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
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
          'type' => 'blob',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'primary key' => array(
        'sid',
        'ssid',
      ),
    ));
    $this->database->insert("sessions")->fields(array(
      'uid',
      'sid',
      'ssid',
      'hostname',
      'timestamp',
      'cache',
      'session',
    ))
    ->execute();
  }

}
#207a95011fb0c0efb42e7a823cae19e7
