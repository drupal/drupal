<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\NodeCommentStatistics.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the node_comment_statistics table.
 */
class NodeCommentStatistics extends DrupalDumpBase {

  public function load() {
    $this->createTable("node_comment_statistics", array(
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
        'last_comment_timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'last_comment_name' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '60',
        ),
        'last_comment_uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'comment_count' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("node_comment_statistics")->fields(array(
      'nid',
      'last_comment_timestamp',
      'last_comment_name',
      'last_comment_uid',
      'comment_count',
    ))
    ->values(array(
      'nid' => '1',
      'last_comment_timestamp' => '1388271197',
      'last_comment_name' => NULL,
      'last_comment_uid' => '1',
      'comment_count' => '0',
    ))->values(array(
      'nid' => '2',
      'last_comment_timestamp' => '1389002813',
      'last_comment_name' => NULL,
      'last_comment_uid' => '1',
      'comment_count' => '0',
    ))->execute();
  }

}
#ef7d3494e2e6e8244b4642db7a6fd80b
