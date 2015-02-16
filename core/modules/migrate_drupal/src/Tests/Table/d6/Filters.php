<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Filters.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the filters table.
 */
class Filters extends DrupalDumpBase {

  public function load() {
    $this->createTable("filters", array(
      'primary key' => array(
        'fid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'format' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'delta' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '4',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("filters")->fields(array(
      'fid',
      'format',
      'module',
      'delta',
      'weight',
    ))
    ->values(array(
      'fid' => '1',
      'format' => '1',
      'module' => 'filter',
      'delta' => '2',
      'weight' => '0',
    ))->values(array(
      'fid' => '2',
      'format' => '1',
      'module' => 'filter',
      'delta' => '0',
      'weight' => '1',
    ))->values(array(
      'fid' => '3',
      'format' => '1',
      'module' => 'filter',
      'delta' => '1',
      'weight' => '2',
    ))->values(array(
      'fid' => '4',
      'format' => '1',
      'module' => 'filter',
      'delta' => '3',
      'weight' => '10',
    ))->values(array(
      'fid' => '5',
      'format' => '2',
      'module' => 'filter',
      'delta' => '2',
      'weight' => '0',
    ))->values(array(
      'fid' => '6',
      'format' => '2',
      'module' => 'filter',
      'delta' => '1',
      'weight' => '1',
    ))->values(array(
      'fid' => '7',
      'format' => '2',
      'module' => 'filter',
      'delta' => '3',
      'weight' => '10',
    ))->values(array(
      'fid' => '8',
      'format' => '6',
      'module' => 'filter',
      'delta' => '2',
      'weight' => '0',
    ))->values(array(
      'fid' => '9',
      'format' => '6',
      'module' => 'filter',
      'delta' => '0',
      'weight' => '1',
    ))->values(array(
      'fid' => '10',
      'format' => '6',
      'module' => 'filter',
      'delta' => '1',
      'weight' => '2',
    ))->values(array(
      'fid' => '11',
      'format' => '6',
      'module' => 'filter',
      'delta' => '3',
      'weight' => '10',
    ))->execute();
  }

}
