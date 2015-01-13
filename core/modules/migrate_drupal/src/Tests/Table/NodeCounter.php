<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\NodeCounter.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the node_counter table.
 */
class NodeCounter extends Drupal6DumpBase {

  public function load() {
    $this->createTable("node_counter", array(
      'primary key' => array(
        'nid',
      ),
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'totalcount' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '20',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'daycount' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '8',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("node_counter")->fields(array(
      'nid',
      'totalcount',
      'daycount',
      'timestamp',
    ))
    ->execute();
  }

}
