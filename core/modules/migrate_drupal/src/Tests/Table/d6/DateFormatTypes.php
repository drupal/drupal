<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\DateFormatTypes.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the date_format_types table.
 */
class DateFormatTypes extends DrupalDumpBase {

  public function load() {
    $this->createTable("date_format_types", array(
      'primary key' => array(
        'type',
      ),
      'fields' => array(
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '200',
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
    $this->database->insert("date_format_types")->fields(array(
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
