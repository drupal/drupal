<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\ForumIndex.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the forum_index table.
 */
class ForumIndex extends DrupalDumpBase {

  public function load() {
    $this->createTable("forum_index", array(
      'fields' => array(
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'sticky' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '11',
          'default' => '0',
        ),
        'created' => array(
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
    $this->database->insert("forum_index")->fields(array(
      'nid',
      'title',
      'tid',
      'sticky',
      'created',
      'last_comment_timestamp',
      'comment_count',
    ))
    ->execute();
  }

}
#a1a3b65a0b88c88bd22e60963cffe5a2
