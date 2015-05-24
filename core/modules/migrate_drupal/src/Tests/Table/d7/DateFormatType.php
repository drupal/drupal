<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\DateFormatType.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the date_format_type table.
 */
class DateFormatType extends DrupalDumpBase {

  public function load() {
    $this->createTable("date_format_type", array(
      'primary key' => array(
        'type',
      ),
      'fields' => array(
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'locked' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("date_format_type")->fields(array(
      'type',
      'title',
      'locked',
    ))
    ->values(array(
      'type' => 'long',
      'title' => 'Long',
      'locked' => '1',
    ))->values(array(
      'type' => 'medium',
      'title' => 'Medium',
      'locked' => '1',
    ))->values(array(
      'type' => 'short',
      'title' => 'Short',
      'locked' => '1',
    ))->execute();
  }

}
#6dea95587332bbefb15bbb41e27c8a44
