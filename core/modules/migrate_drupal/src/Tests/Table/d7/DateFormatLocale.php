<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\DateFormatLocale.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the date_format_locale table.
 */
class DateFormatLocale extends DrupalDumpBase {

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
          'length' => '64',
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
#4c27250f4901da2ee5c69485e52865b0
