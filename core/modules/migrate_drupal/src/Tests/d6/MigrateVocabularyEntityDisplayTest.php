<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateVocabularyEntityDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
 */
class MigrateVocabularyEntityDisplayTest extends MigrateDrupalTestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('field', 'node', 'taxonomy');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Vocabulary entity display migration',
      'description'  => 'Vocabulary entity display migration',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('field_config', array(
      'entity_type' => 'node',
      'name' => 'tags',
      'type' => 'taxonomy_term_reference',
    ))->save();

    foreach (array('page', 'article', 'story') as $type) {
      entity_create('node_type', array('type' => $type))->save();
      entity_create('field_instance_config', array(
        'label' => 'Tags',
        'description' => '',
        'field_name' => 'tags',
        'entity_type' => 'node',
        'bundle' => $type,
        'required' => 1,
      ))->save();
    }

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_taxonomy_vocabulary' => array(
        array(array(4), array('tags')),
      ),
      'd6_vocabulary_field_instance' => array(
        array(array(4, 'page'), array('node', 'page', 'tags')),
      )
    );
    $this->prepareIdMappings($id_mappings);

    $migration = entity_load('migration', 'd6_vocabulary_entity_display');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6VocabularyField.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityDisplay() {
    // Test that the field exists.
    $component = entity_get_display('node', 'page', 'default')->getComponent('tags');
    $this->assertEqual($component['type'], 'taxonomy_term_reference_link');
    $this->assertEqual($component['weight'], 20);
    // Test the Id map.
    $this->assertEqual(array('node', 'article', 'default', 'tags'), entity_load('migration', 'd6_vocabulary_entity_display')->getIdMap()->lookupDestinationID(array(4, 'article')));
  }

}
