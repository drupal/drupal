<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Semaphore.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the semaphore table.
 */
class Semaphore extends Drupal6DumpBase {

  public function load() {
    $this->createTable("semaphore", array(
      'primary key' => array(
        'name',
      ),
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'value' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'expire' => array(
          'type' => 'numeric',
          'not null' => TRUE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("semaphore")->fields(array(
      'name',
      'value',
      'expire',
    ))
    ->execute();
  }

}
