<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity display migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Execute Dependency Migrations.
    $this->migrateContentTypes();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'd6_node_type',
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
      'd6_vocabulary_field_instance',
    ]);
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityDisplay() {
    $this->executeMigration('d6_vocabulary_entity_display');

    // Test that the field exists.
    $component = EntityViewDisplay::load('node.page.default')->getComponent('field_tags');
    $this->assertSame('entity_reference_label', $component['type']);
    $this->assertSame(20, $component['weight']);
    // Test the Id map.
    $this->assertSame([['node', 'article', 'default', 'field_tags']], $this->getMigration('d6_vocabulary_entity_display')->getIdMap()->lookupDestinationIds([4, 'article']));

    // Tests that a vocabulary named like a D8 base field will be migrated and
    // prefixed with 'field_' to avoid conflicts.
    $field_type = EntityViewDisplay::load('node.sponsor.default')->getComponent('field_type');
    $this->assertIsArray($field_type);
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

    // d6_vocabulary_entity_display should skip over the "story" node type
    // config because, according to the map table, it didn't occur.
    $migration = $this->getMigration('d6_vocabulary_entity_display');

    $this->executeMigration($migration);
    $this->assertNull($migration->getIdMap()->lookupDestinationIds(['type' => 'story'])[0][0]);
  }

}
