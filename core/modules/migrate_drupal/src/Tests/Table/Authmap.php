<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Authmap.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the authmap table.
 */
class Authmap extends Drupal6DumpBase {

  public function load() {
    $this->createTable("authmap", array(
      'primary key' => array(
        'aid',
      ),
      'fields' => array(
        'aid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'authname' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("authmap")->fields(array(
      'aid',
      'uid',
      'authname',
      'module',
    ))
    ->execute();
  }

}
