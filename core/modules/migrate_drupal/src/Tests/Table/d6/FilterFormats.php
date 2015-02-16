<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\FilterFormats.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the filter_formats table.
 */
class FilterFormats extends DrupalDumpBase {

  public function load() {
    $this->createTable("filter_formats", array(
      'primary key' => array(
        'format',
      ),
      'fields' => array(
        'format' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'roles' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'cache' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("filter_formats")->fields(array(
      'format',
      'name',
      'roles',
      'cache',
    ))
    ->values(array(
      'format' => '1',
      'name' => 'Filtered HTML',
      'roles' => ',1,2,',
      'cache' => '1',
    ))->values(array(
      'format' => '2',
      'name' => 'Full HTML',
      'roles' => '3',
      'cache' => '1',
    ))->values(array(
      'format' => '3',
      'name' => 'Escape HTML Filter',
      'roles' => '',
      'cache' => '1',
    ))->execute();
  }

}
