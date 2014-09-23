<?php
/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6TaxonomyTerm.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing taxonomy term migration.
 */
class Drupal6TaxonomyTerm extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->setModuleVersion('taxonomy', 6000);

    $this->createTable('term_data', array(
      'fields' => array(
        'tid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'vid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'name' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'big',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
        ),
      ),
      'primary key' => array(
        'tid',
      ),
      'indexes' => array(
        'taxonomy_tree' => array(
          'vid',
          'weight',
          'name',
        ),
        'vid_name' => array(
          'vid',
          'name',
        ),
      ),
      'module' => 'taxonomy',
      'name' => 'term_data',
    ));

    $this->createTable('term_hierarchy', array(
      'fields' => array(
        'tid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'parent' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'parent' => array(
          'parent',
        ),
      ),
      'primary key' => array(
        'tid',
        'parent',
      ),
      'module' => 'taxonomy',
      'name' => 'term_hierarchy',
    ));

    $this->database->insert('term_data')->fields(array(
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
      ))
      ->values(array(
        'tid' => '2',
        'vid' => '2',
        'name' => 'term 2 of vocabulary 2',
        'description' => 'description of term 2 of vocabulary 2',
        'weight' => '3',
      ))
      ->values(array(
        'tid' => '3',
        'vid' => '2',
        'name' => 'term 3 of vocabulary 2',
        'description' => 'description of term 3 of vocabulary 2',
        'weight' => '4',
      ))
      ->values(array(
        'tid' => '4',
        'vid' => '3',
        'name' => 'term 4 of vocabulary 3',
        'description' => 'description of term 4 of vocabulary 3',
        'weight' => '6',
      ))
      ->values(array(
        'tid' => '5',
        'vid' => '3',
        'name' => 'term 5 of vocabulary 3',
        'description' => 'description of term 5 of vocabulary 3',
        'weight' => '7',
      ))
      ->values(array(
        'tid' => '6',
        'vid' => '3',
        'name' => 'term 6 of vocabulary 3',
        'description' => 'description of term 6 of vocabulary 3',
        'weight' => '8',
      ))
      ->execute();

    $this->database->insert('term_hierarchy')->fields(array(
      'tid',
      'parent',
    ))
      ->values(array(
        'tid' => '1',
        'parent' => '2',
      ))
      ->values(array(
        'tid' => '2',
        'parent' => '0',
      ))
      ->values(array(
        'tid' => '4',
        'parent' => '0',
      ))
      ->values(array(
        'tid' => '3',
        'parent' => '2',
      ))
      ->values(array(
        'tid' => '5',
        'parent' => '4',
      ))
      ->values(array(
        'tid' => '6',
        'parent' => '4',
      ))
      ->values(array(
        'tid' => '6',
        'parent' => '5',
      ))
      ->execute();
  }

}
