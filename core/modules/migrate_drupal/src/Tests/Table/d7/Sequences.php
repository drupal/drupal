<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Sequences.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the sequences table.
 */
class Sequences extends DrupalDumpBase {

  public function load() {
    $this->createTable("sequences", array(
      'primary key' => array(
        'value',
      ),
      'fields' => array(
        'value' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("sequences")->fields(array(
      'value',
    ))
    ->values(array(
      'value' => '1',
    ))->execute();
  }

}
#09fb20ca43790e7429303f3ae35779b0
