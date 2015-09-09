<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldDataFieldTags.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_data_field_tags table.
 */
class FieldDataFieldTags extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_data_field_tags", array(
      'primary key' => array(
        'entity_type',
        'deleted',
        'entity_id',
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
          'not null' => FALSE,
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
        'field_tags_tid' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("field_data_field_tags")->fields(array(
      'entity_type',
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'language',
      'delta',
      'field_tags_tid',
    ))
    ->values(array(
      'entity_type' => 'node',
      'bundle' => 'article',
      'deleted' => '0',
      'entity_id' => '2',
      'revision_id' => '2',
      'language' => 'und',
      'delta' => '0',
      'field_tags_tid' => '9',
    ))->values(array(
      'entity_type' => 'node',
      'bundle' => 'article',
      'deleted' => '0',
      'entity_id' => '2',
      'revision_id' => '2',
      'language' => 'und',
      'delta' => '1',
      'field_tags_tid' => '14',
    ))->values(array(
      'entity_type' => 'node',
      'bundle' => 'article',
      'deleted' => '0',
      'entity_id' => '2',
      'revision_id' => '2',
      'language' => 'und',
      'delta' => '2',
      'field_tags_tid' => '17',
    ))->execute();
  }

}
#b72078545dd0cae56f1c0b4698d064ad
