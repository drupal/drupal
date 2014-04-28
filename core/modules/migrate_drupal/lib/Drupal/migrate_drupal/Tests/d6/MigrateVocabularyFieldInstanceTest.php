<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateVocabularyFieldInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
 */
class MigrateVocabularyFieldInstanceTest extends MigrateDrupalTestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('node', 'field', 'taxonomy');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Vocabulary field instance migration',
      'description'  => 'Vocabulary field instance migration',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('node_type', array('type' => 'page'))->save();
    entity_create('node_type', array('type' => 'article'))->save();
    entity_create('node_type', array('type' => 'story'))->save();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_taxonomy_vocabulary' => array(
        array(array(4), array('tags')),
      ),
      'd6_vocabulary_field' => array(
        array(array(4), array('node', 'tags')),
      )
    );
    $this->prepareIdMappings($id_mappings);

    // Create the vocab.
    entity_create('taxonomy_vocabulary', array(
      'name' => 'Test Vocabulary',
      'description' => 'Test Vocabulary',
      'vid' => 'tags',
    ))->save();
    // Create the field itself.
    entity_create('field_config', array(
      'entity_type' => 'node',
      'name' => 'tags',
      'type' => 'taxonomy_term_reference',
    ))->save();

    $migration = entity_load('migration', 'd6_vocabulary_field_instance');
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
  public function testVocabularyFieldInstance() {
    // Test that the field exists.
    $field_id = 'node.article.tags';
    $field = entity_load('field_instance_config', $field_id);
    $this->assertEqual($field->id(), $field_id, 'Field instance exists on article bundle.');
    $settings = $field->getSettings();
    $this->assertEqual('tags', $settings['allowed_values'][0]['vocabulary'], "Vocabulary has correct settings.");

    // Test the page bundle as well.
    $field_id = 'node.page.tags';
    $field = entity_load('field_instance_config', $field_id);
    $this->assertEqual($field->id(), $field_id, 'Field instance exists on page bundle.');
    $settings = $field->getSettings();
    $this->assertEqual('tags', $settings['allowed_values'][0]['vocabulary'], "Vocabulary has correct settings.");

    $this->assertEqual(array('node', 'article', 'tags'), entity_load('migration', 'd6_vocabulary_field_instance')->getIdMap()->lookupDestinationID(array(4, 'article')));
  }

}
