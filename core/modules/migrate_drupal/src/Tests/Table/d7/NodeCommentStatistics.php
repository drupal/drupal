<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\NodeCommentStatistics.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

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
        'cid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
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
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("node_comment_statistics")->fields(array(
      'nid',
      'cid',
      'last_comment_timestamp',
      'last_comment_name',
      'last_comment_uid',
      'comment_count',
    ))
    ->values(array(
      'nid' => '1',
      'cid' => '1',
      'last_comment_timestamp' => '1421727536',
      'last_comment_name' => '',
      'last_comment_uid' => '1',
      'comment_count' => '1',
    ))->execute();
  }

}
#e98845f037e6af4ef34c957ffd2d4f94
