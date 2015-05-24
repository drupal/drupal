<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Batch.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the batch table.
 */
class Batch extends DrupalDumpBase {

  public function load() {
    $this->createTable("batch", array(
      'primary key' => array(
        'bid',
      ),
      'fields' => array(
        'bid' => array(
          'type' => 'int',
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
          'type' => 'blob',
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
#741ce54d4a3cd8d238e99e032264b2a4
