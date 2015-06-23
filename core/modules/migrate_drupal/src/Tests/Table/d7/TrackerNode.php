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
          'length' => '11',
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
      'mysql_character_set' => 'utf8',
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
#64524b94e5b88c9e2be2d4db8b98155d
