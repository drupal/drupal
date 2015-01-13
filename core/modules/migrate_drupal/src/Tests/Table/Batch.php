<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Batch.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the batch table.
 */
class Batch extends Drupal6DumpBase {

  public function load() {
    $this->createTable("batch", array(
      'primary key' => array(
        'bid',
      ),
      'fields' => array(
        'bid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'token' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
        ),
        'batch' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("batch")->fields(array(
      'bid',
      'token',
      'timestamp',
      'batch',
    ))
    ->execute();
  }

}
