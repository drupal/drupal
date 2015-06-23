<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\NodeCounter.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the node_counter table.
 */
class NodeCounter extends DrupalDumpBase {

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
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'daycount' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
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
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("node_counter")->fields(array(
      'nid',
      'totalcount',
      'daycount',
      'timestamp',
    ))
    ->values(array(
      'nid' => '1',
      'totalcount' => '2',
      'daycount' => '0',
      'timestamp' => '1421727536',
    ))->execute();
  }

}
#f2d6ef67cb9e076d80baa83d50c00d45
