<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ContentGroupFields.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the content_group_fields table.
 */
class ContentGroupFields extends DrupalDumpBase {

  public function load() {
    $this->createTable("content_group_fields", array(
      'primary key' => array(
        'type_name',
        'group_name',
        'field_name',
      ),
      'fields' => array(
        'type_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'group_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'field_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("content_group_fields")->fields(array(
      'type_name',
      'group_name',
      'field_name',
    ))
    ->execute();
  }

}
#d27d7713a767febbe851794916fc9b0b
