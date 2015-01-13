<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\NodeAccess.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the node_access table.
 */
class NodeAccess extends Drupal6DumpBase {

  public function load() {
    $this->createTable("node_access", array(
      'primary key' => array(
        'nid',
        'gid',
        'realm',
      ),
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'gid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'realm' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'grant_view' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'grant_update' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'grant_delete' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("node_access")->fields(array(
      'nid',
      'gid',
      'realm',
      'grant_view',
      'grant_update',
      'grant_delete',
    ))
    ->values(array(
      'nid' => '0',
      'gid' => '0',
      'realm' => 'all',
      'grant_view' => '1',
      'grant_update' => '0',
      'grant_delete' => '0',
    ))->execute();
  }

}
