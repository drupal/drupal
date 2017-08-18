<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyFieldInstanceTest extends MigrateDrupal6TestBase {

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
      'd6_node_type',
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
    ]);
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyFieldInstance() {
    $this->executeMigration('d6_vocabulary_field_instance');

    // Test that the field exists.
    $field_id = 'node.article.field_tags';
    $field = FieldConfig::load($field_id);
    $this->assertSame($field_id, $field->id(), 'Field instance exists on article bundle.');
    $this->assertSame('Tags', $field->label());
    $this->assertTrue($field->isRequired(), 'Field is required');

    // Test the page bundle as well.
    $field_id = 'node.page.field_tags';
    $field = FieldConfig::load($field_id);
    $this->assertSame($field_id, $field->id(), 'Field instance exists on page bundle.');
    $this->assertSame('Tags', $field->label());
    $this->assertTrue($field->isRequired(), 'Field is required');

    $settings = $field->getSettings();
    $this->assertSame('default:taxonomy_term', $settings['handler'], 'The handler plugin ID is correct.');
    $this->assertSame(['field_tags'], $settings['handler_settings']['target_bundles'], 'The target_bundles handler setting is correct.');
    $this->assertSame(TRUE, $settings['handler_settings']['auto_create'], 'The "auto_create" setting is correct.');

    $this->assertSame(['node', 'article', 'field_tags'], $this->getMigration('d6_vocabulary_field_instance')->getIdMap()->lookupDestinationID([4, 'article']));

    // Test the the field vocabulary_1_i_0_.
    $field_id = 'node.story.field_vocabulary_1_i_0_';
    $field = FieldConfig::load($field_id);
    $this->assertFalse($field->isRequired(), 'Field is not required');

    // Tests that a vocabulary named like a D8 base field will be migrated and
    // prefixed with 'field_' to avoid conflicts.
    $field_type = FieldConfig::load('node.sponsor.field_type');
    $this->assertInstanceOf(FieldConfig::class, $field_type);
  }

  /**
   * Tests that vocabulary field instances are ignored appropriately.
   *
   * Vocabulary field instances should be ignored when they belong to node
   * types which were not migrated.
   */
  public function testSkipNonExistentNodeType() {
    // The "story" node type is migrated by d6_node_type but we need to pretend
    // that it didn't occur, so record that in the map table.
    $this->mockFailure('d6_node_type', ['type' => 'story']);

    // d6_vocabulary_field_instance should skip over the "story" node type
    // config because, according to the map table, it didn't occur.
    $migration = $this->getMigration('d6_vocabulary_field_instance');

    $this->executeMigration($migration);
    $this->assertNull($migration->getIdMap()->lookupDestinationIds(['type' => 'story'])[0][0]);
  }

}
