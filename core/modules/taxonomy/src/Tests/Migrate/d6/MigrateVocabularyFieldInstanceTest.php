<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyFieldInstanceTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('node', 'field', 'taxonomy', 'text', 'entity_reference');

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
      'd6_node_type' => array(
        array(array('article'), array('article')),
        array(array('page'), array('page')),
        array(array('story'), array('story')),
      ),
      'd6_taxonomy_vocabulary' => array(
        array(array(4), array('tags')),
      ),
      'd6_vocabulary_field' => array(
        array(array(4), array('node', 'tags')),
      )
    );
    $this->prepareMigrations($id_mappings);

    // Create the vocab.
    entity_create('taxonomy_vocabulary', array(
      'field_name' => 'Test Vocabulary',
      'description' => 'Test Vocabulary',
      'vid' => 'tags',
    ))->save();
    // Create the field storage.
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'tags',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
    ))->save();

    $this->executeMigration('d6_vocabulary_field_instance');
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyFieldInstance() {
    // Test that the field exists.
    $field_id = 'node.article.tags';
    $field = FieldConfig::load($field_id);
    $this->assertIdentical($field_id, $field->id(), 'Field instance exists on article bundle.');

    // Test the page bundle as well.
    $field_id = 'node.page.tags';
    $field = FieldConfig::load($field_id);
    $this->assertIdentical($field_id, $field->id(), 'Field instance exists on page bundle.');

    $settings = $field->getSettings();
    $this->assertIdentical('default:taxonomy_term', $settings['handler'], 'The handler plugin ID is correct.');
    $this->assertIdentical(['tags'], $settings['handler_settings']['target_bundles'], 'The target_bundle handler setting is correct.');
    $this->assertIdentical(TRUE, $settings['handler_settings']['auto_create'], 'The "auto_create" setting is correct.');

    $this->assertIdentical(array('node', 'article', 'tags'), entity_load('migration', 'd6_vocabulary_field_instance')->getIdMap()->lookupDestinationID(array(4, 'article')));
  }

}
