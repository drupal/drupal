<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TaxonomyTermData.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the taxonomy_term_data table.
 */
class TaxonomyTermData extends DrupalDumpBase {

  public function load() {
    $this->createTable("taxonomy_term_data", array(
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
        'format' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
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
    $this->database->insert("taxonomy_term_data")->fields(array(
      'tid',
      'vid',
      'name',
      'description',
      'format',
      'weight',
    ))
    ->values(array(
      'tid' => '1',
      'vid' => '2',
      'name' => 'General discussion',
      'description' => '',
      'format' => NULL,
      'weight' => '2',
    ))->values(array(
      'tid' => '2',
      'vid' => '3',
      'name' => 'Term1',
      'description' => 'The first term.',
      'format' => 'filtered_html',
      'weight' => '0',
    ))->values(array(
      'tid' => '3',
      'vid' => '3',
      'name' => 'Term2',
      'description' => 'The second term.',
      'format' => 'filtered_html',
      'weight' => '0',
    ))->values(array(
      'tid' => '4',
      'vid' => '3',
      'name' => 'Term3',
      'description' => 'The third term.',
      'format' => 'full_html',
      'weight' => '0',
    ))->values(array(
      'tid' => '5',
      'vid' => '2',
      'name' => 'Custom Forum',
      'description' => 'Where the cool kids are.',
      'format' => NULL,
      'weight' => '3',
    ))->values(array(
      'tid' => '6',
      'vid' => '2',
      'name' => 'Games',
      'description' => '',
      'format' => NULL,
      'weight' => '4',
    ))->values(array(
      'tid' => '7',
      'vid' => '2',
      'name' => 'Minecraft',
      'description' => '',
      'format' => NULL,
      'weight' => '1',
    ))->values(array(
      'tid' => '8',
      'vid' => '2',
      'name' => 'Half Life 3',
      'description' => '',
      'format' => NULL,
      'weight' => '0',
    ))->execute();
  }

}
#77a4e5089be7384cbdf2b8c42efc2707
