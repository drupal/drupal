<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\NodeRevision.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the node_revision table.
 */
class NodeRevision extends DrupalDumpBase {

  public function load() {
    $this->createTable("node_revision", array(
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'vid' => array(
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
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'log' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '1',
        ),
        'comment' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'promote' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'sticky' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'primary key' => array(
        'vid',
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("node_revision")->fields(array(
      'nid',
      'vid',
      'uid',
      'title',
      'log',
      'timestamp',
      'status',
      'comment',
      'promote',
      'sticky',
    ))
    ->values(array(
      'nid' => '1',
      'vid' => '1',
      'uid' => '1',
      'title' => 'A Node',
      'log' => '',
      'timestamp' => '1441032132',
      'status' => '1',
      'comment' => '2',
      'promote' => '1',
      'sticky' => '0',
    ))->values(array(
      'nid' => '2',
      'vid' => '2',
      'uid' => '1',
      'title' => 'The thing about Deep Space 9',
      'log' => '',
      'timestamp' => '1441306832',
      'status' => '1',
      'comment' => '2',
      'promote' => '1',
      'sticky' => '0',
    ))->execute();
  }

}
#e111edf15130b7307ccaffac6f8f9b6f
