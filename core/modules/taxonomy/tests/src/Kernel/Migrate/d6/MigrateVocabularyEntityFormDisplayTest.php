<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity form display migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyEntityFormDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Execute Dependency Migrations.
    $this->migrateContentTypes();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
      'd6_vocabulary_field_instance',
      'd6_vocabulary_entity_display',
    ]);
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityFormDisplay() {
    $this->executeMigration('d6_vocabulary_entity_form_display');

    // Test that the field exists.
    $component = EntityFormDisplay::load('node.page.default')->getComponent('field_tags');
    $this->assertSame('options_select', $component['type']);
    $this->assertSame(20, $component['weight']);
    // Test the Id map.
    $this->assertSame(['node', 'article', 'default', 'field_tags'], $this->getMigration('d6_vocabulary_entity_form_display')->getIdMap()->lookupDestinationID([4, 'article']));

    // Test the term widget tags setting.
    $entity_form_display = EntityFormDisplay::load('node.story.default');
    $this->assertSame($entity_form_display->getComponent('field_vocabulary_1_i_0_')['type'], 'options_select');
    $this->assertSame($entity_form_display->getComponent('field_vocabulary_2_i_1_')['type'], 'entity_reference_autocomplete_tags');

    // Tests that a vocabulary named like a D8 base field will be migrated and
    // prefixed with 'field_' to avoid conflicts.
    $field_type = EntityFormDisplay::load('node.sponsor.default')->getComponent('field_type');
    $this->assertTrue(is_array($field_type));
  }

  /**
   * Tests that vocabulary displays are ignored appropriately.
   *
   * Vocabulary displays should be ignored when they belong to node types which
   * were not migrated.
   */
  public function testSkipNonExistentNodeType() {
    // The "story" node type is migrated by d6_node_type but we need to pretend
    // that it didn't occur, so record that in the map table.
    $this->mockFailure('d6_node_type', ['type' => 'story']);

    // d6_vocabulary_entity_form_display should skip over the "story" node type
    // config because, according to the map table, it didn't occur.
    $migration = $this->getMigration('d6_vocabulary_entity_form_display');

    $this->executeMigration($migration);
    $this->assertNull($migration->getIdMap()->lookupDestinationIds(['type' => 'story'])[0][0]);
  }

}
