<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Semaphore.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the semaphore table.
 */
class Semaphore extends DrupalDumpBase {

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
#2a687fbf97f600464a9297e3b027312d
