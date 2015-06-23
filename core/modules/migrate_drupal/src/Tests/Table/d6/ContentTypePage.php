<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ContentTypePage.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_type_page table.
 */
class ContentTypePage extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_type_page", array(
      'primary key' => array(
        'vid',
      ),
      'fields' => array(
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'field_text_field_value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("content_type_page")->fields(array(
      'vid',
      'nid',
      'field_text_field_value',
    ))
    ->values(array(
      'vid' => '1',
      'nid' => '1',
      'field_text_field_value' => NULL,
    ))->values(array(
      'vid' => '3',
      'nid' => '1',
      'field_text_field_value' => NULL,
    ))->execute();
  }

}
#a22194f55d9c79d0c83e97ee7c96714b
