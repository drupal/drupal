<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TaxonomyVocabulary.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the taxonomy_vocabulary table.
 */
class TaxonomyVocabulary extends DrupalDumpBase {

  public function load() {
    $this->createTable("taxonomy_vocabulary", array(
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
        'machine_name' => array(
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
        'hierarchy' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '3',
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
    ));
    $this->database->insert("taxonomy_vocabulary")->fields(array(
      'vid',
      'name',
      'machine_name',
      'description',
      'hierarchy',
      'module',
      'weight',
    ))
    ->values(array(
      'vid' => '1',
      'name' => 'Tags',
      'machine_name' => 'tags',
      'description' => 'Use tags to group articles on similar topics into categories.',
      'hierarchy' => '0',
      'module' => 'taxonomy',
      'weight' => '0',
    ))->values(array(
      'vid' => '2',
      'name' => 'Forums',
      'machine_name' => 'forums',
      'description' => 'Forum navigation vocabulary',
      'hierarchy' => '1',
      'module' => 'forum',
      'weight' => '-10',
    ))->values(array(
      'vid' => '3',
      'name' => 'Test Vocabulary',
      'machine_name' => 'test_vocabulary',
      'description' => 'This is the vocabulary description',
      'hierarchy' => '1',
      'module' => 'taxonomy',
      'weight' => '0',
    ))->execute();
  }

}
#3e939a42e961bc13f4cc5688b2c06b4b
