<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateVocabularyEntityDisplayTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity display migration.
 *
 * @group taxonomy
 */
class MigrateVocabularyEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * The modules to be enabled during the test.
   *
   * @var array
   */
  static $modules = array('field', 'node', 'taxonomy', 'text', 'entity_reference');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'tags',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
    ))->save();

    foreach (array('page', 'article', 'story') as $type) {
      entity_create('node_type', array('type' => $type))->save();
      entity_create('field_config', array(
        'label' => 'Tags',
        'description' => '',
        'field_name' => 'tags',
        'entity_type' => 'node',
        'bundle' => $type,
        'required' => 1,
          'settings' => array(
            'handler' => 'default',
            'handler_settings' => array(
              'target_bundles' => array(
                'tags' => 'tags',
              ),
              'auto_create' => TRUE,
            ),
          ),
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
    $this->prepareMigrations($id_mappings);

    $this->loadDumps(['Vocabulary.php', 'VocabularyNodeTypes.php']);
    $this->executeMigration('d6_vocabulary_entity_display');
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityDisplay() {
    // Test that the field exists.
    $component = entity_get_display('node', 'page', 'default')->getComponent('tags');
    $this->assertIdentical('entity_reference_label', $component['type']);
    $this->assertIdentical(20, $component['weight']);
    // Test the Id map.
    $this->assertIdentical(array('node', 'article', 'default', 'tags'), entity_load('migration', 'd6_vocabulary_entity_display')->getIdMap()->lookupDestinationID(array(4, 'article')));
  }

}
