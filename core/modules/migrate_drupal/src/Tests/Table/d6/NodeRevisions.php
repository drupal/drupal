<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\NodeRevisions.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the node_revisions table.
 */
class NodeRevisions extends DrupalDumpBase {

  public function load() {
    $this->createTable("node_revisions", array(
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
        'body' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'teaser' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
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
        'format' => array(
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
    $this->database->insert("node_revisions")->fields(array(
      'nid',
      'vid',
      'uid',
      'title',
      'body',
      'teaser',
      'log',
      'timestamp',
      'format',
    ))
    ->values(array(
      'nid' => '1',
      'vid' => '1',
      'uid' => '1',
      'title' => 'Test title',
      'body' => 'test',
      'teaser' => 'test',
      'log' => '',
      'timestamp' => '1420861423',
      'format' => '1',
    ))->values(array(
      'nid' => '1',
      'vid' => '2',
      'uid' => '2',
      'title' => 'Test title rev 2',
      'body' => 'body test rev 2',
      'teaser' => 'teaser test rev 2',
      'log' => 'modified rev 2',
      'timestamp' => '1390095702',
      'format' => '1',
    ))->values(array(
      'nid' => '2',
      'vid' => '3',
      'uid' => '1',
      'title' => 'Test title rev 3',
      'body' => 'test rev 3',
      'teaser' => 'test rev 3',
      'log' => '',
      'timestamp' => '1420718386',
      'format' => '1',
    ))->values(array(
      'nid' => '3',
      'vid' => '4',
      'uid' => '1',
      'title' => 'Test page title rev 4',
      'body' => 'test page body rev 4',
      'teaser' => 'test page teaser rev 4',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '0',
    ))->values(array(
      'nid' => '1',
      'vid' => '5',
      'uid' => '1',
      'title' => 'Test title rev 3',
      'body' => 'body test rev 3',
      'teaser' => 'teaser test rev 3',
      'log' => 'modified rev 3',
      'timestamp' => '1390095703',
      'format' => '1',
    ))->values(array(
      'nid' => '4',
      'vid' => '6',
      'uid' => '1',
      'title' => 'Node 4',
      'body' => 'Node 4 body',
      'teaser' => 'test for node 4',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '1',
    ))->values(array(
      'nid' => '5',
      'vid' => '7',
      'uid' => '1',
      'title' => 'Node 5',
      'body' => 'Node 5 body',
      'teaser' => 'test for node 5',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '1',
    ))->values(array(
      'nid' => '6',
      'vid' => '8',
      'uid' => '1',
      'title' => 'Node 6',
      'body' => 'Node 6 body',
      'teaser' => 'test for node 6',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '1',
    ))->values(array(
      'nid' => '7',
      'vid' => '9',
      'uid' => '1',
      'title' => 'Node 7',
      'body' => 'Node 7 body',
      'teaser' => 'test for node 7',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '1',
    ))->values(array(
      'nid' => '8',
      'vid' => '10',
      'uid' => '1',
      'title' => 'Node 8',
      'body' => 'Node 8 body',
      'teaser' => 'test for node 8',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '1',
    ))->values(array(
      'nid' => '9',
      'vid' => '11',
      'uid' => '1',
      'title' => 'Node 9',
      'body' => 'Node 9 body',
      'teaser' => 'test for node 9',
      'log' => '',
      'timestamp' => '1390095701',
      'format' => '1',
    ))->execute();
  }

}
#d3c03811fc5ee9b9b9e57ea430ecaa40
