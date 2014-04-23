<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6TaxonomyVocabulary.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing taxonomy vocabulary migration.
 */
class Drupal6TaxonomyVocabulary extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('vocabulary', array(
      'description' => 'Stores vocabulary information.',
      'fields' => array(
        'vid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Primary Key: Unique vocabulary ID.',
        ),
        'name' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Name of the vocabulary.',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'big',
          'description' => 'Description of the vocabulary.',
        ),
        'help' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Help text to display for the vocabulary.',
        ),
        'relations' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Whether or not related terms are enabled within the vocabulary. (0 = disabled, 1 = enabled)',
        ),
        'hierarchy' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'The type of hierarchy allowed within the vocabulary. (0 = disabled, 1 = single, 2 = multiple)',
        ),
        'multiple' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Whether or not multiple terms from this vocabulary may be assigned to a node. (0 = disabled, 1 = enabled)',
        ),
        'required' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Whether or not terms are required for nodes using this vocabulary. (0 = disabled, 1 = enabled)',
        ),
        'tags' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Whether or not free tagging is enabled for the vocabulary. (0 = disabled, 1 = enabled)',
        ),
        'module' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The module which created the vocabulary.',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'The weight of the vocabulary in relation to other vocabularies.',
        ),
      ),
      'primary key' => array('vid'),
      'indexes' => array(
        'list' => array('weight', 'name'),
      ),
    ));
    $this->createTable('vocabulary_node_types', array(
      'description' => 'Stores which node types vocabularies may be used with.',
      'fields' => array(
        'vid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: the {vocabulary}.vid of the vocabulary.',
        ),
        'type' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The {node}.type of the node type for which the vocabulary may be used.',
        ),
      ),
      'primary key' => array('type', 'vid'),
      'indexes' => array(
        'vid' => array('vid'),
      ),
    ));
    $this->database->insert('vocabulary')->fields(array(
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
    ))
    ->values(array(
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
    ))
    ->values(array(
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
    ))
    ->execute();
    $this->setModuleVersion('taxonomy', 6001);
  }

}
