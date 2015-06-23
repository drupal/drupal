<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FilterFormat.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the filter_format table.
 */
class FilterFormat extends DrupalDumpBase {

  public function load() {
    $this->createTable("filter_format", array(
      'primary key' => array(
        'format',
      ),
      'fields' => array(
        'format' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'cache' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '1',
          'unsigned' => TRUE,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("filter_format")->fields(array(
      'format',
      'name',
      'cache',
      'status',
      'weight',
    ))
    ->values(array(
      'format' => 'custom_text_format',
      'name' => 'Custom Text format',
      'cache' => '1',
      'status' => '1',
      'weight' => '0',
    ))->values(array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'cache' => '1',
      'status' => '1',
      'weight' => '0',
    ))->values(array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'cache' => '1',
      'status' => '1',
      'weight' => '1',
    ))->values(array(
      'format' => 'plain_text',
      'name' => 'Plain text',
      'cache' => '1',
      'status' => '1',
      'weight' => '10',
    ))->execute();
  }

}
#1e6fc650aec40ccdac7d9290778b5708
