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
      'timestamp' => '1421727515',
      'status' => '1',
      'comment' => '2',
      'promote' => '1',
      'sticky' => '0',
    ))->execute();
  }

}
#903cc4ad46a15d52515a89def967ae0f
