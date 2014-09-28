<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateVocabularyToFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Vocabulary field migration.
 *
 * @group migrate_drupal
 */
class MigrateVocabularyFieldTest extends MigrateDrupalTestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('taxonomy', 'field');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_taxonomy_vocabulary' => array(
        array(array(4), array('tags')),
      ),
    );
    $this->prepareMigrations($id_mappings);

    entity_create('taxonomy_vocabulary', array(
      'name' => 'Test Vocabulary',
      'description' => 'Test Vocabulary',
      'vid' => 'test_vocab',
    ))->save();

    $migration = entity_load('migration', 'd6_vocabulary_field');
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
  public function testVocabularyField() {
    // Test that the field exists.
    $field_storage_id = 'node.tags';
    $field_storage = entity_load('field_storage_config', $field_storage_id);
    $this->assertEqual($field_storage->id(), $field_storage_id);
    $settings = $field_storage->getSettings();
    $this->assertEqual('tags', $settings['allowed_values'][0]['vocabulary'], "Vocabulary has correct settings.");
    $this->assertEqual(array('node', 'tags'), entity_load('migration', 'd6_vocabulary_field')->getIdMap()->lookupDestinationID(array(4)), "Test IdMap");
  }

}
