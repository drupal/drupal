<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FieldDataFieldLink.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the field_data_field_link table.
 */
class FieldDataFieldLink extends DrupalDumpBase {

  public function load() {
    $this->createTable("field_data_field_link", array(
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
          'length' => '4',
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
        'field_link_url' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '2048',
        ),
        'field_link_title' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'field_link_attributes' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("field_data_field_link")->fields(array(
      'entity_type',
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'language',
      'delta',
      'field_link_url',
      'field_link_title',
      'field_link_attributes',
    ))
    ->values(array(
      'entity_type' => 'node',
      'bundle' => 'test_content_type',
      'deleted' => '0',
      'entity_id' => '1',
      'revision_id' => '1',
      'language' => 'und',
      'delta' => '0',
      'field_link_url' => 'http://google.com',
      'field_link_title' => 'Click Here',
      'field_link_attributes' => 'a:1:{s:5:"title";s:10:"Click Here";}',
    ))->execute();
  }

}
#34821b2528b8037ca3189302a3e62e31
