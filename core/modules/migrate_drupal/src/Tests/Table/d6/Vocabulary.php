<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Vocabulary.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the vocabulary table.
 */
class Vocabulary extends DrupalDumpBase {

  public function load() {
    $this->createTable("vocabulary", array(
      'primary key' => array(
        'vid',
      ),
      'fields' => array(
        'vid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
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
        'help' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'relations' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'hierarchy' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'multiple' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'required' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'tags' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
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
    $this->database->insert("vocabulary")->fields(array(
      'vid',
      'name',
      'description',
      'help',
      'relations',
      'hierarchy',
      'multiple',
      'required',
      'tags',
      'module',
      'weight',
    ))
    ->values(array(
      'vid' => '1',
      'name' => 'vocabulary 1 (i=0)',
      'description' => 'description of vocabulary 1 (i=0)',
      'help' => '',
      'relations' => '1',
      'hierarchy' => '0',
      'multiple' => '0',
      'required' => '0',
      'tags' => '0',
      'module' => 'taxonomy',
      'weight' => '4',
    ))->values(array(
      'vid' => '2',
      'name' => 'vocabulary 2 (i=1)',
      'description' => 'description of vocabulary 2 (i=1)',
      'help' => '',
      'relations' => '1',
      'hierarchy' => '1',
      'multiple' => '1',
      'required' => '0',
      'tags' => '0',
      'module' => 'taxonomy',
      'weight' => '5',
    ))->values(array(
      'vid' => '3',
      'name' => 'vocabulary 3 (i=2)',
      'description' => 'description of vocabulary 3 (i=2)',
      'help' => '',
      'relations' => '1',
      'hierarchy' => '2',
      'multiple' => '0',
      'required' => '0',
      'tags' => '0',
      'module' => 'taxonomy',
      'weight' => '6',
    ))->values(array(
      'vid' => '4',
      'name' => 'Tags',
      'description' => 'Tags Vocabulary',
      'help' => '',
      'relations' => '1',
      'hierarchy' => '0',
      'multiple' => '0',
      'required' => '0',
      'tags' => '0',
      'module' => 'taxonomy',
      'weight' => '0',
    ))->values(array(
      'vid' => '5',
      'name' => 'vocabulary name much longer than thirty two characters',
      'description' => 'description of vocabulary name much longer than thirty two characters',
      'help' => '',
      'relations' => '1',
      'hierarchy' => '3',
      'multiple' => '1',
      'required' => '0',
      'tags' => '0',
      'module' => 'taxonomy',
      'weight' => '7',
    ))->execute();
  }

}
#94130b73f1ac1038218716a57d465163
