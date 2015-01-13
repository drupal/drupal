<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Node.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the node table.
 */
class Node extends Drupal6DumpBase {

  public function load() {
    $this->createTable("node", array(
      'primary key' => array(
        'nid',
      ),
      'fields' => array(
        'nid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
          'default' => '',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'uid' => array(
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
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'changed' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
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
        'moderate' => array(
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
        'tnid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'translate' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("node")->fields(array(
      'nid',
      'vid',
      'type',
      'language',
      'title',
      'uid',
      'status',
      'created',
      'changed',
      'comment',
      'promote',
      'moderate',
      'sticky',
      'tnid',
      'translate',
    ))
    ->values(array(
      'nid' => '1',
      'vid' => '1',
      'type' => 'story',
      'language' => '',
      'title' => 'Test title',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271197',
      'changed' => '1420861423',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '2',
      'vid' => '3',
      'type' => 'story',
      'language' => '',
      'title' => 'Test title rev 3',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271197',
      'changed' => '1420718386',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '3',
      'vid' => '4',
      'type' => 'test_planet',
      'language' => '',
      'title' => 'Test planet title 3',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271527',
      'changed' => '1390096401',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '4',
      'vid' => '6',
      'type' => 'test_planet',
      'language' => '',
      'title' => '',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271527',
      'changed' => '1390096401',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '5',
      'vid' => '7',
      'type' => 'test_planet',
      'language' => '',
      'title' => '',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271527',
      'changed' => '1390096401',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '6',
      'vid' => '8',
      'type' => 'test_planet',
      'language' => '',
      'title' => '',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271527',
      'changed' => '1390096401',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '7',
      'vid' => '9',
      'type' => 'test_planet',
      'language' => '',
      'title' => '',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271527',
      'changed' => '1390096401',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->values(array(
      'nid' => '8',
      'vid' => '10',
      'type' => 'test_planet',
      'language' => '',
      'title' => '',
      'uid' => '1',
      'status' => '1',
      'created' => '1388271527',
      'changed' => '1390096401',
      'comment' => '0',
      'promote' => '0',
      'moderate' => '0',
      'sticky' => '0',
      'tnid' => '0',
      'translate' => '0',
    ))->execute();
  }

}
