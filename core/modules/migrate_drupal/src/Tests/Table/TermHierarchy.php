<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\TermHierarchy.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the term_hierarchy table.
 */
class TermHierarchy extends Drupal6DumpBase {

  public function load() {
    $this->createTable("term_hierarchy", array(
      'primary key' => array(
        'tid',
        'parent',
      ),
      'fields' => array(
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'parent' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("term_hierarchy")->fields(array(
      'tid',
      'parent',
    ))
    ->values(array(
      'tid' => '1',
      'parent' => '0',
    ))->values(array(
      'tid' => '2',
      'parent' => '0',
    ))->values(array(
      'tid' => '3',
      'parent' => '2',
    ))->values(array(
      'tid' => '4',
      'parent' => '0',
    ))->values(array(
      'tid' => '5',
      'parent' => '4',
    ))->values(array(
      'tid' => '6',
      'parent' => '4',
    ))->values(array(
      'tid' => '6',
      'parent' => '5',
    ))->execute();
  }

}
