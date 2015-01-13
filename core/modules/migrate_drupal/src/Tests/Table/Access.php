<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Access.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the access table.
 */
class Access extends Drupal6DumpBase {

  public function load() {
    $this->createTable("access", array(
      'primary key' => array(
        'aid',
      ),
      'fields' => array(
        'aid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'mask' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("access")->fields(array(
      'aid',
      'mask',
      'type',
      'status',
    ))
    ->execute();
  }

}
