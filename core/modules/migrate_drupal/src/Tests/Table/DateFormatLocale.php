<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\DateFormatLocale.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the date_format_locale table.
 */
class DateFormatLocale extends Drupal6DumpBase {

  public function load() {
    $this->createTable("date_format_locale", array(
      'fields' => array(
        'format' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '100',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '200',
        ),
        'language' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '12',
        ),
      ),
      'primary key' => array(
        'type',
        'language',
      ),
    ));
    $this->database->insert("date_format_locale")->fields(array(
      'format',
      'type',
      'language',
    ))
    ->execute();
  }

}
