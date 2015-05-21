<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\TermData.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the term_data table.
 */
class TermData extends DrupalDumpBase {

  public function load() {
    $this->createTable("term_data", array(
      'primary key' => array(
        'tid',
      ),
      'fields' => array(
        'tid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("term_data")->fields(array(
      'tid',
      'vid',
      'name',
      'description',
      'weight',
    ))
    ->values(array(
      'tid' => '1',
      'vid' => '1',
      'name' => 'term 1 of vocabulary 1',
      'description' => 'description of term 1 of vocabulary 1',
      'weight' => '0',
    ))->values(array(
      'tid' => '2',
      'vid' => '2',
      'name' => 'term 2 of vocabulary 2',
      'description' => 'description of term 2 of vocabulary 2',
      'weight' => '3',
    ))->values(array(
      'tid' => '3',
      'vid' => '2',
      'name' => 'term 3 of vocabulary 2',
      'description' => 'description of term 3 of vocabulary 2',
      'weight' => '4',
    ))->values(array(
      'tid' => '4',
      'vid' => '3',
      'name' => 'term 4 of vocabulary 3',
      'description' => 'description of term 4 of vocabulary 3',
      'weight' => '6',
    ))->values(array(
      'tid' => '5',
      'vid' => '3',
      'name' => 'term 5 of vocabulary 3',
      'description' => 'description of term 5 of vocabulary 3',
      'weight' => '7',
    ))->values(array(
      'tid' => '6',
      'vid' => '3',
      'name' => 'term 6 of vocabulary 3',
      'description' => 'description of term 6 of vocabulary 3',
      'weight' => '8',
    ))->execute();
  }

}
#a392bc00314b5ac5a418fb00a52cb821
