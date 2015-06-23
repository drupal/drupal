<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldRevisionFieldFile.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_revision_field_file table.
 */
class FieldRevisionFieldFile extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_revision_field_file", array(
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
        'field_file_fid' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'field_file_display' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '1',
          'unsigned' => TRUE,
        ),
        'field_file_description' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("field_revision_field_file")->fields(array(
      'entity_type',
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'language',
      'delta',
      'field_file_fid',
      'field_file_display',
      'field_file_description',
    ))
    ->execute();
  }

}
#5cf9c6ff7da8c55d1c4da2f44bc43001
