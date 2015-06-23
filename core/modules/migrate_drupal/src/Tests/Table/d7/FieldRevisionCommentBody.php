<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldRevisionCommentBody.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_revision_comment_body table.
 */
class FieldRevisionCommentBody extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_revision_comment_body", array(
      'primary key' => array(
        'entity_type',
        'deleted',
        'entity_id',
        'revision_id',
        'language',
        'delta',
      ),
      'fields' => array(
        'entity_type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'bundle' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'deleted' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'entity_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'revision_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'delta' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'comment_body_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'comment_body_format' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("field_revision_comment_body")->fields(array(
      'entity_type',
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'language',
      'delta',
      'comment_body_value',
      'comment_body_format',
    ))
    ->values(array(
      'entity_type' => 'comment',
      'bundle' => 'comment_node_test_content_type',
      'deleted' => '0',
      'entity_id' => '1',
      'revision_id' => '1',
      'language' => 'und',
      'delta' => '0',
      'comment_body_value' => 'This is a comment',
      'comment_body_format' => 'filtered_html',
    ))->execute();
  }

}
#c3196f37793be4c3a67a5fabbe96d638
