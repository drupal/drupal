<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ContentGroupFields.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the content_group_fields table.
 */
class ContentGroupFields extends Drupal6DumpBase {

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
    ));
    $this->database->insert("content_group_fields")->fields(array(
      'type_name',
      'group_name',
      'field_name',
    ))
    ->execute();
  }

}
