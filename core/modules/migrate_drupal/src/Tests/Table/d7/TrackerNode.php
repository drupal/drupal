<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TrackerNode.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the tracker_node table.
 */
class TrackerNode extends DrupalDumpBase {

  public function load() {
    $this->createTable("tracker_node", array(
      'primary key' => array(
        'nid',
      ),
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'published' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '4',
          'default' => '0',
        ),
        'changed' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("tracker_node")->fields(array(
      'nid',
      'published',
      'changed',
    ))
    ->values(array(
      'nid' => '1',
      'published' => '1',
      'changed' => '1421727536',
    ))->execute();
  }

}
#315c7b27e9e15f6954f59b858fb22fcd
